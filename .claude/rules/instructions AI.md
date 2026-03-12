---
description: Describe when these instructions should be loaded
paths:
. - ""
---
Never edit the bootstrap.css file. All style editing needs to be done via .scss files.

Never edit files inside library/js/ subdirectories (e.g. intense-images, plyr, masonry, etc.). These are third-party libraries that may be updated from their upstream repositories — any custom edits would be lost on update. Instead, apply fixes or overrides via custom SCSS or separate wrapper scripts.

never check how the SCSS is compiled. i will always ensure i have koala running on the backgroun when working with you

## Theme structure

This is a custom WordPress theme called "tiagsspace" (Tiags' Space). It uses a custom 48-column Bootstrap grid (not the default 12-column).

## Custom Post Types
The theme uses several custom post types: `hyper`, `4k-lento`, `films`, `dusk`, `cityburns`, and `log`.

## Custom Taxonomies
The theme uses custom taxonomies: `log-branch`, `medium`, `from` (time/dating), and `places`.

## Header logic
The header in header.php has conditional logic for displaying the site name:
- Default / homepage: shows full "Space"
- Singular posts (except the "index" page): shows abbreviated "S"
- Archive pages and taxonomy pages: uses responsive `$Webpage_name` variable ("S" on mobile, "Space" on ≥sm breakpoint)
- The "index" page (slug: `index`) has its own header condition and is treated differently from other singular pages

When adding new post types or taxonomies, remember to add corresponding header title conditions in header.php and menu entries in the collapseMenu section.

## ACF (Advanced Custom Fields)
The theme uses ACF for custom fields. For example, `get_field('is_archived', $term)` is used on `log-branch` taxonomy terms to distinguish active vs. archived log branches.
