#!/usr/bin/osascript -l JavaScript
//
// favicon-from-emoji.js — build the theme's favicon set from one Apple emoji.
// Plain macOS JXA + AppKit: no compiler, no Pillow, no ImageMagick.
//
//   osascript -l JavaScript bin/favicon-from-emoji.js "💿" favicon.ico
//   osascript -l JavaScript bin/favicon-from-emoji.js "🕳️" favicon.ico/adminarea --touch-bg '#ffffff'
//
// Renders the glyph with Apple Color Emoji, crops to its alpha bounding box and
// scales it to FILL each canvas (zero margin) so a round emoji survives Google's
// circular favicon crop. Writes the same file set the theme has always shipped:
// favicon-*.png, android-icon-*.png, apple-icon-*.png, ms-icon-*.png, favicon.ico
// (PNG-in-ICO, 16/32/48), manifest.json and browserconfig.xml.
//
// apple-icon-* get an opaque background (--touch-bg, default #ffffff) because iOS
// paints transparent pixels black on the home screen. Everything else keeps alpha.
//
// Apple Color Emoji is a bitmap font (largest strike 160px): anything above that
// is an upscale, as it always was. Paths are relative to the cwd. Mac only.

ObjC.import('Foundation');
ObjC.import('Cocoa');

function run(argv) {
    const args = argv.slice();
    let touchBg = '#ffffff';
    let appName = "Tiags' Space";
    const take = (flag) => {
        const i = args.indexOf(flag);
        if (i < 0 || i + 1 >= args.length) return null;
        return args.splice(i, 2)[1];
    };
    touchBg = take('--touch-bg') || touchBg;
    appName = take('--name') || appName;
    if (args.length !== 2) {
        throw new Error("usage: favicon-from-emoji.js <emoji> <out-dir> [--touch-bg '#rrggbb'] [--name 'App name']");
    }
    const emoji = args[0];
    const cwd = $.NSFileManager.defaultManager.currentDirectoryPath.js;
    const outDir = args[1].startsWith('/') ? args[1] : cwd + '/' + args[1];
    $.NSFileManager.defaultManager.createDirectoryAtPathWithIntermediateDirectoriesAttributesError(outDir, true, $(), null);

    const bg = parseHex(touchBg);

    // ---------- render the glyph big, on transparent ----------
    const MASTER = 1024;
    const master = bitmap(MASTER);
    withContext(master, false, () => {
        const font = $.NSFont.fontWithNameSize('Apple Color Emoji', 720);
        const attrs = $.NSDictionary.dictionaryWithObjectForKey(font, $.NSFontAttributeName);
        // NSString's AppKit drawing additions; NSAttributedString init is not bridged in JXA
        const str = $(emoji);
        const size = str.sizeWithAttributes(attrs);
        str.drawAtPointWithAttributes({ x: (MASTER - size.width) / 2, y: (MASTER - size.height) / 2 }, attrs);
    });

    // ---------- alpha bounding box (bitmap rows are top-origin) ----------
    const box = alphaBounds(master, MASTER);
    if (!box) throw new Error('glyph rendered empty — is that an emoji?');
    console.log('glyph bbox ' + box.w + 'x' + box.h + ' px');

    const masterImage = $.NSImage.alloc.initWithSize({ width: MASTER, height: MASTER });
    masterImage.addRepresentation(master);
    // NSImage source rect is bottom-origin
    const src = { origin: { x: box.x, y: MASTER - (box.y + box.h) }, size: { width: box.w, height: box.h } };

    const png = (size, background) => {
        const rep = bitmap(size);
        withContext(rep, false, (ctx) => {
            if (background) {
                background.setFill;
                $.NSBezierPath.fillRect({ origin: { x: 0, y: 0 }, size: { width: size, height: size } });
            }
            const scale = Math.min(size / box.w, size / box.h);
            const w = box.w * scale, h = box.h * scale;
            const dst = { origin: { x: (size - w) / 2, y: (size - h) / 2 }, size: { width: w, height: h } };
            ctx.setImageInterpolation($.NSImageInterpolationHigh);
            masterImage.drawInRectFromRectOperationFraction(dst, src, $.NSCompositingOperationSourceOver, 1.0);
        });
        return rep.representationUsingTypeProperties($.NSBitmapImageFileTypePNG, $());
    };
    const write = (name, data) => {
        if (!data.writeToFileAtomically(outDir + '/' + name, true)) throw new Error('cannot write ' + name);
        console.log('  ' + name);
    };
    const writeText = (name, text) => {
        write(name, $(text).dataUsingEncoding($.NSUTF8StringEncoding));
    };

    // ---------- the file set ----------
    [16, 32, 96].forEach(s => write('favicon-' + s + 'x' + s + '.png', png(s, null)));
    [36, 48, 72, 96, 144, 192].forEach(s => write('android-icon-' + s + 'x' + s + '.png', png(s, null)));
    [57, 60, 72, 76, 114, 120, 144, 152, 180].forEach(s => write('apple-icon-' + s + 'x' + s + '.png', png(s, bg)));
    const touch192 = png(192, bg);
    write('apple-icon.png', touch192);
    write('apple-icon-precomposed.png', touch192);
    [70, 144, 150, 310].forEach(s => write('ms-icon-' + s + 'x' + s + '.png', png(s, null)));

    // favicon.ico — PNG-in-ICO (Windows Vista+ and every current browser)
    const icoSizes = [16, 32, 48];
    const icoImages = icoSizes.map(s => png(s, null));
    const header = [];
    const le16 = (v) => { header.push(v & 0xff, (v >> 8) & 0xff); };
    const le32 = (v) => { le16(v & 0xffff); le16((v >>> 16) & 0xffff); };
    le16(0); le16(1); le16(icoSizes.length);
    let offset = 6 + 16 * icoSizes.length;
    icoSizes.forEach((s, i) => {
        const len = icoImages[i].length;
        header.push(s === 256 ? 0 : s, s === 256 ? 0 : s, 0, 0);
        le16(1); le16(32); le32(len); le32(offset);
        offset += len;
    });
    const ico = $.NSMutableData.alloc.initWithData(
        $.NSData.alloc.initWithBase64EncodedStringOptions(base64(header), 0));
    icoImages.forEach(d => ico.appendData(d));
    write('favicon.ico', ico);

    // manifest.json — src relative to the manifest itself (the old absolute "/android-…" 404'd)
    const densities = [[36, '0.75'], [48, '1.0'], [72, '1.5'], [96, '2.0'], [144, '3.0'], [192, '4.0']];
    const manifest = {
        name: appName,
        icons: densities.map(([s, d]) => ({ src: 'android-icon-' + s + 'x' + s + '.png', sizes: s + 'x' + s, type: 'image/png', density: d }))
    };
    writeText('manifest.json', JSON.stringify(manifest, null, 1) + '\n');

    writeText('browserconfig.xml',
        '<?xml version="1.0" encoding="utf-8"?>\n' +
        '<browserconfig><msapplication><tile>' +
        '<square70x70logo src="ms-icon-70x70.png"/>' +
        '<square150x150logo src="ms-icon-150x150.png"/>' +
        '<square310x310logo src="ms-icon-310x310.png"/>' +
        '<TileColor>' + touchBg + '</TileColor>' +
        '</tile></msapplication></browserconfig>\n');

    console.log('done → ' + outDir);
}

// ---------- helpers ----------

function bitmap(size) {
    return $.NSBitmapImageRep.alloc
        .initWithBitmapDataPlanesPixelsWidePixelsHighBitsPerSampleSamplesPerPixelHasAlphaIsPlanarColorSpaceNameBytesPerRowBitsPerPixel(
            null, size, size, 8, 4, true, false, $.NSCalibratedRGBColorSpace, 0, 0);
}

function withContext(rep, flipped, fn) {
    const ctx = $.NSGraphicsContext.graphicsContextWithBitmapImageRep(rep);
    $.NSGraphicsContext.saveGraphicsState;
    $.NSGraphicsContext.setCurrentContext(ctx);
    try { fn(ctx); } finally {
        ctx.flushGraphics;
        $.NSGraphicsContext.restoreGraphicsState;
    }
}

// Scan alpha on a coarse grid first (fast through the ObjC bridge), then refine
// each edge with full-resolution rows/columns just outside the coarse box.
function alphaBounds(rep, size) {
    const alpha = (x, y) => rep.colorAtXY(x, y).alphaComponent > 0.03;
    const step = 8;
    let minX = size, minY = size, maxX = -1, maxY = -1;
    for (let y = 0; y < size; y += step) {
        for (let x = 0; x < size; x += step) {
            if (alpha(x, y)) {
                if (x < minX) minX = x; if (x > maxX) maxX = x;
                if (y < minY) minY = y; if (y > maxY) maxY = y;
            }
        }
    }
    if (maxX < 0) return null;
    const lo = (v) => Math.max(0, v - step), hi = (v) => Math.min(size - 1, v + step);
    let x0 = minX, x1 = maxX, y0 = minY, y1 = maxY;
    for (let x = lo(minX); x < minX; x++) { for (let y = lo(minY); y <= hi(maxY); y++) if (alpha(x, y)) { x0 = Math.min(x0, x); break; } }
    for (let x = hi(maxX); x > maxX; x--) { for (let y = lo(minY); y <= hi(maxY); y++) if (alpha(x, y)) { x1 = Math.max(x1, x); break; } }
    for (let y = lo(minY); y < minY; y++) { for (let x = lo(minX); x <= hi(maxX); x++) if (alpha(x, y)) { y0 = Math.min(y0, y); break; } }
    for (let y = hi(maxY); y > maxY; y--) { for (let x = lo(minX); x <= hi(maxX); x++) if (alpha(x, y)) { y1 = Math.max(y1, y); break; } }
    return { x: x0, y: y0, w: x1 - x0 + 1, h: y1 - y0 + 1 };
}

function parseHex(s) {
    const hex = s.trim().replace(/^#/, '');
    if (!/^[0-9a-fA-F]{6}$/.test(hex)) throw new Error('bad colour: ' + s);
    const v = parseInt(hex, 16);
    return $.NSColor.colorWithSRGBRedGreenBlueAlpha(((v >> 16) & 0xff) / 255, ((v >> 8) & 0xff) / 255, (v & 0xff) / 255, 1);
}

function base64(bytes) {
    const T = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    let out = '';
    for (let i = 0; i < bytes.length; i += 3) {
        const a = bytes[i], b = bytes[i + 1], c = bytes[i + 2];
        const n = (a << 16) | ((b || 0) << 8) | (c || 0);
        out += T[(n >> 18) & 63] + T[(n >> 12) & 63] +
            (b === undefined ? '=' : T[(n >> 6) & 63]) +
            (c === undefined ? '=' : T[n & 63]);
    }
    return out;
}
