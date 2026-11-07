/**
 * Media modal: move the selection to a duplicate of this post, or delete it
 * (post editor "Add media" modal).
 *
 * Registrations on the shared toolbar (library/js/media-toolbar.js):
 *
 * - Move to ▾ A new duplicate of this post…: duplicates the current post and
 *   re-parents the selected media onto the copy (keeping gallery order).
 *   Re-parenting moves them OFF this post, since an attachment has one
 *   parent. The new draft opens in a new tab.
 *
 * - Delete ▾ The N selected…: permanently deletes the selected attachments
 *   (WordPress media has no trash). Confirmed in the shared panel.
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.tiagsspaceMediaToolbar || ! window.tiagsspaceMediaDuplicate ) {
		return;
	}

	var toolbar = window.tiagsspaceMediaToolbar;
	var cfg     = window.tiagsspaceMediaDuplicate;
	var i18n    = cfg.i18n || {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	function format( str, value ) {
		return String( str ).replace( /%[ds]/, value );
	}

	function count( n, key, fallback, key1, fallback1 ) {
		return n === 1 ? t( key1, fallback1 ) : format( t( key, fallback ), n );
	}

	/* ---------------------------------------------------------------------
	 * Move to ▾ A new duplicate of this post…
	 * ------------------------------------------------------------------ */

	function performDuplicate( ctx ) {
		var ids = ctx.ids();
		if ( ! ids.length ) {
			return;
		}
		if ( ! ctx.postId ) {
			window.alert( t( 'postError', 'Could not determine the current post.' ) );
			return;
		}

		// Opened synchronously inside the confirm click (a user gesture) so
		// popup blockers let it through; the URL is filled once AJAX returns.
		var tab = window.open( 'about:blank', '_blank' );

		ctx.busy( true );
		$.post( cfg.ajaxurl, {
			action:         'tiagsspace_duplicate_to_post',
			_nonce:         cfg.nonce,
			source_id:      ctx.postId,
			attachment_ids: ids
		} )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.edit_url ) {
					if ( tab ) {
						tab.location = res.data.edit_url;
					} else {
						window.location.href = res.data.edit_url;
					}
					// The selected media just left this post.
					ctx.refresh();
					if ( ctx.frame && ctx.frame.close ) {
						ctx.frame.close();
					}
				} else {
					if ( tab ) {
						tab.close();
					}
					window.alert( res && res.data && res.data.message ? res.data.message : t( 'duplicateError', 'Could not duplicate the post.' ) );
				}
			} )
			.fail( function () {
				if ( tab ) {
					tab.close();
				}
				window.alert( t( 'duplicateError', 'Could not duplicate the post.' ) );
			} )
			.always( function () {
				ctx.busy( false );
			} );
	}

	toolbar.register( {
		id:    'move-duplicate',
		group: 'move',
		order: 10,
		label: function () {
			return t( 'duplicateItem', 'A new duplicate of this post…' );
		},
		enabled: function ( ctx ) {
			return ctx.n > 0;
		},
		run: function ( ctx ) {
			ctx.confirm( {
				title:        count( ctx.n, 'duplicateTitle', 'Move %d items to a new duplicate of this post?', 'duplicateTitle1', 'Move one item to a new duplicate of this post?' ),
				text:         t( 'duplicateText', 'A draft copy of this post is created and the selected items leave this post for it. The draft opens in a new tab.' ),
				confirmLabel: count( ctx.n, 'moveConfirm', 'Move %d', 'moveConfirm1', 'Move one' )
			} ).done( function ( ok ) {
				// .done() runs synchronously inside the confirm click, which
				// keeps window.open() inside the user gesture.
				if ( ok ) {
					performDuplicate( ctx );
				}
			} );
		}
	} );

	/* ---------------------------------------------------------------------
	 * Delete ▾ The N selected…
	 * ------------------------------------------------------------------ */

	toolbar.register( {
		id:    'delete-selected',
		group: 'delete',
		order: 10,
		label: function ( ctx ) {
			if ( ! ctx.n ) {
				return t( 'deleteItem0', 'The selected items…' );
			}
			return count( ctx.n, 'deleteItem', 'The %d selected…', 'deleteItem1', 'The one selected…' );
		},
		enabled: function ( ctx ) {
			return ctx.n > 0;
		},
		run: function ( ctx ) {
			var ids = ctx.ids();
			if ( ! ids.length ) {
				return;
			}
			ctx.confirm( {
				title:        count( ids.length, 'deleteTitle', 'Delete %d attachments permanently?', 'deleteTitle1', 'Delete one attachment permanently?' ),
				text:         t( 'deleteText', 'They are removed from the Media Library and from disk. This cannot be undone.' ),
				confirmLabel: count( ids.length, 'deleteConfirm', 'Delete %d', 'deleteConfirm1', 'Delete one' ),
				danger:       true
			} ).done( function ( ok ) {
				if ( ! ok ) {
					return;
				}
				ctx.busy( true );
				$.post( cfg.ajaxurl, {
					action:         'tiagsspace_delete_attachments',
					_nonce:         cfg.deleteNonce,
					attachment_ids: ids
				} )
					.done( function ( res ) {
						if ( res && res.success ) {
							ctx.refresh();
						} else {
							window.alert( res && res.data && res.data.message ? res.data.message : t( 'deleteError', 'Could not delete the selected media.' ) );
						}
					} )
					.fail( function () {
						window.alert( t( 'deleteError', 'Could not delete the selected media.' ) );
					} )
					.always( function () {
						ctx.busy( false );
					} );
			} );
		}
	} );
} )( jQuery );
