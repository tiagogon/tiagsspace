/**
 * Front-end gallery editor: the toolbar.
 *
 * A bar fixed to the bottom of the window with the media modal's four menus,
 * in the same order and with the same words: Delete ▾ · Select ▾ · Sort ▾ ·
 * Move to ▾, after a plain Hide button (a `direct` group: no menu, one action). When they do not fit, one "Actions ▾" with section headers
 * (ResizeObserver, like library/js/media-toolbar.js).
 *
 * This file owns all toolbar DOM: the bar, the menus and the dialogs. Actions
 * come from providers (gallery-edit/actions.js) through a registry with the
 * same contract as the admin's tiagsspaceMediaToolbar.register():
 *
 *   tiagsspaceGalleryEdit.register( {
 *       id:              'unique-id',
 *       group:           'delete' | 'select' | 'sort' | 'move',
 *       order:           10,
 *       separatorBefore: false,
 *       label:   function ( ctx ) {},  // item text ("…" when it confirms)
 *       count:   function ( ctx ) {},  // optional number at the right
 *       enabled: function ( ctx ) {},  // optional, default true
 *       prepare: function ( ctx ) {},  // optional, may return a Promise
 *       run:     function ( ctx, e ) {}
 *   } );
 *
 * ctx = { postId, ids, n, total, hiddenCount, items, cfg, selection,
 *         post( action, data ) → Promise, confirm( opts ) → Promise<bool>,
 *         dialog( opts ), busy( on ), notice( text, link ), reload(),
 *         removeItems( ids ), hiddenPool() }
 *
 * Also exposes tiagsspaceGalleryEdit.dialog() and .confirm(): focus trap, Esc
 * closes only the dialog, focus goes back to the opener.
 */
( function () {
	'use strict';

	var cfg = window.tiagsspaceGalleryEditCfg;
	var api = window.tiagsspaceGalleryEdit;
	if ( ! cfg || ! api || ! api.selection ) return;

	var T = ( cfg.i18n && cfg.i18n.toolbar ) || {};
	var E = ( cfg.i18n && cfg.i18n.edit ) || {};

	function t( key, fallback ) {
		return T[ key ] || E[ key ] || fallback;
	}

	function format( str ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return String( str ).replace( /%([ds])/g, function () {
			return args[ i++ ];
		} );
	}
	api.format = format;

	function el( tag, attrs, text ) {
		var n = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( k ) {
			if ( k === 'class' ) {
				n.className = attrs[ k ];
			} else if ( attrs[ k ] !== null && attrs[ k ] !== undefined && attrs[ k ] !== false ) {
				n.setAttribute( k, attrs[ k ] === true ? '' : attrs[ k ] );
			}
		} );
		if ( text !== undefined && text !== null ) n.textContent = text;
		return n;
	}

	// ── Registry ────────────────────────────────────────────

	var actions = [];

	api.register = function ( action ) {
		if ( ! action || ! action.id || ! action.group ) return;
		actions = actions.filter( function ( a ) { return a.id !== action.id; } );
		actions.push( action );
	};

	function groupActions( group ) {
		return actions
			.filter( function ( a ) { return a.group === group; } )
			.sort( function ( a, b ) { return ( a.order || 0 ) - ( b.order || 0 ); } );
	}

	// ── AJAX ────────────────────────────────────────────────

	// POST to admin-ajax.php; arrays become name[] pairs. Resolves with the
	// JSON body; rejects with a message.
	function post( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		Object.keys( data || {} ).forEach( function ( k ) {
			var v = data[ k ];
			if ( Array.isArray( v ) ) {
				v.forEach( function ( x ) { body.append( k + '[]', x ); } );
			} else if ( v !== undefined && v !== null ) {
				body.append( k, v );
			}
		} );
		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json().catch( function () { return { success: false }; } );
		} ).then( function ( res ) {
			if ( res && res.success ) return res;
			var msg = res && res.data && ( res.data.message || ( typeof res.data === 'string' ? res.data : '' ) );
			throw new Error( msg || '' );
		} );
	}
	api.post = post;

	// ── Dialogs ─────────────────────────────────────────────

	api.modalOpen = false;

	/**
	 * opts: { title, className, body (Node | Node[]), buttons: [ { label,
	 *         primary, danger, onClick( close, e ) } ], onClose, initialFocus }
	 * Returns { close, root, body }.
	 */
	function dialog( opts ) {
		var opener   = document.activeElement;
		var backdrop = el( 'div', { 'class': 'tge-dialog-backdrop' } );
		var titleId  = 'tge-dialog-title-' + Date.now();
		var box      = el( 'div', {
			'class':           'tge-dialog' + ( opts.className ? ' ' + opts.className : '' ),
			role:              'dialog',
			'aria-modal':      'true',
			'aria-labelledby': titleId
		} );
		var head = el( 'div', { 'class': 'tge-dialog-head' } );
		head.appendChild( el( 'h2', { id: titleId }, opts.title || '' ) );
		var x = el( 'button', { type: 'button', 'class': 'tge-dialog-close', 'aria-label': t( 'close', 'Close' ) } );
		x.innerHTML = '&times;';
		head.appendChild( x );

		var body = el( 'div', { 'class': 'tge-dialog-body' } );
		[].concat( opts.body || [] ).forEach( function ( n ) {
			if ( n ) body.appendChild( n );
		} );

		var foot = el( 'div', { 'class': 'tge-dialog-foot' } );
		var closed = false;

		function close() {
			if ( closed ) return;
			closed = true;
			backdrop.remove();
			api.modalOpen = false;
			document.documentElement.classList.remove( 'tge-modal-open' );
			if ( opts.onClose ) opts.onClose();
			if ( opener && opener.focus && document.contains( opener ) ) opener.focus();
		}

		( opts.buttons || [] ).forEach( function ( b ) {
			var cls = 'tge-button' + ( b.primary ? ' tge-button--primary' : '' ) + ( b.danger ? ' tge-button--danger' : '' );
			var btn = el( 'button', { type: 'button', 'class': cls }, b.label );
			btn.addEventListener( 'click', function ( e ) {
				if ( b.onClick ) {
					b.onClick( close, e );
				} else {
					close();
				}
			} );
			foot.appendChild( btn );
		} );

		box.appendChild( head );
		box.appendChild( body );
		if ( foot.children.length ) box.appendChild( foot );
		backdrop.appendChild( box );
		document.body.appendChild( backdrop );
		api.modalOpen = true;
		document.documentElement.classList.add( 'tge-modal-open' );

		x.addEventListener( 'click', close );
		backdrop.addEventListener( 'mousedown', function ( e ) {
			if ( e.target === backdrop ) close();
		} );

		// Keys stay inside the dialog: Esc closes it, Tab cycles, and nothing
		// reaches the gallery's own keys (arrows, H, U, N, X).
		box.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				close();
			} else if ( e.key === 'Tab' ) {
				var f = Array.prototype.filter.call(
					box.querySelectorAll( 'button, [href], input, [tabindex]:not([tabindex="-1"])' ),
					function ( n ) { return ! n.disabled && n.offsetParent !== null; }
				);
				if ( f.length ) {
					var first = f[ 0 ], last = f[ f.length - 1 ];
					if ( e.shiftKey && document.activeElement === first ) {
						e.preventDefault();
						last.focus();
					} else if ( ! e.shiftKey && document.activeElement === last ) {
						e.preventDefault();
						first.focus();
					}
				}
			}
			e.stopPropagation();
		} );

		var focusTarget = opts.initialFocus || foot.querySelector( '.tge-button--primary' ) || foot.querySelector( 'button' ) || x;
		setTimeout( function () { focusTarget.focus(); }, 0 );

		return { close: close, root: box, body: body };
	}
	api.dialog = dialog;

	/**
	 * opts: { title, text (string | string[]), confirmLabel, danger,
	 *         onConfirm() } → Promise<boolean>. onConfirm runs inside the
	 * click, so a window.open() there is not blocked.
	 */
	function confirmDialog( opts ) {
		return new Promise( function ( resolve ) {
			var ok = false;
			var paras = [].concat( opts.text || [] ).map( function ( p ) { return el( 'p', {}, p ); } );
			dialog( {
				title:     opts.title,
				className: 'tge-dialog--confirm',
				body:      paras,
				buttons:   [
					{ label: t( 'cancel', 'Cancel' ) },
					{
						label:   opts.confirmLabel || t( 'ok', 'OK' ),
						primary: ! opts.danger,
						danger:  !! opts.danger,
						onClick: function ( close ) {
							ok = true;
							if ( opts.onConfirm ) opts.onConfirm();
							close();
						}
					}
				],
				onClose: function () { resolve( ok ); }
			} );
		} );
	}
	api.confirm = confirmDialog;

	// ── Context ─────────────────────────────────────────────

	function hiddenPool() {
		return document.getElementById( cfg.poolId );
	}

	function hiddenCount() {
		var pool = hiddenPool();
		return pool ? pool.querySelectorAll( ':scope > .item' ).length : 0;
	}

	function relayout() {
		if ( window.jQuery && jQuery.fn.masonry ) {
			var $g = jQuery( api.grid );
			if ( $g.data( 'masonry' ) ) $g.masonry( 'reloadItems' ).masonry( 'layout' );
		}
	}
	api.relayout = relayout;

	function removeItems( ids ) {
		var want = {};
		( ids || [] ).forEach( function ( id ) { want[ String( id ) ] = true; } );
		api.items().forEach( function ( item ) {
			if ( want[ String( item.getAttribute( 'attachmentId' ) ) ] ) item.remove();
		} );
		var pool = hiddenPool();
		if ( pool ) {
			Array.prototype.forEach.call( pool.querySelectorAll( ':scope > .item' ), function ( item ) {
				if ( want[ String( item.getAttribute( 'attachmentId' ) ) ] ) item.remove();
			} );
		}
		if ( typeof window.galleryKeyboardReorderReset === 'function' ) window.galleryKeyboardReorderReset();
		relayout();
		sync();
	}

	function buildCtx() {
		var ids = api.selection.ids();
		var visible = api.items().length;
		var hidden = hiddenCount();
		return {
			postId:      cfg.postId,
			ids:         ids,
			n:           ids.length,
			visible:     visible,
			hiddenCount: hidden,
			total:       visible + hidden,
			items:       api.items(),
			cfg:         cfg,
			selection:   api.selection,
			post:        post,
			confirm:     confirmDialog,
			dialog:      dialog,
			busy:        setBusy,
			notice:      notice,
			reload:      function () { window.location.reload(); },
			removeItems: removeItems,
			hiddenPool:  hiddenPool,
			relayout:    relayout,
			format:      format
		};
	}

	// ── Groups: button words and menu headers (as in media-toolbar.js) ──

	var GROUPS = [ 'hide', 'delete', 'select', 'sort', 'move' ];

	var IS_MAC = /Mac|iPhone|iPad/.test( navigator.platform || navigator.userAgent || '' );

	// A `direct` group is a plain button (no menu): it runs its one registered action.
	var groupDefs = {
		hide: {
			direct:  true,
			label:   function ( ctx ) {
				return ctx.n ? format( t( 'hideN', 'Hide %d' ), ctx.n ) : t( 'hide', 'Hide' );
			},
			headers: function () { return []; },
			hint:    function () { return t( 'tipHideBar', 'Hide the selected from the gallery  H · undo  U' ); }
		},
		'delete': {
			danger:  true,
			label:   function () { return t( 'delete', 'Delete' ); },
			headers: function () { return [ { header: t( 'cannotUndo', 'Cannot be undone' ), danger: true } ]; }
		},
		select: {
			label: function ( ctx ) {
				return ctx.n ? format( t( 'selectN', 'Select (%d)' ), ctx.n ) : t( 'select', 'Select' );
			},
			headers: function () { return [ { header: t( 'shiftAdds', 'Shift-click adds to the selection' ) } ]; },
			hint: function () {
				return format( t( 'selectKeys', 'Select  S · Shift+arrows extend · %s all · Esc clears' ), IS_MAC ? '⌘A' : 'Ctrl+A' );
			}
		},
		sort: {
			label: function () { return t( 'sort', 'Sort' ); },
			headers: function ( ctx ) {
				if ( ctx.n > 1 ) {
					return [
						{ header: format( t( 'sortSelectedN', 'Sort the %d selected' ), ctx.n ) },
						{ header: t( 'sortKeepSlots', 'They keep their slots' ) }
					];
				}
				if ( ctx.n === 1 ) {
					return [ { header: t( 'sortOneSelected', 'One item selected: nothing to sort' ) } ];
				}
				return [ { header: ctx.total ? format( t( 'sortAllN', 'Sort all %d attachments' ), ctx.total ) : t( 'sortAll', 'Sort all attachments' ) } ];
			}
		},
		move: {
			label: function ( ctx ) {
				return ctx.n ? format( t( 'moveN', 'Move %d to' ), ctx.n ) : t( 'move', 'Move to' );
			},
			headers: function ( ctx ) {
				if ( ctx.n ) {
					return [
						{ header: format( t( 'moveSelectedN', 'Move the %d selected' ), ctx.n ) },
						{ header: t( 'moveLeave', 'They leave this post' ) }
					];
				}
				return [ { header: t( 'moveNone', 'Select items to move' ) } ];
			}
		}
	};

	function menuItemsFor( group, ctx ) {
		var list = groupDefs[ group ].headers( ctx ).slice();
		groupActions( group ).forEach( function ( a, i ) {
			if ( a.separatorBefore && i > 0 ) list.push( { separator: true } );
			var enabled = a.enabled ? !! a.enabled( ctx ) : true;
			list.push( {
				title:    a.label( ctx ),
				count:    a.count ? a.count( ctx ) : null,
				disabled: ! enabled,
				danger:   group === 'delete',
				onClick:  function ( e ) { return a.run( buildCtx(), e ); }
			} );
		} );
		return list;
	}

	function prepareGroups( groups, ctx ) {
		var preps = [];
		groups.forEach( function ( g ) {
			groupActions( g ).forEach( function ( a ) {
				if ( a.prepare ) {
					try {
						var p = a.prepare( ctx );
						if ( p && p.then ) preps.push( p.catch( function () {} ) );
					} catch ( err ) {}
				}
			} );
		} );
		return Promise.all( preps );
	}

	// ── Bar ─────────────────────────────────────────────────

	var bar    = el( 'div', { 'class': 'tge-bar', role: 'toolbar', 'aria-label': t( 'groupLabel', 'Gallery actions' ) } );
	var status = el( 'div', { 'class': 'tge-status', 'aria-live': 'polite' } );
	var row    = el( 'div', { 'class': 'tge-groups' } );
	bar.appendChild( status );
	bar.appendChild( row );

	var buttons = {};
	var openMenu = null;

	function makeButton( key, extra ) {
		var direct = !! ( groupDefs[ key ] && groupDefs[ key ].direct );
		var b = el( 'button', {
			type:            'button',
			'class':         'tge-button tge-group' + ( extra ? ' ' + extra : '' ),
			'aria-haspopup': direct ? null : 'menu',
			'aria-expanded': direct ? null : 'false'
		} );
		b.appendChild( el( 'span', { 'class': 'tge-group-text' } ) );
		if ( direct ) {
			b.addEventListener( 'click', function ( e ) {
				var a = groupActions( key )[ 0 ];
				var ctx = buildCtx();
				closeMenu( false );
				if ( a && ( ! a.enabled || a.enabled( ctx ) ) ) a.run( ctx, e );
			} );
			row.appendChild( b );
			buttons[ key ] = b;
			return b;
		}
		b.appendChild( el( 'span', { 'class': 'tge-caret', 'aria-hidden': 'true' }, '▾' ) );
		b.addEventListener( 'click', function () {
			if ( openMenu && openMenu.button === b ) {
				closeMenu( true );
			} else {
				openMenuFor( key, b );
			}
		} );
		b.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowUp' || e.key === 'ArrowDown' ) {
				e.preventDefault();
				openMenuFor( key, b, e.key === 'ArrowUp' ? 'last' : 'first' );
			}
		} );
		row.appendChild( b );
		buttons[ key ] = b;
		return b;
	}

	GROUPS.forEach( function ( g ) {
		makeButton( g, groupDefs[ g ].danger ? 'tge-group--danger' : '' );
	} );
	makeButton( 'all', 'tge-group--all' );

	// Nothing typed on the bar reaches the gallery's keys.
	bar.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && openMenu ) {
			e.preventDefault();
			closeMenu( true );
		}
		e.stopPropagation();
	} );

	function setLabel( b, text ) {
		b.querySelector( '.tge-group-text' ).textContent = text;
	}

	var noticeTimer = null;
	var noticeNode  = null;

	function notice( text, link ) {
		clearTimeout( noticeTimer );
		noticeNode = el( 'span', { 'class': 'tge-notice' }, text );
		if ( link && link.href ) {
			noticeNode.appendChild( document.createTextNode( ' · ' ) );
			noticeNode.appendChild( el( 'a', { href: link.href, target: '_blank', rel: 'noopener' }, link.label ) );
		}
		renderStatus();
		noticeTimer = setTimeout( function () {
			noticeNode = null;
			renderStatus();
		}, 8000 );
	}

	function renderStatus() {
		var ctx = buildCtx();
		status.textContent = '';
		if ( ctx.n ) {
			status.appendChild( el( 'span', {}, format( ctx.n === 1 ? t( 'selectedOne', '%d selected' ) : t( 'selectedN', '%d selected' ), ctx.n ) ) );
			status.appendChild( document.createTextNode( ' · ' ) );
			var clear = el( 'button', { type: 'button', 'class': 'tge-link' }, t( 'clear', 'Clear' ) );
			clear.addEventListener( 'click', function () { api.selection.clear(); } );
			status.appendChild( clear );
		} else {
			status.appendChild( el( 'span', {}, format( ctx.total === 1 ? t( 'attachmentsOne', '%d attachment' ) : t( 'attachmentsN', '%d attachments' ), ctx.total ) ) );
		}
		if ( noticeNode ) {
			status.appendChild( document.createTextNode( ' — ' ) );
			status.appendChild( noticeNode );
		}
	}

	function sync() {
		var ctx = buildCtx();
		GROUPS.forEach( function ( g ) {
			setLabel( buttons[ g ], groupDefs[ g ].label( ctx ) );
			var hint = groupDefs[ g ].hint ? groupDefs[ g ].hint( ctx ) : '';
			if ( hint ) buttons[ g ].setAttribute( 'data-tip', hint );
			if ( groupDefs[ g ].direct && ! bar.classList.contains( 'is-busy' ) ) {
				var a = groupActions( g )[ 0 ];
				buttons[ g ].disabled = ! a || ( a.enabled ? ! a.enabled( ctx ) : false );
			}
		} );
		setLabel( buttons.all, ctx.n ? format( t( 'actionsN', 'Actions (%d)' ), ctx.n ) : t( 'actions', 'Actions' ) );
		renderStatus();
		layout();
	}
	api.refreshToolbar = sync;

	function setBusy( on ) {
		bar.classList.toggle( 'is-busy', !! on );
		Array.prototype.forEach.call( row.querySelectorAll( 'button' ), function ( b ) { b.disabled = !! on; } );
		if ( ! on ) sync();
	}

	// Four buttons, or one "Actions ▾" when they would not fit beside the status.
	function layout() {
		bar.classList.remove( 'is-overflow' );
		var need = status.scrollWidth + row.scrollWidth + 48;
		if ( need > bar.clientWidth ) bar.classList.add( 'is-overflow' );
	}

	// ── Menus ───────────────────────────────────────────────

	function closeMenu( focusButton ) {
		if ( ! openMenu ) return;
		var m = openMenu;
		openMenu = null;
		m.node.remove();
		m.button.setAttribute( 'aria-expanded', 'false' );
		document.removeEventListener( 'mousedown', onDocDown, true );
		if ( focusButton ) m.button.focus();
	}

	function onDocDown( e ) {
		if ( openMenu && ! openMenu.node.contains( e.target ) && ! openMenu.button.contains( e.target ) ) {
			closeMenu( false );
		}
	}

	function renderMenu( menu, items ) {
		menu.textContent = '';
		items.forEach( function ( item ) {
			if ( item.separator ) {
				menu.appendChild( el( 'div', { 'class': 'tge-menu-sep', role: 'separator' } ) );
				return;
			}
			if ( item.header ) {
				menu.appendChild( el( 'div', {
					'class': 'tge-menu-header' + ( item.title ? ' tge-menu-title' : '' ) + ( item.danger ? ' tge-menu-danger' : '' )
				}, item.header ) );
				return;
			}
			var b = el( 'button', {
				type:     'button',
				'class':  'tge-menu-item' + ( item.danger ? ' tge-menu-danger' : '' ),
				role:     'menuitem',
				disabled: item.disabled
			} );
			b.appendChild( el( 'span', {}, item.title ) );
			if ( item.count !== null && item.count !== undefined ) {
				b.appendChild( el( 'span', { 'class': 'tge-menu-count' }, String( item.count ) ) );
			}
			b.addEventListener( 'click', function ( e ) {
				if ( item.disabled ) return;
				closeMenu( true );
				item.onClick( e );
			} );
			menu.appendChild( b );
		} );
	}

	function menuButtons( menu ) {
		return Array.prototype.filter.call( menu.querySelectorAll( '.tge-menu-item' ), function ( b ) { return ! b.disabled; } );
	}

	function openMenuFor( key, button, focusWhich ) {
		closeMenu( false );
		var menu = el( 'div', { 'class': 'tge-menu', role: 'menu' } );
		bar.appendChild( menu );
		openMenu = { node: menu, button: button };
		button.setAttribute( 'aria-expanded', 'true' );
		document.addEventListener( 'mousedown', onDocDown, true );

		var groups = key === 'all' ? GROUPS : [ key ];
		var ctx = buildCtx();
		renderMenu( menu, [ { header: t( 'loading', 'Loading…' ) } ] );
		position( menu, button );

		prepareGroups( groups, ctx ).then( function () {
			if ( ! openMenu || openMenu.node !== menu ) return;
			var fresh = buildCtx();
			var list = [];
			if ( key === 'all' ) {
				groups.forEach( function ( g, i ) {
					if ( i > 0 ) list.push( { separator: true } );
					// A direct group's item already says what it does: no title above it.
					if ( ! groupDefs[ g ].direct ) {
						list.push( { header: groupDefs[ g ].label( fresh ), title: true, danger: !! groupDefs[ g ].danger } );
					}
					list = list.concat( menuItemsFor( g, fresh ) );
				} );
			} else {
				list = menuItemsFor( key, fresh );
			}
			renderMenu( menu, list );
			position( menu, button );
			var items = menuButtons( menu );
			if ( items.length ) {
				( focusWhich === 'last' ? items[ items.length - 1 ] : items[ 0 ] ).focus();
			}
		} );

		menu.addEventListener( 'keydown', function ( e ) {
			var items = menuButtons( menu );
			var i = items.indexOf( document.activeElement );
			if ( e.key === 'ArrowDown' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				if ( ! items.length ) return;
				i = e.key === 'ArrowDown' ? ( i + 1 ) % items.length : ( i - 1 + items.length ) % items.length;
				items[ i ].focus();
			} else if ( e.key === 'Home' || e.key === 'End' ) {
				e.preventDefault();
				if ( items.length ) items[ e.key === 'Home' ? 0 : items.length - 1 ].focus();
			} else if ( e.key === 'Escape' ) {
				e.preventDefault();
				closeMenu( true );
			} else if ( e.key === 'Tab' ) {
				closeMenu( false );
			}
			e.stopPropagation();
		} );
	}

	// Above its button, kept inside the window.
	function position( menu, button ) {
		var barRect = bar.getBoundingClientRect();
		var bRect   = button.getBoundingClientRect();
		menu.style.left = '0px';
		var width = menu.offsetWidth;
		var left  = bRect.right - barRect.left - width;
		left = Math.max( 8, Math.min( left, barRect.width - width - 8 ) );
		menu.style.left = left + 'px';
	}

	// ── Mount ───────────────────────────────────────────────

	// Inline in the entry footer, after its meta line (template-parts/entry-body.php
	// prints [data-tge-mount]); a bar fixed to the window's bottom only where the
	// page has no such footer. The footer comes after the gallery in the page, so
	// mounting waits for the document.
	var inline = false;

	function barHeight() {
		document.documentElement.style.setProperty( '--tge-bar-h', bar.offsetHeight + 'px' );
	}

	// The footer is a Bootstrap collapse behind the date's "+": picking an image
	// opens it, so the actions are there as soon as there is something to act on.
	function revealFooter( ids ) {
		if ( ! inline || ! ids.length ) return;
		var footer = bar.closest( '.collapse' );
		if ( ! footer || footer.classList.contains( 'show' ) || footer.classList.contains( 'collapsing' ) ) return;
		if ( window.jQuery && jQuery.fn.collapse ) {
			jQuery( footer ).collapse( 'show' );
		} else {
			footer.classList.add( 'show' );
		}
	}

	function mount() {
		var slot = document.querySelector( '[data-tge-mount="' + cfg.postId + '"]' ) || document.querySelector( '[data-tge-mount]' );
		if ( slot ) {
			inline = true;
			bar.classList.add( 'tge-bar--inline' );
			slot.appendChild( bar );
		} else {
			document.body.appendChild( bar );
			document.documentElement.classList.add( 'tge-has-bar' );
			barHeight();
		}

		if ( window.ResizeObserver ) {
			new ResizeObserver( function () {
				if ( ! inline ) barHeight();
				layout();
			} ).observe( bar );
		}
		window.addEventListener( 'resize', layout );

		api.selection.on( sync );
		api.selection.on( revealFooter );
		if ( window.MutationObserver ) {
			new MutationObserver( sync ).observe( api.grid, { childList: true } );
			var pool = hiddenPool();
			if ( pool ) new MutationObserver( sync ).observe( pool, { childList: true } );
		}

		sync();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', mount );
	} else {
		mount();
	}
} )();
