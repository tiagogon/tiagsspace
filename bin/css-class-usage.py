#!/usr/bin/env python3
"""Which Bootstrap-generated classes does anything on this site reference?

Run before pruning Bootstrap output (library/styles/_foundations-bootstrap-grid.scss,
_foundations-utilities.scss) and whenever a template, plugin or post might have
started using a new utility:

    python3 -I bin/css-class-usage.py [--css path/to/main.css] [--db-dump file]

It collects every class Bootstrap 4 generates (grid, offsets, order, utilities)
from the compiled CSS, then looks for each one as a whole word in:
  - the theme's own PHP and JS (vendored libraries left out),
  - the plugins that print on the front end (FRONT_END_PLUGINS below),
  - optionally a text dump of the database (post content, menu item classes,
    widgets, theme mods); make one with --dump-local-db, or on production with
    the query printed by --print-query.

A word found anywhere counts as used. False positives (a plain word such as
"visible") only keep a class; they never remove one.

The classes the theme BUILDS from variables are not found by a word search;
they are listed in DYNAMIC below and must be kept by hand. When a template starts
building a new family (another breakpoint, an offset from a variable), add it.
"""
import argparse
import os
import re
import subprocess
import sys

THEME = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PLUGINS = os.path.normpath(os.path.join(THEME, '..', '..', 'plugins'))

# Plugins whose markup can reach a visitor's page.
FRONT_END_PLUGINS = [
    'activitypub', 'seriously-simple-podcasting', 'visual-archive-search',
    'yet-another-related-posts-plugin', 'video-embed-thumbnail-generator',
    'public-post-preview',
]

# Vendored code inside the theme: never a source of the theme's own classes.
VENDOR = re.compile(
    r'^library/(bootstrap|players)/|^library/js/(plyr|photoswipe|hls|masonry|bootstrap|intense|'
    r'infinite-scroll\.pkgd|jquery|sortable)|images/graphics|^tumblr/')

# Class families built in PHP from variables (a word search cannot see them).
DYNAMIC = [
    (r'^col-(sm-|md-|lg-)?([1-9]|[1-3][0-9]|4[0-8])$',
     'template-parts/gallery-item.php, entry-gallery.php (sizer), archive-grid.php'),
    (r'^d-(sm|md|lg|xl)-(inline|none)$',
     'library/page-title/render.php: short / long crumb forms, "responsive" breakpoint'),
]

BOOTSTRAP_CLASS = re.compile(
    r'\.((?:col|offset|order)(?:-(?:sm|md|lg|xl))?(?:-\d+|-auto|-first|-last)?'
    r'|(?:m|p)(?:t|b|l|r|x|y)?-(?:sm-|md-|lg-|xl-)?(?:n?\d|auto)'
    r'|(?:d|flex|justify-content|align-items|align-content|align-self|float|text|font|bg|border'
    r'|rounded|shadow|position|overflow|embed-responsive|w|h|mw|mh|user-select)-[\w-]+'
    r'|fixed-top|fixed-bottom|sticky-top|sr-only(?:-focusable)?|clearfix|visible|invisible'
    r'|text-hide|stretched-link|embed-responsive|border|rounded|shadow|flex-fill|row|no-gutters'
    r'|container(?:-fluid)?|col)(?=[\s,.:{>\[)~+])')

QUERY = (
    "SELECT post_content FROM wp_posts WHERE post_type NOT IN ('revision','attachment') "
    "AND post_content LIKE '%class%'; "
    "SELECT meta_value FROM wp_postmeta WHERE meta_key='_menu_item_classes' OR meta_value LIKE '%class=%'; "
    "SELECT option_value FROM wp_options WHERE option_name LIKE 'widget_%' OR option_name LIKE 'theme_mods_%'"
)

WORD = re.compile(r'[A-Za-z0-9_-]+')


def words_of_files(paths):
    words = {}
    for p in paths:
        try:
            with open(p, errors='ignore') as f:
                for w in WORD.findall(f.read()):
                    words.setdefault(w, p)
        except OSError:
            pass
    return words


def theme_files():
    out = subprocess.run(['git', '-C', THEME, 'ls-files', '*.php', '*.js'],
                         capture_output=True, text=True, check=True).stdout.split()
    return [os.path.join(THEME, p) for p in out if not VENDOR.search(p)]


def plugin_files():
    files = []
    for name in FRONT_END_PLUGINS:
        for root, dirs, fs in os.walk(os.path.join(PLUGINS, name)):
            dirs[:] = [d for d in dirs if d not in ('node_modules', 'tests', 'test')]
            files += [os.path.join(root, f) for f in fs
                      if f.endswith(('.php', '.js', '.html')) and not f.endswith('.min.js')]
    return files


def dump_local_db(path):
    wp = os.path.normpath(os.path.join(THEME, '..', '..', '..', 'wp-config.php'))
    cfg = open(wp).read()
    get = lambda k: re.search(r"define\(\s*'%s',\s*'([^']*)'" % k, cfg).group(1)
    env = dict(os.environ, MYSQL_PWD=get('DB_PASSWORD'))
    with open(path, 'w') as f:
        subprocess.run(['/Applications/MAMP/Library/bin/mysql', '-S', '/Applications/MAMP/tmp/mysql/mysql.sock',
                        '-u', get('DB_USER'), get('DB_NAME'), '-N', '-r', '-e', QUERY],
                       stdout=f, env=env, check=True)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--css', default=os.path.join(THEME, 'library/styles/main.css'))
    ap.add_argument('--db-dump', help='text file with the output of --print-query')
    ap.add_argument('--dump-local-db', metavar='FILE', help='write the local MAMP DB dump to FILE first')
    ap.add_argument('--print-query', action='store_true')
    a = ap.parse_args()

    if a.print_query:
        print(QUERY)
        return
    if a.dump_local_db:
        dump_local_db(a.dump_local_db)
        a.db_dump = a.db_dump or a.dump_local_db

    classes = sorted(set(BOOTSTRAP_CLASS.findall(open(a.css).read())))
    sources = [('theme', words_of_files(theme_files())), ('plugins', words_of_files(plugin_files()))]
    if a.db_dump:
        sources.append(('db', words_of_files([a.db_dump])))

    print(f'{len(classes)} Bootstrap classes in {os.path.relpath(a.css, THEME)}\n')
    print('Used (keep):')
    for c in classes:
        dyn = next((why for pat, why in DYNAMIC if re.match(pat, c)), None)
        hits = [(name, w[c]) for name, w in sources if c in w]
        if hits and not dyn:
            where = ', '.join(f'{n}: {os.path.relpath(p, THEME) if n != "db" else "dump"}' for n, p in hits)
            print(f'  {c:28s} {where}')
    print('\nBuilt from variables (keep, see DYNAMIC):')
    for pat, why in DYNAMIC:
        print(f'  {pat}  ({why})')


if __name__ == '__main__':
    sys.exit(main())
