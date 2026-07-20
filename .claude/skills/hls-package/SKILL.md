---
name: hls-package
description: Create a self-hosted HLS bundle (.hlspack.zip) from a video master so a film can stream adaptively on tiagsspace. Use when the user wants to package/encode a film for the video player, "make the m3u8", generate an HLS bundle, convert a film to HLS, or prep a master (ProRes/.mov/.mp4) for upload to the WordPress Media Library.
---

# HLS packaging for tiagsspace films

Films on this site can play two ways (see `library/video/`): a progressive MP4
ladder, or **HLS** for adaptive 4K. This skill produces the HLS bundle.

## The tool

`bin/hls-package.sh` (in the theme root) is the authoring tool. It runs on the
user's Mac (needs `ffmpeg` + `ffprobe` — installed at `/opt/homebrew/bin`) and
turns ONE master video into a `<slug>.hlspack.zip`.

**The encoding config lives inside that script — it is the source of truth.**
Do not hardcode bitrates in this skill. Current setup (as of last edit): a
2160/1080/720/480/360 ladder at 20/10/5/2.5/1 Mbps, H.264 High + AAC, `-preset
slow`, 6 s fMP4 segments, and it **preserves the master's frame rate**
(24/25/30/23.976/29.97 — never forces 24) with keyframes on segment boundaries.
The ladder auto-caps to the master's height. To change quality, edit the
`LADDER=(...)` array and encode params in the script, don't work around them.

Flags: `--thumb` (muted loop MP4 for the archive grid), `--fallback` (a single
progressive 1080p MP4), `--keep` (keep the unzipped working folder).

## Picking the source file (criteria)

One file in → the whole ladder is derived from it, so use the **best master**:

1. **Full film, not a teaser/tease.**
2. **Highest-quality codec:** ProRes (422 HQ) is ideal — near-lossless, one
   generation of compression. Otherwise the **highest-bitrate H.264** (≥ ~60
   Mbps). Never encode a 20 Mbps 4K rung from a source that's already a ~14 Mbps
   H.264 export — you'd just waste space; that only makes sense from ProRes.
3. **Highest resolution** (for 4K, avoid the 720p/HD exports — they cap the
   ladder below 4K).

If unsure which file, `ffprobe` the candidates for resolution/bitrate/codec and
recommend, rather than guessing from filenames.

## How to run it

1. **Probe first** (fast) to confirm the path is reachable and report
   resolution / fps / codec / bitrate:
   ```bash
   ffprobe -v error -select_streams v:0 \
     -show_entries stream=codec_name,width,height,r_frame_rate,bit_rate \
     -show_entries format=duration -of default=noprint_wrappers=1 "<MASTER>"
   ```
2. **Run in the background** — ProRes 4K + `-preset slow` is CPU-heavy (tens of
   minutes for a full film). Write the zip somewhere easy to find (default the
   user's Desktop unless they say otherwise):
   ```bash
   cd ~/Desktop && /Users/tiagogoncalves/Sites/tiagsspace/wp-content/themes/tiagsspace/bin/hls-package.sh "<MASTER>" <slug>
   ```
   Use `run_in_background: true` and notify the user with the exact zip path when
   it finishes. Never fabricate completion — wait for the real result.

   **During the encode a working folder `<slug>/` appears and grows** — that is
   normal; the `.hlspack.zip` is only written at the END and the folder is then
   removed. Warn the user so a "folder but no zip yet" doesn't look like a failure.

### Color handling (guaranteed by the script)
The script converts 10-bit 4:2:2 ProRes to 8-bit 4:2:0 (H.264 High can't take
4:2:2) with high-precision chroma (`lanczos+accurate_rnd+full_chroma_int`) and
**stamps BT.709 primaries/transfer/matrix + limited (tv) range on the frames via
`setparams`** — so every rung (including SD-sized 480/360, which players would
otherwise guess as BT.601) carries explicit color metadata even if the master is
untagged. Pixels are never matrix-converted (BT.709 in → BT.709 out). Masters are
assumed Rec.709; if one is ever HDR/P3, stop and discuss before encoding.

### Source location / NAS
Any path the Mac can read works, including a NAS **mounted** under `/Volumes/…`.
Claude cannot mount a NAS (needs the user's Finder credentials) — if it isn't
mounted, ask the user to connect to it first, then use the `/Volumes/...` path.
Reading a large ProRes over the network is slower than local disk; copying it
local first speeds up the encode but isn't required.

## After generating

Tell the user to:
1. **Drag `<slug>.hlspack.zip` into the WordPress Media Library** (prod). The
   theme's `library/video/hls-import.php` unpacks it into an `.m3u8` attachment
   (see `wp-content/uploads/hls/import.log` if anything fails).
2. Set that attachment as the film's **Self Host Film** field. Done — the player
   detects `.m3u8` and streams HLS.

## Captions

- The export is **identical** for captioned films — captions are NOT encoded
  into the HLS bundle. They stay sidecar WebVTT files, uploaded separately and
  wired via Videopack's `_kgvid-meta` **on the .m3u8 attachment** (same workflow
  as MP4 films; `library/video/player-hls.php` renders them as `<track>`
  elements, which work with both hls.js and native Safari HLS).
- The script stamps `CLOSED-CAPTIONS=NONE` on every `#EXT-X-STREAM-INF` line of
  `master.m3u8`. That only declares "no *embedded* CEA-608 captions in the video
  stream" — without it Safari synthesizes a phantom CC track and the player
  shows a captions menu on caption-less films. It does not affect sidecar VTT.
- **Unverified caveat (check on the first captioned HLS migration):** Videopack's
  captions UI may not appear on an attachment whose MIME is
  `application/vnd.apple.mpegurl`. If so, the `_kgvid-meta` `track` array needs
  a small filter or manual meta entry.

## Gotchas

- **nginx MIME (prod, one-time):** native Safari/iOS may refuse the playlist
  unless nginx serves `.m3u8` as `application/vnd.apple.mpegurl` and `.m4s` as
  `video/iso.segment`. hls.js (Chrome/Firefox) works regardless. Flag this if the
  user hasn't done it yet.
- The `.m3u8` file lives *inside* the zip as `master.m3u8`; the user never
  handles it directly.
- For deeper context see `README.md` § Video and
  `.claude/rules/instructions AI.md` (the `library/video/` module).
