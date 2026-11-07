/**
 * Media modal: the gallery actions (post editor "Add media" modal).
 *
 * Registrations on the shared toolbar (library/js/media-toolbar.js):
 *
 * - Select ▾: All / None / Portrait / Landscape / Images / Videos / Audio /
 *   Hidden from gallery / In the gallery (the `remove_from_default_gallery`
 *   flag, shipped on the model as `tiagsspaceHidden`), each with a live count
 *   over the whole library (paged in first). An item REPLACES the selection;
 *   Shift-click ADDS to it.
 *
 * - Sort ▾: By capture time (EXIF) / By upload date / Shuffle. The scope is
 *   the selection (they keep their slots) or, with nothing selected, every
 *   attachment of the post, which asks for confirmation first.
 *
 * - Delete ▾ Hidden from gallery (N)…: deletes the post's attachments flagged
 *   `remove_from_default_gallery`. N is fetched when the menu opens.
 *
 * The selection is only changed through the frame's own Selection collection
 * with models from its own library (what thumbnail clicks do).
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.tiagsspaceMediaToolbar || ! window.galleryMediaActions ) {
		return;
	}

	var toolbar = window.tiagsspaceMediaToolbar;
	var cfg     = window.galleryMediaActions;
	var i18n    = cfg.i18n || {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	function format( str, value ) {
		return String( str ).replace( /%[ds]/, value );
	}

	/**
	 * "%d things" or the singular form.
	 */
	function count( n, key, fallback, key1, fallback1 ) {
		return n === 1 ? t( key1, fallback1 ) : format( t( key, fallback ), n );
	}

	/* ---------------------------------------------------------------------
	 * Select ▾
	 * ------------------------------------------------------------------ */

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
		},
		hidden: function ( m ) {
			return !! m.get( 'tiagsspaceHidden' );
		},
		visible: function ( m ) {
			return ! m.get( 'tiagsspaceHidden' );
		}
	};

	function countOf( ctx, key ) {
		return ctx.library ? ctx.library.filter( predicates[ key ] ).length : 0;
	}

	function applySelection( ctx, key, additive ) {
		if ( ! ctx.selection || ! ctx.library ) {
			return;
		}
		var models = ctx.library.filter( predicates[ key ] );
		if ( additive ) {
			ctx.selection.add( models );
		} else {
			ctx.selection.reset( models );
		}
	}

	function loadAll( ctx ) {
		return ctx.ensureLibraryLoaded();
	}

	function selectEntry( key, order, labelKey, fallback, separatorBefore ) {
		toolbar.register( {
			id:              'select-' + key,
			group:           'select',
			order:           order,
			separatorBefore: !! separatorBefore,
			prepare:         loadAll,
			label: function () {
				return t( labelKey, fallback );
			},
			count: function ( ctx ) {
				return countOf( ctx, key );
			},
			enabled: function ( ctx ) {
				return countOf( ctx, key ) > 0;
			},
			run: function ( ctx, e ) {
				applySelection( ctx, key, !! ( e && e.shiftKey ) );
			}
		} );
	}

	selectEntry( 'all', 10, 'selectAll', 'All' );
	toolbar.register( {
		id:      'select-none',
		group:   'select',
		order:   11,
		prepare: loadAll,
		label: function () {
			return t( 'selectNone', 'None' );
		},
		enabled: function ( ctx ) {
			return ctx.n > 0;
		},
		run: function ( ctx ) {
			if ( ctx.selection ) {
				ctx.selection.reset();
			}
		}
	} );
	selectEntry( 'portrait', 20, 'portrait', 'Portrait', true );
	selectEntry( 'landscape', 21, 'landscape', 'Landscape' );
	selectEntry( 'image', 30, 'images', 'Images', true );
	selectEntry( 'video', 31, 'videos', 'Videos' );
	selectEntry( 'audio', 32, 'audio', 'Audio' );
	selectEntry( 'hidden', 40, 'hidden', 'Hidden from gallery', true );
	selectEntry( 'visible', 41, 'visible', 'In the gallery' );

	/* ---------------------------------------------------------------------
	 * Sort ▾
	 * ------------------------------------------------------------------ */

	function performSort( ctx, mode ) {
		if ( ! ctx.postId ) {
			window.alert( t( 'postError', 'Could not determine the current post.' ) );
			return;
		}
		ctx.busy( true );
		$.post( cfg.ajaxurl, {
			action:  'gallery_reorder_attachments',
			_nonce:  cfg.reorderNonce,
			post_id: ctx.postId,
			mode:    mode,
			ids:     ctx.ids()
		} )
			.done( function ( res ) {
				if ( res && res.success ) {
					ctx.refresh( { orderby: 'menuOrder', order: 'ASC' } );
				} else {
					window.alert( res && res.data && res.data.message ? res.data.message : t( 'sortError', 'Could not sort the gallery.' ) );
				}
			} )
			.fail( function () {
				window.alert( t( 'sortError', 'Could not sort the gallery.' ) );
			} )
			.always( function () {
				ctx.busy( false );
			} );
	}

	function sortEntry( mode, order, labelKey, fallback ) {
		toolbar.register( {
			id:    'sort-' + mode,
			group: 'sort',
			order: order,
			label: function () {
				return t( labelKey, fallback );
			},
			enabled: function ( ctx ) {
				return ctx.n !== 1;
			},
			run: function ( ctx ) {
				if ( ctx.n > 0 ) {
					performSort( ctx, mode );
					return;
				}
				// Nothing selected: the whole post is re-sorted. Ask first.
				ctx.confirm( {
					title:        ctx.total ? format( t( 'sortAllTitle', 'Sort all %d attachments?' ), ctx.total ) : t( 'sortAllTitleNoCount', 'Sort all attachments?' ),
					text:         [ t( labelKey, fallback ) + '.', t( 'sortAllText', 'The current order of the gallery is replaced.' ) ],
					confirmLabel: t( 'sortConfirm', 'Sort' )
				} ).done( function ( ok ) {
					if ( ok ) {
						performSort( ctx, mode );
					}
				} );
			}
		} );
	}

	sortEntry( 'capture_time', 10, 'byCapture', 'By capture time (EXIF)' );
	sortEntry( 'chronological', 20, 'byUpload', 'By upload date' );
	sortEntry( 'random', 30, 'shuffle', 'Shuffle' );

	/* ---------------------------------------------------------------------
	 * Delete ▾ Hidden from gallery (N)…
	 * ------------------------------------------------------------------ */

	function fetchHiddenCount( ctx ) {
		ctx.hiddenCount = 0;
		if ( ! ctx.postId ) {
			return null;
		}
		return $.post( cfg.ajaxurl, {
			action:  'gallery_count_hidden_attachments',
			_nonce:  cfg.hiddenNonce,
			post_id: ctx.postId
		} ).then( function ( res ) {
			ctx.hiddenCount = ( res && res.success && res.data ) ? ( parseInt( res.data.count, 10 ) || 0 ) : 0;
		}, function () {
			ctx.hiddenCount = 0;
		} );
	}

	toolbar.register( {
		id:      'delete-hidden',
		group:   'delete',
		order:   20,
		prepare: fetchHiddenCount,
		label: function ( ctx ) {
			return format( t( 'hiddenItem', 'Hidden from gallery (%d)…' ), ctx.hiddenCount || 0 );
		},
		enabled: function ( ctx ) {
			return ( ctx.hiddenCount || 0 ) > 0;
		},
		run: function ( ctx ) {
			var n = ctx.hiddenCount || 0;
			if ( ! ctx.postId || ! n ) {
				return;
			}
			ctx.confirm( {
				title:        count( n, 'hiddenTitle', 'Delete %d hidden attachments permanently?', 'hiddenTitle1', 'Delete one hidden attachment permanently?' ),
				text:         [ t( 'hiddenText', 'They are the attachments flagged “remove from default gallery”.' ), t( 'deleteText', 'They are removed from the Media Library and from disk. This cannot be undone.' ) ],
				confirmLabel: count( n, 'deleteConfirm', 'Delete %d', 'deleteConfirm1', 'Delete one' ),
				danger:       true
			} ).done( function ( ok ) {
				if ( ! ok ) {
					return;
				}
				ctx.busy( true );
				$.post( cfg.ajaxurl, {
					action:  'gallery_delete_hidden_attachments',
					_nonce:  cfg.hiddenNonce,
					post_id: ctx.postId
				} )
					.done( function ( res ) {
						if ( res && res.success ) {
							ctx.refresh();
						} else {
							window.alert( res && res.data && res.data.message ? res.data.message : t( 'deleteError', 'Could not delete the hidden attachments.' ) );
						}
					} )
					.fail( function () {
						window.alert( t( 'deleteError', 'Could not delete the hidden attachments.' ) );
					} )
					.always( function () {
						ctx.busy( false );
					} );
			} );
		}
	} );
} )( jQuery );
