# Player icons

The media players' icons (library/media/README.md), drawn exactly like the cursors (`library/images/cursors/README.md`): the 36-unit grid, a 1.5px line (stroke-width 1.9286 on the grid at 28px), straight segments with round joins and caps.

- **Draw** one file per icon in `src/<name>.svg`, with `#000` for the line and `#fff` for the inside, nothing else.
- **Build** with `python3 bin/player-icons-build.py` (from the theme folder). It writes `sprite.svg`, one `<symbol id="tg-<name>">` per icon: `#000` becomes `currentColor` (the ink), `#fff` becomes `url(#tg-pagefill)`, the page's gradient, whose stops read `--tg-paper-bottom` / `--tg-paper-top`. One sprite serves light and dark pages; never edit `sprite.svg` by hand.
- **Fixed icons** keep one look on every page: the captions icons are a dark box with white lines, like captions on a film (Tiago, 22 Nov 2026). They are listed in `FIXED` in the build script; there `#000` becomes the dark page's gradient (`url(#tg-darkfill)`) and `#fff` stays white.
- **Use** in PHP with `tiagsspace_player_icon( 'play-large', 'class' )` (`library/media/icons.php`): it prints the sprite inline with the page's first icon (CSS variables do not reach into an external file) and `<svg><use href="#tg-…">` for the icon.

Icons: `play`, `play-large`, `pause`, `pause-large`, `captions-on`, `captions-off`, `enter-fullscreen`, `exit-fullscreen`, `airplay`, `restart`, `rewind`, `fast-forward`, `settings`. The loading wheel (L1) is drawn in `library/js/media/wait.js`; the icon sheet on the design page (`library/players/test.html`) shows them all, light and dark.
