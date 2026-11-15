# Tumblr theme

The custom theme of <https://tiagssssspace.tumblr.com/>. It is not part of the
WordPress theme: nothing here is loaded by WordPress. It lives in this repo so it
has a history and a place to be changed.

- `theme.html` — the whole theme, as Tumblr wants it. **Source of truth.**
- `preview.js` — builds local preview pages from the blog's real posts.
- `preview/` — the built pages (gitignored).

## What it is

Georgia Harris's *Disassemble 3.0* theme, as it has been on the blog: posts of fixed,
varied pixel widths (250–530px, `{block:Post1}` … `{block:Post15}`) packed by the
theme's own script, 580px on a post page. **The grid and the sizes stay as they are**
(Tiago, Nov 2026): Tumblr serves its own image resolutions, so the tiags.space layout
does not carry over.

Two things are taken from tiags.space. The **colours**: the index background
(`#E3E5F2` to `#F2EFE3`, flat on phones) with black text, and in dark mode `#16161b`
to `#1d1a17` with white text, switched by the visitor's system
(`prefers-color-scheme`), on every page of the blog. They are fixed in the CSS, so the
theme's old Text and Background colour options are gone. And the **typeface: Lora** (Google Fonts, since
Tumblr cannot host the font files), weight 400 as on the site, with the same macOS
font smoothing. Text is 21px (raised by Tiago from the original 14px/16px).

**The fixed words** (tiags.space, Message, Random, Back to top) invert what is behind
them, like the header on tiags.space: white text with `mix-blend-mode: difference`,
dark over the light background or a light picture, light over a dark one. For that the
page background sits on `<html>`, not `<body>`, and nothing between the words and the
pictures (`<body>`, `#wrapper`) may get a z-index, opacity, transform, filter or
isolation.

**Infinite scroll** comes from `disassemble_v3.js`, hosted by Tumblr (never edited). It
fetches the next page with the plugin default, 80px before the end, and keeps the new
posts hidden until all their images arrive, which on Tumblr's slow image servers left a
blank wait at the bottom. A small handler in `theme.html` raises its `bufferPx` to 2.5
screen heights, so the next page is requested while about two screens are still left.
The preview builds four linked index pages (`page-2.html`…) to test it.

## Change it

1. Edit `theme.html`.
2. `node tumblr/preview.js`, then open
   <http://localhost:8888/tiagsspace/wp-content/themes/tiagsspace/tumblr/preview/index.html>
   (MAMP running). `--start 15` builds from the next page of posts.
3. Copy the whole of `theme.html` into Tumblr: blog settings → Edit appearance →
   Custom theme → Edit theme → Edit HTML → Update preview → Save.
4. Commit.

Never change the HTML on Tumblr only: the next paste from here would undo it.

## Not in this file

Tumblr keeps two things outside the HTML, and a paste does not touch them:

- the **theme options** ("Uppercase Links", on at the moment, and the others), set in the Edit theme sidebar;
- the **custom CSS** box (Advanced options), inserted where the theme says `{CustomCSS}`.

The local preview has neither, so it shows the theme's defaults (links not in capitals). Tumblr's own preview in Edit theme is the last check.
