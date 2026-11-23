# Media players

One player system for the whole theme, drawn like the cursors. Every look and behaviour was decided on the design page, `library/players/test.html` (in the repo; open it through MAMP, or on tiags.space after a deploy). **Change a look there first, then here.**

## A player is a *kind*

Each kind has four parts in fixed places, and adding a kind never touches another kind.

| Part | Place | Contract |
|---|---|---|
| Renderer (PHP) | `library/video/` or `library/audio/` (portable, no ACF) | Returns the player's resting look as markup, opened with `tiagsspace_media_open( $kind, $attrs )` and closed with `tiagsspace_media_close()`. Works without JavaScript (the native element). |
| Module (JS) | `library/js/media/<module>.js` (ES module) | `export function mount( el, ctx )`. Never looks outside its own `el`. |
| Skin (SCSS) | `library/styles/_component-player-<look>.scss` | Scoped to `.media[data-media-kind=…]`; tokens only from `_component-player-base.scss`. |
| Icons | `library/images/player-icons/src/` | Drawn like the cursors; `python3 bin/player-icons-build.py`. |

Kinds (`registry.php`): `gallery-video`, `gallery-audio`, `content-audio` (audio in post text), `film`.

## Layers (one direction only)

```
library/media/           THEME GLUE: the only layer that reads ACF / theme rules
  registry.php           tiagsspace_media_kinds(), tiagsspace_media_open() / _close()
  enqueue.php            <html> tg-media / tg-touch (head, inline); the loader once per page
  icons.php              tiagsspace_player_icon(): the one inline sprite + <use>
library/video/, library/audio/   PORTABLE renderers (no ACF)
library/js/media/
  loader.js              one observer mounts every .media 600px ahead (Plyr once, import(), mount)
                         and one says in view (data-in-view, `tiagsspace:media-view`)
  engine.js              Plyr as engine only (no controls, storage off, no PiP) + the play bus
  clock.js               smoothClock(): progress at a constant speed; clock(): "04:07"
  wait.js                the L1 wheel (watchWaiting) and the "Dance ahead" motion (waitMotion)
  envelope.js            peaks -> the envelope path (dB, Stereo L/R, contrast 0.8, Finer)
  airplay.js             the AirPlay button (Safari only, shown only with a device nearby)
library/styles/_component-player-base.scss   tokens, sizes by player width, play mark, wheel
```

## Markup every renderer prints

```html
<div class="media" data-media-kind="gallery-video" data-media-module="gallery-video" …>
  <div class="media__frame">             <!-- the size tokens live here -->
    <img class="media__poster" …>        <!-- videos: the resting picture (srcset) -->
    <video …></video>                    <!-- hidden until mounted (html.tg-media) -->
    <span class="media__playmark …">…</span>   <!-- touch only, while not playing -->
  </div>
</div>
```

States set on `.media`: `is-mounted`, `is-native` (the module failed: the browser's own controls show), `is-started`, `is-playing`, `is-waiting`, `data-in-view`.

## Rules

- **Plyr is the engine only.** `engine()` in `engine.js` is the only `new Plyr()`; it has no controls, so there is no Plyr control CSS to fight. `plyr.css` stays its own request in the head.
- **Sizes follow the player's width**, not the screen: compact below 768px of player, wide from 768px, gutter 16px from 992px. This Sass drops `@container` blocks, so the steps are written as `clamp()` on `100cqw` (the `tg-player-step` mixin).
- **Nothing in the visitor's browser** (storage off), **no Picture-in-Picture**, every `<video>` carries `disablepictureinpicture`.
- **One version for the module folder**: the loader's `?v=` is a hash of every file in `library/js/media/`, and every module is imported with it. Never import a media module without `ctx.version` / `new URL( import.meta.url ).search`.
- No scroll handlers, no second observer: visibility comes from the loader.

## Adding a kind

1. A row in `tiagsspace_media_kinds()` (`registry.php`).
2. The renderer, using `tiagsspace_media_open()` / `tiagsspace_media_close()`.
3. The module `library/js/media/<module>.js` with `mount( el, ctx )`.
4. The skin partial, imported in `main.scss` after `component-player-base`.
5. Its icons, if new, in `library/images/player-icons/src/`, then the build script.
6. A row on the design page.
7. A row in the validation list (the plan) and a check on the real pages.
