/**
 * Media modal "Add to existing post" call-to-action (post editor "Add media").
 *
 * Adds a button to the modal's primary toolbar that lets the editor move the
 * selected attachments onto an existing post: click it, search any post by
 * title (any post type, any status), pick one, and the selected attachments
 * are re-parented onto it (appended to the end of that post's attachments,
 * keeping their relative order). Two tabs then open — the target's edit screen
 * and, when the target is publicly viewable, its front-end view.
 *
 * Self-contained: it carries its own small copies of the frame-open wiring and
 * media-cache helpers so it never depends on the sibling duplicate script's
 * load order. Its search overlay is styled from JS (these CTAs aren't part of
 * the SCSS pipeline — mirrors trash-post-modal.js).
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.tiagsspaceMediaAttach ) {
		return;
	}

	var cfg       = window.tiagsspaceMediaAttach;
	var i18n      = cfg.i18n || {};
	var BUTTON_ID = 'tiagsspace-add-to-existing';
	var DEBOUNCE  = 250;

	// Set after a successful attach so the next modal open force-refreshes the
	// library (the selected media were re-parented away from this post).
	var pendingRefresh = false;

	/**
	 * printf-style %d / %s helper for the localized strings.
	 */
	function format( str, value ) {
		return String( str ).replace( /%[ds]/, value );
	}

	/**
	 * Resolve the post being edited (for excluding it from search results).
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
	 * Selected attachment IDs sorted by gallery order (menu_order).
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

	/* ---------------------------------------------------------------------
	 * Search overlay
	 * ------------------------------------------------------------------ */

	var overlayInjectedStyles = false;

	function injectOverlayStyles() {
		if ( overlayInjectedStyles ) {
			return;
		}
		overlayInjectedStyles = true;

		var css = [
			'.tiagsspace-attach-backdrop{position:fixed;inset:0;z-index:200000;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;}',
			'.tiagsspace-attach-panel{background:#fff;width:520px;max-width:calc(100vw - 40px);max-height:calc(100vh - 80px);border-radius:4px;box-shadow:0 5px 30px rgba(0,0,0,.4);display:flex;flex-direction:column;overflow:hidden;}',
			'.tiagsspace-attach-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #dcdcde;}',
			'.tiagsspace-attach-head h2{margin:0;font-size:16px;line-height:1.3;}',
			'.tiagsspace-attach-close{border:0;background:none;cursor:pointer;font-size:20px;line-height:1;color:#646970;padding:4px;}',
			'.tiagsspace-attach-close:hover{color:#135e96;}',
			'.tiagsspace-attach-body{padding:16px 20px;overflow:auto;}',
			'.tiagsspace-attach-search{width:100%;box-sizing:border-box;padding:8px 10px;font-size:14px;border:1px solid #8c8f94;border-radius:4px;}',
			'.tiagsspace-attach-results{list-style:none;margin:12px 0 0;padding:0;border:1px solid #dcdcde;border-radius:4px;max-height:40vh;overflow:auto;}',
			'.tiagsspace-attach-results:empty{display:none;}',
			'.tiagsspace-attach-result{padding:10px 12px;cursor:pointer;border-bottom:1px solid #f0f0f1;}',
			'.tiagsspace-attach-result:last-child{border-bottom:0;}',
			'.tiagsspace-attach-result:hover,.tiagsspace-attach-result:focus{background:#f0f6fc;outline:none;}',
			'.tiagsspace-attach-result-title{font-weight:600;color:#1d2327;}',
			'.tiagsspace-attach-result-meta{font-size:12px;color:#646970;text-transform:capitalize;margin-top:2px;}',
			'.tiagsspace-attach-note{margin:12px 0 0;color:#646970;font-size:13px;}',
			'.tiagsspace-attach-foot{padding:12px 20px;border-top:1px solid #dcdcde;text-align:right;}'
		].join( '' );

		var style = document.createElement( 'style' );
		style.id = 'tiagsspace-attach-styles';
		style.textContent = css;
		document.head.appendChild( style );
	}

	/**
	 * Open the search overlay for a set of attachment IDs. `onPick` receives the
	 * chosen result object.
	 */
	function openSearchOverlay( count, currentPostId, onPick ) {
		injectOverlayStyles();

		var $backdrop = $( '<div>', { 'class': 'tiagsspace-attach-backdrop' } );
		var $panel    = $( '<div>', { 'class': 'tiagsspace-attach-panel', role: 'dialog', 'aria-modal': 'true' } );

		var $head = $( '<div>', { 'class': 'tiagsspace-attach-head' } )
			.append( $( '<h2>' ).text( format( i18n.heading || 'Add %d item(s) to an existing post', count ) ) )
			.append(
				$( '<button>', { type: 'button', 'class': 'tiagsspace-attach-close', 'aria-label': i18n.close || 'Close' } ).html( '&times;' )
			);

		var $input = $( '<input>', {
			type: 'search',
			'class': 'tiagsspace-attach-search',
			placeholder: i18n.placeholder || 'Search posts by title…',
			autocomplete: 'off'
		} );

		var $note    = $( '<p>', { 'class': 'tiagsspace-attach-note' } ).text( i18n.hint || 'Type a title to search…' );
		var $results = $( '<ul>', { 'class': 'tiagsspace-attach-results' } );

		var $body = $( '<div>', { 'class': 'tiagsspace-attach-body' } ).append( $input, $note, $results );

		var $cancel = $( '<button>', { type: 'button', 'class': 'button button-large' } ).text( i18n.cancel || 'Cancel' );
		var $foot   = $( '<div>', { 'class': 'tiagsspace-attach-foot' } ).append( $cancel );

		$panel.append( $head, $body, $foot );
		$backdrop.append( $panel );
		$( 'body' ).append( $backdrop );

		$input.trigger( 'focus' );

		function close() {
			$backdrop.remove();
			$( document ).off( 'keydown.tiagsspaceAttach' );
		}

		$cancel.on( 'click', close );
		$head.find( '.tiagsspace-attach-close' ).on( 'click', close );
		$backdrop.on( 'click', function ( e ) {
			if ( e.target === $backdrop[ 0 ] ) {
				close();
			}
		} );
		$( document ).on( 'keydown.tiagsspaceAttach', function ( e ) {
			if ( 'Escape' === e.key ) {
				close();
			}
		} );

		function renderResults( results ) {
			$results.empty();

			if ( ! results.length ) {
				$note.text( format( i18n.noMatches || 'No posts match “%s”.', $input.val() ) ).show();
				return;
			}

			$note.hide();

			results.forEach( function ( item ) {
				var $li = $( '<li>', { 'class': 'tiagsspace-attach-result', tabindex: 0, role: 'button' } );
				$li.append( $( '<div>', { 'class': 'tiagsspace-attach-result-title' } ).text( item.title ) );
				$li.append( $( '<div>', { 'class': 'tiagsspace-attach-result-meta' } ).text( item.type_label + ' · ' + item.status ) );

				function pick() {
					// Keep the overlay up until the attach AJAX confirms; onPick
					// closes it on success. Opening tabs happens inside this
					// user-gesture click (see performAttach).
					onPick( item, close );
				}

				$li.on( 'click', pick );
				$li.on( 'keydown', function ( e ) {
					if ( 'Enter' === e.key || ' ' === e.key ) {
						e.preventDefault();
						pick();
					}
				} );

				$results.append( $li );
			} );
		}

		var timer = null;
		var xhr   = null;

		function runSearch() {
			var term = $.trim( $input.val() );

			if ( term.length < 2 ) {
				$results.empty();
				$note.text( i18n.hint || 'Type a title to search…' ).show();
				return;
			}

			$note.text( i18n.searching || 'Searching…' ).show();
			$results.empty();

			if ( xhr && xhr.abort ) {
				xhr.abort();
			}

			xhr = $.post( cfg.ajaxurl, {
				action:          'tiagsspace_media_attach_search',
				_nonce:          cfg.searchNonce,
				search:          term,
				current_post_id: currentPostId
			} )
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.results ) {
						renderResults( res.data.results );
					} else {
						$note.text( i18n.searchError || 'Search failed. Try again.' ).show();
					}
				} )
				.fail( function ( jqXHR, statusText ) {
					if ( 'abort' === statusText ) {
						return;
					}
					$note.text( i18n.searchError || 'Search failed. Try again.' ).show();
				} );
		}

		$input.on( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( runSearch, DEBOUNCE );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Attach action
	 * ------------------------------------------------------------------ */

	function performAttach( frame, item, closeOverlay ) {
		var selection = getSelection( frame );
		var ids       = getOrderedSelectionIds( selection );

		if ( ! ids.length ) {
			closeOverlay();
			return;
		}

		// Open tabs synchronously inside the result click (a user gesture) so
		// popup blockers don't intervene. URLs are filled once AJAX returns.
		var editTab = window.open( 'about:blank', '_blank' );
		var viewTab = item.is_viewable ? window.open( 'about:blank', '_blank' ) : null;

		$.post( cfg.ajaxurl, {
			action:         'tiagsspace_media_attach_attach',
			_nonce:         cfg.attachNonce,
			target_id:      item.id,
			attachment_ids: ids
		} )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.edit_url ) {
					if ( editTab ) {
						editTab.location = res.data.edit_url;
					}
					if ( viewTab ) {
						if ( res.data.view_url ) {
							viewTab.location = res.data.view_url;
						} else {
							viewTab.close();
						}
					}

					// The selected media just left this post; clear the stale
					// selection and flush caches so reopening the modal shows the
					// post's current media without a manual page refresh.
					if ( selection ) {
						selection.reset();
					}
					flushMediaCaches();
					pendingRefresh = true;

					closeOverlay();
					if ( frame && frame.close ) {
						frame.close();
					}
				} else {
					if ( editTab ) { editTab.close(); }
					if ( viewTab ) { viewTab.close(); }
					var msg = res && res.data && res.data.message ? res.data.message : ( i18n.attachError || 'Could not add the media to that post.' );
					window.alert( msg );
				}
			} )
			.fail( function () {
				if ( editTab ) { editTab.close(); }
				if ( viewTab ) { viewTab.close(); }
				window.alert( i18n.attachError || 'Could not add the media to that post.' );
			} );
	}

	/* ---------------------------------------------------------------------
	 * Button injection + frame wiring
	 * ------------------------------------------------------------------ */

	function injectButton( frame ) {
		var $primary = $( '.media-frame-toolbar .media-toolbar-primary' );

		if ( ! $primary.length || document.getElementById( BUTTON_ID ) ) {
			return;
		}

		var $button = $( '<button>', {
			type:  'button',
			id:    BUTTON_ID,
			'class': 'button media-button button-large',
			text:  cfg.label
		} );

		$button.on( 'click', function ( e ) {
			e.preventDefault();

			var selection = getSelection( frame );
			var ids       = getOrderedSelectionIds( selection );

			if ( ! ids.length ) {
				return;
			}

			openSearchOverlay( ids.length, getSourcePostId(), function ( item, closeOverlay ) {
				performAttach( frame, item, closeOverlay );
			} );
		} );

		$primary.append( $button );

		var selection = getSelection( frame );

		function sync() {
			$button.prop( 'disabled', ! selection || ! selection.length );
		}

		if ( selection ) {
			selection.on( 'add remove reset', sync );
		}
		sync();
	}

	// Hook every media frame's open event (mirrors the sibling CTA scripts).
	var originalMedia = wp.media;
	wp.media = function () {
		var frame = originalMedia.apply( this, arguments );
		if ( frame && frame.on ) {
			frame.on( 'open', function () {
				setTimeout( function () {
					injectButton( frame );
					if ( pendingRefresh ) {
						pendingRefresh = false;
						forceLibraryRefresh( frame );
					}
				}, 0 );
			} );
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
