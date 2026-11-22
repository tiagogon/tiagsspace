#!/usr/bin/env python3
"""
Build the player icon sprite from its drawings (library/images/player-icons/).

An icon is drawn ONCE, in src/<name>.svg, exactly like a cursor (see
library/images/cursors/README.md): the 36-unit grid, the 1.5px line
(stroke-width 1.9286), #fff for the inside, #000 for the line. This script
writes ONE sprite, library/images/player-icons/sprite.svg, with a <symbol
id="tg-<name>"> per icon, in which:

  - #000 (the line, the ink)  -> currentColor, so the player's `color` decides
                                 it (ink on light pages, light on dark pages);
  - #fff (the inside)         -> url(#tg-pagefill), the main page's gradient,
                                 bottom to top, like a cursor's inside.

The gradient's two stops read CSS custom properties, with the light page's
colours as fallbacks:
  --tg-paper-bottom (#F2EFE3)   --tg-paper-top (#E3E5F2)
A dark page sets them to the dark gradient (#1d1a17 / #16161b) and its `color`
to light: that is the cursors' dark rule (colours swapped) done in CSS, so one
sprite serves both schemes. This only works because the sprite is printed
INLINE in the page (once): CSS variables do not reach into an external file.
Keep PAGE and PAGE_DARK in step with bin/cursors-derive.py.

FIXED icons keep one look on light and dark pages (Tiago, 22 Nov): the captions
icon is a dark box with white lines, like captions on a film. In those, #000
(the box) -> url(#tg-darkfill), the dark page's gradient with fixed stops, and
#fff (the lines) stays #fff.

Run from the theme folder:   python3 bin/player-icons-build.py
It overwrites sprite.svg. Never edit that file by hand.
"""
import pathlib
import re

THEME = pathlib.Path(__file__).resolve().parent.parent
ROOT = THEME / 'library' / 'images' / 'player-icons'
SRC = ROOT / 'src'
OUT = ROOT / 'sprite.svg'
HEAD_RE = re.compile(r'^<svg[^>]*>(.*)</svg>\s*$', re.S)

# The main page's light background, bottom colour to top colour (_foundations-fonts.scss)
PAGE = ('#F2EFE3', '#E3E5F2')
PAGE_DARK = ('#1d1a17', '#16161b')
FIXED = {'captions-on', 'captions-off'}

symbols = []
for f in sorted(SRC.glob('*.svg')):
    body = HEAD_RE.match(f.read_text()).group(1)
    if f.stem in FIXED:
        body = body.replace('"#000"', '"url(#tg-darkfill)"')
    else:
        body = body.replace('"#000"', '"currentColor"').replace('"#fff"', '"url(#tg-pagefill)"')
    symbols.append(f'<symbol id="tg-{f.stem}" viewBox="0 0 36 36">{body}</symbol>')

defs = ('<defs><linearGradient id="tg-pagefill" gradientUnits="userSpaceOnUse" x1="0" y1="36" x2="0" y2="0">'
        f'<stop offset="0" style="stop-color:var(--tg-paper-bottom,{PAGE[0]})"/>'
        f'<stop offset="1" style="stop-color:var(--tg-paper-top,{PAGE[1]})"/>'
        '</linearGradient>'
        '<linearGradient id="tg-darkfill" gradientUnits="userSpaceOnUse" x1="0" y1="36" x2="0" y2="0">'
        f'<stop offset="0" stop-color="{PAGE_DARK[0]}"/><stop offset="1" stop-color="{PAGE_DARK[1]}"/>'
        '</linearGradient></defs>')

OUT.write_text(
    '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" '
    'style="position:absolute;width:0;height:0;overflow:hidden">'
    + defs + ''.join(symbols) + '</svg>\n')
print(f'{OUT.relative_to(THEME)}: {len(symbols)} icons')
