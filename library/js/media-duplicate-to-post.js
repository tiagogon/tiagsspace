/**
 * Media modal call-to-action buttons (post editor "Add media" modal).
 *
 * Hosts two bulk actions on the selected attachments, added to the modal's
 * primary (bottom-right) toolbar:
 *
 * 1. "Add to a duplicated post" — duplicates the current post, re-parents the
 *    selected media onto the copy (keeping gallery order; re-parenting moves
 *    them OFF the original, since an attachment has one parent), opens the new
 *    draft in a new tab.
 *
 * 2. "Delete permanently" — permanently deletes the selected attachments
 *    (WordPress media has no trash). Guarded by a confirm dialog.
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.tiagsspaceMediaDuplicate ) {
		return;
	}

	var cfg            = window.tiagsspaceMediaDuplicate;
	var BUTTON_ID      = 'tiagsspace-duplicate-to-post';
	var DELETE_BTN_ID  = 'tiagsspace-delete-attachments';

	// Set after a successful duplicate so the next modal open force-refreshes
	// the library (the selected media were re-parented away from this post).
	var pendingRefresh = false;

	/**
	 * Resolve the post being edited.
	 */
	function getSourcePostId( frame ) {
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

	/**
	 * Current selection collection of the frame (or null).
	 */
	function getSelection( frame ) {
		if ( frame && frame.state && frame.state() && frame.state().get ) {
			return frame.state().get( 'selection' );
		}
		return null;
	}

	/**
	 * Invalidate WP's global media query cache.
	 */
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
	 * Force a frame's library to re-fetch from the server.
	 *
	 * The reused frame keeps a library that mirrors a cached Query instance;
	 * library.more() won't re-hit the server once that Query is "complete", so
	 * after media is re-parented away the grid stays empty. Bumping a throwaway
	 * prop changes the query cache key, which makes the library re-mirror a
	 * fresh Query and pull the post's current media from the server.
	 */
	function forceLibraryRefresh( frame ) {
		try {
			var state   = frame && frame.state && frame.state();
			var library = state && state.get && state.get( 'library' );
			if ( library && library.props && library.props.set ) {
				library.props.set( 'ignore', ( + new Date() ) );
			}
		} catch ( e ) {}
	}

	function performDuplicate( frame, $button ) {
		var selection = getSelection( frame );
		if ( ! selection || ! selection.length ) {
			return;
		}

		var sourceId = getSourcePostId( frame );
		if ( ! sourceId ) {
			window.alert( 'Could not determine the current post to duplicate.' );
			return;
		}

		// Selected attachments sorted by their existing gallery order (menu_order),
		// so the copy keeps the original order rather than the click order.
		var ids = selection.models
			.slice()
			.sort( function ( a, b ) {
				return ( a.get( 'menuOrder' ) || 0 ) - ( b.get( 'menuOrder' ) || 0 );
			} )
			.map( function ( model ) {
				return model.get( 'id' );
			} );

		// Open the tab synchronously inside the click handler to avoid popup
		// blockers; its URL is filled in once the AJAX call returns.
		var tab = window.open( 'about:blank', '_blank' );

		$button.prop( 'disabled', true );

		$.post( cfg.ajaxurl, {
			action:         'tiagsspace_duplicate_to_post',
			_nonce:         cfg.nonce,
			source_id:      sourceId,
			attachment_ids: ids
		} )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.edit_url ) {
					if ( tab ) {
						tab.location = res.data.edit_url;
					} else {
						window.location.href = res.data.edit_url;
					}
					// The selected media just left this post (re-parented onto the
					// copy). Clear the stale selection and flush WP's media caches
					// so reopening the modal shows the post's current media without
					// a manual page refresh.
					if ( selection ) {
						selection.reset();
					}
					flushMediaCaches();
					pendingRefresh = true;
					if ( frame && frame.close ) {
						frame.close();
					}
				} else {
					if ( tab ) {
						tab.close();
					}
					var msg = res && res.data && res.data.message ? res.data.message : 'Could not duplicate the post.';
					window.alert( msg );
				}
			} )
			.fail( function () {
				if ( tab ) {
					tab.close();
				}
				window.alert( 'Could not duplicate the post.' );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
			} );
	}

	function performDelete( frame, $button ) {
		var selection = getSelection( frame );
		if ( ! selection || ! selection.length ) {
			return;
		}

		var ids = selection.models.map( function ( model ) {
			return model.get( 'id' );
		} );

		if ( ! window.confirm( 'Permanently delete ' + ids.length + ' media file(s)? This cannot be undone.' ) ) {
			return;
		}

		$button.prop( 'disabled', true );

		$.post( cfg.ajaxurl, {
			action:         'tiagsspace_delete_attachments',
			_nonce:         cfg.deleteNonce,
			attachment_ids: ids
		} )
			.done( function ( res ) {
				if ( res && res.success ) {
					// Drop the deleted items from the grid without a manual refresh.
					selection.reset();
					flushMediaCaches();
					forceLibraryRefresh( frame );
				} else {
					var msg = res && res.data && res.data.message ? res.data.message : 'Could not delete the selected media.';
					window.alert( msg );
				}
			} )
			.fail( function () {
				window.alert( 'Could not delete the selected media.' );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
			} );
	}

	/**
	 * Inject the CTA buttons into the modal's primary toolbar and wire them to
	 * the current selection state.
	 */
	function injectButton( frame ) {
		// Primary (right-hand) toolbar area that holds "Insert into post".
		var $primary = $( '.media-frame-toolbar .media-toolbar-primary' );

		if ( ! $primary.length || document.getElementById( BUTTON_ID ) ) {
			return;
		}

		var $button = $( '<button>', {
			type:  'button',
			id:    BUTTON_ID,
			class: 'button media-button button-large',
			text:  cfg.label
		} );

		$button.on( 'click', function ( e ) {
			e.preventDefault();
			performDuplicate( frame, $button );
		} );

		var $delete = $( '<button>', {
			type:  'button',
			id:    DELETE_BTN_ID,
			class: 'button media-button button-large',
			text:  cfg.deleteLabel
		} );

		$delete.css( 'color', '#b32d2e' );

		$delete.on( 'click', function ( e ) {
			e.preventDefault();
			performDelete( frame, $delete );
		} );

		// Primary toolbar buttons float right (first in DOM = right-most), so
		// appending places these to the left of the native "Insert into post".
		$primary.append( $button );
		$primary.append( $delete );

		var selection = getSelection( frame );

		function sync() {
			var disabled = ! selection || ! selection.length;
			$button.prop( 'disabled', disabled );
			$delete.prop( 'disabled', disabled );
		}

		if ( selection ) {
			selection.on( 'add remove reset', sync );
		}
		sync();
	}

	// Hook every media frame's open event (mirrors media-library-default-uploaded.js).
	var originalMedia = wp.media;
	wp.media = function () {
		var frame = originalMedia.apply( this, arguments );
		if ( frame && frame.on ) {
			frame.on( 'open', function () {
				// Defer so the toolbar DOM exists.
				setTimeout( function () {
					injectButton( frame );
					// Runs after media-library-default-uploaded.js's reset()/more()
					// so our fresh re-query wins and the grid isn't left empty.
					if ( pendingRefresh ) {
						pendingRefresh = false;
						forceLibraryRefresh( frame );
					}
				}, 0 );
			} );
			// Re-inject when switching states (e.g. back from Create gallery).
			frame.on( 'content:render', function () {
				setTimeout( function () {
					injectButton( frame );
				}, 0 );
			} );
		}
		return frame;
	};
	$.extend( wp.media, originalMedia );
} )( jQuery );
