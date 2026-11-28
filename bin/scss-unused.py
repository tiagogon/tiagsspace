#!/usr/bin/env python3
"""SCSS settings and helpers in the theme that change nothing.

    python3 -I bin/scss-unused.py

Lists, for library/styles/_*.scss and main.scss:
  1. variables the theme defines that nothing reads: no theme partial, no
     imported Bootstrap file, and no Bootstrap setting that is itself in use
  2. Bootstrap settings set to Bootstrap's own default (a no-op), unless the theme
     reads the name itself (its palette: $white, $dark)
  3. variables that only repeat another variable (one name per value; Bootstrap's
     own settings are exempt)
  4. mixins, functions, placeholders and keyframes that are never used

The rule (Tiago, Nov 2026): what changes nothing is removed, not kept "to be safe".
After removing something, let Koala compile and check main.css is byte-identical
(cmp against a copy taken before): a no-op leaves it unchanged.

Bootstrap files count as readers only when main.scss or _foundations-utilities.scss
imports them. All of Bootstrap's mixins and functions count (they are imported as a
library), which errs on the side of keeping a setting. Prints nothing under a heading
when it is clean.
"""
import glob
import os
import re

THEME = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
STYLES = os.path.join(THEME, 'library', 'styles')
BS = os.path.join(THEME, 'library', 'bootstrap', 'scss')


def strip(s):
    s = re.sub(r'/\*.*?\*/', '', s, flags=re.S)
    return re.sub(r'(?m)//.*$', '', s)


def read(p):
    with open(p) as f:
        return strip(f.read())


def bs_partial(name):
    d, b = os.path.split(name)
    for cand in (os.path.join(BS, d, '_' + b + '.scss'), os.path.join(BS, d, b + '.scss')):
        if os.path.exists(cand):
            return cand
    return None


def imported_bootstrap():
    files = set()
    for entry in ('main.scss', '_foundations-utilities.scss'):
        for imp in re.findall(r'@import\s+"\.\./bootstrap/scss/([^"]+)"', read(os.path.join(STYLES, entry))):
            p = bs_partial(imp)
            if p:
                files.add(p)
    files |= set(glob.glob(os.path.join(BS, 'mixins', '_*.scss')))
    files.discard(os.path.join(BS, '_variables.scss'))   # var-to-var reads are followed below
    return files


def norm(v):
    return ' '.join(v.replace('!default', '').split()).strip()


def main():
    theme_files = sorted(glob.glob(os.path.join(STYLES, '_*.scss'))) + [os.path.join(STYLES, 'main.scss')]
    theme = {p: read(p) for p in theme_files}
    all_theme = '\n'.join(theme.values())
    bs_readers = '\n'.join(read(p) for p in imported_bootstrap())

    # Bootstrap's settings: name -> default expression
    bs_vars = {}
    for m in re.finditer(r'(?ms)^\$([\w-]+)\s*:\s*(.+?)\s*!default\s*;', read(os.path.join(BS, '_variables.scss'))):
        bs_vars['$' + m.group(1)] = m.group(2)

    # The theme's settings: name -> (file, value)
    defined = {}
    for p, s in theme.items():
        for m in re.finditer(r'(?ms)^\s*(\$[\w-]+)\s*:\s*(.+?);', s):
            defined.setdefault(m.group(1), (p, m.group(2)))

    def reads(name, text):
        uses = len(re.findall(re.escape(name) + r'(?![\w-])', text))
        defs = len(re.findall(r'(?m)^\s*' + re.escape(name) + r'\s*:', text))
        return uses - defs

    memo = {}

    def effective(name, seen=()):
        if name in memo:
            return memo[name]
        if name in seen:
            return False
        ok = reads(name, all_theme) > 0 or reads(name, bs_readers) > 0
        if not ok:
            for other, expr in bs_vars.items():
                if other != name and other not in defined and re.search(re.escape(name) + r'(?![\w-])', expr):
                    if effective(other, seen + (name,)):
                        ok = True
                        break
        memo[name] = ok
        return ok

    rel = lambda p: os.path.relpath(p, THEME)

    print('Variables nothing reads:')
    for name, (p, _) in sorted(defined.items()):
        if not effective(name):
            print(f'  {name:30s} {rel(p)}')

    # A pure Bootstrap knob set to its default is a no-op. A name the theme reads itself
    # ($white) is part of the theme's palette and stays, even if Bootstrap has the same value.
    print("\nBootstrap settings equal to Bootstrap's default:")
    for name, (p, value) in sorted(defined.items()):
        if name in bs_vars and norm(value) == norm(bs_vars[name]) and reads(name, all_theme) == 0:
            print(f'  {name:30s} {norm(value)}  ({rel(p)})')

    # One name per value: a theme variable whose value is only another variable is a
    # second name for it. Bootstrap's own settings are exempt ($link-color: $dark sets
    # Bootstrap's link colour; it is not an alias).
    print('\nVariables that only repeat another variable:')
    for name, (p, value) in sorted(defined.items()):
        if name not in bs_vars and re.fullmatch(r'\$[\w-]+', norm(value)):
            print(f'  {name:30s} = {norm(value)}  ({rel(p)})')

    print('\nMixins, functions, placeholders, keyframes never used:')
    for p, s in theme.items():
        for m in re.findall(r'@mixin\s+([\w-]+)', s):
            if not re.search(r'@include\s+' + re.escape(m) + r'(?![\w-])', all_theme):
                print(f'  mixin {m}  ({rel(p)})')
        for f in re.findall(r'@function\s+([\w-]+)', s):
            if len(re.findall(re.escape(f) + r'\(', all_theme)) < 2:
                print(f'  function {f}  ({rel(p)})')
        for ph in re.findall(r'(?m)^\s*%([\w-]+)', s):
            if not re.search(r'@extend\s+%' + re.escape(ph) + r'(?![\w-])', all_theme):
                print(f'  placeholder %{ph}  ({rel(p)})')
        for k in re.findall(r'@keyframes\s+([\w-]+)', s):
            if len(re.findall(r'(?<![\w-])' + re.escape(k) + r'(?![\w-])', all_theme)) < 2:
                print(f'  keyframes {k}  ({rel(p)})')


if __name__ == '__main__':
    main()
