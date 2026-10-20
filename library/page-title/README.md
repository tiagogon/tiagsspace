# Page title module

One model and one renderer for every line on the site that says where the visitor is:

- the fixed top-left header line ("Tiags' Space / Dusk Series / Them"),
- a listing's entry title after each gallery ("Blwww / Thunder" on the Log archive),
- the label the header shows while the mouse is over a thumbnail.

Before this module the same line was composed four ways in four files. Now the
words live in `config.php`, the lists are built in `crumbs.php`, the HTML is
written in `render.php`, and the three templates that print a title are one
call each.

## Files

| file | job |
|---|---|
| `page-title.php` | loader: requires the two below, registers the WP-CLI command |
| `config.php` | the words: which post types get a type crumb, short forms, taxonomy words, header overrides |
| `crumbs.php` | builders: `tiagsspace_crumb()`, `tiagsspace_site_crumb()`, `tiagsspace_entry_crumbs()`, `tiagsspace_page_crumbs()` |
| `render.php` | `tiagsspace_render_crumbs()`, `tiagsspace_render_crumb()`: the only place HTML is written |

Callers: `template-parts/header-title.php`, `template-parts/entry-body.php`,
`template-parts/archive-grid.php`. Guard: `bin/page-title-snapshot.sh`.

## The contract

A **crumb** is a plain array with exactly four keys, made only through `tiagsspace_crumb()`:

```php
array( 'label' => 'Dusk Series', 'short' => 'Dusk', 'url' => 'https://…/dusk/', 'heading' => false )
```

- `label` — the words, plain text, unescaped (required)
- `short` — the form shown below the responsive breakpoint, or `''`
- `url` — where the crumb links, or `''`
- `heading` — `true` on the one crumb that is the page's heading (the work's title)

A **page title** is a list of crumbs read left to right. Builders return lists;
the renderer turns a list into one line of HTML.

## Builders (`crumbs.php`)

Data in, data out. No HTML. No `global $post`, no template conditionals: give
them a post id or a `WP_Query`, and the same input always gives the same list.

### `tiagsspace_entry_crumbs( $post_id, $args = [] )`

The crumbs that name one work: its series (or the Log), its Log branches, its title.

| input (local site) | result |
|---|---|
| `35362` (a Dusk set) | `Dusk Series → /dusk/` (short "Dusk"), `Them` * |
| `36413` (a Log entry) | `Log → /log/`, `Blwww → /log-branch/blwww/`, `Thunder` * |
| `30179` (a film) | `32 Sec Brush` * |
| `33989` (a page) | `About` * |
| `36413` with `[ 'omit' => [ 'type' ] ]` | `Blwww → …`, `Thunder` * |
| `36413` with `[ 'omit' => [ 'type', 'branch' ] ]` | `Thunder` * |

(* the heading crumb.) `link_title => true` links the title crumb to the post (listings do that).
A listing omits what its page header already says: the Log archive omits the type,
a branch listing the type and the branch.

### `tiagsspace_page_crumbs( $query = null )`

The crumbs of the page a query shows (the main query by default).

| page | result |
|---|---|
| home, search, 404 | `Tiags' Space` |
| the Index page | `Tiags' Space`, `Index` |
| a series archive | `Tiags' Space`, `Dusk Series` (short "Dusk"); films: `Film` (see `archive_word`) |
| the Log archive | `Tiags' Space`, `Log` |
| a Log branch | `Tiags' Space`, `Log → /log/`, `Blwww` |
| a taxonomy term | `Tiags' Space`, `Place`, `Algarve` |
| a single work | `Tiags' Space` + `tiagsspace_entry_crumbs()` |

The site crumb always links home. On every line but a single work the whole line
is the page's heading; on a single work only the title crumb is. The filter
`tiagsspace_page_crumbs( $crumbs, $query )` is the extension point for anything
`config.php` cannot say.

## Renderer (`render.php`)

`tiagsspace_render_crumbs( $crumbs, $args )` returns one line of HTML. Everything
is escaped here; print the result as it is.

| option | default | meaning |
|---|---|---|
| `heading` | `'none'` | `'h1'`, `'h2'` or `'none'`. With a heading crumb in the list, only that crumb becomes the heading element, inline inside a `<div>`; without one, the whole line is the heading element. `'none'` wraps in a `<p>`. |
| `links` | `true` | `false`: no anchors (the hover label). |
| `responsive` | `'sm'` | Bootstrap breakpoint from which the long form shows; `false` prints the long form only. |
| `before` | `''` | Text before everything (`' > Space'`, the old label root on singles and taxonomy pages). |
| `leading_sep` | `false` | Start with the separator (a label is appended after the header text). |
| `separator` | `' / '` | Between crumbs. The slash is written nowhere else. |
| `heading_attrs` | `[]` | Attributes on the heading element, e.g. `[ 'itemprop' => 'headline' ]`. |
| `over_text` | `false` | Emit `<span id="over-text" class="d-none d-sm-inline"></span>` inside the wrapper, before its end: the hover label's target. |
| `class` | `'site-title'` | Wrapper class. The prefix span is `{class}-prefix`, the heading `{class}-heading`, the group `{class}-entry`. |
| `group_from` | `null` | Wrap the crumbs from this index to the end in `<span class="{class}-entry">`, so a script can swap them as one. |
| `wrap` | `true` | `false` returns the crumbs only, no wrapper element (labels). |

Two responsive forms, both from the theme's markup: when the short form is the
start of the label ("Dusk" / "Dusk Series") only the rest is hidden on small
screens (`Dusk<span class="d-none d-sm-inline"> Series</span>`); otherwise ("S" /
"Tiags' Space") the two forms are swapped (`<span class="d-inline d-sm-none">S</span><span class="d-none d-sm-inline">Tiags' Space</span>`).

The prefix and group spans only appear around a heading element; a plain line
(a label, a `<p>`) is words and separators alone.

### Examples (taken from the snapshot of the local site)

Series archive header:

```php
tiagsspace_render_crumbs( tiagsspace_page_crumbs(), [ 'heading' => 'h1', 'over_text' => true ] );
```
```html
<h1 class="site-title"><a href="https://…"><span class="d-inline d-sm-none">S</span><span class="d-none d-sm-inline">Tiags' Space</span></a> / Dusk<span class="d-none d-sm-inline"> Series</span><span id="over-text" class="d-none d-sm-inline"></span></h1>
```

Log listing entry:

```php
tiagsspace_render_crumbs( tiagsspace_entry_crumbs( 36413, [ 'omit' => [ 'type' ], 'link_title' => true ] ),
    [ 'heading' => 'h2', 'responsive' => false, 'class' => 'single-title', 'heading_attrs' => [ 'itemprop' => 'headline' ] ] );
```
```html
<div class="single-title"><span class="single-title-prefix"><a href="https://…/log-branch/blwww/">Blwww</a> / </span><h2 class="single-title-heading" itemprop="headline"><a href="https://…/log/thunder/">Thunder</a></h2></div>
```

Hover label on the home page (goes into `data-label` with `esc_attr()`):

```php
tiagsspace_render_crumbs( tiagsspace_entry_crumbs( 35362 ), [ 'links' => false, 'responsive' => 'md', 'leading_sep' => true, 'wrap' => false ] );
```
```
 / Dusk<span class="d-none d-md-inline"> Series</span> / Them
```

## Rules this module keeps

- One function, one job. Builders return data; `render.php` is the only file that writes HTML.
- No string literal of a series or taxonomy name outside `config.php` and `tiagsspace_series_name()`
  (`library/template-functions.php`), which stays the single source of the long spelling.
- Adding a post type or a taxonomy is one line in `config.php`. Nothing in the templates changes.
- Theme-aware on purpose: it knows this theme's post types, so it lives in `library/`, not in a
  portable folder like `library/video/`.
- Two oddities are intentional, do not "fix" them: the Links page template keeps its only `<h1>`
  inside a header that is `display: none` (as before the module); the `from` taxonomy reads
  "Dating" in the header and "Time" in the menu (Tiago's wording).

## Checking a change

`bin/page-title-snapshot.sh <file>` writes what the module renders on a fixed list of
local pages (header line, hover labels, entry titles, `<h1>` count). Take one snapshot
before and one after, and `diff` them: every differing line is a change the module made.
The script's header lists the differences a change is allowed to make.

`wp tiagsspace page-title <id>…` prints the crumbs and the rendered header, listing
and label lines of a post, to compare with the tables above. (There is no WP-CLI on
the local Mac at the time of writing; the snapshot is the check that runs here.)
