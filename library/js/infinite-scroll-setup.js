/**
 * The theme's Infinite Scroll setup, shared by the archive grid
 * (template-parts/archive-grid.php) and the Log archive (archive-log.php).
 *
 *   var $container = tiagsspaceInfiniteScroll( selector, options );
 *
 * Same options as Infinite Scroll (the vendored infinite-scroll.pkgd.min.js,
 * never edited); returns the jQuery container. What it adds:
 *
 * 1. Loads early. The next page is asked for when the bottom of the list is
 *    within max(1600px, 2.5 window heights) of the window's bottom, kept up to
 *    date on resize. A page of thumbnails is about one screen tall and a page
 *    takes up to a second to arrive, so the old fixed 800px was spent before
 *    the page came and the visitor waited at the bottom.
 *
 * 2. Keeps going. The plugin only checks the distance on a scroll event; after
 *    each append it is checked once more, so a visitor who stopped scrolling
 *    inside the distance still gets the next page. No page limit: it runs until
 *    the last page.
 *
 * 3. Safari srcset (metafizzy/infinite-scroll#770): WebKit does not pick a
 *    source from srcset for images parsed in another document, so they are
 *    re-parsed once after the append. Only on WebKit (every iOS browser, desktop
 *    Safari): elsewhere it only threw the <img> away and built it again.
 */
( function ( window, $ ) {
	'use strict';

	if ( ! $ || ! $.fn.infiniteScroll ) {
		return;
	}

	var ua = window.navigator.userAgent;
	var isWebKit = /AppleWebKit/.test( ua ) && ! /Chrome\/|Chromium\//.test( ua );

	function threshold() {
		return Math.max( 1600, Math.round( window.innerHeight * 2.5 ) );
	}

	window.tiagsspaceInfiniteScroll = function ( selector, options ) {
		var $container = $( selector );
		if ( ! $container.length ) {
			return $container;
		}

		$container.infiniteScroll( $.extend( {}, options, { scrollThreshold: threshold() } ) );
		var infScroll = $container.data( 'infiniteScroll' );

		var frame = 0;
		window.addEventListener( 'resize', function () {
			if ( frame ) {
				return;
			}
			frame = window.requestAnimationFrame( function () {
				frame = 0;
				infScroll.options.scrollThreshold = threshold();
			} );
		} );

		$container.on( 'append.infiniteScroll', function ( event, body, path, items ) {
			if ( isWebKit ) {
				$( items ).find( 'img[srcset]' ).each( function ( i, img ) {
					img.outerHTML = img.outerHTML;
				} );
			}
			// After the page's own append handlers have laid the new items out.
			window.requestAnimationFrame( function () {
				if ( infScroll.options.loadOnScroll && infScroll.getBottomDistance() <= infScroll.options.scrollThreshold ) {
					infScroll.loadNextPage();
				}
			} );
		} );

		return $container;
	};
} )( window, window.jQuery );
