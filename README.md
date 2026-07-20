# Tiags' Space — WordPress Theme

Custom WordPress theme for [tiags.space](https://tiags.space).
Based on Eddie Machado's Bones starter theme, heavily customised with a **48-column Bootstrap grid**, custom post types, and ACF-driven galleries.

## Requirements

- WordPress 5.3+
- PHP 7.4+
- [Advanced Custom Fields PRO](https://www.advancedcustomfields.com/pro/) (repeater fields, field groups registered in `library/acf-fields.php`)
- [Koala](http://koala-app.com/) (SCSS compiler, runs locally in the background)

## Custom Post Types

| Slug | Label | Notes |
|---|---|---|
| `hyper` | Hyper | |
| `4k-lento` | 4K Lento | |
| `dusk` | Dusk | |
| `films` | Films | |
| `log` | Log | Uses `log-branch` taxonomy |
| `cityburns` | City Series | Archived / closed |

## Custom Taxonomies

| Slug | Label | Type | Notes |
|---|---|---|---|
| `places` | Places | Flat | Rewrite slug: `in` |
| `medium` | Medium | Hierarchical | |
| `log-branch` | Log Branch | Flat | Active vs archived (ACF `is_archived`) |
| `from` | Year Archive | Flat | Rewrite slug: `from` |

Categories are disabled.

## Theme Structure

```
├── header.php                  # Head + top bar skeleton
├── footer.php
├── single.php                  # Single post/CPT template
├── archive.php                 # Default archive
├── archive-log.php             # Log archive
├── taxonomy-log-branch.php     # Log branch taxonomy archive
├── page.php / page-links.php / page-table-index.php
├── search.php / searchform.php
├── attachment.php
├── functions.php               # Theme setup, enqueues, helpers
│
├── library/
│   ├── acf-fields.php          # All ACF field groups (version-controlled)
│   ├── custom-post-types.php   # CPT registrations
│   ├── custom-taxonomies.php   # Taxonomy registrations
│   ├── template-functions.php  # Template helpers (taxonomy_list, content_wrap, etc.)
│   ├── helpers.php             # WP cleanup, pagination, excerpt formatting
│   ├── gallery-functions.php   # Gallery admin UI, AJAX handlers (reorder/resize/margin)
│   ├── query-filters.php       # Hide posts from archives, adjacent post exclusions
│   ├── seo-and-feed.php        # Feed formatting, Yoast overrides
│   ├── admin.php               # Dashboard cleanup
│   │
│   ├── video/                  # Portable video module (Plyr + HLS) — see § Video
│   │   ├── player.php          # render_video_player() dispatcher + helpers
│   │   ├── player-mp4.php      # progressive MP4 quality ladder
│   │   ├── player-hls.php      # HLS (.m3u8) renderer
│   │   ├── hls-import.php      # Media Library .hlspack.zip → HLS attachment
│   │   └── enqueues.php        # conditional asset registration
│   │
│   ├── styles/                 # SCSS source files
│   │   ├── main.scss           # Entry point → compiled to main.css + main.min.css
│   │   ├── _foundations-*      # Variables, fonts, tokens
│   │   ├── _layout-*           # Grid spacing, thumbnails
│   │   ├── _component-*        # Plyr, VideoJS, lightbox
│   │   ├── _vendor-*           # Third-party CSS (Magnific Popup)
│   │   ├── _region-*           # Header/nav, archive grid, footer
│   │   └── _page-*             # Single content, gallery, table index
│   │
│   ├── bootstrap/scss/         # Bootstrap source (selective imports only)
│   ├── fonts/sporting-grotesque/  # Sporting Grotesque Regular + Bold
│   │
│   └── js/
│       ├── header-helpers.js          # showText/showYear hover helpers
│       ├── plyr-adaptive-quality.js   # MP4 films only: hand-rolled quality switch
│       ├── video-player/init-hls.js   # HLS: native (Safari) / hls.js + Plyr menu
│       ├── simple-ajax-example.js     # Gallery AJAX (logged-in only)
│       ├── bootstrap.min.js           # Compiled Bootstrap JS
│       └── (third-party libraries — do not edit)
│           ├── jquery/        ├── masonry/
│           ├── modernizr/     ├── Magnific-Popup/
│           ├── picturefill/   ├── swiper/
│           ├── plyr/          ├── hls/         # vendored hls.js
│           ├── intense-images/├── Sortable-master/
│           └── model-viewer/  └── infinite-scroll/
│
└── template-parts/
    ├── header-title.php              # Conditional page title (breadcrumb-style)
    ├── header-menu.php               # Collapse navigation menu
    ├── header-lower.php              # Fixed bottom bar (hover year on archives)
    ├── archive-grid.php              # Masonry grid layout
    ├── archive-table.php             # Table layout
    ├── entry-body.php                # Post content
    ├── entry-gallery.php             # Vertical gallery
    ├── entry-gallery-horizontal.php  # Horizontal gallery (Swiper)
    └── entry-video-player.php        # Video player embed
```

## Development

### SCSS

SCSS source files live in `library/styles/`. The entry point `main.scss` is compiled by **Koala** to `main.css` and `main.min.css` (the theme enqueues the minified version). Only selective Bootstrap modules are imported (grid, reboot, utilities — no navbar/modal/etc.).

**Do not edit** `bootstrap.css`, `main.css`, or `main.min.css` directly.

### JavaScript

Third-party libraries in `library/js/` subdirectories should **not be edited** — apply fixes via custom scripts or SCSS overrides instead.

### ACF Fields

All field groups are registered via PHP in `library/acf-fields.php` — no database import needed. ACF PRO is required for repeater fields used in gallery and video configurations.

### Grid

The theme uses a custom **48-column** Bootstrap grid (not the default 12-column).

## Video

Films (`films` post type) play through the portable module in `library/video/`. The
attachment chosen in the **Self Host Film** field decides the tech automatically:

- an **`.m3u8`** attachment → **HLS** (adaptive; native on Safari/iOS, hls.js elsewhere)
- any other video file → the **progressive MP4 ladder** (Plyr + `plyr-adaptive-quality.js`)

Videopack is still used for archive thumbnail loops and gallery videos — only films use
this module.

### MP4 workflow (simple, unchanged)

1. Upload the video (MP4 · H.264 — `.mov` won't play in every browser) in the Media Library.
2. Optionally let Videopack encode smaller versions (they attach as children → become
   quality rungs).
3. Set it as **Self Host Film** on the film. Done.

### HLS workflow (adaptive, best for 4K / mobile / festival jurors)

1. Export the master from DaVinci (MP4 preferred: H.264 High, 2160p24 ~14–18 Mbps, AAC).
   `.mov` masters also work as script input.
2. On your Mac, build the bundle:
   ```bash
   bin/hls-package.sh master.mp4 my-film-slug
   # → my-film-slug.hlspack.zip   (add --thumb / --fallback if you want those extras)
   ```
   Requires `ffmpeg` + `ffprobe`. Ladder rungs are 16:9 boxes the picture is fitted
   into with its aspect ratio preserved (never padded), and a rung is only used if it
   needs no upscaling — so a sub-4K master simply produces a shorter ladder, and a
   2.39:1 4K master tops out at 3840x1608 instead of a letterboxed 1920x1080.
3. **Drag `my-film-slug.hlspack.zip` into the Media Library** like any file. The theme
   unpacks it into `uploads/hls/<attachment-id>/` and turns it into an `.m3u8` attachment.
4. Select that attachment in **Self Host Film**. The player detects `.m3u8` and streams HLS.

Notes:
- No terminal upload — the zip goes through the normal Media Library uploader.
- Import problems are logged to `wp-content/uploads/hls/import.log` and shown as an admin
  notice; the zip is left intact on failure.
- Deleting the attachment removes its `uploads/hls/<id>/` folder.
- Add `?video_debug=1` to a film URL to log the playback engine, quality switches and
  bandwidth to the browser console.

**Server (one-time on prod):** ensure nginx serves `.m3u8` as
`application/vnd.apple.mpegurl` and `.m4s` as `video/iso.segment`, and that the long-cache
static rule covers `uploads/hls/` (VOD playlists are immutable).

## Deployment

Push to the `main` branch on GitHub → a webhook on the production server (Ubuntu 24.04 / nginx / PHP 8.4) automatically pulls the new version and flushes the page cache.

### Deployment flow

```
git push origin main
      │
      ▼
GitHub webhook (push event)
      │
      ▼
https://tiags.space/hook-tiagsspace
      │
      ▼
/var/www/webhooks/tiagsspace/deploy.php
      │
      ├─ Verify HMAC-SHA256 signature
      ├─ Check ref === refs/heads/main
      ├─ git pull origin main
      └─ Flush W3 Total Cache (if active)
      │
      ▼
Theme updated on tiags.space
```

### Server components

| Component | Details |
|---|---|
| Webhook script | `/var/www/webhooks/tiagsspace/deploy.php` |
| Nginx location | `/hook-tiagsspace` → PHP-FPM (`php8.4-fpm.sock`) via `fastcgi_params` |
| Theme directory | `/var/www/tiagsspace/wp-content/themes/tiagsspace` (git repo, owned by `www-data`) |
| Git remote | `git@github.com:tiagogon/tiagsspace.git` (SSH) |
| Cache | W3 Total Cache flushed via WP-CLI after pull (skipped if plugin inactive) |

### GitHub webhook settings

- **Payload URL:** `https://tiags.space/hook-tiagsspace`
- **Content type:** `application/json`
- **Secret:** HMAC secret (stored in `deploy.php`, not in the repo)
- **Events:** Just the push event

### What the deploy script does

1. Reads the raw POST payload and the `X-Hub-Signature-256` header
2. Computes `sha256=` HMAC of the payload using the shared secret
3. Compares signatures with `hash_equals()` — rejects with 403 on mismatch
4. Decodes JSON and checks `ref === refs/heads/main` — ignores other branches
5. Runs `git pull origin main` in the theme directory
6. Checks if W3 Total Cache is active via WP-CLI; if so, flushes all caches

### Manual deployment

```bash
cd /var/www/tiagsspace/wp-content/themes/tiagsspace
git pull origin main
```

### Debugging

```bash
# Nginx access log
sudo tail -f /var/log/nginx/access.log

# PHP-FPM errors
sudo tail -f /var/log/php*-fpm.log

# Git status in theme directory
cd /var/www/tiagsspace/wp-content/themes/tiagsspace && git status
```

### Plugin webhooks

The same pattern is used for plugin auto-deployment. Each plugin has its own webhook endpoint and deploy script, pulling to both `/var/www/tiagsspace/` and `/var/www/lentoworld/`:

| Plugin | Webhook URL | Deploy script |
|---|---|---|
| instagram-posting-api | `https://tiags.space/hook-instagram-posting-api` | `deploy-instagram-posting-api.php` |
| tumblr-posting-api | `https://tiags.space/hook-tumblr-posting-api` | `deploy-tumblr-posting-api.php` |

All deploy scripts live in `/var/www/webhooks/tiagsspace/` and share the same HMAC secret.

### Case-sensitive filesystem

The production server is **Linux (case-sensitive)**, local dev is **macOS (case-insensitive)**. When renaming files with only a case change, macOS won't detect it. Use a two-step `git mv`:
```bash
git mv old-Name temp-name && git mv temp-name new-name
```

### Security notes

- HMAC-SHA256 signature verification prevents unauthorized deployments
- Only pushes to `main` trigger a pull; other branches are ignored
- The webhook secret is stored only in `deploy.php` on the server, never in the repository
- The deploy script runs as `www-data` (no elevated privileges for git operations)

## Contact

[mail@tiags.space](mailto:mail@tiags.space)