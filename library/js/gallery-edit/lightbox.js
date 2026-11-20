/**
 * Front-end gallery editor: keyboard editing inside the lightbox.
 *
 * Open an image, move through the set in the lightbox, and the editor's
 * keyboard focus (data-kb-focus / data-kb-tracked-id on the grid, owned by
 * library/js/gallery-keyboard-reorder.js) moves with it. Every editor key then
 * acts on the image on screen (↑/↓ move it, H hides it, U / N bring images
 * back, S selects it, F features it), and the lightbox follows the grid: after
 * any change it shows the tracked image again, from the grid's current order.
 *
 * This file knows nothing about a particular lightbox. A lightbox plugs in
 * through an adapter (gallery-edit/lightbox-magnific.js is the only one):
 *
 *   tiagsspaceGalleryEdit.lightbox.use( {
 *       name:        'magnific_popup',
 *       isOpen:      function () {},        // this gallery's lightbox is open
 *       current:     function () {},        // the grid .item on screen, or null
 *       canShow:     function ( item ) {},  // the lightbox can show this .item
 *       show:        function ( item ) {},  // show it, re-reading the grid's
 *                                           // order; no-op when already there
 *       close:       function () {},
 *       mountPoint:  function () {},        // element the status line goes in
 *       on:          function ( event, fn ) {}  // 'open' | 'change' | 'close'
 *   } );
 *
 * Rules:
 *  - lightbox → grid: on 'change' the grid focus moves to the image on screen,
 *    unless it already is the tracked item (that keeps the ↑/↓ algorithm's
 *    focus slot, and ends the loop below).
 *  - grid → lightbox: while open, a MutationObserver on the grid (its children
 *    and data-kb-tracked-id) shows the tracked item after every change, from
 *    any path (keys, the bar, a reveal). A tracked item the lightbox cannot
 *    show (a video) gives way to the nearest one after it, else before it; no
 *    image left closes the lightbox. Observers live only while open.
 *  - A letter key (S, H, N, F…) first focuses the image on screen: after ↑/↓
 *    the grid's focus stays on its slot while the moved image is shown.
 *  - Shift + ← / →: the lightbox moves, then the image left and the image
 *    reached are both selected (gallery-edit/selection.js stands aside).
 *
 * A status line in the lightbox says what the grid shows but the overlay
 * hides: Selected · Featured, and a short "Hidden · U to undo" after H.
 * An image previewed in black and white (View ▾, B: class tge-bw) shows so in
 * the lightbox too (class tge-lb-bw on the mount point).
 *
 * Exposes tiagsspaceGalleryEdit.lightbox: use( adapter ), isOpen().
 * Loaded only in gallery edit mode, and only with an adapter for the
 * gallery's lightbox (tiagsspace_gallery_edit_lightbox_adapters()).
 */
( function () {
	'use strict';

	var cfg = window.tiagsspaceGalleryEditCfg;
	var api = window.tiagsspaceGalleryEdit;
	if ( ! cfg || ! api || ! api.items || ! api.grid ) return;

	var grid = api.grid;
	var pool = document.getElementById( cfg.poolId );
	var E = ( cfg.i18n && cfg.i18n.edit ) || {};

	function t( key, fallback ) {
		return E[ key ] || fallback;
	}

	var adapter   = null;
	var observer  = null;
	var shiftFrom = null; // id of the image left with Shift + ← / →
	var noteText  = '';
	var noteTimer = null;
	var status    = document.createElement( 'div' );
	status.className = 'tge-lb-status';
	status.setAttribute( 'aria-live', 'polite' );

	function idOf( el ) {
		return el ? String( el.getAttribute( 'attachmentId' ) || '' ) : '';
	}

	function isOpen() {
		try {
			return !! ( adapter && adapter.isOpen() );
		} catch ( err ) {
			return false;
		}
	}

	// ── Status line ─────────────────────────────────────────

	function renderStatus() {
		if ( ! isOpen() ) return;
		var mount = adapter.mountPoint();
		if ( mount && status.parentNode !== mount ) mount.appendChild( status );
		var item = adapter.current();
		var id = idOf( item );
		var parts = [];
		if ( id && api.selection && api.selection.has( id ) ) parts.push( t( 'lbSelected', 'Selected' ) );
		if ( id && api.featured && String( api.featured.id() ) === id ) parts.push( t( 'featured', 'Featured' ) );
		if ( noteText ) parts.push( noteText );
		status.textContent = parts.join( ' · ' );
		status.hidden = ! parts.length;
		// The black and white preview (View ▾, B) follows the image on screen.
		if ( mount ) mount.classList.toggle( 'tge-lb-bw', !! ( item && item.classList.contains( 'tge-bw' ) ) );
	}

	function note( text ) {
		noteText = text;
		clearTimeout( noteTimer );
		noteTimer = setTimeout( function () {
			noteText = '';
			renderStatus();
		}, 2500 );
		renderStatus();
	}

	// ── Grid → lightbox ─────────────────────────────────────

	function trackedItem( items ) {
		var id = grid.getAttribute( 'data-kb-tracked-id' );
		for ( var i = 0; i < items.length; i++ ) {
			if ( idOf( items[ i ] ) === id ) return i;
		}
		var f = grid.getAttribute( 'data-kb-focus' );
		return f === null ? -1 : Math.min( parseInt( f, 10 ) || 0, items.length - 1 );
	}

	function syncFromGrid() {
		if ( ! isOpen() ) return;
		var items = api.items();
		var at = trackedItem( items );
		var target = null;
		var i;
		if ( at >= 0 ) {
			for ( i = at; i < items.length && ! target; i++ ) {
				if ( adapter.canShow( items[ i ] ) ) target = items[ i ];
			}
			for ( i = at - 1; i >= 0 && ! target; i-- ) {
				if ( adapter.canShow( items[ i ] ) ) target = items[ i ];
			}
		}
		if ( ! target ) {
			for ( i = 0; i < items.length && ! target; i++ ) {
				if ( adapter.canShow( items[ i ] ) ) target = items[ i ];
			}
		}
		if ( ! target ) {
			adapter.close();
			return;
		}
		adapter.show( target );
		renderStatus();
	}

	function watch() {
		if ( observer || ! window.MutationObserver ) return;
		observer = new MutationObserver( function ( records ) {
			var hidden = false;
			var restored = false;
			records.forEach( function ( r ) {
				if ( r.target !== pool ) return;
				if ( r.addedNodes.length ) hidden = true;
				if ( r.removedNodes.length ) restored = true;
			} );
			if ( hidden ) {
				note( t( 'lbHidden', 'Hidden · U to undo' ) );
			} else if ( restored && noteText ) {
				noteText = ''; // U / N: the image is back, the note no longer applies
				clearTimeout( noteTimer );
			}
			syncFromGrid();
		} );
		observer.observe( grid, { childList: true, attributes: true, attributeFilter: [ 'data-kb-tracked-id' ] } );
		if ( pool ) observer.observe( pool, { childList: true } );
	}

	function unwatch() {
		if ( observer ) observer.disconnect();
		observer = null;
	}

	// ── Lightbox → grid ─────────────────────────────────────

	function onChange() {
		var item = adapter.current();
		if ( ! item ) return;
		var id = idOf( item );
		if ( id !== grid.getAttribute( 'data-kb-tracked-id' ) && typeof window.galleryKeyboardReorderFocus === 'function' ) {
			window.galleryKeyboardReorderFocus( item, { scroll: true } );
		}
		if ( shiftFrom !== null && api.selection ) {
			api.selection.add( shiftFrom && shiftFrom !== id ? [ shiftFrom, id ] : [ id ] );
			shiftFrom = null;
		}
		renderStatus();
	}

	function onOpen() {
		onChange();
		watch();
		renderStatus();
	}

	function onClose() {
		unwatch();
		shiftFrom = null;
		noteText = '';
		clearTimeout( noteTimer );
		if ( status.parentNode ) {
			status.parentNode.classList.remove( 'tge-lb-bw' );
			status.parentNode.removeChild( status );
		}
	}

	// A letter key (S, H, N, F…) acts on the focused item. After ↑ / ↓ the grid's
	// focus stays on its slot while the moved image is on screen: focus the image
	// on screen first, so the key acts on what is seen. Capture phase, before the
	// editor's own listeners.
	window.addEventListener( 'keydown', function ( e ) {
		if ( e.metaKey || e.ctrlKey || e.altKey || ! /^[a-z]$/i.test( e.key ) ) return;
		if ( ! isOpen() || typeof window.galleryKeyboardReorderFocus !== 'function' ) return;
		var item = adapter.current();
		if ( item && api.items().indexOf( item ) !== parseInt( grid.getAttribute( 'data-kb-focus' ), 10 ) ) {
			window.galleryKeyboardReorderFocus( item );
		}
	}, true );

	// Shift + ← / →: remember the image being left; the lightbox's own key
	// handler moves next, and onChange() selects both.
	window.addEventListener( 'keydown', function ( e ) {
		if ( ! e.shiftKey || e.metaKey || e.ctrlKey || e.altKey ) return;
		if ( e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ) return;
		if ( ! isOpen() ) return;
		shiftFrom = idOf( adapter.current() );
		// A one-image set does not move: select it anyway.
		setTimeout( function () {
			if ( shiftFrom !== null && api.selection ) {
				if ( shiftFrom ) api.selection.add( [ shiftFrom ] );
				shiftFrom = null;
			}
		}, 0 );
	}, true );

	if ( api.selection && api.selection.on ) api.selection.on( renderStatus );
	document.addEventListener( 'tiagsspace:gallery-view', renderStatus );
	if ( api.featured && api.featured.on ) api.featured.on( renderStatus );

	api.lightbox = {
		use: function ( a ) {
			if ( ! a || adapter ) return;
			adapter = a;
			a.on( 'open', onOpen );
			a.on( 'change', onChange );
			a.on( 'close', onClose );
		},
		isOpen: isOpen
	};
} )();
