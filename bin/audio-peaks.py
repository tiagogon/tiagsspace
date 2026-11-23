#!/usr/bin/env python3
"""
Work out an audio file's envelope: the loudness of N equal slices of it.

The players draw a mix's envelope (library/audio/, the `mix` cursor's drawing
made long) and a film's progress (its soundtrack, rising from the frame's edge)
from these numbers, so the shape is the file's real sound. This is the
reference implementation; library/audio/peaks.php (on upload, on the server,
Tiago's choice of 22 Nov 2026) must give the same numbers.

  1. ffmpeg decodes the file to 16-bit PCM at 8 kHz (plenty for a loudness
     shape, and fast): once as mono, once as stereo (left and right apart).
  2. The samples are cut into N equal slices (default 2,000).
  3. Per slice: the PEAK (largest absolute sample) and the RMS, for the mono
     mix and for each channel. A mono file gives L = R.
  4. Each list is divided by the largest value of its kind (all peak lists by
     the loudest peak of the three, all RMS lists by the loudest RMS), so the
     loudest slice is 1 and left and right stay comparable. Rounded to 3 decimals.
  5. With --lufs (films): ffmpeg's ebur128 momentary loudness (a 400 ms window,
     every 100 ms), the loudest value per slice, mapped from [-60 LUFS, the
     loudest] to [0, 1].

The dB view is NOT stored: the player derives it from the RMS lists
(20 * log10), so it costs nothing extra.

Output (stdout, or --out file): JSON
  {"v":2,"slices":2000,"duration":2799.1,
   "peak":[...],"rms":[...],"peakL":[...],"peakR":[...],"rmsL":[...],"rmsR":[...],
   "lufs":[...]}            <- lufs only with --lufs

Usage:  python3 bin/audio-peaks.py <file> [--slices 2000] [--lufs] [--out peaks.json]
Needs ffmpeg and ffprobe on the PATH (Homebrew: /opt/homebrew/bin).
"""
import argparse
import array
import json
import math
import re
import subprocess
import sys

RATE = 8000
LUFS_FLOOR = -60.0


def duration_of(path):
    out = subprocess.run(['ffprobe', '-v', 'error', '-show_entries', 'format=duration',
                          '-of', 'default=nw=1:nk=1', path], capture_output=True, text=True, check=True)
    return float(out.stdout.strip())


def pcm(path, channels):
    raw = subprocess.run(['ffmpeg', '-v', 'error', '-i', path, '-vn', '-ac', str(channels), '-ar', str(RATE),
                          '-f', 's16le', '-'], capture_output=True, check=True).stdout
    s = array.array('h')
    s.frombytes(raw[: len(raw) - len(raw) % 2])
    if sys.byteorder == 'big':
        s.byteswap()
    return s


def slices_of(samples, slices):
    """Peak and RMS per slice, raw (not normalised)."""
    n = len(samples)
    peak, rms = [], []
    for i in range(slices):
        a, b = n * i // slices, max(n * (i + 1) // slices, n * i // slices + 1)
        part = samples[a:min(b, n)] or array.array('h', [0])
        peak.append(max(abs(x) for x in part))
        rms.append(math.sqrt(sum(x * x for x in part) / len(part)))
    return peak, rms


def lufs_of(path, slices, duration):
    """ebur128 momentary loudness, loudest per slice, mapped to 0..1."""
    out = subprocess.run(['ffmpeg', '-hide_banner', '-nostats', '-i', path, '-vn',
                          '-af', 'ebur128=metadata=1,ametadata=print:key=lavfi.r128.M:file=-',
                          '-f', 'null', '-'], capture_output=True, text=True, check=True).stdout
    times = [float(t) for t in re.findall(r'pts_time:([0-9.]+)', out)]
    values = [float(m) for m in re.findall(r'lavfi\.r128\.M=(-?[0-9.]+|-inf)', out.replace('-inf', '-120'))]
    best = [LUFS_FLOOR] * slices
    for t, m in zip(times, values):
        k = min(slices - 1, int(t / duration * slices)) if duration else 0
        best[k] = max(best[k], m)
    for i in range(1, slices):                       # a slice between two readings keeps the last one
        if best[i] == LUFS_FLOOR and best[i - 1] > LUFS_FLOOR:
            best[i] = best[i - 1]
    top = max(best)
    span = (top - LUFS_FLOOR) or 1
    return [round(max(0.0, (v - LUFS_FLOOR) / span), 3) for v in best]


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[1])
    ap.add_argument('file')
    ap.add_argument('--slices', type=int, default=2000)
    ap.add_argument('--lufs', action='store_true', help='also store momentary loudness (films)')
    ap.add_argument('--out')
    args = ap.parse_args()

    duration = duration_of(args.file)
    mono_peak, mono_rms = slices_of(pcm(args.file, 1), args.slices)
    st = pcm(args.file, 2)
    l_peak, l_rms = slices_of(st[0::2], args.slices)
    r_peak, r_rms = slices_of(st[1::2], args.slices)

    top_peak = max(mono_peak + l_peak + r_peak) or 1
    top_rms = max(mono_rms + l_rms + r_rms) or 1
    norm = lambda values, top: [round(v / top, 3) for v in values]

    data = {'v': 2, 'slices': args.slices, 'duration': round(duration, 2),
            'peak': norm(mono_peak, top_peak), 'rms': norm(mono_rms, top_rms),
            'peakL': norm(l_peak, top_peak), 'peakR': norm(r_peak, top_peak),
            'rmsL': norm(l_rms, top_rms), 'rmsR': norm(r_rms, top_rms)}
    if args.lufs:
        data['lufs'] = lufs_of(args.file, args.slices, duration)

    text = json.dumps(data, separators=(',', ':'))
    if args.out:
        with open(args.out, 'w') as fh:
            fh.write(text)
    else:
        print(text)


if __name__ == '__main__':
    main()
