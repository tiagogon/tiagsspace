/**
 * Front-end gallery editor: the selection.
 *
 * Same pattern as the media modal's grid: a check box in each image's
 * top-right corner, shown on hover (and on keyboard focus, and always on touch
 * screens), kept while the image is selected; selected images carry a ring.
 *
 *   click ☐            select / deselect that image
 *   Shift-click ☐      select the range from the last image clicked
 *   S                  select / deselect the keyboard-focused image
 *   Shift + ← / →      move the focus (gallery-keyboard-reorder.js) and
 *                      extend the selection over it
 *   ⌘A / Ctrl+A        select every image in the grid
 *   Esc                clear the selection
 *
 * The keyboard focus is the one gallery-keyboard-reorder.js keeps on the grid
 * (data-kb-focus); this file only reads it, through
 * window.galleryKeyboardReorderFocus() when it has to set it.
 *
 * API (window.tiagsspaceGalleryEdit.selection):
 *   ids()  items()  has( id )  set( ids )  add( ids )  toggle( id )
 *   clear()  on( fn )  // fn( ids ) after every change
 *
 * Loaded only in gallery edit mode (tiagsspace_gallery_edit_mode()); config in
 * window.tiagsspaceGalleryEditCfg (tiagsspace_gallery_edit_config()).
 */
( function () {
	'use strict';

	var cfg = window.tiagsspaceGalleryEditCfg;
	if ( ! cfg ) return;

	var grid = document.getElementById( cfg.gridId );
	if ( ! grid ) return;

	var api = window.tiagsspaceGalleryEdit = window.tiagsspaceGalleryEdit || {};
	var i18n = ( cfg.i18n && cfg.i18n.edit ) || {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	var selected  = {};   // id → true
	var listeners = [];
	var anchorId  = null; // last id clicked, for Shift-click ranges

	api.grid = grid;

	// Grid items in DOM order (skips the Masonry sizer and anything else).
	api.items = function () {
		return Array.prototype.filter.call( grid.children, function ( el ) {
			return el.classList && el.classList.contains( 'item' );
		} );
	};

	function idOf( el ) {
		return el ? String( el.getAttribute( 'attachmentId' ) || '' ) : '';
	}

	function itemById( id ) {
		var list = api.items();
		for ( var i = 0; i < list.length; i++ ) {
			if ( idOf( list[ i ] ) === String( id ) ) return list[ i ];
		}
		return null;
	}

	api.itemOf = function ( node ) {
		while ( node && node !== grid ) {
			if ( node.classList && node.classList.contains( 'item' ) && node.parentNode === grid ) return node;
			node = node.parentNode;
		}
		return null;
	};

	// ── Check boxes ─────────────────────────────────────────

	function checkOf( item ) {
		return item.querySelector( ':scope > .tge-check' );
	}

	function decorate( item ) {
		if ( checkOf( item ) ) return;
		var title = item.getAttribute( 'data-title' ) || idOf( item );
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'tge-check';
		b.setAttribute( 'role', 'checkbox' );
		b.setAttribute( 'aria-checked', 'false' );
		b.setAttribute( 'aria-label', t( 'selectItem', 'Select %s' ).replace( '%s', title ) );
		b.setAttribute( 'data-tip', t( 'tipSelect', 'Select  S · Shift-click a range' ) );
		b.innerHTML = '<span class="tge-check-box" aria-hidden="true"></span>';
		item.insertBefore( b, item.firstChild );
		paint( item );
	}

	function paint( item ) {
		var on = !! selected[ idOf( item ) ];
		item.classList.toggle( 'tge-selected', on );
		var b = checkOf( item );
		if ( b ) b.setAttribute( 'aria-checked', on ? 'true' : 'false' );
	}

	function paintAll() {
		api.items().forEach( paint );
	}

	// ── Model ───────────────────────────────────────────────

	function emit() {
		paintAll();
		var ids = api.selection.ids();
		listeners.forEach( function ( fn ) {
			try { fn( ids ); } catch ( err ) { if ( window.console ) console.error( err ); }
		} );
	}

	api.selection = {
		// Selected ids in grid order.
		ids: function () {
			return api.items().map( idOf ).filter( function ( id ) { return selected[ id ]; } );
		},
		items: function () {
			return api.items().filter( function ( el ) { return selected[ idOf( el ) ]; } );
		},
		has: function ( id ) {
			return !! selected[ String( id ) ];
		},
		set: function ( ids ) {
			selected = {};
			( ids || [] ).forEach( function ( id ) { selected[ String( id ) ] = true; } );
			emit();
		},
		add: function ( ids ) {
			( ids || [] ).forEach( function ( id ) { selected[ String( id ) ] = true; } );
			emit();
		},
		toggle: function ( id ) {
			id = String( id );
			if ( selected[ id ] ) {
				delete selected[ id ];
			} else {
				selected[ id ] = true;
			}
			anchorId = id;
			emit();
		},
		clear: function () {
			if ( ! Object.keys( selected ).length ) return;
			selected = {};
			emit();
		},
		on: function ( fn ) {
			listeners.push( fn );
		}
	};

	// Items leaving the grid (H parks them in the hidden pool, DELETE removes
	// them, Delete ▾ / Move to ▾ take them away) leave the selection; items
	// coming in (U, N, a reveal) get their check box.
	function sync() {
		// Paint too: an item hidden while selected comes back (U, N) carrying
		// the old look; the model is the truth.
		api.items().forEach( function ( el ) {
			decorate( el );
			paint( el );
		} );
		var present = {};
		api.items().forEach( function ( el ) { present[ idOf( el ) ] = true; } );
		var changed = false;
		Object.keys( selected ).forEach( function ( id ) {
			if ( ! present[ id ] ) {
				delete selected[ id ];
				changed = true;
			}
		} );
		if ( changed ) emit();
	}

	sync();
	if ( window.MutationObserver ) {
		new MutationObserver( sync ).observe( grid, { childList: true } );
	}

	// ── Pointer ─────────────────────────────────────────────

	function rangeIds( fromId, toId ) {
		var list = api.items().map( idOf );
		var a = list.indexOf( String( fromId ) );
		var b = list.indexOf( String( toId ) );
		if ( a < 0 || b < 0 ) return [ String( toId ) ];
		return list.slice( Math.min( a, b ), Math.max( a, b ) + 1 );
	}

	// Capture phase on the grid: the check box never reaches the lightbox, the
	// keyboard script's click-to-focus or Sortable's click guard.
	grid.addEventListener( 'click', function ( e ) {
		var b = e.target.closest ? e.target.closest( '.tge-check' ) : null;
		if ( ! b || ! grid.contains( b ) ) return;
		e.preventDefault();
		e.stopPropagation();

		var item = api.itemOf( b );
		if ( ! item ) return;
		var id = idOf( item );

		if ( e.shiftKey && anchorId && anchorId !== id ) {
			api.selection.add( rangeIds( anchorId, id ) );
			anchorId = id;
		} else {
			api.selection.toggle( id );
		}

		if ( typeof window.galleryKeyboardReorderFocus === 'function' ) {
			window.galleryKeyboardReorderFocus( item );
		}
	}, true );

	// Pressing the box must not start a Sortable drag either (fallback mode
	// listens to pointer/mouse down on the item).
	[ 'pointerdown', 'mousedown', 'touchstart' ].forEach( function ( type ) {
		grid.addEventListener( type, function ( e ) {
			if ( e.target.closest && e.target.closest( '.tge-check' ) ) {
				e.stopPropagation();
			}
		}, { capture: true, passive: true } );
	} );

	// ── Keyboard ────────────────────────────────────────────

	function typing( e ) {
		var tag = e.target && e.target.tagName;
		return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || ( e.target && e.target.isContentEditable );
	}

	function focusIndex() {
		var f = grid.getAttribute( 'data-kb-focus' );
		return f === null ? -1 : ( parseInt( f, 10 ) || 0 );
	}

	// The reorder script moves the focus in its own (bubble) listener; snapshot
	// where it was before, in the capture phase, so Shift+arrow can select both.
	var focusBefore = -1;
	window.addEventListener( 'keydown', function ( e ) {
		focusBefore = focusIndex();
	}, true );

	function lightboxOpen() {
		return !! ( api.lightbox && api.lightbox.isOpen() );
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( typing( e ) || api.modalOpen ) return;
		var key = e.key;
		var list;

		// In the lightbox, Shift + ← / → is handled by gallery-edit/lightbox.js
		// (the lightbox moves, then both images are selected) and Esc only
		// closes the lightbox.
		if ( lightboxOpen() && ( key === 'Escape' || ( e.shiftKey && ( key === 'ArrowLeft' || key === 'ArrowRight' ) ) ) ) return;

		// S: toggle the focused image (the first one when nothing has focus yet).
		if ( ( key === 's' || key === 'S' ) && ! e.metaKey && ! e.ctrlKey && ! e.altKey && ! e.shiftKey ) {
			list = api.items();
			if ( ! list.length ) return;
			e.preventDefault();
			var i = focusIndex();
			if ( i < 0 || i >= list.length ) {
				i = 0;
				if ( typeof window.galleryKeyboardReorderFocus === 'function' ) {
					window.galleryKeyboardReorderFocus( list[ 0 ] );
				}
			}
			api.selection.toggle( idOf( list[ i ] ) );
			return;
		}

		// Shift + ← / →: the focus has already moved; select where it was and where it is.
		if ( e.shiftKey && ! e.metaKey && ! e.ctrlKey && ! e.altKey && ( key === 'ArrowLeft' || key === 'ArrowRight' ) ) {
			list = api.items();
			var now = focusIndex();
			var ids = [];
			if ( focusBefore >= 0 && list[ focusBefore ] ) ids.push( idOf( list[ focusBefore ] ) );
			if ( now >= 0 && list[ now ] ) ids.push( idOf( list[ now ] ) );
			if ( ids.length ) {
				anchorId = ids[ ids.length - 1 ];
				api.selection.add( ids );
			}
			return;
		}

		// ⌘A / Ctrl+A: every image in the grid (instead of the page's text).
		if ( ( key === 'a' || key === 'A' ) && ( e.metaKey || e.ctrlKey ) && ! e.altKey && ! e.shiftKey ) {
			list = api.items();
			if ( ! list.length ) return;
			e.preventDefault();
			api.selection.set( list.map( idOf ) );
			return;
		}

		if ( key === 'Escape' && openMargins ) {
			closeMargins( true );
			return;
		}

		if ( key === 'Escape' && api.selection.ids().length ) {
			api.selection.clear();
		}
	} );

	// ── Margins panel (per-image "Margins ▾") ───────────────
	// One open at a time; stays open while its buttons are used (each click
	// runs atachementChangeMargin() from entry-gallery.php), closes on Esc,
	// a click outside, or the toggle.

	var openMargins = null; // the .tge-item-layout whose panel is open

	function closeMargins( focusToggle ) {
		if ( ! openMargins ) return;
		var layout = openMargins;
		openMargins = null;
		var toggle = layout.querySelector( '.tge-margins-toggle' );
		var panel = layout.querySelector( '.tge-margins' );
		if ( panel ) panel.hidden = true;
		layout.classList.remove( 'is-open' );
		if ( toggle ) {
			toggle.setAttribute( 'aria-expanded', 'false' );
			if ( focusToggle ) toggle.focus();
		}
	}

	grid.addEventListener( 'click', function ( e ) {
		var toggle = e.target.closest ? e.target.closest( '.tge-margins-toggle' ) : null;
		if ( ! toggle || ! grid.contains( toggle ) ) return;
		e.preventDefault();
		e.stopPropagation();
		var layout = toggle.closest( '.tge-item-layout' );
		var wasOpen = openMargins === layout;
		closeMargins( false );
		if ( wasOpen || ! layout ) return;
		var panel = layout.querySelector( '.tge-margins' );
		if ( ! panel ) return;
		panel.hidden = false;
		layout.classList.add( 'is-open' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		openMargins = layout;
	}, true );

	document.addEventListener( 'mousedown', function ( e ) {
		if ( openMargins && ! openMargins.contains( e.target ) ) closeMargins( false );
	}, true );

	// No control on an image starts a Sortable drag.
	[ 'pointerdown', 'mousedown', 'touchstart' ].forEach( function ( type ) {
		grid.addEventListener( type, function ( e ) {
			if ( e.target.closest && e.target.closest( '.tge-item-actions, .tge-item-layout' ) ) {
				e.stopPropagation();
			}
		}, { capture: true, passive: true } );
	} );

	document.documentElement.classList.add( 'tge-on' );
	if ( window.matchMedia && window.matchMedia( '(hover: none)' ).matches ) {
		document.documentElement.classList.add( 'tge-touch' );
	}
} )();
