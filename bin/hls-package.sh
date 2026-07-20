#!/usr/bin/env bash
#
# hls-package.sh — build a self-hosted HLS bundle from a video master.
#
# Runs on your Mac (needs ffmpeg + ffprobe). Produces a `<slug>.hlspack.zip`
# you drag into the WordPress Media Library; the theme's hls-import.php unpacks
# it into an .m3u8 attachment you then select in the film's "Self Host Film"
# field. See library/video/ and README § Video.
#
# The bundle is a fMP4 (CMAF) HLS ladder with a master.m3u8 at its root and
# RELATIVE segment paths, so it plays from wherever it is unzipped.
#
# Usage:
#   bin/hls-package.sh <master.mp4|master.mov> <slug> [--thumb] [--fallback] [--keep]
#
#   <slug>        Output basename, e.g. my-film → my-film.hlspack.zip
#   --thumb       Also emit <slug>-thumb.mp4 (short muted loop for the archive grid)
#   --fallback    Also emit <slug>-1080.mp4 (progressive MP4 for download/no-JS)
#   --keep        Keep the unzipped working folder next to the zip
#
# Rungs are 16:9 BOXES the picture is fitted into with its aspect ratio
# preserved: 3840x2160 / 1920x1080 / 1280x720 / 854x480 / 640x360. A box is used
# only if the picture fits without upscaling, so a 1080p master yields the bottom
# four and a 2.39:1 4K master yields 3840x1608 at the top. Nothing is padded.
#
set -euo pipefail

# ---- args -------------------------------------------------------------------
if [[ $# -lt 2 ]]; then
  grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed -n '2,25p'
  exit 1
fi

MASTER="$1"; SLUG="$2"; shift 2
WANT_THUMB=0; WANT_FALLBACK=0; KEEP=0
for arg in "$@"; do
  case "$arg" in
    --thumb)    WANT_THUMB=1 ;;
    --fallback) WANT_FALLBACK=1 ;;
    --keep)     KEEP=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

[[ -f "$MASTER" ]] || { echo "Master not found: $MASTER" >&2; exit 1; }
command -v ffmpeg  >/dev/null || { echo "ffmpeg not found" >&2; exit 1; }
command -v ffprobe >/dev/null || { echo "ffprobe not found" >&2; exit 1; }

# ---- probe the master -------------------------------------------------------
SRC_W=$(ffprobe -v error -select_streams v:0 -show_entries stream=width  -of csv=p=0 "$MASTER" | head -1)
SRC_H=$(ffprobe -v error -select_streams v:0 -show_entries stream=height -of csv=p=0 "$MASTER" | head -1)
[[ -n "$SRC_W" && -n "$SRC_H" ]] || { echo "Could not read master dimensions" >&2; exit 1; }
echo "Master: ${SRC_W}x${SRC_H}"

# The filter chain below stamps setsar=1, i.e. it assumes square pixels. An
# anamorphic master (SAR other than 1:1) would come out horizontally squeezed,
# so say so rather than silently shipping distorted rungs.
SRC_SAR=$(ffprobe -v error -select_streams v:0 -show_entries stream=sample_aspect_ratio -of csv=p=0 "$MASTER" | head -1)
if [[ -n "$SRC_SAR" && "$SRC_SAR" != "1:1" && "$SRC_SAR" != "N/A" && "$SRC_SAR" != "0:1" ]]; then
  echo "WARNING: master has a non-square sample aspect ratio (SAR ${SRC_SAR})." >&2
  echo "         Rungs are encoded with setsar=1 and will look horizontally" >&2
  echo "         squeezed. Stop and de-anamorphose the master first." >&2
fi

# Segment length (s). Keyframes are forced to exact segment boundaries below, so
# rungs stay aligned for clean ABR switching whatever the master's frame rate is.
SEG=6
# Preserve the master's frame rate — do NOT resample to 24, which judders 25 or
# 23.976 masters (very visible on slow art-film pans). GOP is derived from the
# real fps so the -g hint matches the forced keyframe cadence.
FPS_RAW=$(ffprobe -v error -select_streams v:0 -show_entries stream=r_frame_rate -of csv=p=0 "$MASTER" | head -1)
FPS=$(awk -F/ '{ if (NF==2 && $2>0) printf "%.3f", $1/$2; else printf "%.3f", $1 }' <<<"$FPS_RAW")
GOP=$(awk -v f="$FPS" -v s="$SEG" 'BEGIN{ printf "%d", (f*s)+0.5 }')
echo "Master fps: ${FPS} (GOP ${GOP})"

# Ladder: "box_height box_width video_kbps audio_kbps". Highest first. The
# height/width are the BOX the picture is fitted into, not the encoded size —
# see the rung selection below.
# Tuned for art films (grain/gradients/shadows) from a high-quality master
# (ProRes). 4K at 20 Mbps ~ good-era Vimeo 4K; encode with -preset slow below.
LADDER=(
  "2160 3840 20000 256"
  "1080 1920 10000 256"
  "720  1280 5000  192"
  "480  854  2500  128"
  "360  640  1000  128"
)

# Fit the picture into each box with its aspect ratio preserved, and keep the
# box only if that needs no upscaling — scale = min(boxW/srcW, boxH/srcH) <= 1.
# Testing BOTH dimensions matters for wide masters: a 4096x1716 scope master is
# only 1716 tall, but it is 4096 wide, so it still deserves the 3840-wide top
# box (as 3840x1608). A height-only test would demote it to 1920x1080 and throw
# away most of the master.
#
# Each selected rung becomes "out_w out_h video_kbps audio_kbps", where out_w/h
# are the real encoded dimensions (rounded to even, as H.264 4:2:0 requires).
# The bitrate is scaled by how much of the box the picture actually fills — a
# 2.39:1 frame covers ~74% of a 16:9 box, so budgeting it a full-box bitrate
# would just overspend. For a 16:9 master the ratio is exactly 1 and the ladder
# bitrates are used unchanged.
SELECTED=()
SEEN=""
for rung in "${LADDER[@]}"; do
  ow=""; oh=""; vk=""; ak=""
  # awk prints nothing for a box that would upscale, so read hits EOF and
  # returns non-zero — tolerate that explicitly under `set -e`.
  read -r ow oh vk ak < <(awk -v sw="$SRC_W" -v sh="$SRC_H" '
    {
      bh = $1; bw = $2; vk = $3; ak = $4;
      s = (bw / sw < bh / sh) ? bw / sw : bh / sh;
      if (s > 1) exit;                      # would upscale — skip this box
      ow = int(sw * s / 2 + 0.5) * 2;
      oh = int(sh * s / 2 + 0.5) * 2;
      vk = int(vk * (ow * oh) / (bw * bh) + 0.5);
      if (vk < 300) vk = 300;
      printf "%d %d %d %d", ow, oh, vk, ak;
    }' <<<"$rung") || true
  [[ -n "$ow" ]] || continue
  case " $SEEN " in *" ${ow}x${oh} "*) continue ;; esac   # dedupe identical sizes
  SEEN+=" ${ow}x${oh}"
  SELECTED+=("$ow $oh $vk $ak")
done

# Master smaller than every box: encode it at its own size rather than upscaling
# it into the 360 box, so "never upscales" holds unconditionally.
if (( ${#SELECTED[@]} == 0 )); then
  # NB: ${LADDER[-1]} would be cleaner but macOS ships bash 3.2, which has no
  # negative array subscripts — index the last element explicitly.
  read -r vk ak < <(awk '{print $3, $4}' <<<"${LADDER[$(( ${#LADDER[@]} - 1 ))]}")
  SELECTED+=("$(( SRC_W / 2 * 2 )) $(( SRC_H / 2 * 2 )) $vk $ak")
fi

echo "Rungs:"
for r in "${SELECTED[@]}"; do awk '{printf "  %sx%s  %s kbps video / %s kbps audio\n", $1, $2, $3, $4}' <<<"$r"; done

# ---- working dir ------------------------------------------------------------
WORK="${SLUG}"
rm -rf "$WORK"
mkdir -p "$WORK"

# ---- build ffmpeg args dynamically -----------------------------------------
N=${#SELECTED[@]}
# split video into N outputs
split_labels=""
for ((i=0;i<N;i++)); do split_labels+="[v${i}]"; done
FILTER="[0:v]split=${N}${split_labels};"
for ((i=0;i<N;i++)); do
  w=$(awk '{print $1}' <<<"${SELECTED[$i]}")
  h=$(awk '{print $2}' <<<"${SELECTED[$i]}")
  # w/h are the exact even dimensions computed during rung selection, so the
  # picture is only ever scaled — never padded. Black bars in a letterboxed film
  # are whatever the master already contains; none are added here, and the
  # player letterboxes non-16:9 films in CSS instead.
  # format=yuv420p converts to 8-bit 4:2:0 — H.264 High profile can't take the
  # 10-bit 4:2:2 that ProRes 422 masters carry. This is the correct web delivery
  # format. lanczos = cleaner downscales; accurate_rnd+full_chroma_int = full-
  # precision chroma when halving 4:2:2 → 4:2:0 (fine color edges stay clean).
  # setparams stamps BT.709 + limited range on the FRAMES — modern ffmpeg
  # encoders take colour metadata from frames (overriding -color_* context
  # options), so this is what reliably lands in the H.264 VUI on every rung.
  FILTER+="[v${i}]scale=w=${w}:h=${h}:flags=lanczos+accurate_rnd+full_chroma_int,setsar=1,format=yuv420p,setparams=range=tv:color_primaries=bt709:color_trc=bt709:colorspace=bt709[v${i}out];"
done
FILTER="${FILTER%;}"  # strip trailing ;

ARGS=(-y -i "$MASTER" -filter_complex "$FILTER")
VAR_MAP=""
for ((i=0;i<N;i++)); do
  vk=$(awk '{print $3}' <<<"${SELECTED[$i]}")
  ak=$(awk '{print $4}' <<<"${SELECTED[$i]}")
  maxrate=$(( vk * 120 / 100 ))
  bufsize=$(( vk * 2 ))
  ARGS+=(
    -map "[v${i}out]" -map 0:a:0?
    -c:v:${i} libx264 -profile:v:${i} high -preset slow
    -b:v:${i} "${vk}k" -maxrate:v:${i} "${maxrate}k" -bufsize:v:${i} "${bufsize}k"
    -g "$GOP" -keyint_min "$GOP" -sc_threshold 0
    -force_key_frames:v:${i} "expr:gte(t,n_forced*${SEG})"
    -colorspace:v:${i} bt709 -color_primaries:v:${i} bt709 -color_trc:v:${i} bt709 -color_range:v:${i} tv
    -c:a:${i} aac -b:a:${i} "${ak}k" -ac 2
  )
  VAR_MAP+="v:${i},a:${i} "
done
VAR_MAP="${VAR_MAP% }"

ARGS+=(
  -f hls
  -hls_time "$SEG"
  -hls_playlist_type vod
  -hls_segment_type fmp4
  -hls_flags independent_segments
  -hls_fmp4_init_filename "init.mp4"
  -master_pl_name "master.m3u8"
  -hls_segment_filename "${WORK}/stream_%v/seg_%03d.m4s"
  -var_stream_map "$VAR_MAP"
  "${WORK}/stream_%v/playlist.m3u8"
)

echo "Encoding HLS ladder… (segments appear in ./${WORK}/ while encoding; the .zip is written at the END and the folder removed)"
ffmpeg "${ARGS[@]}"

# master.m3u8 uses paths relative to WORK root (stream_N/playlist.m3u8) — good.

# Declare that no embedded CEA-608/708 closed captions exist. Without this
# attribute the HLS spec lets clients assume captions MAY be present, so Safari
# synthesizes a phantom captions track and the player shows a CC menu even for
# caption-less films. Sidecar WebVTT captions (separate .vtt uploads) are
# unrelated and unaffected by this.
sed -i '' -E '/CLOSED-CAPTIONS/!s/^(#EXT-X-STREAM-INF:.*)$/\1,CLOSED-CAPTIONS=NONE/' "${WORK}/master.m3u8"

# ---- optional extras --------------------------------------------------------
if (( WANT_FALLBACK )); then
  echo "Encoding 1080 fallback…"
  ffmpeg -y -i "$MASTER" -vf "scale=w=1920:h=1080:force_original_aspect_ratio=decrease:flags=lanczos+accurate_rnd+full_chroma_int,format=yuv420p,setparams=range=tv:color_primaries=bt709:color_trc=bt709:colorspace=bt709" \
    -c:v libx264 -profile:v high -preset slow -b:v 10000k -maxrate 12000k -bufsize 20000k \
    -colorspace bt709 -color_primaries bt709 -color_trc bt709 -color_range tv \
    -movflags +faststart -c:a aac -b:a 256k -ac 2 "${SLUG}-1080.mp4"
fi

if (( WANT_THUMB )); then
  echo "Encoding muted thumbnail loop…"
  ffmpeg -y -i "$MASTER" -t 8 -an -vf "scale=w=720:h=-2:flags=lanczos+accurate_rnd+full_chroma_int,format=yuv420p,setparams=range=tv:color_primaries=bt709:color_trc=bt709:colorspace=bt709" \
    -c:v libx264 -profile:v high -preset veryfast -b:v 900k -maxrate 1000k -bufsize 1800k \
    -colorspace bt709 -color_primaries bt709 -color_trc bt709 -color_range tv \
    -movflags +faststart "${SLUG}-thumb.mp4"
fi

# ---- zip (contents at root so master.m3u8 is at the zip root) ---------------
ZIP="${SLUG}.hlspack.zip"
rm -f "$ZIP"
( cd "$WORK" && zip -q -r -X "../${ZIP}" . )
echo "Wrote ${ZIP} ($(du -h "$ZIP" | awk '{print $1}'))"

if (( ! KEEP )); then rm -rf "$WORK"; fi

echo "Done. Drag ${ZIP} into the WordPress Media Library."
