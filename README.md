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
│   ├── plyr-player.php         # Plyr video player helper
│   ├── admin.php               # Dashboard cleanup
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
│       ├── plyr-adaptive-quality.js   # Plyr quality switcher
│       ├── simple-ajax-example.js     # Gallery AJAX (logged-in only)
│       ├── bootstrap.min.js           # Compiled Bootstrap JS
│       └── (third-party libraries — do not edit)
│           ├── jquery/        ├── masonry/
│           ├── modernizr/     ├── Magnific-Popup/
│           ├── picturefill/   ├── swiper/
│           ├── plyr/          ├── Sortable-master/
│           ├── intense-images/├── model-viewer/
│           └── infinite-scroll/
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

## Deployment

Push to the GitHub repo → a webhook on the production server (Ubuntu/nginx) automatically downloads the new version.

**Important:** The production server is Linux (case-sensitive filesystem), while local dev is macOS (case-insensitive). When renaming files with only a case change, use a two-step `git mv`:
```
git mv old-Name temp-name && git mv temp-name new-name
```

## Contact

[mail@tiags.space](mailto:mail@tiags.space)