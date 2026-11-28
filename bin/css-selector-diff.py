#!/usr/bin/env python3
"""Selectors present in one compiled CSS file and missing from another.

    python3 -I bin/css-selector-diff.py old.css new.css [--added]

Use it after pruning or reorganising the SCSS: copy main.css aside before the
change, let Koala compile, then diff. Every selector listed as removed must be
one you meant to remove; any theme selector in the list means the change is
wrong. Selectors are compared per media/supports context, so a rule moved into
or out of a media query also shows.
"""
import re
import sys


def selectors(path):
    css = re.sub(r'/\*.*?\*/', '', open(path).read(), flags=re.S)
    out = set()
    stack = []
    buf = ''
    for ch in css:
        if ch == '{':
            head = ' '.join(buf.split())
            buf = ''
            if head.startswith('@'):
                stack.append(head)
            else:
                ctx = ' | '.join(h for h in stack if not h.startswith('@keyframes'))
                if not any(h.startswith(('@keyframes', '@font-face')) for h in stack) and not head.startswith('@'):
                    for s in head.split(','):
                        out.add((ctx, ' '.join(s.split())))
                stack.append(None)
        elif ch == '}':
            if stack:
                stack.pop()
            buf = ''
        elif ch == ';':
            buf = ''  # @charset / @import, or a declaration (discarded at '}' anyway)
        else:
            buf += ch
    return out


def main():
    args = [a for a in sys.argv[1:] if not a.startswith('--')]
    if len(args) != 2:
        print(__doc__)
        return 2
    old, new = selectors(args[0]), selectors(args[1])
    removed = sorted(old - new)
    print(f'{len(old)} selectors before, {len(new)} after, {len(removed)} removed')
    for ctx, s in removed:
        print(f'- {s}' + (f'    [{ctx}]' if ctx else ''))
    if '--added' in sys.argv:
        added = sorted(new - old)
        print(f'\n{len(added)} added')
        for ctx, s in added:
            print(f'+ {s}' + (f'    [{ctx}]' if ctx else ''))


if __name__ == '__main__':
    sys.exit(main())
