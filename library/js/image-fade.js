/**
 * Image fade-in (Nov 2026).
 *
 * Every image in an `.imgcontainer` (archive thumbnails, teaser posters, gallery
 * images) stays transparent in its reserved box until it is downloaded AND
 * decoded, then fades in (`is-loaded`, styles in _component-image-fade.scss).
 * Without this a big JPEG paints itself in strips from the top down while its
 * bytes arrive.
 *
 * Printed INLINE at the top of <head> by functions.php (no request), so its one
 * listener exists before the first <img> is parsed:
 * - ONE capture-phase listener on the document for `load` and `error`. Those
 *   events do not bubble but can be captured, so every image reports here, the
 *   ones in the HTML and every one added later (Infinite Scroll pages, the Back
 *   rebuild, WebKit's srcset re-parse that recreates <img>, the gallery editor's
 *   reveal). Never add per-image or per-template code for this.
 * - src / srcset / sizes are never touched: the browser picks the file exactly as
 *   before. A later swap to a sharper candidate (resize, retina) fires `load`
 *   again on an image that is already visible: nothing happens.
 * - Images that are ready before the first frame is painted show at once
 *   (`is-instant`): nothing to fade, they are simply there.
 * - An image must never stay invisible: `error` shows it too (alt text), the CSS
 *   only acts under html.tg-fade (set here, so no script = no hiding), and a
 *   sweep at DOMContentLoaded, window load and after each Infinite Scroll append
 *   (`window.tiagsspaceImageFadeSweep()`) catches any image already complete.
 */
( function ( doc, win ) {
	'use strict';

	var root = doc.documentElement;
	if ( ! root.classList || ! doc.addEventListener ) {
		return;
	}
	root.classList.add( 'tg-fade' );

	// Before the first frame, an image that is ready shows without a transition.
	var painted = false;
	if ( win.requestAnimationFrame ) {
		win.requestAnimationFrame( function () {
			setTimeout( function () {
				painted = true;
			}, 0 );
		} );
	} else {
		painted = true;
	}

	function inScope( img ) {
		return img && img.tagName === 'IMG' && img.closest && img.closest( '.imgcontainer' );
	}

	function show( img ) {
		if ( ! painted ) {
			img.classList.add( 'is-instant' );
		}
		img.classList.add( 'is-loaded' );
	}

	function onEvent( event ) {
		var img = event.target;
		if ( ! inScope( img ) || img.classList.contains( 'is-loaded' ) ) {
			return;
		}
		// decode(): ready to paint in one go, which is what removes the strips.
		if ( event.type === 'load' && img.decode ) {
			img.decode().then( function () {
				show( img );
			}, function () {
				show( img );
			} );
		} else {
			show( img );
		}
	}

	doc.addEventListener( 'load', onEvent, true );
	doc.addEventListener( 'error', onEvent, true );

	// Images complete before anyone listened, or inserted already complete.
	// naturalWidth: a lazy image not loaded yet may report `complete` in some
	// browsers; a broken one reports through `error` above.
	function sweep() {
		var imgs = doc.querySelectorAll( '.imgcontainer img:not(.is-loaded)' );
		for ( var i = 0; i < imgs.length; i++ ) {
			if ( imgs[ i ].complete && imgs[ i ].naturalWidth > 0 ) {
				show( imgs[ i ] );
			}
		}
	}

	win.tiagsspaceImageFadeSweep = sweep;
	doc.addEventListener( 'DOMContentLoaded', sweep );
	win.addEventListener( 'load', sweep );
}( document, window ) );
