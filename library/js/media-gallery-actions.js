/**
 * Media modal gallery CTAs (post editor "Add media" modal).
 *
 * Adds two gallery-wide (post-level) actions to the modal's SECONDARY (left)
 * toolbar, distinct from the selection/insert buttons on the right:
 *
 * 1. "Order" — a mode dropdown (Chronological / Capture time (EXIF) / Random)
 *    plus an "Apply" button. With attachments selected, the button reads
 *    "Apply (N)" and reorders only those N in place; with none selected it
 *    reads "Apply to all" and reorders the whole gallery. The grid re-renders
 *    in the new order immediately.
 *
 * 2. "Delete hidden attachments (N)" — deletes the post's attachments flagged
 *    `remove_from_default_gallery`; N is the hidden count (not the selection).
 *    Guarded by a confirm dialog.
 *
 * Self-contained (its own frame-wiring + cache helpers) so it never depends on
 * the sibling CTA scripts' load order.
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.galleryMediaActions ) {
		return;
	}

	var cfg       = window.galleryMediaActions;
	var i18n      = cfg.i18n || {};
	var WRAP_ID   = 'tiagsspace-gallery-actions';
	var APPLY_ID  = 'tiagsspace-gallery-apply';
	var MODE_ID   = 'tiagsspace-gallery-mode';
	var DELETE_ID = 'tiagsspace-gallery-delete-hidden';

	// Force the next modal open to refetch the library (attachments changed).
	var pendingRefresh = false;

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

	function getSelection( frame ) {
		if ( frame && frame.state && frame.state() && frame.state().get ) {
			return frame.state().get( 'selection' );
		}
		return null;
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
			var state   = frame && frame.state && frame.state();
			var library = state && state.get && state.get( 'library' );
			if ( library && library.props && library.props.set ) {
				library.props.set( 'orderby', 'menuOrder' );
				library.props.set( 'order', 'ASC' );
				library.props.set( 'ignore', ( + new Date() ) );
			}
		} catch ( e ) {}
	}

	function forceLibraryRefresh( frame ) {
		try {
			var state   = frame && frame.state && frame.state();
			var library = state && state.get && state.get( 'library' );
			if ( library && library.props && library.props.set ) {
				library.props.set( 'ignore', ( + new Date() ) );
			}
		} catch ( e ) {}
	}

	/* ---------------------------------------------------------------------
	 * Actions
	 * ------------------------------------------------------------------ */

	function performReorder( frame, $mode, $apply ) {
		var postId = getSourcePostId();
		if ( ! postId ) {
			window.alert( 'Could not determine the current post.' );
			return;
		}

		var selection = getSelection( frame );
		var ids       = getOrderedSelectionIds( selection );

		$apply.prop( 'disabled', true );

		$.post( cfg.ajaxurl, {
			action:  'gallery_reorder_attachments',
			_nonce:  cfg.reorderNonce,
			post_id: postId,
			mode:    $mode.val(),
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
					var msg = res && res.data && res.data.message ? res.data.message : ( i18n.reorderError || 'Could not reorder the gallery.' );
					window.alert( msg );
				}
			} )
			.fail( function () {
				window.alert( i18n.reorderError || 'Could not reorder the gallery.' );
			} )
			.always( function () {
				$apply.prop( 'disabled', false );
			} );
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
		$delete.text( format( i18n.deleteHidden || 'Delete hidden attachments (%d)', count ) );
		$delete.prop( 'disabled', ! count );
	}

	function performDeleteHidden( frame, $delete ) {
		var postId = getSourcePostId();
		var count  = parseInt( $delete.data( 'count' ), 10 ) || 0;
		if ( ! postId || ! count ) {
			return;
		}

		if ( ! window.confirm( format( i18n.confirmDelete || 'Permanently delete %d hidden attachment(s)? This cannot be undone.', count ) ) ) {
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
					var msg = res && res.data && res.data.message ? res.data.message : ( i18n.deleteError || 'Could not delete the hidden attachments.' );
					window.alert( msg );
					$delete.prop( 'disabled', false );
				}
			} )
			.fail( function () {
				window.alert( i18n.deleteError || 'Could not delete the hidden attachments.' );
				$delete.prop( 'disabled', false );
			} );
	}

	/* ---------------------------------------------------------------------
	 * Injection
	 * ------------------------------------------------------------------ */

	function injectControls( frame ) {
		// The primary (right) toolbar — same home as the other CTAs. The
		// secondary (left) toolbar can't be used: WP renders its own absolutely
		// positioned selection strip there once anything is selected, which
		// overlaps injected controls.
		var $bar = $( '.media-frame-toolbar .media-toolbar-primary' );

		if ( ! $bar.length || document.getElementById( WRAP_ID ) ) {
			return;
		}

		// One floated group, full toolbar height with its contents centered, so
		// the select + buttons line up with the native (vertically centered)
		// media-buttons regardless of the toolbar's exact metrics.
		var $wrap = $( '<span>', { id: WRAP_ID } ).css( {
			'float': 'right',
			height: '100%',
			display: 'flex',
			'align-items': 'center',
			gap: '6px',
			'margin-left': '8px'
		} );

		var $mode = $( '<select>', { id: MODE_ID, 'class': 'attachment-filters' } );
		$mode.append( $( '<option>', { value: 'chronological' } ).text( i18n.chronological || 'Chronological' ) );
		$mode.append( $( '<option>', { value: 'capture_time', selected: 'selected' } ).text( i18n.captureTime || 'Capture time (EXIF)' ) );
		$mode.append( $( '<option>', { value: 'random' } ).text( i18n.random || 'Random' ) );

		var $apply = $( '<button>', { type: 'button', id: APPLY_ID, 'class': 'button media-button button-large' } ).text( i18n.applyAll || 'Apply to all' );

		var $delete = $( '<button>', { type: 'button', id: DELETE_ID, 'class': 'button media-button button-large' } ).text( format( i18n.deleteHidden || 'Delete hidden attachments (%d)', 0 ) );
		$delete.css( 'color', '#b32d2e' ).prop( 'disabled', true );

		$apply.on( 'click', function ( e ) {
			e.preventDefault();
			performReorder( frame, $mode, $apply );
		} );

		$delete.on( 'click', function ( e ) {
			e.preventDefault();
			performDeleteHidden( frame, $delete );
		} );

		$wrap.append( $mode, $apply, $delete );
		$bar.append( $wrap );

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

		// Live "Apply (N)" / "Apply to all" based on the current selection.
		var selection = getSelection( frame );
		function syncApply() {
			var n = selection ? selection.length : 0;
			$apply.text( n ? format( i18n.applyN || 'Apply (%d)', n ) : ( i18n.applyAll || 'Apply to all' ) );
		}
		if ( selection ) {
			selection.on( 'add remove reset', syncApply );
		}
		syncApply();

		refreshHiddenCount( $delete );
	}

	// Hook every media frame's open event (mirrors the sibling CTA scripts).
	var originalMedia = wp.media;
	wp.media = function () {
		var frame = originalMedia.apply( this, arguments );
		if ( frame && frame.on ) {
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
		}
		return frame;
	};
	$.extend( wp.media, originalMedia );
} )( jQuery );
