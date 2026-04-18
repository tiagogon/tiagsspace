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
- `library/plyr-player.php` — Plyr video player helper (self-hosted + YouTube/Vimeo)
- `library/admin.php` — Dashboard widget cleanup (commented out by default)
- `library/custom-post-types.php` — CPT registrations (films, dusk, hyper, 4k-lento, log, cityburns), log-branch admin filter, admin menu order, CPTs in main loop/feed/archives
- `library/custom-taxonomies.php` — Taxonomy registrations (places, medium, log-branch, from), admin taxonomy filters, CPTs on tag archives, disable categories
- `library/template-functions.php` — Template helpers: taxonomy_list(), content_wrap(), background colors, number/stats helpers, attachment margins, media counts, [last_post_link] shortcode
- `library/seo-and-feed.php` — Feed links, Feedly support, feed title/content formatting, Yoast OpenGraph and Twitter image overrides
- `library/gallery-functions.php` — Gallery admin UI (hide/delete/reorder/resize), AJAX handlers for media order, attachment size, margin changes
- `library/query-filters.php` — Hide posts from index/archives/feeds (ACF field), exclude hidden adjacent posts, purge Table Index cache on post changes
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