/**
 * Front-end gallery editor: the toolbar's items.
 *
 * The same items as the media modal, calling the same AJAX handlers:
 *   Delete ▾  The N selected…          tiagsspace_delete_attachments       (library/duplicate-post.php)
 *             Hidden from gallery (N)… gallery_delete_hidden_attachments   (library/gallery-functions.php)
 *   Set as featured image (one image selected), the per-image Feature chip and
 *   the F key (the focused image)
 *                                      tiagsspace_set_featured_image       (library/gallery-functions.php)
 *   Select ▾  All · None · Portrait · Landscape · Images · Videos · Audio
 *   Sort ▾    By capture time (EXIF) · By upload date · Shuffle
 *                                      gallery_reorder_attachments         (library/gallery-functions.php)
 *   View ▾    Black and white · Back to colour (the selected) · All black and
 *             white · All back to colour, and the B key: a preview on this
 *             screen only, nothing saved (class tge-bw, gallery-edit.css);
 *             then "Save N in black and white…": the previewed images, for
 *             everyone, colour files deleted  tiagsspace_save_bw (library/gallery-bw.php)
 *   Move to ▾ A new duplicate of this post…  tiagsspace_duplicate_to_post (library/duplicate-post.php)
 *             Another post…            tiagsspace_media_attach_search / _attach (library/media-attach-to-post.php)
 *
 * Words come from the same PHP tables as the modal (cfg.i18n.gallery,
 * .duplicate, .attach). A new item is one tiagsspaceGalleryEdit.register() call.
 */
( function () {
	'use strict';

	var cfg = window.tiagsspaceGalleryEditCfg;
	var api = window.tiagsspaceGalleryEdit;
	if ( ! cfg || ! api || ! api.register ) return;

	var I = cfg.i18n || {};
	var format = api.format;

	function tr( table, key, fallback ) {
		return ( I[ table ] && I[ table ][ key ] ) || fallback;
	}
	function g( key, fb ) { return tr( 'gallery', key, fb ); }
	function d( key, fb ) { return tr( 'duplicate', key, fb ); }
	function a( key, fb ) { return tr( 'attach', key, fb ); }

	function fail( err, fallback ) {
		window.alert( ( err && err.message ) || fallback );
	}

	// ── Hide (a plain button, first on the bar) ─────────────
	// Same path as the H key: the images park in the hidden pool, the flag is
	// saved (gallery_toggle_hide_attachment), and U brings them back one by one.

	api.register( {
		id:      'hide-selected',
		group:   'hide',
		order:   10,
		label:   function ( ctx ) {
			return ctx.n ? format( tr( 'edit', 'hideN', 'Hide %d (H)' ), ctx.n ) : tr( 'edit', 'hide', 'Hide (H)' );
		},
		enabled: function ( ctx ) { return ctx.n > 0 && typeof window.galleryKeyboardReorderHideSelected === 'function'; },
		run:     function () { window.galleryKeyboardReorderHideSelected(); }
	} );

	// ── Set as featured image ───────────────────────────────
	// Two ways in: the Feature chip on each image, and a plain button on the bar
	// while exactly one image is selected. Saved at once; the chip of the
	// featured image reads "Featured".

	var featuredId = parseInt( cfg.featuredId, 10 ) || 0;
	var featuredListeners = [];

	function paintFeatured() {
		Array.prototype.forEach.call( document.querySelectorAll( '.tge-feature' ), function ( chip ) {
			var on = parseInt( chip.getAttribute( 'data-attachment' ), 10 ) === featuredId;
			chip.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			chip.textContent = on ? tr( 'edit', 'featured', 'Featured' ) : tr( 'edit', 'feature', 'Feature (F)' );
			chip.setAttribute( 'data-tip', on ? tr( 'edit', 'tipFeatured', 'The featured image of this post' ) : tr( 'edit', 'tipFeature', 'Set as the featured image of this post  F' ) );
		} );
	}

	function setFeatured( id ) {
		id = parseInt( id, 10 ) || 0;
		if ( ! id || id === featuredId ) return Promise.resolve();
		return api.post( 'tiagsspace_set_featured_image', {
			_nonce:        cfg.nonces.featured,
			post_id:       cfg.postId,
			attachment_id: id
		} ).then( function () {
			featuredId = id;
			paintFeatured();
			featuredListeners.forEach( function ( fn ) {
				try { fn( featuredId ); } catch ( err ) { if ( window.console ) console.error( err ); }
			} );
			if ( api.notice ) api.notice( g( 'featuredDone', 'Featured image set' ) );
			if ( api.refreshToolbar ) api.refreshToolbar();
		}, function ( err ) {
			fail( err, g( 'featuredError', 'Could not set the featured image.' ) );
		} );
	}

	// The featured state, shaped like api.selection: the lightbox reads it.
	api.featured = {
		id:  function () { return featuredId; },
		set: setFeatured,
		on:  function ( fn ) { featuredListeners.push( fn ); }
	};

	document.addEventListener( 'click', function ( e ) {
		var chip = e.target && e.target.closest ? e.target.closest( '.tge-feature' ) : null;
		if ( ! chip ) return;
		e.preventDefault();
		e.stopPropagation();
		setFeatured( chip.getAttribute( 'data-attachment' ) );
	} );

	// F: the keyboard-focused image (the one on screen in the lightbox) becomes
	// the featured image. Images only; same guards as the S key.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'f' && e.key !== 'F' ) return;
		if ( e.metaKey || e.ctrlKey || e.altKey || e.shiftKey || api.modalOpen ) return;
		var tag = e.target && e.target.tagName;
		if ( tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || ( e.target && e.target.isContentEditable ) ) return;
		var grid = api.grid;
		var f = grid ? grid.getAttribute( 'data-kb-focus' ) : null;
		if ( f === null ) return;
		var item = api.items()[ parseInt( f, 10 ) || 0 ];
		if ( ! item || item.getAttribute( 'data-kind' ) !== 'image' ) return;
		e.preventDefault();
		setFeatured( item.getAttribute( 'attachmentId' ) );
	} );

	function oneSelectedImage( ctx ) {
		if ( ctx.n !== 1 ) return 0;
		var id = String( ctx.ids[ 0 ] );
		var item = ctx.items.filter( function ( el ) { return String( el.getAttribute( 'attachmentId' ) ) === id; } )[ 0 ];
		return item && item.getAttribute( 'data-kind' ) === 'image' ? parseInt( id, 10 ) : 0;
	}

	api.register( {
		id:      'set-featured',
		group:   'feature',
		order:   10,
		label:   function () { return tr( 'edit', 'setFeaturedKey', 'Set as featured image (F)' ); },
		enabled: function ( ctx ) {
			var id = oneSelectedImage( ctx );
			return id > 0 && id !== featuredId;
		},
		run: function ( ctx ) {
			ctx.busy( true );
			setFeatured( oneSelectedImage( ctx ) ).then( function () { ctx.busy( false ); } );
		}
	} );

	// ── Restore hidden (a plain button, only while something is hidden) ──
	// Every hidden image goes back to its saved place (the U key does one).

	api.register( {
		id:      'restore-hidden',
		group:   'restore',
		order:   10,
		label:   function ( ctx ) { return format( tr( 'edit', 'restoreN', 'Restore hidden (%d)' ), ctx.hiddenCount ); },
		enabled: function ( ctx ) { return ctx.hiddenCount > 0 && typeof window.galleryKeyboardReorderRestoreAll === 'function'; },
		run:     function () { window.galleryKeyboardReorderRestoreAll(); }
	} );

	// ── View ▾: black and white preview ─────────────────────
	// Only on this screen: a class on the grid item (grayscale filter in
	// gallery-edit.css), nothing saved; a reload brings colour back. A hidden
	// image keeps the class in the pool, so U / N bring it back as it was.

	var BW = 'tge-bw';

	function isBw( el ) { return el.classList.contains( BW ); }
	function setBw( els, on ) {
		els.forEach( function ( el ) { el.classList.toggle( BW, on ); } );
		if ( api.refreshToolbar ) api.refreshToolbar();
		document.dispatchEvent( new CustomEvent( 'tiagsspace:gallery-view' ) );
	}
	function selectedEls( ctx ) {
		var ids = {};
		ctx.ids.forEach( function ( id ) { ids[ String( id ) ] = true; } );
		return ctx.items.filter( function ( el ) { return ids[ String( el.getAttribute( 'attachmentId' ) ) ]; } );
	}
	function not( fn ) { return function ( el ) { return ! fn( el ); }; }

	api.register( {
		id:      'view-bw-selected',
		group:   'view',
		order:   10,
		label:   function () { return tr( 'edit', 'bwSelected', 'Black and white' ); },
		count:   function ( ctx ) { return selectedEls( ctx ).filter( not( isBw ) ).length; },
		enabled: function ( ctx ) { return selectedEls( ctx ).some( not( isBw ) ); },
		run:     function ( ctx ) { setBw( selectedEls( ctx ), true ); }
	} );

	api.register( {
		id:      'view-colour-selected',
		group:   'view',
		order:   20,
		label:   function () { return tr( 'edit', 'colourSelected', 'Back to colour' ); },
		count:   function ( ctx ) { return selectedEls( ctx ).filter( isBw ).length; },
		enabled: function ( ctx ) { return selectedEls( ctx ).some( isBw ); },
		run:     function ( ctx ) { setBw( selectedEls( ctx ), false ); }
	} );

	api.register( {
		id:              'view-bw-all',
		group:           'view',
		order:           30,
		separatorBefore: true,
		label:   function () { return tr( 'edit', 'bwAll', 'All black and white' ); },
		enabled: function ( ctx ) { return ctx.items.some( not( isBw ) ); },
		run:     function ( ctx ) { setBw( ctx.items, true ); }
	} );

	api.register( {
		id:      'view-colour-all',
		group:   'view',
		order:   40,
		label:   function () { return tr( 'edit', 'colourAll', 'All back to colour' ); },
		count:   function ( ctx ) { return ctx.items.filter( isBw ).length; },
		enabled: function ( ctx ) { return ctx.items.some( isBw ); },
		run:     function ( ctx ) { setBw( ctx.items, false ); }
	} );

	// ── Download (a plain button) ───────────────────────────
	// A zip of the original files from download_all_attachments(): the
	// selected, or every attachment of the post (hidden ones too) when nothing
	// is selected. The response is an attachment, so the page stays.

	api.register( {
		id:      'download',
		group:   'download',
		order:   10,
		label:   function ( ctx ) {
			return ctx.n ? format( tr( 'edit', 'downloadN', 'Download %d' ), ctx.n ) : tr( 'edit', 'downloadAll', 'Download all' );
		},
		enabled: function ( ctx ) { return !! ( cfg.nonces && cfg.nonces.download ) && ( ctx.n > 0 || ctx.total > 0 ); },
		run:     function ( ctx ) {
			var q = [
				'action=download_all_attachments',
				'post_id=' + encodeURIComponent( cfg.postId ),
				'_nonce=' + encodeURIComponent( cfg.nonces.download )
			];
			if ( ctx.n ) q.push( 'ids=' + encodeURIComponent( ctx.ids.join( ',' ) ) );
			ctx.notice( tr( 'edit', 'downloadBusy', 'Preparing the zip…' ) );
			var link = document.createElement( 'a' );
			link.href = cfg.ajaxUrl + '?' + q.join( '&' );
			link.setAttribute( 'download', '' );
			document.body.appendChild( link );
			link.click();
			link.remove();
		}
	} );

	// ── View ▾ → Save for everyone (library/gallery-bw.php) ──
	// What is saved is what is previewed: the images on screen in black and
	// white that are not saved yet. Destructive, so "…", a confirm panel with
	// Cancel focused, no key; one image per request with a running count; the
	// colour files are deleted on the server; the page swaps in the new URLs.

	function saveableEls( ctx ) {
		return ctx.items.filter( function ( el ) {
			return isBw( el ) && el.getAttribute( 'data-kind' ) === 'image' && el.getAttribute( 'data-bw' ) !== 'saved';
		} );
	}

	// Every attribute of the item that holds an image URL (picture sources,
	// img src / srcset, the lightbox link) gets the new file's URL.
	function swapUrls( item, pairs ) {
		var attrs = [ 'src', 'srcset', 'href', 'data-image', 'data-src', 'data-srcset' ];
		Array.prototype.forEach.call( item.querySelectorAll( 'img, source, a' ), function ( node ) {
			attrs.forEach( function ( name ) {
				var v = node.getAttribute( name );
				if ( ! v ) return;
				var next = v;
				( pairs || [] ).forEach( function ( p ) { next = next.split( p[ 0 ] ).join( p[ 1 ] ); } );
				if ( next !== v ) node.setAttribute( name, next );
			} );
		} );
	}

	function saveBw( els, ctx ) {
		var total = els.length;
		var i = 0;
		var saved = 0;
		var failed = [];
		var leaveMsg = tr( 'edit', 'saveBwLeave', 'Images are still being saved in black and white.' );
		function onLeave( e ) {
			e.preventDefault();
			e.returnValue = leaveMsg;
			return leaveMsg;
		}
		window.addEventListener( 'beforeunload', onLeave );
		ctx.busy( true );

		function finish() {
			window.removeEventListener( 'beforeunload', onLeave );
			ctx.busy( false );
			if ( saved && api.notice ) api.notice( format( tr( 'edit', 'saveBwDone', '%d saved in black and white' ), saved ) );
			// A failure stays on screen until read: a dialog, not the fading notice.
			if ( failed.length && api.dialog ) {
				var list = document.createElement( 'ul' );
				failed.forEach( function ( line ) {
					var li = document.createElement( 'li' );
					li.textContent = line;
					list.appendChild( li );
				} );
				var p = document.createElement( 'p' );
				p.textContent = tr( 'edit', 'saveBwFailedText', 'These images keep their colour files:' );
				api.dialog( {
					title:   format( tr( 'edit', 'saveBwFailedTitle', '%d not saved in black and white' ), failed.length ),
					body:    [ p, list ],
					buttons: [ { label: tr( 'toolbar', 'ok', 'OK' ), primary: true } ]
				} );
			}
			if ( api.refreshToolbar ) api.refreshToolbar();
			document.dispatchEvent( new CustomEvent( 'tiagsspace:gallery-view' ) );
		}

		function next() {
			if ( i >= total ) {
				finish();
				return;
			}
			var el = els[ i++ ];
			if ( api.notice ) api.notice( format( tr( 'edit', 'saveBwProgress', 'Saving %d of %d in black and white…' ), i, total ) );
			api.post( 'tiagsspace_save_bw', {
				_nonce:        cfg.nonces.saveBw,
				post_id:       cfg.postId,
				attachment_id: el.getAttribute( 'attachmentId' )
			} ).then( function ( res ) {
				swapUrls( el, res.data && res.data.replace );
				el.setAttribute( 'data-bw', 'saved' );
				el.classList.remove( BW ); // the file itself is black and white now
				saved++;
			}, function ( err ) {
				var name = el.getAttribute( 'data-title' ) || el.getAttribute( 'attachmentId' );
				failed.push( name + ( err && err.message ? ' (' + err.message + ')' : '' ) );
			} ).then( next );
		}

		next();
	}

	api.register( {
		id:            'view-save-bw',
		group:         'view',
		order:         50,
		danger:        true,
		sectionHeader: function () { return tr( 'edit', 'saveBwHeader', 'Save for everyone · cannot be undone' ); },
		label:         function ( ctx ) { return format( tr( 'edit', 'saveBwN', 'Save %d in black and white…' ), saveableEls( ctx ).length ); },
		enabled:       function ( ctx ) { return !! ( cfg.nonces && cfg.nonces.saveBw ) && saveableEls( ctx ).length > 0; },
		run: function ( ctx ) {
			var els = saveableEls( ctx );
			var n = els.length;
			if ( ! n ) return;
			ctx.confirm( {
				title: n === 1
					? tr( 'edit', 'saveBwTitleOne', 'Save 1 image in black and white?' )
					: format( tr( 'edit', 'saveBwTitleN', 'Save %d images in black and white?' ), n ),
				text: [
					tr( 'edit', 'saveBwText1', 'New black and white files replace the colour ones everywhere: the gallery, the lightbox, share previews and downloads. Their names end in -bw.' ),
					tr( 'edit', 'saveBwText2', 'The colour files are deleted. This cannot be undone.' ),
					tr( 'edit', 'saveBwText3', 'The images are indexed again for the visual search.' )
				],
				confirmLabel: tr( 'edit', 'saveBwConfirm', 'Save in black and white' ),
				danger:       true
			} ).then( function ( ok ) {
				if ( ok ) saveBw( els, ctx );
			} );
		}
	} );

	// B: toggle the selected images, or the focused one when nothing is
	// selected (the H rule). Mixed selection: all go black and white first.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'b' && e.key !== 'B' ) return;
		if ( e.metaKey || e.ctrlKey || e.altKey || e.shiftKey || api.modalOpen ) return;
		var tag = e.target && e.target.tagName;
		if ( tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || ( e.target && e.target.isContentEditable ) ) return;
		var els = api.selection ? api.selection.items() : [];
		if ( ! els.length ) {
			var f = api.grid ? api.grid.getAttribute( 'data-kb-focus' ) : null;
			var item = f === null ? null : api.items()[ parseInt( f, 10 ) || 0 ];
			if ( item ) els = [ item ];
		}
		if ( ! els.length ) return;
		e.preventDefault();
		setBw( els, ! els.every( isBw ) );
	} );

	// ── Select ▾ ────────────────────────────────────────────

	function idsWhere( ctx, test ) {
		return ctx.items.filter( test ).map( function ( el ) { return String( el.getAttribute( 'attachmentId' ) ); } );
	}

	var FILTERS = {
		all:       function () { return true; },
		portrait:  function ( el ) { return el.getAttribute( 'data-orientation' ) === 'portrait'; },
		landscape: function ( el ) { return el.getAttribute( 'data-orientation' ) === 'landscape'; },
		images:    function ( el ) { return el.getAttribute( 'data-kind' ) === 'image'; },
		videos:    function ( el ) { return el.getAttribute( 'data-kind' ) === 'video'; },
		audio:     function ( el ) { return el.getAttribute( 'data-kind' ) === 'audio'; }
	};

	function selectEntry( key, order, labelKey, fallback, separatorBefore ) {
		api.register( {
			id:              'select-' + key,
			group:           'select',
			order:           order,
			separatorBefore: !! separatorBefore,
			label:   function () { return g( labelKey, fallback ); },
			count:   function ( ctx ) { return idsWhere( ctx, FILTERS[ key ] ).length; },
			enabled: function ( ctx ) { return idsWhere( ctx, FILTERS[ key ] ).length > 0; },
			run: function ( ctx, e ) {
				var ids = idsWhere( ctx, FILTERS[ key ] );
				// The item replaces the selection; Shift-click adds to it.
				if ( e && e.shiftKey ) {
					ctx.selection.add( ids );
				} else {
					ctx.selection.set( ids );
				}
			}
		} );
	}

	selectEntry( 'all', 10, 'selectAll', 'All' );
	api.register( {
		id:      'select-none',
		group:   'select',
		order:   11,
		label:   function () { return g( 'selectNone', 'None' ); },
		enabled: function ( ctx ) { return ctx.n > 0; },
		run:     function ( ctx ) { ctx.selection.clear(); }
	} );
	selectEntry( 'portrait', 20, 'portrait', 'Portrait', true );
	selectEntry( 'landscape', 21, 'landscape', 'Landscape' );
	selectEntry( 'images', 30, 'images', 'Images', true );
	selectEntry( 'videos', 31, 'videos', 'Videos' );
	selectEntry( 'audio', 32, 'audio', 'Audio' );

	// ── Sort ▾ ──────────────────────────────────────────────

	function performSort( ctx, mode ) {
		ctx.busy( true );
		// A drag saved a moment ago may still be on its way: let it land, so a
		// sort of the selection reads the slots the page shows.
		var pending = typeof window.flushGalleryOrderSave === 'function' ? window.flushGalleryOrderSave() : null;
		Promise.resolve( pending ).catch( function () {} ).then( function () {
			return ctx.post( 'gallery_reorder_attachments', {
				_nonce:  cfg.nonces.reorder,
				post_id: ctx.postId,
				mode:    mode,
				ids:     ctx.ids
			} );
		} ).then( function () {
			ctx.reload();
		}, function ( err ) {
			ctx.busy( false );
			fail( err, g( 'sortError', 'Could not sort the gallery.' ) );
		} );
	}

	function sortEntry( mode, order, labelKey, fallback ) {
		api.register( {
			id:      'sort-' + mode,
			group:   'sort',
			order:   order,
			label:   function () { return g( labelKey, fallback ); },
			enabled: function ( ctx ) { return ctx.n !== 1 && ctx.total > 1; },
			run: function ( ctx ) {
				if ( ctx.n > 0 ) {
					performSort( ctx, mode );
					return;
				}
				// Nothing selected: the whole post is re-sorted. Ask first.
				ctx.confirm( {
					title:        ctx.total ? format( g( 'sortAllTitle', 'Sort all %d attachments?' ), ctx.total ) : g( 'sortAllTitleNoCount', 'Sort all attachments?' ),
					text:         [ g( labelKey, fallback ) + '.', g( 'sortAllText', 'The current order of the gallery is replaced.' ) ],
					confirmLabel: g( 'sortConfirm', 'Sort' )
				} ).then( function ( ok ) {
					if ( ok ) performSort( ctx, mode );
				} );
			}
		} );
	}

	sortEntry( 'capture_time', 10, 'byCapture', 'By capture time (EXIF)' );
	sortEntry( 'chronological', 20, 'byUpload', 'By upload date' );
	sortEntry( 'random', 30, 'shuffle', 'Shuffle' );

	// ── Delete ▾ ────────────────────────────────────────────

	api.register( {
		id:    'delete-selected',
		group: 'delete',
		order: 10,
		label: function ( ctx ) {
			if ( ! ctx.n ) return d( 'deleteItem0', 'The selected items…' );
			return ctx.n === 1 ? d( 'deleteItem1', 'The one selected…' ) : format( d( 'deleteItem', 'The %d selected…' ), ctx.n );
		},
		enabled: function ( ctx ) { return ctx.n > 0; },
		run: function ( ctx ) {
			var n = ctx.n;
			ctx.confirm( {
				title:        n === 1 ? d( 'deleteTitle1', 'Delete one attachment permanently?' ) : format( d( 'deleteTitle', 'Delete %d attachments permanently?' ), n ),
				text:         d( 'deleteText', 'They are removed from the Media Library and from disk. This cannot be undone.' ),
				confirmLabel: n === 1 ? d( 'deleteConfirm1', 'Delete one' ) : format( d( 'deleteConfirm', 'Delete %d' ), n ),
				danger:       true
			} ).then( function ( ok ) {
				if ( ! ok ) return;
				ctx.busy( true );
				ctx.post( 'tiagsspace_delete_attachments', {
					_nonce:         cfg.nonces.deleteSelected,
					attachment_ids: ctx.ids
				} ).then( function ( res ) {
					ctx.removeItems( res.data.ids || [] );
					if ( window.scheduleGalleryOrderSave ) window.scheduleGalleryOrderSave();
				}, function ( err ) {
					fail( err, d( 'deleteError', 'Could not delete the selected media.' ) );
				} ).then( function () { ctx.busy( false ); } );
			} );
		}
	} );

	api.register( {
		id:    'delete-hidden',
		group: 'delete',
		order: 20,
		label: function ( ctx ) {
			return format( g( 'hiddenItem', 'Hidden from gallery (%d)…' ), ctx.hiddenCount || 0 );
		},
		enabled: function ( ctx ) { return ( ctx.hiddenCount || 0 ) > 0; },
		run: function ( ctx ) {
			var n = ctx.hiddenCount;
			ctx.confirm( {
				title:        n === 1 ? g( 'hiddenTitle1', 'Delete one hidden attachment permanently?' ) : format( g( 'hiddenTitle', 'Delete %d hidden attachments permanently?' ), n ),
				text:         [ g( 'hiddenText', 'They are the attachments flagged “remove from default gallery”.' ), g( 'deleteText', 'They are removed from the Media Library and from disk. This cannot be undone.' ) ],
				confirmLabel: n === 1 ? g( 'deleteConfirm1', 'Delete one' ) : format( g( 'deleteConfirm', 'Delete %d' ), n ),
				danger:       true
			} ).then( function ( ok ) {
				if ( ! ok ) return;
				ctx.busy( true );
				ctx.post( 'gallery_delete_hidden_attachments', {
					_nonce:  cfg.nonces.hidden,
					post_id: ctx.postId
				} ).then( function () {
					var pool = ctx.hiddenPool();
					if ( pool ) pool.textContent = '';
					document.querySelectorAll( '.nexthidden' ).forEach( function ( b ) { b.style.display = 'none'; } );
					if ( window.galleryKeyboardReorderReset ) window.galleryKeyboardReorderReset();
					if ( api.refreshToolbar ) api.refreshToolbar();
				}, function ( err ) {
					fail( err, g( 'deleteError', 'Could not delete the hidden attachments.' ) );
				} ).then( function () { ctx.busy( false ); } );
			} );
		}
	} );

	// ── Move to ▾ ───────────────────────────────────────────

	api.register( {
		id:      'move-duplicate',
		group:   'move',
		order:   10,
		label:   function () { return d( 'duplicateItem', 'A new duplicate of this post…' ); },
		enabled: function ( ctx ) { return ctx.n > 0; },
		run: function ( ctx ) {
			var n = ctx.n;
			var tab = null;
			ctx.confirm( {
				title:        n === 1 ? d( 'duplicateTitle1', 'Move one item to a new duplicate of this post?' ) : format( d( 'duplicateTitle', 'Move %d items to a new duplicate of this post?' ), n ),
				text:         d( 'duplicateText', 'A draft copy of this post is created and the selected items leave this post for it. The draft opens in a new tab.' ),
				confirmLabel: n === 1 ? d( 'moveConfirm1', 'Move one' ) : format( d( 'moveConfirm', 'Move %d' ), n ),
				// Inside the click, so the browser lets the tab open.
				onConfirm:    function () { tab = window.open( 'about:blank', '_blank' ); }
			} ).then( function ( ok ) {
				if ( ! ok ) return;
				ctx.busy( true );
				ctx.post( 'tiagsspace_duplicate_to_post', {
					_nonce:         cfg.nonces.duplicate,
					source_id:      ctx.postId,
					attachment_ids: ctx.ids
				} ).then( function ( res ) {
					if ( tab ) {
						tab.location = res.data.edit_url;
					} else {
						ctx.notice( format( tr( 'edit', 'movedToCopy', '%d moved to a new draft' ), res.data.moved ), { href: res.data.edit_url, label: tr( 'edit', 'open', 'Open' ) } );
					}
					ctx.removeItems( ctx.ids );
				}, function ( err ) {
					if ( tab ) tab.close();
					fail( err, d( 'duplicateError', 'Could not duplicate the post.' ) );
				} ).then( function () { ctx.busy( false ); } );
			} );
		}
	} );

	api.register( {
		id:      'move-other',
		group:   'move',
		order:   20,
		label:   function () { return a( 'item', 'Another post…' ); },
		enabled: function ( ctx ) { return ctx.n > 0; },
		run: function ( ctx ) {
			var ids = ctx.ids.slice();
			var input = document.createElement( 'input' );
			input.type = 'search';
			input.className = 'tge-dialog-search';
			input.placeholder = a( 'placeholder', 'Search posts by title…' );
			input.setAttribute( 'aria-label', a( 'placeholder', 'Search posts by title…' ) );
			var list = document.createElement( 'ul' );
			list.className = 'tge-dialog-results';
			list.setAttribute( 'role', 'listbox' );
			var note = document.createElement( 'p' );
			note.className = 'tge-dialog-note';
			note.setAttribute( 'aria-live', 'polite' );
			note.textContent = a( 'hint', 'Type a title to search…' );

			var dlg = ctx.dialog( {
				title:        ids.length === 1 ? a( 'heading1', 'Move one item to another post' ) : format( a( 'heading', 'Move %d items to another post' ), ids.length ),
				className:    'tge-dialog--search',
				body:         [ input, list, note ],
				buttons:      [ { label: a( 'cancel', 'Cancel' ) } ],
				initialFocus: input
			} );

			var timer = null, seq = 0;

			function pick( item ) {
				dlg.close();
				// Opened inside the click / Enter: the browser lets the tab open.
				var tab = window.open( 'about:blank', '_blank' );
				ctx.busy( true );
				ctx.post( 'tiagsspace_media_attach_attach', {
					_nonce:         cfg.nonces.attach,
					target_id:      item.id,
					attachment_ids: ids
				} ).then( function ( res ) {
					var url = res.data.view_url || res.data.edit_url;
					if ( tab ) tab.location = url;
					ctx.notice( format( tr( 'edit', 'movedTo', '%d moved to %s' ), res.data.moved, item.title ), { href: url, label: tr( 'edit', 'open', 'Open' ) } );
					ctx.removeItems( ids );
				}, function ( err ) {
					if ( tab ) tab.close();
					fail( err, a( 'attachError', 'Could not move the media to that post.' ) );
				} ).then( function () { ctx.busy( false ); } );
			}

			function render( results ) {
				list.textContent = '';
				results.forEach( function ( r ) {
					var li = document.createElement( 'li' );
					li.className = 'tge-dialog-result';
					li.tabIndex = 0;
					li.setAttribute( 'role', 'option' );
					var title = document.createElement( 'div' );
					title.className = 'tge-dialog-result-title';
					title.textContent = r.title;
					var meta = document.createElement( 'div' );
					meta.className = 'tge-dialog-result-meta';
					meta.textContent = r.type_label + ' — ' + r.status;
					li.appendChild( title );
					li.appendChild( meta );
					li.addEventListener( 'click', function () { pick( r ); } );
					li.addEventListener( 'keydown', function ( e ) {
						if ( e.key === 'Enter' || e.key === ' ' ) {
							e.preventDefault();
							pick( r );
						} else if ( e.key === 'ArrowDown' && li.nextElementSibling ) {
							e.preventDefault();
							li.nextElementSibling.focus();
						} else if ( e.key === 'ArrowUp' ) {
							e.preventDefault();
							( li.previousElementSibling || input ).focus();
						}
					} );
					list.appendChild( li );
				} );
			}

			function search() {
				var q = input.value.trim();
				if ( q.length < 2 ) {
					render( [] );
					note.textContent = a( 'hint', 'Type a title to search…' );
					note.hidden = false;
					return;
				}
				var mine = ++seq;
				note.textContent = a( 'searching', 'Searching…' );
				note.hidden = false;
				ctx.post( 'tiagsspace_media_attach_search', {
					_nonce:          cfg.nonces.attachSearch,
					search:          q,
					current_post_id: ctx.postId
				} ).then( function ( res ) {
					if ( mine !== seq ) return;
					var results = ( res.data && res.data.results ) || [];
					render( results );
					if ( results.length ) {
						note.hidden = true;
					} else {
						note.textContent = format( a( 'noMatches', 'No posts match “%s”.' ), q );
						note.hidden = false;
					}
				}, function () {
					if ( mine !== seq ) return;
					note.textContent = a( 'searchError', 'Search failed. Try again.' );
					note.hidden = false;
				} );
			}

			input.addEventListener( 'input', function () {
				clearTimeout( timer );
				timer = setTimeout( search, 250 );
			} );
			input.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'ArrowDown' && list.firstElementChild ) {
					e.preventDefault();
					list.firstElementChild.focus();
				}
			} );
		}
	} );

	if ( api.refreshToolbar ) api.refreshToolbar();
} )();
