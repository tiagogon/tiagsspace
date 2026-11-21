/**
 * The theme's Infinite Scroll setup, shared by the archive grid
 * (template-parts/archive-grid.php) and the Log archive (archive-log.php).
 *
 *   var $container = tiagsspaceInfiniteScroll( selector, options );
 *
 * Same options as Infinite Scroll (the vendored infinite-scroll.pkgd.min.js,
 * never edited); returns the jQuery container. Call it in an inline script
 * right after the list, so the Back rebuild (4) can hide the list before the
 * browser paints it. What it adds:
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
 *
 * 4. Back returns to the same spot (Nov 2026). Chrome and Safari keep the whole
 *    page in their back/forward cache and show it again on Back; no script runs
 *    then, so none of this happens for them. Firefox will not cache a page with
 *    a request still open, and a big thumbnail teaser always has one (footer.php
 *    explains), so Firefox reloads the archive from page 1. When leaving, the
 *    page count and the item clicked (or the one at the top of the window) with
 *    its place in the window go to sessionStorage; when the page is loaded again
 *    by Back, the list stays hidden (1.5s at most) while the same pages are fetched
 *    (four at a time; from the browser's HTTP cache when the server sent cache
 *    headers) and handed to the plugin in order, then that item is put back at
 *    its place. Never on a plain visit or a reload; at most 30 pages.
 */
( function ( window, document, $ ) {
	'use strict';

	if ( ! $ || ! $.fn.infiniteScroll ) {
		return;
	}

	var ua = window.navigator.userAgent;
	var isWebKit = /AppleWebKit/.test( ua ) && ! /Chrome\/|Chromium\//.test( ua );
	var MAX_REBUILD_PAGES = 30;
	var REVEAL_AFTER = 1500;

	function threshold() {
		return Math.max( 1600, Math.round( window.innerHeight * 2.5 ) );
	}

	// --- 4. Back: where the visitor was -------------------------------------

	function storageKey() {
		return 'tiagsspace-scroll:' + window.location.pathname + window.location.search;
	}

	function readState() {
		try {
			var raw = window.sessionStorage.getItem( storageKey() );
			return raw ? JSON.parse( raw ) : null;
		} catch ( e ) {
			return null;
		}
	}

	function writeState( state ) {
		try {
			window.sessionStorage.setItem( storageKey(), JSON.stringify( state ) );
		} catch ( e ) {}
	}

	function clearState() {
		try {
			window.sessionStorage.removeItem( storageKey() );
		} catch ( e ) {}
	}

	// An item is named by its id (Log entries) or its first link (thumbnails).
	function anchorOf( item ) {
		if ( ! item ) {
			return null;
		}
		if ( item.id ) {
			return { id: item.id };
		}
		var link = item.querySelector( 'a[href]' );
		return link ? { href: link.href } : null;
	}

	function findAnchor( container, itemSelector, anchor ) {
		if ( ! anchor ) {
			return null;
		}
		if ( anchor.id ) {
			var byId = document.getElementById( anchor.id );
			return byId && container.contains( byId ) ? byId : null;
		}
		var items = container.querySelectorAll( itemSelector );
		for ( var i = 0; i < items.length; i++ ) {
			var link = items[ i ].querySelector( 'a[href]' );
			if ( link && link.href === anchor.href ) {
				return items[ i ];
			}
		}
		return null;
	}

	function isBackForwardLoad() {
		var nav = window.performance && window.performance.getEntriesByType ?
			window.performance.getEntriesByType( 'navigation' )[ 0 ] : null;
		return !! nav && nav.type === 'back_forward';
	}

	function rememberPosition( $container, infScroll, itemSelector ) {
		var container = $container[ 0 ];
		var savedByClick = false;

		function save( item ) {
			var anchor = anchorOf( item );
			writeState( {
				pages: infScroll.pageIndex,
				anchor: anchor,
				offset: item && anchor ? Math.round( item.getBoundingClientRect().top ) : 0,
				y: Math.round( window.scrollY )
			} );
		}

		// The clicked item is the best anchor: Back puts it where it was.
		document.addEventListener( 'click', function ( event ) {
			if ( event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}
			var link = event.target.closest && event.target.closest( 'a[href]' );
			var item = link && link.closest( itemSelector );
			if ( item && container.contains( item ) ) {
				save( item );
				savedByClick = true;
			}
		}, true );

		// Any other way out: one probe for the item near the top of the window.
		// pagehide, never unload: an unload listener keeps every browser from
		// caching the page.
		window.addEventListener( 'pagehide', function () {
			if ( savedByClick ) {
				return;
			}
			var hit = document.elementFromPoint( window.innerWidth / 2, 100 );
			var item = hit && hit.closest ? hit.closest( itemSelector ) : null;
			save( item && container.contains( item ) && anchorOf( item ) ? item : null );
		} );

		window.addEventListener( 'pageshow', function ( event ) {
			if ( event.persisted ) {
				savedByClick = false;
			}
		} );
	}

	function rebuild( $container, infScroll, itemSelector, state ) {
		var container = $container[ 0 ];
		var target = Math.min( state.pages, MAX_REBUILD_PAGES );
		var revealed = false;
		var userMoved = false;

		infScroll.options.loadOnScroll = false;

		function onUserMove() {
			userMoved = true;
		}
		[ 'wheel', 'touchstart', 'keydown', 'mousedown' ].forEach( function ( type ) {
			window.addEventListener( type, onUserMove, { once: true, passive: true } );
		} );

		function scrollToSpot() {
			var item = findAnchor( container, itemSelector, state.anchor );
			var y = item ?
				item.getBoundingClientRect().top + window.scrollY - ( state.offset || 0 ) :
				state.y;
			window.scrollTo( 0, Math.max( 0, Math.round( y || 0 ) ) );
			return !! item;
		}

		function reveal() {
			if ( revealed ) {
				return;
			}
			revealed = true;
			container.style.visibility = '';
		}

		// A slow network must not leave the archive blank: show it where the
		// rebuild has got to, and keep going.
		var safety = window.setTimeout( function () {
			scrollToSpot();
			reveal();
		}, REVEAL_AFTER );

		function stopWatchingUser() {
			[ 'wheel', 'touchstart', 'keydown', 'mousedown' ].forEach( function ( type ) {
				window.removeEventListener( type, onUserMove );
			} );
		}

		function done() {
			window.clearTimeout( safety );
			if ( ! userMoved ) {
				scrollToSpot();
			}
			reveal();
			clearState();
			infScroll.options.loadOnScroll = true;
			// Something above the item may still change height until the page has
			// loaded (a Log gallery, a web font): put it back once more then.
			if ( document.readyState === 'complete' ) {
				stopWatchingUser();
			} else {
				window.addEventListener( 'load', function () {
					if ( ! userMoved ) {
						scrollToSpot();
					}
					stopWatchingUser();
				}, { once: true } );
			}
		}

		// The pages are fetched in parallel (a page the server sends without cache
		// headers is not in the browser's cache and takes up to ~0.7s), then handed
		// to the plugin in order through its own onPageLoad(), so each one goes the
		// normal way: page count, last-page check, append (scripts included), the
		// page's append handlers (Masonry, teasers).
		var urls = [];
		for ( var n = infScroll.pageIndex + 1; n <= target; n++ ) {
			urls.push( pageUrl( infScroll, n ) );
		}
		var pages = fetchAll( urls, 4 );

		function next( i ) {
			if ( i >= pages.length || ! infScroll.canLoad ) {
				return done();
			}
			pages[ i ].then( function ( page ) {
				if ( ! page || ! infScroll.canLoad ) {
					return done();
				}
				var before = infScroll.pageIndex;
				try {
					infScroll.onPageLoad( page.body, page.url, page.response );
				} catch ( e ) {
					return done();
				}
				// An empty page: the plugin has marked the end.
				if ( infScroll.pageIndex === before ) {
					return done();
				}
				next( i + 1 );
			} );
		}

		next( 0 );
	}

	// Same address the plugin builds for page n (path template, relative to the
	// listing's own address).
	function pageUrl( infScroll, n ) {
		var path = window.location.pathname;
		return path.substring( 0, path.lastIndexOf( '/' ) ) + '/' + String( infScroll.options.path ).replace( '{{#}}', n );
	}

	// One promise per URL, in order; at most `limit` requests at once. A failed
	// page resolves to null.
	function fetchAll( urls, limit ) {
		var parser = new window.DOMParser();
		var resolvers = [];
		var promises = urls.map( function () {
			return new Promise( function ( resolve ) {
				resolvers.push( resolve );
			} );
		} );
		var cursor = 0;

		function run() {
			if ( cursor >= urls.length ) {
				return;
			}
			var i = cursor++;
			window.fetch( urls[ i ] ).then( function ( response ) {
				if ( ! response.ok ) {
					return null;
				}
				return response.text().then( function ( text ) {
					return { body: parser.parseFromString( text, 'text/html' ), url: urls[ i ], response: response };
				} );
			} ).catch( function () {
				return null;
			} ).then( function ( page ) {
				resolvers[ i ]( page );
				run();
			} );
		}

		for ( var k = 0; k < Math.min( limit, urls.length ); k++ ) {
			run();
		}
		return promises;
	}

	// --- Setup ---------------------------------------------------------------

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
				// Images inserted already complete fire no load event (image-fade.js).
				if ( window.tiagsspaceImageFadeSweep ) {
					window.tiagsspaceImageFadeSweep();
				}
				if ( infScroll.options.loadOnScroll && infScroll.getBottomDistance() <= infScroll.options.scrollThreshold ) {
					infScroll.loadNextPage();
				}
			} );
		} );

		if ( typeof options.append === 'string' ) {
			rememberPosition( $container, infScroll, options.append );

			var state = readState();
			// With one page there is nothing to fetch; the saved item still corrects
			// the browser's own restore, which lands off when heights settle late.
			if ( state && ( state.pages > 1 || state.anchor ) && isBackForwardLoad() ) {
				// Now, before the list is painted: no flash of page 1, no jump by
				// the browser's own scroll restore.
				$container[ 0 ].style.visibility = 'hidden';
				infScroll.options.loadOnScroll = false;
				if ( 'scrollRestoration' in window.history ) {
					window.history.scrollRestoration = 'manual';
				}
				// The page's own append handlers (Masonry, teasers) are bound right
				// after this call returns: start on the next task.
				window.setTimeout( function () {
					rebuild( $container, infScroll, options.append, state );
				}, 0 );
			}
		}

		return $container;
	};
} )( window, document, window.jQuery );
