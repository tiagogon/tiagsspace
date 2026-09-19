---
name: transcribe-captions
description: Transcribe speech from a video/audio master into timed WebVTT captions, locally on an Apple Silicon Mac. Tuned for European Portuguese (pt-PT) but works for any language. Use when the user wants to "transcribe", "make captions/subtitles", "get the dialogue as text", or produce a .vtt/.srt from a film. For wiring captions INTO a film afterwards, see the HLS caption workflow in library/film-captions.php.
---

# Local speech-to-text → WebVTT captions

Turns spoken audio into timed caption cues on the user's Mac (Apple M-series),
no cloud service. The engine is **mlx-whisper** with **large-v3**, running on the
GPU via Apple's MLX framework.

This skill was built transcribing two European-Portuguese films; the defaults
below are the ones that survived that work. Read the **Verify** section — it is
not optional.

## Tools

- `ffmpeg` / `ffprobe` — audio extraction and silence scan (`/opt/homebrew/bin`)
- `mlx-whisper` — the ASR engine (Python, Apple Silicon only)

## One-time install

```bash
python3 -m pip install mlx-whisper
```

First transcription downloads `mlx-community/whisper-large-v3-mlx` (~3 GB) into
`~/.cache/huggingface`. Cached after that, so later films start instantly.

## Step 1 — extract audio

Whisper resamples to 16 kHz mono internally, so hand it exactly that. Extract to
a scratch dir, not the Desktop.

```bash
ffmpeg -y -i "master.mov" -vn -ac 1 -ar 16000 -c:a pcm_s16le "audio.wav"
```

Probe first if unsure there's a usable track:
`ffprobe -v error -show_streams -select_streams a "master.mov"`.

## Step 2 — silence scan (the hallucination guard)

Run this BEFORE transcribing and keep the output. Whisper invents fluent text
over silence and ambience; this is how you catch it.

```bash
ffmpeg -hide_banner -i "audio.wav" -af "volumedetect" -f null -          # mean/max level
ffmpeg -hide_banner -i "audio.wav" -af "silencedetect=noise=-35dB:d=2" -f null -
```

Note the silent windows (>2 s under −35 dB). Any cue whose timing lands inside
one is almost certainly hallucinated.

## Step 3 — transcribe

```bash
mlx_whisper "audio.wav" \
  --model mlx-community/whisper-large-v3-mlx \
  --language pt \
  --word-timestamps True \
  --condition-on-previous-text False \
  --output-format vtt \
  --output-dir .
```

Change `--language` for other languages (`en`, `es`, `fr`…). Omit it only if the
language is genuinely unknown — forcing it is more reliable.

**Why these flags:**

- `--condition-on-previous-text False` — the single most important one. Stops
  Whisper feeding its own output back as context, which is how it drifts into
  confident fiction across a long quiet stretch.
- `--word-timestamps True` — lets cues be cut at real word boundaries instead of
  interpolated.
- `--language pt` — never auto-detect when you know the language.

For scripting several files, or to tune `no_speech_threshold` / `temperature`,
call `mlx_whisper.transcribe()` from Python — see the pattern the original run
used (kept in this skill's git history).

## Step 4 — VERIFY (do not skip)

`.vtt` output is a draft, never a deliverable. Whisper's characteristic failures:

- **Hallucinated lines over silence/music** — training-data ghosts like
  `A CIDADE NO BRASIL`, a fake subtitle credit (`Legendado por…`), or a YouTube
  outro reflex (`Obrigado por assistir`). Cross-check every cue against the Step
  2 silence map and delete the ones sitting in silence.
- **Degenerate repetition** — the same short line repeated many times.

Then, for the language:

- **European Portuguese**: large-v3 is trained mostly on Brazilian Portuguese,
  so it mis-hears regional/rural vocabulary, idiom, and place names, and may
  Brazilianise spelling. A native speaker MUST review the text — that is where
  the real errors are, not in the timing. Present it as a numbered, timecoded
  cue list and get corrections before producing final files.

**Bias toward gaps, not guesses.** A caption with an honest gap is better than
one that reads smoothly and is partly invented — especially on a published film
under someone's name. Flag anything uncertain rather than smoothing it over.

## Step 5 — final WebVTT

Conform to subtitling norms: ≤2 lines/cue, ~42 chars/line, ~1–6 s per cue, breaks
at clause boundaries. A `.vtt` is just:

```
WEBVTT

1
00:00:13.100 --> 00:00:15.600
The caption text.
```

For a second language, translate cue-for-cue keeping the same timings.

## Handing off to the player

Uploading `.vtt` files and wiring them onto a film is a separate concern:
captions live on the **`.m3u8` attachment** (ACF "Caption tracks"), and saving
regenerates in-manifest subtitle renditions. See `library/film-captions.php` and
the caption section of `.claude/rules/instructions AI.md`.
