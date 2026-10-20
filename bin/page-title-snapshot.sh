#!/usr/bin/env bash
#
# Page-title snapshot: the regression guard of library/page-title/.
#
# Fetches a fixed list of pages from the local site, logged out, and writes
# what the page-title module renders on each of them (header line, thumbnail
# hover labels, entry title/date blocks, <h1> count) to one text file.
# Take one snapshot before a change and one after, then diff the two files:
# every line that differs is a change the module made, intended or not.
#
#   bin/page-title-snapshot.sh /path/to/out.txt
#   TIAGSSPACE_BASE=http://localhost:8888/tiagsspace bin/page-title-snapshot.sh out.txt
#
# Needs: curl, the MAMP php binary (no php on the PATH of this Mac), MAMP
# running, YARPP active (so singles have a related grid and its labels).
# Not captured: the editor-only variant of the page (Edit / Download in the
# entry footer); check that by hand, logged in.
#
# Intended diffs of the pure-refactor commit (everything else must match):
#   1. Log entries' hover label: the double space before the title goes.
#   2. A Page's body title: "Pages / " with its empty link goes.
#   3. Log branch links lose rel="category tag"; several branches read "A / B".
#   4. Tag archives gain the #over-text span (they render the grid).

set -euo pipefail

OUT="${1:?usage: $0 <output-file>}"
BASE="${TIAGSSPACE_BASE:-http://localhost:8888/tiagsspace}"
PHP="${TIAGSSPACE_PHP:-/Applications/MAMP/bin/php/php8.2.0/bin/php}"
HERE="$(cd "$(dirname "$0")" && pwd)"

# One line per page: a path relative to the site, with a comment of what it is for.
PATHS=(
  "/"                                            # home
  "/dusk/"                                       # series archive
  "/hyper/"
  "/cityburns/"
  "/4k-lento/"
  "/films/"                                      # archive word "Film"
  "/log/"                                        # listing with <h2> titles
  "/log-branch/blwww/"                           # log-branch listing
  "/in/algarve/"                                 # place
  "/medium/photography/analog/35-mm-film/"       # medium
  "/from/2007/"                                  # year
  "/tag/beach/"                                  # tag (grid, no #over-text today)
  "/index/"                                      # Index page (table)
  "/dusk/them/"                                  # single set
  "/hyper/b12/"
  "/cityburns/higluk/"
  "/4k-lento/untouched/"                         # mix, background image
  "/films/32-seconds-brush/"                     # film, no series crumb
  "/log/thunder/"                                # Log entry
  "/log/382121-6063n-72627-6683w/"               # long title with ' and " (texturized)
  "/hyper/dont-take-it/"                         # straight apostrophe in the title
  "/hyper/eternal-int-ext/"                      # ampersand in the title
  "/about/"                                      # Links page template (header hidden)
)

: > "$OUT"
for p in "${PATHS[@]}"; do
  url="${BASE}${p}"
  printf '==== %s\n' "$p" >> "$OUT"
  if ! curl -sS -L --max-time 60 "$url" | "$PHP" "$HERE/page-title-snapshot.php" >> "$OUT"; then
    printf 'ERROR fetching %s\n' "$url" >> "$OUT"
  fi
done
printf 'snapshot written to %s (%s lines)\n' "$OUT" "$(wc -l < "$OUT" | tr -d ' ')"
