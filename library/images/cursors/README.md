# Cursors

The theme's own mouse cursors: native CSS cursors (`cursor: url(…) x y, fallback`), drawn as SVG. The system draws them, so they never lag behind the hand. There is no JavaScript follower.

**The set:** `straight/` (Tiago, Nov 2026). 1.5px line, 28px (a 1px line was tried and dropped). A grease-pencil "contact sheet" set was tried and dropped. The stylesheet names the set in one variable, `$cursor-set`. A new set would be a folder beside `straight/` (with the same names), an entry in `$cursor-hotspots`, and that variable.

## Files

```
<set>/src/<name>.svg     drawn by hand, in plain colours: #fff inside, #000 line
<set>/light/<name>.svg   built: inside = the main page's light gradient, black line (light pages)
<set>/dark/<name>.svg    built: colours swapped (white line), inside = the page's dark gradient (dark pages)
<set>/*/previous.svg     built: next.svg mirrored
```

Names: `arrow`; `hand` (links); `folder` (links to an archive); `external` (links that leave the site: a chain); `magnify` (a picture that opens: lightbox, Hyper's Intense); `close`, `previous`, `next` (lightbox: beside the picture, its left half, its right half); `grab` / `grabbing` (a gallery dragged sideways: open hand, closed hand); `play` (a link to a film: ▶ in the lens circle); `mix` (a link to a 4K Lento mix: a filled sound envelope); `stack` (a link to any other work, not a page, whose gallery shows two or more pictures: a stack of photographs); `single` (the same when the gallery shows one picture or none: one square frame).

**Draw only in `src/`, then run `python3 bin/cursors-derive.py`** from the theme folder. It rewrites every `light/` and `dark/` file, so never edit those by hand. In `src/`, colours must be written exactly `#000` and `#fff`.

**The inside of every cursor is the main page's background gradient** (Tiago, Nov 2026), bottom to top: `#F2EFE3` → `#E3E5F2` in light, `#1d1a17` → `#16161b` in dark (`$tiagsssss-color-gradient` and its dark pair in `_foundations-fonts.scss`). The script writes it as `<linearGradient id="pagefill">` and replaces the inside colour with `url(#pagefill)`. If the page colours change, change `PAGE` in the script and run it again.

## Drawing rules

- `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 36 36">`: drawn on a 36-unit square, shown at **28px** (Tiago, Nov 2026). 1 unit = 0.778px.
- **32px is the ceiling** (they are 28). Chrome replaces a cursor bigger than 32 × 32 with the system one wherever it would cross the browser's own toolbar, and Windows clips large cursors.
- **Outer line contrasts with the page.** Light files have a black outer line, so the cursor reads on white, on the theme gradient and on photographs. The dark files have a white one.
- Leave about 1 unit of margin so round caps and outer strokes are not cut at the edge.
- One idea per glyph. No text: it would be about 9px tall.

### Straight

- White shapes with a 1.5px black line: `fill="#fff" stroke="#000" stroke-width="1.9286" stroke-linejoin="round" stroke-linecap="round"` (1.9286 units = 1.5px at 28).
- Inner detail lines (the hand's knuckles) use the same line.
- `magnify` and `close` are a pair, drawn big (disc radius 16 units) so they look as large as the arrow and the hand: a black disc with a white ellipse turned -25°, which gives the ring Lora-like stress, thicker at the sides. Inside, `+` opens and `×` closes, in the same line, round caps.
- `next` is the `arrow` path itself, turned to point right: `transform="translate(36.62 18) rotate(135)"`.
- `external` is two stadium links on the diagonal. They interlock by redrawing one link over the other inside a clip around the top crossing only. Keep the crossings far apart (link radius 6.5, ends 6 apart), or the overlaps meet and the cut shows.

## Hotspots

The hotspot is the pixel that clicks. Hotspots live in `$cursor-hotspots` in `library/styles/_component-cursors.scss`, in px at 28 (units × 28/36, rounded).

- `arrow` and `hand`: the tip. **The hands are turned** (Tiago, chosen with the test page's Hand angle slider): all three (pointing, open, closed) −21°. Each file is the upright drawing in `<g transform="translate(…) rotate(<angle> 17 17)">`, the translate re-centring it in the square. The pointing hand clicks at its fingertip (5 3); the open and closed hands at the middle of the palm (14 15, 13 13).
- `folder`: its top-left corner.
- `previous` and `next`: the arrow tips (2 14 and 26 14).
- Everything else: the centre (14 14).

**A new cursor = its `src/` file (then run the script) + one entry in that map** (plus a fallback in `$cursor-fallbacks` when `pointer` is wrong).

## Where each cursor shows

All rules live in `_component-cursors.scss`, mouse and trackpad only (`hover: hover` and `pointer: fine`).

- **Specificity.** The general rules sit in `:where(body…)`, so a component that sets its own cursor on a class keeps it: Plyr's controls, the front-end gallery editor.
- **Light or dark files.**
  - Dark files on `body.background-w-dark-color` (Films, 4K Lento).
  - Dark files under `prefers-color-scheme: dark` on `body.white-darkmode` and `body.tiagsssss-color`.
  - Plain `body.white` pages stay light.
  - The lightbox follows the system scheme only.
- **What gets what.**
  - **hand:** `a`, `button`, `summary`, `label[for]`, `[role=button]`, the Index rows `tr[data-href]`.
  - **folder:** the menu's own links (not its collapse toggles, not `target` links), the header crumbs `.site-title a`, the terms in an entry's `.footer-meta` (not Edit / Download).
  - **external:** `a[target=_blank]`, `mailto:`, `rel~=external`.
  - **previous / next:** the adjacent set on a single work (`a[rel=prev]`, `a[rel=next]`) and the archive pages (`a.pagination__previous`, `a.pagination__next`, classes from the `*_posts_link_attributes` filters in `functions.php`).
  - **play / mix / stack / single:** links that open a work. `data-opens="film"` / `"mix"` (4K Lento) / `"set"` (two or more visible attachments) / `"single"` (one or none; counted by `tiagsspace_visible_attachment_count()`, one query per loop through `tiagsspace_prime_attachment_counts()`) from `tiagsspace_opens_attr()` (`library/template-functions.php`) on the archive and related thumbnails (`archive-grid.php`) and the Index rows (`archive-table.php`); the Log listing's entry titles (`.single-title-heading a`) get the stack.
  - **grab / grabbing:** galleries dragged sideways (Swiper): `.swiper`, and `.swiper.is-grabbing` while dragging, a class set from Swiper's `touchStart` / `touchEnd` in `entry-gallery-horizontal.php` (Swiper prevents pointerdown's default, so `:active` never applies). `grabCursor` is off there, or Swiper's inline cursor would win.
  - **magnify:** `.gallery-lightbox a.lightbox-link`, `.intense`.
  - **I-beam stays:** text fields, `[contenteditable]` and the prose of `section.post-content`.
- **Hyper's viewer (Intense).** While a picture is open, the whole viewer shows the dark `close`. Intense sets its cursor inline, so this rule (`cursor-intense`) is the one `!important`.
- **Lightbox halves.** `library/js/lightbox.js` sets `.pswp--img-left` while the pointer is on the image's left half: a mouse click there goes to the previous picture, on the right half to the next, beside the image it closes. A tap on a touch screen always goes to the next (no cursor there to explain the halves).
- **Base for every device.** Outside the mouse-only block, plain system cursors keep clickable things looking clickable where the drawn set does not apply (pointer on the Index rows, the date "+" and the lightbox picture, zoom-out beside it, zoom-in on `.intense`). They are weighted to beat PhotoSwipe's own CSS and lose to the drawn cursors.
- **Versions.** Every cursor URL ends in `?v=$cursor-version`, a hash the derive script writes into `library/styles/_component-cursors-version.scss`, so a redraw is a new URL.

## Preview

`test.html` (local only: it is in `.gitignore` and never deployed; open it through the local site, e.g. `http://localhost:8888/tiagsspace/wp-content/themes/tiagsspace/library/images/cursors/test.html`) shows the set, for checking a new or changed drawing before it goes live:

- the drawings at 4× with the hotspot in red, and at real size on four backgrounds;
- a light/dark page switch, a size slider, and hover cards for each context.

Its set list and hotspots are its own copy: keep them in step with the SCSS map. Screenshots never show the cursor, so judge by hand, on a Retina screen too.
