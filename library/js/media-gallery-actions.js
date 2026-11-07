/**
 * Media modal gallery CTAs (post editor "Add media" modal).
 *
 * Adds one right-floated group to the modal's PRIMARY (right) toolbar, next to
 * the other theme CTAs:
 *
 * 1. "Select ▾" — a menu that sets the frame's selection from the loaded
 *    library: All / None / Portrait / Landscape / Images / Videos / Audio, each
 *    with a live count. An item REPLACES the selection; Shift-click ADDS to it.
 *    Rendered only on frames that allow multiple selection.
 *
 * 2. "Reorder ▾" — a menu whose header names the scope (all N attachments, or
 *    the N selected, which keep their slots) and whose items are the order
 *    modes: Capture time (EXIF) / Upload date / Random. One click orders.
 *
 * 3. "Delete hidden attachments (N)" — deletes the post's attachments flagged
 *    `remove_from_default_gallery`; N is the hidden count (not the selection).
 *    Guarded by a confirm dialog.
 *
 * Plugin safety: the same MediaFrame.Select frames are opened by other
 * plugins (Instagram, Tumblr, …). Every DOM query is scoped to the frame's
 * own element, the selection is only changed through the frame's own
 * Selection collection with models from its own library (what thumbnail
 * clicks do), nothing on wp.media.* is patched beyond the frame-constructor
 * hook, and the code injects nothing when the toolbar, state or library can't
 * be resolved.
 *
 * Self-contained (its own frame-wiring + cache helpers) so it never depends on
 * the sibling CTA scripts' load order.
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.galleryMediaActions ) {
		return;
	}

	var cfg        = window.galleryMediaActions;
	var i18n       = cfg.i18n || {};
	var WRAP_ID    = 'tiagsspace-gallery-actions';
	var SELECT_ID  = 'tiagsspace-gallery-select';
	var REORDER_ID = 'tiagsspace-gallery-reorder';
	var DELETE_ID  = 'tiagsspace-gallery-delete-hidden';
	var STYLE_ID   = 'tiagsspace-media-menu-styles';
	var MENU_CLASS = 'tiagsspace-media-menu';
	var MAX_PAGES  = 50;

	// Force the next modal open to refetch the library (attachments changed).
	var pendingRefresh = false;

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	function format( str, value ) {
		return String( str ).replace( /%d/, value );
	}

	/**
	 * Resolve the post being edited.
	 */
	function getSourcePostId() {
		if ( wp.media.view.settings.post && wp.media.view.settings.post.id ) {
			return parseInt( wp.media.view.settings.post.id, 10 );
		}
		if ( window.wp.data && wp.data.select( 'core/editor' ) ) {
			var id = wp.data.select( 'core/editor' ).getCurrentPostId();
			if ( id ) {
				return parseInt( id, 10 );
			}
		}
		return 0;
	}

	function getState( frame ) {
		try {
			return frame && frame.state && frame.state();
		} catch ( e ) {
			return null;
		}
	}

	function getSelection( frame ) {
		var state = getState( frame );
		return state && state.get ? state.get( 'selection' ) : null;
	}

	function getLibrary( frame ) {
		var state = getState( frame );
		return state && state.get ? state.get( 'library' ) : null;
	}

	/**
	 * Selected attachment IDs sorted by current gallery order (menu_order).
	 */
	function getOrderedSelectionIds( selection ) {
		if ( ! selection || ! selection.length ) {
			return [];
		}
		return selection.models
			.slice()
			.sort( function ( a, b ) {
				return ( a.get( 'menuOrder' ) || 0 ) - ( b.get( 'menuOrder' ) || 0 );
			} )
			.map( function ( model ) {
				return model.get( 'id' );
			} );
	}

	function flushMediaCaches() {
		try {
			if ( wp.media.model && wp.media.model.Query ) {
				wp.media.model.Query.queries = [];
			}
			var all = wp.media.model && wp.media.model.Attachment && wp.media.model.Attachment.all;
			if ( all && all.reset ) {
				all.reset();
			}
		} catch ( e ) {}
	}

	/**
	 * Re-render the frame's library, sorted by menu_order so a reorder is visible.
	 */
	function refreshLibraryByMenuOrder( frame ) {
		try {
			var library = getLibrary( frame );
			if ( library && library.props && library.props.set ) {
				library.props.set( 'orderby', 'menuOrder' );
				library.props.set( 'order', 'ASC' );
				library.props.set( 'ignore', ( + new Date() ) );
			}
		} catch ( e ) {}
	}

	function forceLibraryRefresh( frame ) {
		try {
			var library = getLibrary( frame );
			if ( library && library.props && library.props.set ) {
				library.props.set( 'ignore', ( + new Date() ) );
			}
		} catch ( e ) {}
	}

	/**
	 * Page the library with more() until it has everything (capped), so counts
	 * and "select all" cover the whole post, not just the first page.
	 */
	function ensureLibraryLoaded( library ) {
		var d     = $.Deferred();
		var pages = 0;

		function step() {
			var hasMore = false;
			try {
				hasMore = library && library.hasMore && library.hasMore();
			} catch ( e ) {}
			if ( ! hasMore || pages >= MAX_PAGES ) {
				d.resolve();
				return;
			}
			pages++;
			var p = null;
			try {
				p = library.more();
			} catch ( e ) {}
			if ( p && p.then ) {
				p.then( step, function () { d.resolve(); } );
			} else {
				d.resolve();
			}
		}
		step();
		return d.promise();
	}

	function libraryTotal( library ) {
		if ( ! library ) {
			return null;
		}
		try {
			if ( library.hasMore && ! library.hasMore() ) {
				return library.length;
			}
			var total = library.getTotalAttachments ? library.getTotalAttachments() : 0;
			return total || library.length || null;
		} catch ( e ) {
			return null;
		}
	}

	/* ---------------------------------------------------------------------
	 * Menu component (shared by Select ▾ and Reorder ▾)
	 * ------------------------------------------------------------------ */

	function injectStyles() {
		if ( document.getElementById( STYLE_ID ) ) {
			return;
		}
		var css = ''
			+ '.' + MENU_CLASS + '{position:absolute;bottom:calc(100% - 4px);min-width:220px;margin:0;padding:6px 0;'
			+ 'background:#fff;color:#1d2327;border:1px solid #c3c4c7;border-radius:4px;'
			+ 'box-shadow:0 6px 18px rgba(0,0,0,.18);z-index:200;text-align:left;font-size:13px;line-height:1.4}'
			+ '.' + MENU_CLASS + '[hidden]{display:none}'
			+ '.' + MENU_CLASS + '-header{padding:6px 14px 8px;color:#646970;font-size:12px;white-space:nowrap}'
			+ '.' + MENU_CLASS + '-sep{height:0;margin:4px 0;border-top:1px solid #dcdcde}'
			+ '.' + MENU_CLASS + '-item{display:flex;align-items:center;justify-content:space-between;gap:16px;width:100%;box-sizing:border-box;min-height:0;'
			+ 'margin:0;padding:8px 14px;border:0;background:none;color:inherit;font:inherit;text-align:left;cursor:pointer;white-space:nowrap}'
			+ '.' + MENU_CLASS + '-item:hover,.' + MENU_CLASS + '-item:focus{background:#f0f0f1;outline:none}'
			+ '.' + MENU_CLASS + '-item:focus-visible{box-shadow:inset 0 0 0 2px #2271b1}'
			+ '.' + MENU_CLASS + '-item[disabled]{opacity:.5;cursor:default;background:none}'
			+ '.' + MENU_CLASS + '-count{color:#646970;font-variant-numeric:tabular-nums}'
			+ '.' + MENU_CLASS + '-caret{display:inline-block;margin-left:4px;font-size:10px;line-height:1}'
			// Dark admin skin (dark-mode-for-wp-dashboard keys on body.dark-mode and
			// exposes --dm-* tokens); its own rules never reach injected elements.
			+ 'body.dark-mode .' + MENU_CLASS + '{background:var(--dm-bg-elevated,#252a34);color:var(--dm-text-primary,#eceff4);border-color:var(--dm-border,#3b4252);box-shadow:var(--dm-shadow-lg,0 8px 24px rgba(0,0,0,.5))}'
			+ 'body.dark-mode .' + MENU_CLASS + '-item:hover,body.dark-mode .' + MENU_CLASS + '-item:focus{background:var(--dm-bg-hover,#3d4555)}'
			+ 'body.dark-mode .' + MENU_CLASS + '-item:focus-visible{box-shadow:inset 0 0 0 2px var(--dm-accent,#6b9cff)}'
			+ 'body.dark-mode .' + MENU_CLASS + '-header,body.dark-mode .' + MENU_CLASS + '-count{color:var(--dm-text-secondary,#a0aab6)}'
			+ 'body.dark-mode .' + MENU_CLASS + '-sep{border-color:var(--dm-border,#3b4252)}';
		$( '<style>', { id: STYLE_ID } ).text( css ).appendTo( document.head );
	}

	// At most one open menu at a time, across frames.
	var openMenu = null;

	/**
	 * Build a popover menu anchored above $button inside $toolbar.
	 *
	 * items: [{ header: 'text' } | { separator: true } |
	 *         { label, count, disabled, onClick(e) }]
	 * opts.onOpen(): optional; returns the items array or a promise of one
	 *                (the menu shows a "loading" header meanwhile).
	 */
	function buildMenu( $toolbar, $button, opts ) {
		injectStyles();

		var $menu = $( '<div>', { 'class': MENU_CLASS, role: 'menu', hidden: true } );
		$menu.attr( 'aria-labelledby', $button.attr( 'id' ) );
		$toolbar.append( $menu );
		$button.attr( { 'aria-haspopup': 'menu', 'aria-expanded': 'false' } );

		var api = {};

		function render( items ) {
			$menu.empty();
			$.each( items || [], function ( _, item ) {
				if ( item.header ) {
					$menu.append( $( '<div>', { 'class': MENU_CLASS + '-header' } ).text( item.header ) );
					return;
				}
				if ( item.separator ) {
					$menu.append( $( '<div>', { 'class': MENU_CLASS + '-sep', role: 'separator' } ) );
					return;
				}
				var $item = $( '<button>', { type: 'button', 'class': MENU_CLASS + '-item', role: 'menuitem' } );
				$item.append( $( '<span>' ).text( item.label ) );
				if ( typeof item.count === 'number' ) {
					$item.append( $( '<span>', { 'class': MENU_CLASS + '-count' } ).text( item.count ) );
				}
				if ( item.disabled ) {
					$item.prop( 'disabled', true );
				}
				$item.on( 'click', function ( e ) {
					e.preventDefault();
					api.close();
					if ( item.onClick ) {
						item.onClick( e );
					}
				} );
				$menu.append( $item );
			} );
		}

		function position() {
			try {
				var tb = $toolbar[ 0 ].getBoundingClientRect();
				var bb = $button[ 0 ].getBoundingClientRect();
				$menu.css( 'right', Math.max( 0, tb.right - bb.right ) + 'px' );
			} catch ( e ) {}
		}

		function focusItem( delta, absolute ) {
			var $items = $menu.find( '.' + MENU_CLASS + '-item:not([disabled])' );
			if ( ! $items.length ) {
				return;
			}
			var idx = $items.index( document.activeElement );
			if ( typeof absolute === 'number' ) {
				idx = absolute < 0 ? $items.length - 1 : absolute;
			} else {
				idx = idx < 0 ? ( delta > 0 ? 0 : $items.length - 1 ) : ( idx + delta + $items.length ) % $items.length;
			}
			$items.eq( idx ).trigger( 'focus' );
		}

		function onDocMouseDown( e ) {
			if ( ! $menu[ 0 ].contains( e.target ) && ! $button[ 0 ].contains( e.target ) ) {
				api.close();
			}
		}

		$menu.on( 'keydown', function ( e ) {
			switch ( e.key ) {
				case 'Escape':
					e.preventDefault();
					e.stopPropagation(); // keep the media modal open
					api.close();
					$button.trigger( 'focus' );
					break;
				case 'ArrowDown':
					e.preventDefault();
					e.stopPropagation();
					focusItem( 1 );
					break;
				case 'ArrowUp':
					e.preventDefault();
					e.stopPropagation();
					focusItem( -1 );
					break;
				case 'Home':
					e.preventDefault();
					e.stopPropagation();
					focusItem( 0, 0 );
					break;
				case 'End':
					e.preventDefault();
					e.stopPropagation();
					focusItem( 0, -1 );
					break;
				case 'Tab':
					api.close();
					break;
			}
		} );

		api.isOpen = function () {
			return ! $menu.prop( 'hidden' );
		};

		api.close = function () {
			if ( ! api.isOpen() ) {
				return;
			}
			$menu.prop( 'hidden', true );
			$button.attr( 'aria-expanded', 'false' );
			$( document ).off( 'mousedown.tiagsspaceMenu', onDocMouseDown );
			if ( openMenu === api ) {
				openMenu = null;
			}
		};

		api.open = function () {
			if ( openMenu && openMenu !== api ) {
				openMenu.close();
			}
			openMenu = api;
			var result = opts.onOpen ? opts.onOpen() : [];
			if ( result && result.then ) {
				render( [ { header: opts.loadingText || 'Loading…' } ] );
				result.then( render );
			} else {
				render( result );
			}
			$menu.prop( 'hidden', false );
			$button.attr( 'aria-expanded', 'true' );
			position();
			$( document ).on( 'mousedown.tiagsspaceMenu', onDocMouseDown );
			focusItem( 1 );
		};

		api.toggle = function () {
			if ( api.isOpen() ) {
				api.close();
			} else {
				api.open();
			}
		};

		api.setItems = render;

		$button.on( 'click', function ( e ) {
			e.preventDefault();
			api.toggle();
		} );

		$button.on( 'keydown', function ( e ) {
			if ( e.key === 'ArrowUp' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				e.stopPropagation();
				if ( ! api.isOpen() ) {
					api.open();
				}
			}
		} );

		return api;
	}

	function caretLabel( $button, text ) {
		$button.empty().append( document.createTextNode( text + ' ' ) ).append( $( '<span>', { 'class': MENU_CLASS + '-caret', 'aria-hidden': 'true' } ).text( '▾' ) );
	}

	/* ---------------------------------------------------------------------
	 * Actions
	 * ------------------------------------------------------------------ */

	function performReorder( frame, mode, $button ) {
		var postId = getSourcePostId();
		if ( ! postId ) {
			window.alert( 'Could not determine the current post.' );
			return;
		}

		var selection = getSelection( frame );
		var ids       = getOrderedSelectionIds( selection );

		$button.prop( 'disabled', true );
		caretLabel( $button, t( 'reordering', 'Reordering…' ) );

		$.post( cfg.ajaxurl, {
			action:  'gallery_reorder_attachments',
			_nonce:  cfg.reorderNonce,
			post_id: postId,
			mode:    mode,
			ids:     ids
		} )
			.done( function ( res ) {
				if ( res && res.success ) {
					if ( selection ) {
						selection.reset();
					}
					flushMediaCaches();
					refreshLibraryByMenuOrder( frame );
				} else {
					var msg = res && res.data && res.data.message ? res.data.message : t( 'reorderError', 'Could not reorder the gallery.' );
					window.alert( msg );
				}
			} )
			.fail( function () {
				window.alert( t( 'reorderError', 'Could not reorder the gallery.' ) );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
				caretLabel( $button, t( 'reorder', 'Reorder' ) );
			} );
	}

	/**
	 * Replace (or, additive, extend) the frame's selection with the library
	 * models matching `predicate`.
	 */
	function applySelection( frame, predicate, additive ) {
		var selection = getSelection( frame );
		var library   = getLibrary( frame );
		if ( ! selection || ! library ) {
			return;
		}
		var models = library.filter( function ( model ) {
			return predicate( model );
		} );
		if ( additive ) {
			selection.add( models );
		} else {
			selection.reset( models );
		}
	}

	var predicates = {
		all: function () {
			return true;
		},
		portrait: function ( m ) {
			var w = m.get( 'width' ), h = m.get( 'height' );
			return w > 0 && h > 0 && h > w;
		},
		landscape: function ( m ) {
			var w = m.get( 'width' ), h = m.get( 'height' );
			return w > 0 && h > 0 && w > h;
		},
		image: function ( m ) {
			return m.get( 'type' ) === 'image';
		},
		video: function ( m ) {
			return m.get( 'type' ) === 'video';
		},
		audio: function ( m ) {
			return m.get( 'type' ) === 'audio';
		}
	};

	function collectCounts( library ) {
		var counts = {};
		$.each( predicates, function ( key, fn ) {
			counts[ key ] = library ? library.filter( fn ).length : 0;
		} );
		return counts;
	}

	function refreshHiddenCount( $delete ) {
		var postId = getSourcePostId();
		if ( ! postId ) {
			return;
		}
		$.post( cfg.ajaxurl, {
			action:  'gallery_count_hidden_attachments',
			_nonce:  cfg.hiddenNonce,
			post_id: postId
		} ).done( function ( res ) {
			var count = ( res && res.success && res.data ) ? ( res.data.count || 0 ) : 0;
			setDeleteCount( $delete, count );
		} );
	}

	function setDeleteCount( $delete, count ) {
		$delete.data( 'count', count );
		$delete.text( format( t( 'deleteHidden', 'Delete hidden attachments (%d)' ), count ) );
		$delete.prop( 'disabled', ! count );
	}

	function performDeleteHidden( frame, $delete ) {
		var postId = getSourcePostId();
		var count  = parseInt( $delete.data( 'count' ), 10 ) || 0;
		if ( ! postId || ! count ) {
			return;
		}

		if ( ! window.confirm( format( t( 'confirmDelete', 'Permanently delete %d hidden attachment(s)? This cannot be undone.' ), count ) ) ) {
			return;
		}

		$delete.prop( 'disabled', true );

		$.post( cfg.ajaxurl, {
			action:  'gallery_delete_hidden_attachments',
			_nonce:  cfg.hiddenNonce,
			post_id: postId
		} )
			.done( function ( res ) {
				if ( res && res.success ) {
					flushMediaCaches();
					forceLibraryRefresh( frame );
					pendingRefresh = true;
					setDeleteCount( $delete, 0 );
				} else {
					var msg = res && res.data && res.data.message ? res.data.message : t( 'deleteError', 'Could not delete the hidden attachments.' );
					window.alert( msg );
					$delete.prop( 'disabled', false );
				}
			} )
			.fail( function () {
				window.alert( t( 'deleteError', 'Could not delete the hidden attachments.' ) );
				$delete.prop( 'disabled', false );
			} );
	}

	/* ---------------------------------------------------------------------
	 * Menus
	 * ------------------------------------------------------------------ */

	function buildSelectMenu( frame, $toolbar, $button ) {
		var selection = getSelection( frame );

		function items() {
			var library = getLibrary( frame );
			var counts  = collectCounts( library );
			var n       = selection ? selection.length : 0;

			function entry( key, label ) {
				return {
					label:    label,
					count:    counts[ key ],
					disabled: ! counts[ key ],
					onClick:  function ( e ) {
						applySelection( frame, predicates[ key ], !! e.shiftKey );
					}
				};
			}

			return [
				{ header: t( 'shiftAdds', 'Shift-click adds to the selection' ) },
				entry( 'all', t( 'selectAll', 'All' ) ),
				{
					label:    t( 'selectNone', 'None' ),
					disabled: ! n,
					onClick:  function () {
						if ( selection ) {
							selection.reset();
						}
					}
				},
				{ separator: true },
				entry( 'portrait', t( 'portrait', 'Portrait' ) ),
				entry( 'landscape', t( 'landscape', 'Landscape' ) ),
				{ separator: true },
				entry( 'image', t( 'images', 'Images' ) ),
				entry( 'video', t( 'videos', 'Videos' ) ),
				entry( 'audio', t( 'audio', 'Audio' ) )
			];
		}

		var menu = buildMenu( $toolbar, $button, {
			loadingText: t( 'loadingAll', 'Loading all attachments…' ),
			onOpen: function () {
				var library = getLibrary( frame );
				var hasMore = false;
				try {
					hasMore = library && library.hasMore && library.hasMore();
				} catch ( e ) {}
				if ( hasMore ) {
					return ensureLibraryLoaded( library ).then( items );
				}
				return items();
			}
		} );

		function syncLabel() {
			var n = selection ? selection.length : 0;
			caretLabel( $button, n ? format( t( 'selectN', 'Select (%d)' ), n ) : t( 'select', 'Select' ) );
		}
		if ( selection ) {
			selection.on( 'add remove reset', syncLabel );
		}
		syncLabel();

		return menu;
	}

	function buildReorderMenu( frame, $toolbar, $button ) {
		var selection = getSelection( frame );

		function items() {
			var n     = selection ? selection.length : 0;
			var total = libraryTotal( getLibrary( frame ) );
			var list  = [];

			if ( n > 1 ) {
				list.push( { header: format( t( 'reorderSelectedN', 'Reorder the %d selected' ), n ) } );
				list.push( { header: t( 'reorderKeepSlots', '(they keep their slots)' ) } );
			} else if ( n === 1 ) {
				list.push( { header: t( 'reorderOneSelected', 'One item selected: nothing to reorder' ) } );
			} else {
				list.push( { header: total ? format( t( 'reorderAllN', 'Reorder all %d attachments' ), total ) : t( 'reorderAll', 'Reorder all attachments' ) } );
			}
			list.push( { separator: true } );

			var modes = [
				[ 'capture_time', t( 'captureTime', 'Capture time (EXIF)' ) ],
				[ 'chronological', t( 'chronological', 'Upload date' ) ],
				[ 'random', t( 'random', 'Random' ) ]
			];
			$.each( modes, function ( _, pair ) {
				list.push( {
					label:    pair[ 1 ],
					disabled: n === 1,
					onClick:  function () {
						performReorder( frame, pair[ 0 ], $button );
					}
				} );
			} );
			return list;
		}

		caretLabel( $button, t( 'reorder', 'Reorder' ) );

		return buildMenu( $toolbar, $button, { onOpen: items } );
	}

	/* ---------------------------------------------------------------------
	 * Injection
	 * ------------------------------------------------------------------ */

	function injectControls( frame ) {
		// Scoped to THIS frame: other plugins open their own MediaFrame.Select
		// frames, sometimes on top of the editor's, and each gets its own group.
		var $root = frame && frame.$el ? frame.$el : null;
		if ( ! $root || ! $root.length ) {
			return;
		}

		// The primary (right) toolbar — same home as the other CTAs. The
		// secondary (left) toolbar can't be used: WP renders its own absolutely
		// positioned selection strip there once anything is selected, which
		// overlaps injected controls.
		var $toolbar = $root.find( '.media-frame-toolbar .media-toolbar' ).first();
		var $bar     = $toolbar.find( '.media-toolbar-primary' ).first();

		if ( ! $bar.length || $root.find( '#' + WRAP_ID ).length ) {
			return;
		}

		var selection = getSelection( frame );
		var state     = getState( frame );
		if ( ! selection || ! state ) {
			return;
		}

		// One floated group, full toolbar height with its contents centered, so
		// the buttons line up with the native (vertically centered)
		// media-buttons regardless of the toolbar's exact metrics.
		var $wrap = $( '<span>', { id: WRAP_ID } ).css( {
			'float': 'right',
			height: '100%',
			display: 'flex',
			'align-items': 'center',
			gap: '6px',
			'margin-left': '8px'
		} );

		var $delete = $( '<button>', { type: 'button', id: DELETE_ID, 'class': 'button media-button button-large' } ).text( format( t( 'deleteHidden', 'Delete hidden attachments (%d)' ), 0 ) );
		$delete.css( 'color', '#b32d2e' ).prop( 'disabled', true );
		$delete.on( 'click', function ( e ) {
			e.preventDefault();
			performDeleteHidden( frame, $delete );
		} );

		var $reorder = $( '<button>', { type: 'button', id: REORDER_ID, 'class': 'button media-button button-large' } );

		// Select ▾ only where the frame lets the user pick more than one item
		// (single pickers — featured image, plugin pickers — keep their own UX).
		var multiple = false;
		try {
			multiple = !! state.get( 'multiple' );
		} catch ( e ) {}
		var $select = multiple ? $( '<button>', { type: 'button', id: SELECT_ID, 'class': 'button media-button button-large' } ) : null;

		if ( $select ) {
			$wrap.append( $select );
		}
		$wrap.append( $reorder, $delete );
		$bar.append( $wrap );

		if ( $select ) {
			buildSelectMenu( frame, $toolbar, $select );
		}
		buildReorderMenu( frame, $toolbar, $reorder );

		// Belt-and-suspenders: nudge our group's vertical center onto a native
		// toolbar button's center, so it lines up whatever the toolbar metrics.
		try {
			var native = $bar.children( '.media-button' ).filter( function () {
				return ! $wrap[ 0 ].contains( this );
			} )[ 0 ];
			if ( native ) {
				var nb = native.getBoundingClientRect();
				var wb = $wrap[ 0 ].getBoundingClientRect();
				var delta = ( nb.top + nb.height / 2 ) - ( wb.top + wb.height / 2 );
				if ( Math.abs( delta ) > 1 ) {
					$wrap.css( 'margin-top', ( parseFloat( $wrap.css( 'marginTop' ) ) || 0 ) + delta + 'px' );
				}
			}
		} catch ( e ) {}

		refreshHiddenCount( $delete );
	}

	/**
	 * Wire a frame's open / content:render events.
	 *
	 * WordPress 7.1+ block editor (@wordpress/media-utils) builds
	 * MediaFrame.Select / MediaFrame.Post subclasses directly instead of calling
	 * wp.media(), so a wp.media() wrapper alone never sees the Image block's
	 * modal. Hook the frame constructor (every frame class extends Select) and
	 * keep the wp.media() wrapper for anything else; the flag prevents double
	 * wiring when both paths run for the same frame.
	 */
	function wireFrame( frame ) {
		if ( ! frame || ! frame.on || frame.__tiagsspaceGalleryActionsWired ) {
			return;
		}
		frame.__tiagsspaceGalleryActionsWired = true;
		frame.on( 'open', function () {
			setTimeout( function () {
				injectControls( frame );
				if ( pendingRefresh ) {
					pendingRefresh = false;
					forceLibraryRefresh( frame );
				}
			}, 0 );
		} );
		frame.on( 'content:render', function () {
			setTimeout( function () {
				injectControls( frame );
			}, 0 );
		} );
		frame.on( 'close', function () {
			if ( openMenu ) {
				openMenu.close();
			}
		} );
	}

	var SelectFrame      = wp.media.view.MediaFrame.Select;
	var originalSelectInit = SelectFrame.prototype.initialize;
	SelectFrame.prototype.initialize = function () {
		originalSelectInit.apply( this, arguments );
		wireFrame( this );
	};

	var originalMedia = wp.media;
	wp.media = function () {
		var frame = originalMedia.apply( this, arguments );
		wireFrame( frame );
		return frame;
	};
	$.extend( wp.media, originalMedia );
} )( jQuery );
