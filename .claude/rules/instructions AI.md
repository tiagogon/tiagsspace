---
description: Global coding instructions for the tiagsspace WordPress theme
paths:
  - "**"
---
When a change in the codebase invalidates or contradicts information in this file (e.g. renaming files, adding/removing post types, changing directory structure), update this file immediately to reflect the new state. This avoids re-explaining context in future sessions.

Never edit the bootstrap.css file. All style editing needs to be done via .scss files.

Never edit files inside library/js/ subdirectories (e.g. intense-images, plyr, masonry, etc.). These are third-party libraries that may be updated from their upstream repositories — any custom edits would be lost on update. Instead, apply fixes or overrides via custom SCSS or separate wrapper scripts.

Never check how the SCSS is compiled. I will always ensure I have Koala running in the background when working with you.

## Theme structure

This is a custom WordPress theme called "tiagsspace" (Tiags' Space). It uses a custom 48-column Bootstrap grid (not the default 12-column).

### PHP files
- `functions.php` — theme setup: enqueues, theme supports, responsive image helper, thumbnail sizes, upload mimes, favicons, archive title cleanup, misc hooks. Requires all `library/*.php` files.
- `library/helpers.php` — WordPress cleanup utilities (head cleanup, pagination, excerpt formatting, image p-tag filter)
- `library/video/` — **Portable self-hosted video module** (Plyr-based; no ACF/theme coupling inside — callers pass values via `$args`, so the folder copy-pastes into other themes like lento.world). The `self_host_film` attachment on a film is the mode switch: an **`.m3u8`** attachment plays via **HLS**, any other video plays the **progressive MP4 ladder**.
  - `player.php` — `render_video_player($args)` dispatcher + shared helpers (`tiagsspace_video_is_hls()`, poster via `xlarge`, caption tracks from `_kgvid-meta`, Plyr config builder from `player_options`).
  - `player-mp4.php` — progressive MP4 renderer: builds a de-duplicated, correctly-typed quality ladder from the original + Videopack child attachments. A `.mov`/quicktime master is included as a selectable rung with its real MIME (`video/quicktime`) — Safari/Chrome/iOS play it, Firefox skips that `<source>` and uses the MP4 rungs. The default quality (largest ≤1080) is emitted first in DOM so browsers never auto-load the big master; `preload="metadata"`.
  - `player-hls.php` — HLS renderer: single `<video>` with the `master.m3u8` source + `data-hls-src`.
  - `enqueues.php` — registers module JS with `filemtime()` versions; `tiagsspace_video_enqueue_mp4()` (loads `plyr-adaptive-quality.js`) vs `tiagsspace_video_enqueue_hls()` (loads `hls/hls.min.js` + `video-player/init-hls.js`). Also defines `tiagsspace_asset_ver()`.
  - `hls-import.php` — Media Library import: drag a `<slug>.hlspack.zip` (built by `bin/hls-package.sh`), the `add_attachment` hook unzips it to `uploads/hls/<attachment-id>/`, re-points the attachment at `master.m3u8` (MIME `application/vnd.apple.mpegurl`), deletes the zip; `delete_attachment` removes the folder (path-guarded). Logs to `uploads/hls/import.log`; failures show an admin notice and leave the zip intact. Allows `zip`/`m3u8` in `upload_mimes`.
  - Client JS: `library/js/video-player/init-hls.js` (hls.js wherever MSE is available — preferred over Chrome's native HLS because native ignores `capLevelToPlayerSize`; native HLS only on Safari/iOS; `?video_debug=1` logs the engine, ladder and ABR level switches); `library/js/plyr-adaptive-quality.js` is the **MP4 films only** hand-rolled adaptive engine (minimal maintenance — convert a film to HLS rather than extending it).
  - **HLS films have no quality menu on any browser** — ABR picks the rung, and the viewer gets no override. `player-hls.php` passes `['settings' => ['captions']]` as the `$overrides` arg to `tiagsspace_video_build_config()` to drop the pane; the base config in `player.php` still lists `quality` so **MP4 films keep their menu** (they have no ABR to fall back on). Don't "fix" this by editing the shared base config. The settings cog hides itself entirely on an HLS film with no caption tracks.
  - **`library/js/video-player/fullscreen-on-play.js`** — shared by both playback paths (registered once in `enqueues.php`, enqueued from both `tiagsspace_video_enqueue_mp4()` and `tiagsspace_video_enqueue_hls()`). A document-level delegated click listener in the **capture phase** on `[data-plyr="play"]` — not a Plyr `play`-event handler, because `requestFullscreen()` needs transient user activation and only the click itself is reliably inside that gesture. Scoped to `video.film-player` (films only, never gallery/`[KGVID]` videos) and skips films with `loop` or `autoplay` set (ambient by intent, must not seize the screen). On iPhone this hands playback to **Apple's native player** via Plyr's `iosNative: true` (`webkitEnterFullscreen`), replacing the Plyr skin for the duration; iPad and everything else get real fullscreen with the skin intact. By design, fullscreen re-triggers on every play including a deliberate resume after the viewer exited — not a bug.
  - **`library/js/video-player/ios-native-captions.js`** — registered/enqueued alongside `fullscreen-on-play.js` for both playback paths. Fixes captions vanishing in **iPhone fullscreen**: Plyr force-demotes every text track to `mode="hidden"` and paints cues into its own `.plyr__captions` div (`captions.update()` and `captions.toggle()`, which re-hides `currentTrackNode` even when captions are being turned *on*), so once `webkitEnterFullscreen()` hands the element to Apple's player neither renderer draws anything — the overlay is off-screen and the track is hidden. Plyr itself has **zero** `webkitbeginfullscreen`/`webkitendfullscreen` handling. This script promotes the active track to `showing` on enter and back to `hidden` on exit, so iOS draws the cues (in the viewer's **system caption styling**, which our SCSS cannot reach — that is the accepted trade), and adopts any language the viewer picked in Apple's own subtitle menu. Listeners are bound **per `<video>`, not delegated**, because those two events do not bubble; the Plyr instance is still resolved at event time. Leaving a track on `showing` while inline would double up with the Plyr overlay, which is why exit must restore `hidden`. iPad is unaffected (reports as desktop Safari, keeps the Plyr overlay).
  - `bin/hls-package.sh` — local ffmpeg script (Mac; accepts `.mov`/`.mp4`) building the fMP4 HLS ladder → `<slug>.hlspack.zip`; `--thumb`/`--fallback` extras. Ladder entries (2160/1080/720/480/360) are 16:9 **boxes** the picture is fitted into with its aspect ratio preserved and never padded; a box is used only if it needs no upscaling, and per-rung bitrate scales with the real pixel count. So sub-4K masters just shorten the ladder, and a 2.39:1 4K master tops out at 3840x1608 rather than a padded 1920x1080.
  - **Videopack (video-embed-thumbnail-generator) stays in active service** for archive thumbnail loops (`archive-grid.php` `kgflash_video` meta), gallery videos, and `[KGVID]` shortcodes (`entry-gallery.php`). Only *films* use the HLS/MP4 module. `hls.min.js` is vendored third-party (never edit).
  - **Skill `hls-package`** (`.claude/skills/hls-package/SKILL.md`) — invoke when asked to build an HLS bundle / "make the m3u8" / convert a film to HLS. It documents the `bin/hls-package.sh` tool, source-file selection criteria, running the encode in the background, and the upload/test workflow. The encoding config (bitrate ladder, preset, fps handling) lives in `bin/hls-package.sh` itself — that script is the source of truth; edit it there to change quality.
- `library/admin.php` — Dashboard widget cleanup (commented out by default)
- `library/custom-post-types.php` — CPT registrations (films, dusk, hyper, 4k-lento, log, cityburns), log-branch admin filter, admin menu order, CPTs in main loop/feed/archives
- `library/custom-taxonomies.php` — Taxonomy registrations (places, medium, log-branch, from), admin taxonomy filters, CPTs on tag archives, disable categories
- `library/template-functions.php` — Template helpers: taxonomy_list(), content_wrap(), background colors, number/stats helpers, attachment margins, media counts, [last_post_link] shortcode, `tiagsspace_versioned_media_url()` (appends a `?v=<filemtime>` to an uploads URL so a file replaced in place defeats the 1-year media Cache-Control; used by the archive teaser `<source>` tags in `template-parts/archive-grid.php`)
- `library/seo-and-feed.php` — Feed links, Feedly support, feed title/content formatting, Yoast OpenGraph and Twitter image overrides
- `library/gallery-functions.php` — Gallery admin UI (hide/delete/reorder/resize), AJAX handlers for media order, attachment size, margin changes
- `library/query-filters.php` — Hide posts from index/archives/feeds (ACF field), exclude hidden adjacent posts, purge Table Index cache on post changes
- `library/duplicate-post.php` — Reusable `tiagsspace_duplicate_post($id, $overrides)` clones any post (any type/status) to a new draft, copying all meta (ACF/featured image) + taxonomy terms; attachments are NOT re-parented. Exposes a per-row "Duplicate" link, a "Duplicate" bulk action, and a Gutenberg editor-header icon (`library/js/editor-duplicate-button.js`). Title gets a `— copy` / `— copy N` suffix. Fires `tiagsspace_post_duplicated` action. After copying meta it seeds the new post's latest revision (`wp_save_post_revision` + `acf_save_post_revision`) so ACF previews — which read the latest revision — show the duplicated values without needing a manual save. Also adds an "Add to a duplicated post" CTA in the media-modal footer (`library/js/media-duplicate-to-post.js` + AJAX `tiagsspace_duplicate_to_post`) that duplicates the current post and **re-parents** the selected attachments onto the copy (they move off the original; gallery order preserved via untouched `menu_order`; the first moved image becomes the copy's featured image), opening the new draft in a new tab. The same media-modal toolbar also has a "Delete permanently" CTA (`media-duplicate-to-post.js` + AJAX `tiagsspace_delete_attachments`) that bulk-`wp_delete_attachment($id, true)`s the selected attachments after a plain confirm (`delete_post`-gated per item; WP media has no trash so this is irreversible). Also registers an MCP ability `mcp-content/duplicate-post` (tool `mcp-content-duplicate-post`, on `wp_abilities_api_init`, reusing the mcp-content-abilities plugin's namespace/category) wrapping `tiagsspace_duplicate_post()`; it accepts `source_post_id` (+ optional `title`/`status`/`move_attachment_ids`) and shares the re-parent helper `tiagsspace_move_attachments_to_post()`.
- `library/trash-post.php` — Attached-media choice when trashing a post. At trash time the admin picks: (1) trash post only, (2) trash + delete its media when the post is permanently deleted, or (3) permanently delete post + media now. Driven by ONE post-meta flag `_tiagsspace_delete_attached_media` (`TIAGSSPACE_DELETE_MEDIA_META`) set for choices 2/3 and read by a single `before_delete_post` handler that `wp_delete_attachment($id, true)`s every attachment whose `post_parent` is the post (`tiagsspace_delete_post_attachments()`); the flag is cleared on `untrashed_post`. Three UI entry points via the shared modal `library/js/trash-post-modal.js`: the list-table row "Trash" link (per-post nonced URLs injected as `data-` attrs on the trash `<a>` via `post_row_actions`/`page_row_actions`), the bulk "Move to Trash" action (JS rewrites the selected action to hidden bulk handlers `tiagsspace_trash_with_media` / `tiagsspace_delete_with_media`), and the Gutenberg editor "Move to trash" button (`.editor-post-trash`, intercepted in capture phase). Single-post routes go through `admin-post.php` handlers (`tiagsspace_handle_trash_with_media` / `tiagsspace_handle_delete_with_media`). Applies to all post types; modal styling is injected by the JS (not part of the SCSS pipeline).
- `template-parts/` — reusable template parts (header-title, header-menu, header-lower, archive-grid, archive-table, entry-body, entry-gallery, entry-gallery-horizontal, entry-video-player)

### SCSS / CSS
- SCSS source files live in `library/styles/`
- Entry point: `library/styles/main.scss` → compiled by Koala to `main.css` and `main.min.css`
- The theme enqueues `main.min.css` (minified) in functions.php
- SCSS partials follow a naming convention with prefixes:
  - `_foundations-` — variables, fonts, tokens
  - `_layout-` — grid spacing, thumbnails
  - `_component-` — player skins (plyr, videojs), lightbox overrides
  - `_vendor-` — third-party CSS (magnific-popup) — treat like library/js, avoid heavy edits
  - `_region-` — shared page areas (header, footer, archive grid)
  - `_page-` — page-specific styles (single-content, single-gallery, table-index)

### Fonts
- Font files live in `library/fonts/`
- Only active font: `sporting-grotesque/` (Regular + Bold)
- Referenced via relative paths in SCSS: `url('../fonts/sporting-grotesque/...')`

## Custom Post Types
The theme uses several custom post types: `hyper`, `4k-lento`, `films`, `dusk`, `cityburns`, and `log`.

## Custom Taxonomies
The theme uses custom taxonomies: `log-branch`, `medium`, `from` (time/dating), and `places`.

## ACF (Advanced Custom Fields)
The theme uses ACF for custom fields. For example, `get_field('is_archived', $term)` is used on `log-branch` taxonomy terms to distinguish active vs. archived log branches.

When querying posts that use an ACF true/false field to hide from archives (e.g. `hide_post_from_main_page_archives_and_feed`), always use an OR meta_query with both `NOT EXISTS` and `!= '1'`. ACF true/false may store '0' when unchecked (row exists) or have no row at all — checking only one condition will miss posts.

## Header logic
The header in header.php has conditional logic for displaying the site name:
- Default / homepage: shows full "Space"
- Singular posts (except the "index" page): shows abbreviated "S"
- Archive pages and taxonomy pages: uses responsive `$Webpage_name` variable ("S" on mobile, "Space" on ≥sm breakpoint)
- The "index" page (slug: `index`) has its own header condition and is treated differently from other singular pages

When adding new post types or taxonomies, remember to add corresponding header title conditions in header.php and menu entries in the collapseMenu section.

## Deployment
The theme is deployed via GitHub: push to `main` triggers a webhook (`/hook-tiagsspace`) on the production server (Ubuntu 24.04 / nginx / PHP 8.4), which runs `git pull` in `/var/www/tiagsspace/wp-content/themes/tiagsspace` and flushes W3 Total Cache. The deploy script (`/var/www/webhooks/tiagsspace/deploy.php`) verifies the GitHub HMAC-SHA256 signature and only acts on `refs/heads/main`. See README.md § Deployment for full details. The production server is Linux (case-sensitive filesystem), while local development is macOS (case-insensitive).

**Important:** When renaming files or directories with only a case change (e.g. `Sporting-Grotesque` → `sporting-grotesque`), macOS won't detect it. You must use `git mv` with a two-step rename (via a temp name) so Git tracks the change:
```
git mv old-Name temp-name && git mv temp-name new-name
```
Otherwise the server will still have the old-cased directory and paths will 404.