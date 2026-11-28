#!/usr/bin/env python3
"""
Build the cursor files from their drawings (library/images/cursors/).

A cursor is drawn ONCE, in <set>/src/, in plain colours: #fff for the inside,
#000 for the line. This script writes what the site uses:
  - <set>/light/<name>.svg  the inside filled with the main page's light
                            gradient, the line black (for light pages);
  - <set>/dark/<name>.svg   black and white swapped first (white line, so it
                            still contrasts with the page), then the inside
                            filled with the main page's dark gradient;
  - <set>/{light,dark}/previous.svg  from next.svg, mirrored left to right;
  - library/styles/_component-cursors-built.scss: $cursor-hotspots (each
    cursor's click point in px at its size) and $cursor-version, a hash of
    every built file. The stylesheet appends the hash to each cursor URL (?v=),
    so a redrawn cursor gets a new URL and no cache keeps the old one. Koala
    then recompiles main.css.

Size and line are set here, not in the drawings: SIZES (px per cursor), LINE
(px, the same at every size) and HOTSPOTS (in drawing units). Try values first
on library/images/graphics.html (Arrow, Others and Line sliders).

The gradients are the main page's background ($tiagsssss-color-gradient and
$tiagsssss-color-darkmode-gradient in library/styles/_foundations-fonts.scss):
keep PAGE below in step with them.

Run from the theme folder:   python3 bin/cursors-derive.py
It overwrites every light/ and dark/ file. Never edit those by hand.
Colours in src/ must be written exactly as #000 and #fff.
See library/images/cursors/README.md.
"""
import hashlib
import pathlib
import re

THEME = pathlib.Path(__file__).resolve().parent.parent
ROOT = THEME / 'library' / 'images' / 'cursors'
BUILT_FILE = THEME / 'library' / 'styles' / '_component-cursors-built.scss'
HEAD_RE = re.compile(r'^(<svg[^>]*>)(.*)</svg>\s*$', re.S)

# The main page's background, bottom colour to top colour (_foundations-fonts.scss)
PAGE = {
    'light': ('#F2EFE3', '#E3E5F2'),
    'dark': ('#1d1a17', '#16161b'),
}

# Every drawing in src/ is made on a 36-unit square at 28px with a 1.5px line
# (stroke-width 1.9286). What the site gets (Tiago, Nov 2026):
DRAWN_SIZE = 28
DRAWN_LINE = 1.5
LINE = 1      # px, at every size
SIZES = {'arrow': 20}   # px; the arrow is small, the others a little bigger so they look alike
OTHER_SIZE = 28

# Click point of each cursor, in drawing units (36 = the whole square); the rest
# click at the centre (18 18). Per set.
HOTSPOTS = {
    'straight': {'arrow': (2, 2), 'hand': (6.6, 3.6), 'folder': (3.5, 6.5), 'previous': (2.2, 18),
                 'next': (33.8, 18), 'grab': (18.6, 19.5), 'grabbing': (17.1, 16.3)},
}


def size_of(name):
    return SIZES.get(name, OTHER_SIZE)


def sized(svg, name):
    """Write the cursor at its size, with a LINE px line whatever the size."""
    n = size_of(name)
    k = (LINE / DRAWN_LINE) * (DRAWN_SIZE / n)
    svg = re.sub(r'width="[\d.]+" height="[\d.]+"', f'width="{n}" height="{n}"', svg, count=1)
    if k == 1:
        return svg

    def scaled(m):
        w = f'{float(m.group(1)) * k:.4f}'.rstrip('0').rstrip('.')
        return f'stroke-width="{w}"'
    return re.sub(r'stroke-width="([\d.]+)"', scaled, svg)


def swap_colours(svg):
    return svg.replace('#000', '#TMP').replace('#fff', '#000').replace('#TMP', '#fff')


def page_fill(svg, mode):
    """Fill the inside (#fff in light, #000 in dark) with the page gradient."""
    inside = '"#fff"' if mode == 'light' else '"#000"'
    bottom, top = PAGE[mode]
    head, body = HEAD_RE.match(svg).groups()
    defs = (f'<defs><linearGradient id="pagefill" x1="0" y1="1" x2="0" y2="0">'
            f'<stop offset="0" stop-color="{bottom}"/><stop offset="1" stop-color="{top}"/></linearGradient></defs>')
    return f'{head}{defs}{body.replace(inside, chr(34) + "url(#pagefill)" + chr(34))}</svg>\n'


def mirrored(svg):
    head, body = HEAD_RE.match(svg).groups()
    return f'{head}<g transform="matrix(-1 0 0 1 36 0)">{body}</g></svg>\n'


for src in sorted(ROOT.glob('*/src')):
    out = {'light': src.parent / 'light', 'dark': src.parent / 'dark'}
    for folder in out.values():
        folder.mkdir(exist_ok=True)
    for f in sorted(src.glob('*.svg')):
        plain = sized(f.read_text(), f.stem)
        (out['light'] / f.name).write_text(page_fill(plain, 'light'))
        (out['dark'] / f.name).write_text(page_fill(swap_colours(plain), 'dark'))
    for folder in out.values():
        nxt = folder / 'next.svg'
        if nxt.exists():
            prev = mirrored(nxt.read_text())
            if size_of('previous') != size_of('next'):
                prev = re.sub(r'width="[\d.]+" height="[\d.]+"', f'width="{size_of("previous")}" height="{size_of("previous")}"', prev, count=1)
            (folder / 'previous.svg').write_text(prev)
    print(f'{src.parent.name}: light/ and dark/ written from src/')

# The version: one hash over every built file, in a stable order
digest = hashlib.sha1()
for f in sorted(ROOT.glob('*/light/*.svg')) + sorted(ROOT.glob('*/dark/*.svg')):
    digest.update(f.relative_to(ROOT).as_posix().encode() + b'\0' + f.read_bytes())
version = digest.hexdigest()[:10]


def hotspot_px(set_name, name):
    x, y = HOTSPOTS.get(set_name, {}).get(name, (18, 18))
    k = size_of(name) / 36
    return f'{round(x * k)} {round(y * k)}'


lines = []
for d in sorted(p.parent for p in ROOT.glob('*/src')):
    names = sorted(f.stem for f in (d / 'light').glob('*.svg'))
    lines.append(f'  {d.name}: (' + ', '.join(f'{n}: {hotspot_px(d.name, n)}' for n in names) + ')')
BUILT_FILE.write_text(
    '// Generated by bin/cursors-derive.py: never edit. Change SIZES, LINE or HOTSPOTS there.\n'
    '// Click point of each cursor, in px at its own size:\n'
    '$cursor-hotspots: (\n' + ',\n'.join(lines) + '\n);\n'
    '// A hash of every built cursor file, appended to each cursor URL (?v=) so a redraw is a new URL.\n'
    f'$cursor-version: "{version}";\n')
print(f'cursor version {version}')
