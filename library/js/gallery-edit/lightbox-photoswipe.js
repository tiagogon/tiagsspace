/**
 * Front-end gallery editor: the PhotoSwipe adapter for gallery-edit/lightbox.js
 * (contract at the top of that file). Replaced lightbox-magnific.js, Nov 2026.
 *
 * Only PhotoSwipe's public surface, no edit to the vendored files:
 *  - window.tiagsspaceLightbox (library/js/lightbox.js, a module that runs
 *    after this classic script; `tiagsspace:lightbox-ready` says when):
 *    { lightbox, LINK }. lightbox.on() binds to every PhotoSwipe it opens;
 *  - the open PhotoSwipe (lightbox.pswp): isOpen, isDestroying, currIndex,
 *    currSlide.data.element (the link on screen), options.dataSource
 *    ({ gallery, items }), goTo(), refreshSlideContent(), contentLoader,
 *    element, close(), next(), prev().
 *
 * show( item ): the gallery's order changed (↑/↓, H, U, N, the bar). The
 * item list PhotoSwipe read at open is replaced by the grid's links as they
 * are now, every cached slide is dropped, and the three slides around the
 * image are rebuilt. Their files are in the browser's cache, so lightbox.js
 * shows them at once (no fade, no blink).
 *
 * Shift + ← / →: PhotoSwipe ignores arrow keys with a modifier, so this adapter
 * moves for it (gallery-edit/lightbox.js then selects both images).
 *
 * Scoped to its own grid (dataSource.gallery === grid): another gallery's
 * lightbox on the same page is left alone.
 */
( function () {
	'use strict';

	var api = window.tiagsspaceGalleryEdit;
	if ( ! api || ! api.lightbox || ! api.grid ) return;

	var grid = api.grid;
	var LINK = 'a.lightbox-link';
	var handlers = { open: [], change: [], close: [] };
	// While show() rebuilds the slides PhotoSwipe reports 'change' with the old
	// image at the new index; the editor would move its focus to that image.
	// Those are held back and one 'change' is sent once the slides are right.
	var rebuilding = false;

	function lb() {
		return window.tiagsspaceLightbox ? window.tiagsspaceLightbox.lightbox : null;
	}

	function pswp() {
		var l = lb();
		return l ? l.pswp : null;
	}

	function mine( p ) {
		return !! ( p && p.options && p.options.dataSource && p.options.dataSource.gallery === grid );
	}

	function open( p ) {
		return !! ( p && p.isOpen && ! p.isDestroying && mine( p ) );
	}

	function itemOf( node ) {
		while ( node && node.parentNode !== grid ) node = node.parentNode;
		return node && node.classList && node.classList.contains( 'item' ) ? node : null;
	}

	function linkOf( item ) {
		return item ? item.querySelector( LINK ) : null;
	}

	// The same list PhotoSwipe reads (gallery.querySelectorAll( children )).
	function links() {
		return Array.prototype.slice.call( grid.querySelectorAll( LINK ) );
	}

	function fire( name ) {
		handlers[ name ].forEach( function ( fn ) { fn(); } );
	}

	function bind() {
		var l = lb();
		if ( ! l || bind.done ) return;
		bind.done = true;
		l.on( 'afterInit', function () { if ( mine( pswp() ) ) fire( 'open' ); } );
		l.on( 'change', function () { if ( ! rebuilding && open( pswp() ) ) fire( 'change' ); } );
		l.on( 'close', function () { if ( mine( pswp() ) ) fire( 'close' ); } );
		l.on( 'keydown', function ( e ) {
			var p = pswp();
			var ev = e.originalEvent;
			if ( ! open( p ) || ! ev || ! ev.shiftKey || ev.metaKey || ev.ctrlKey || ev.altKey ) return;
			if ( ev.key !== 'ArrowLeft' && ev.key !== 'ArrowRight' ) return;
			ev.preventDefault();
			if ( ev.key === 'ArrowRight' ) {
				p.next();
			} else {
				p.prev();
			}
		} );
	}

	if ( window.tiagsspaceLightbox ) {
		bind();
	} else {
		document.addEventListener( 'tiagsspace:lightbox-ready', bind );
	}

	api.lightbox.use( {
		name: 'lightbox',

		isOpen: function () {
			return open( pswp() );
		},

		current: function () {
			var p = pswp();
			var slide = open( p ) ? p.currSlide : null;
			return slide && slide.data ? itemOf( slide.data.element ) : null;
		},

		canShow: function ( item ) {
			return !! linkOf( item );
		},

		show: function ( item ) {
			var p = pswp();
			if ( ! open( p ) ) return;
			var list = links();
			var i = list.indexOf( linkOf( item ) );
			if ( i < 0 ) return;
			var ds = p.options.dataSource;
			var old = ds.items || [];
			var same = p.currIndex === i && old.length === list.length && old.every( function ( a, k ) {
				return a === list[ k ];
			} );
			if ( same ) return;
			rebuilding = true;
			try {
				ds.items = list;
				for ( var k = 0, n = Math.max( old.length, list.length ); k < n; k++ ) {
					p.contentLoader.removeByIndex( k );
				}
				if ( p.currIndex !== i ) p.goTo( i );
				// Rebuild the slide on screen and its neighbours from the new order.
				[ i - 1, i + 1, i ].forEach( function ( k ) {
					if ( list.length > 1 || k === i ) p.refreshSlideContent( p.getLoopedIndex( k ) );
				} );
			} finally {
				rebuilding = false;
			}
			fire( 'change' );
		},

		close: function () {
			var p = pswp();
			if ( open( p ) ) p.close();
		},

		mountPoint: function () {
			var p = pswp();
			return open( p ) ? p.element : null;
		},

		on: function ( event, fn ) {
			if ( handlers[ event ] ) handlers[ event ].push( fn );
		}
	} );
} )();
