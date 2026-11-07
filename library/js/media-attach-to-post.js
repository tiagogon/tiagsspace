/**
 * Media modal: move the selection to another post (post editor "Add media").
 *
 * Registration on the shared toolbar (library/js/media-toolbar.js):
 *
 * - Move to ▾ Another post…: opens a search dialog (any post type, any
 *   status), and the picked post receives the selected attachments,
 *   re-parented onto it (appended to the end of its attachments, keeping
 *   their relative order). Two tabs then open: the target's edit screen and,
 *   when the target is publicly viewable, its front-end view.
 *
 * The dialog is the shared scaffold (tiagsspaceMediaToolbar.dialog), styled
 * by library/styles/admin-media.css (§3).
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.tiagsspaceMediaToolbar || ! window.tiagsspaceMediaAttach ) {
		return;
	}

	var toolbar  = window.tiagsspaceMediaToolbar;
	var cfg      = window.tiagsspaceMediaAttach;
	var i18n     = cfg.i18n || {};
	var DEBOUNCE = 250;

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	function format( str, value ) {
		return String( str ).replace( /%[ds]/, value );
	}

	/* ---------------------------------------------------------------------
	 * Search dialog
	 * ------------------------------------------------------------------ */

	function openSearchDialog( ctx, onPick ) {
		// NB: set autocomplete via .attr(), NOT in the props map — wp-admin
		// loads jQuery UI Autocomplete, so an `autocomplete` key would be taken
		// for the .autocomplete() widget method and throw.
		var $input = $( '<input>', {
			type:        'search',
			'class':     'tiagsspace-dialog-search',
			placeholder: t( 'placeholder', 'Search posts by title…' ),
			autofocus:   true
		} ).attr( 'autocomplete', 'off' );
		var $note    = $( '<p>', { 'class': 'tiagsspace-dialog-note', 'aria-live': 'polite' } ).text( t( 'hint', 'Type a title to search…' ) );
		var $results = $( '<ul>', { 'class': 'tiagsspace-dialog-results' } );
		var $body    = $( '<div>' ).append( $input, $note, $results );

		var dlg = toolbar.dialog( {
			title:   ctx.n === 1 ? t( 'heading1', 'Move one item to another post' ) : format( t( 'heading', 'Move %d items to another post' ), ctx.n ),
			$body:   $body,
			buttons: [ { label: t( 'cancel', 'Cancel' ) } ]
		} );

		function renderResults( results ) {
			$results.empty();
			if ( ! results.length ) {
				$note.text( format( t( 'noMatches', 'No posts match “%s”.' ), $input.val() ) ).show();
				return;
			}
			$note.hide();
			results.forEach( function ( item ) {
				var $li = $( '<li>', { 'class': 'tiagsspace-dialog-result', tabindex: 0, role: 'button' } );
				$li.append( $( '<div>', { 'class': 'tiagsspace-dialog-result-title' } ).text( item.title ) );
				$li.append( $( '<div>', { 'class': 'tiagsspace-dialog-result-meta' } ).text( item.type_label + ' · ' + item.status ) );
				function pick() {
					// The dialog stays up until the move is confirmed by the
					// server; onPick closes it on success. Tabs open inside
					// this click (a user gesture).
					onPick( item, dlg.close );
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
				$note.text( t( 'hint', 'Type a title to search…' ) ).show();
				return;
			}
			$note.text( t( 'searching', 'Searching…' ) ).show();
			$results.empty();
			if ( xhr && xhr.abort ) {
				xhr.abort();
			}
			xhr = $.post( cfg.ajaxurl, {
				action:          'tiagsspace_media_attach_search',
				_nonce:          cfg.searchNonce,
				search:          term,
				current_post_id: ctx.postId
			} )
				.done( function ( res ) {
					if ( res && res.success && res.data && res.data.results ) {
						renderResults( res.data.results );
					} else {
						$note.text( t( 'searchError', 'Search failed. Try again.' ) ).show();
					}
				} )
				.fail( function ( jqXHR, statusText ) {
					if ( 'abort' === statusText ) {
						return;
					}
					$note.text( t( 'searchError', 'Search failed. Try again.' ) ).show();
				} );
		}

		$input.on( 'input', function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( runSearch, DEBOUNCE );
		} );
	}

	/* ---------------------------------------------------------------------
	 * Move
	 * ------------------------------------------------------------------ */

	function performAttach( ctx, item, closeDialog ) {
		var ids = ctx.ids();
		if ( ! ids.length ) {
			closeDialog();
			return;
		}

		// Tabs open synchronously inside the result click so popup blockers
		// don't intervene; URLs are filled once AJAX returns.
		var editTab = window.open( 'about:blank', '_blank' );
		var viewTab = item.is_viewable ? window.open( 'about:blank', '_blank' ) : null;

		ctx.busy( true );
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
					// The selected media just left this post.
					ctx.refresh();
					closeDialog();
					if ( ctx.frame && ctx.frame.close ) {
						ctx.frame.close();
					}
				} else {
					if ( editTab ) { editTab.close(); }
					if ( viewTab ) { viewTab.close(); }
					window.alert( res && res.data && res.data.message ? res.data.message : t( 'attachError', 'Could not move the media to that post.' ) );
				}
			} )
			.fail( function () {
				if ( editTab ) { editTab.close(); }
				if ( viewTab ) { viewTab.close(); }
				window.alert( t( 'attachError', 'Could not move the media to that post.' ) );
			} )
			.always( function () {
				ctx.busy( false );
			} );
	}

	toolbar.register( {
		id:    'move-existing',
		group: 'move',
		order: 20,
		label: function () {
			return t( 'item', 'Another post…' );
		},
		enabled: function ( ctx ) {
			return ctx.n > 0;
		},
		run: function ( ctx ) {
			if ( ! ctx.ids().length ) {
				return;
			}
			openSearchDialog( ctx, function ( item, closeDialog ) {
				performAttach( ctx, item, closeDialog );
			} );
		}
	} );
} )( jQuery );
