/**
 * Media modal toolbar: the theme's action menus in the editor's "Add media"
 * modal.
 *
 * One group of four menu buttons, prepended before core's "Insert into post"
 * in the primary (right) toolbar, read in the order the work flows:
 *
 *   [Delete ▾]  [Select ▾] [Sort ▾] [Move to ▾]          [Insert into post]
 *
 * The buttons are fixed; what they hold is a registry. A sibling script adds
 * an action with
 *
 *   tiagsspaceMediaToolbar.register( {
 *       id:      'sort-capture',
 *       group:   'sort',                 // delete | select | sort | move
 *       order:   10,                     // position inside the group
 *       label:   function ( ctx ) {},    // item text ("…" when it confirms)
 *       count:   function ( ctx ) {},    // optional number shown at the right
 *       enabled: function ( ctx ) {},    // optional, default true
 *       prepare: function ( ctx ) {},    // optional promise run before the
 *                                        // menu renders (load the library,
 *                                        // fetch a count…)
 *       run:     function ( ctx, e ) {}  // the action
 *   } );
 *
 * `ctx` is built per menu open: frame, state, selection, library, postId,
 * n (selected count), total (library size), ids() in gallery order, busy(),
 * refresh(props), confirm(opts), dialog(opts), format(), i18n.
 *
 * Scope: only MediaFrame.Post frames (the editor's own Add media, which the
 * theme's gallery and both editors open) whose state allows multiple
 * selection and whose post id resolves. Plugin pickers (Instagram, Tumblr,
 * Fediverse, ACF, Yoast) are wp.media() Select frames and get nothing; so do
 * the featured-image and Image-block modals. Everything is scoped to
 * frame.$el; listeners are bound once per frame state and unbound on rebuild.
 *
 * Overflow: a ResizeObserver on the toolbar measures the row and, when the
 * four buttons would not fit beside the selection strip's text and the
 * primary button, swaps them for one "Actions ▾" menu with the same items in
 * sections. Nothing ever wraps onto the grid.
 *
 * Also owned here: the popover menu (keyboard: Arrow/Home/End, Esc closes the
 * menu only), the dialog scaffold and confirm panel both deletes use, and the
 * `data-hidden` flag on grid items (library/styles/admin-media.css dims them).
 * Styles live in library/styles/admin-media.css (sections 1–3).
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! wp.media.view || ! window.tiagsspaceMediaFrame || ! window.tiagsspaceMediaToolbarCfg ) {
		return;
	}
	if ( window.tiagsspaceMediaToolbar ) {
		return;
	}

	var wire      = window.tiagsspaceMediaFrame;
	var cfg       = window.tiagsspaceMediaToolbarCfg;
	var i18n      = cfg.i18n || {};
	var format    = wire.format;
	var GROUPS    = [ 'delete', 'select', 'sort', 'move' ];
	var MAX_PAGES = 50;
	var INST      = '__tiagsspaceToolbar';
	var uid       = 0;

	function t( key, fallback ) {
		return i18n[ key ] || fallback;
	}

	/* ---------------------------------------------------------------------
	 * Registry
	 * ------------------------------------------------------------------ */

	var actions = [];

	function register( action ) {
		if ( ! action || ! action.id || GROUPS.indexOf( action.group ) === -1 || typeof action.run !== 'function' ) {
			if ( window.console && console.warn ) {
				console.warn( 'tiagsspaceMediaToolbar: invalid registration', action );
			}
			return;
		}
		actions = actions.filter( function ( a ) {
			return a.id !== action.id;
		} );
		actions.push( action );
	}

	function groupActions( group ) {
		return actions
			.filter( function ( a ) {
				return a.group === group;
			} )
			.sort( function ( a, b ) {
				return ( a.order || 0 ) - ( b.order || 0 );
			} );
	}

	/**
	 * Group chrome: button label, menu header lines, tone.
	 */
	var groupDefs = {
		'delete': {
			danger: true,
			label: function () {
				return t( 'delete', 'Delete' );
			},
			headers: function () {
				return [ { header: t( 'cannotUndo', 'Cannot be undone' ), danger: true } ];
			}
		},
		select: {
			label: function ( ctx ) {
				return ctx.n ? format( t( 'selectN', 'Select (%d)' ), ctx.n ) : t( 'select', 'Select' );
			},
			headers: function () {
				return [ { header: t( 'shiftAdds', 'Shift-click adds to the selection' ) } ];
			}
		},
		sort: {
			label: function () {
				return t( 'sort', 'Sort' );
			},
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

	/* ---------------------------------------------------------------------
	 * Library helpers
	 * ------------------------------------------------------------------ */

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
	 * Dialogs
	 * ------------------------------------------------------------------ */

	/**
	 * Highest z-index among the rendered media-modal layers, read live: the
	 * modal stacks high and the value has drifted across WP versions.
	 */
	function topMediaZ() {
		var max = 0;
		document.querySelectorAll( '.media-modal, .media-modal-backdrop, .media-frame' ).forEach( function ( el ) {
			var z = parseInt( window.getComputedStyle( el ).zIndex, 10 );
			if ( ! isNaN( z ) && z > max ) {
				max = z;
			}
		} );
		return max;
	}

	/**
	 * Modal dialog above the media modal.
	 *
	 * opts: { title, className, $body, buttons: [ { label, primary, danger,
	 *         onClick( close ) } ], onClose }
	 * Returns { $panel, $body, close }. Esc, the × and the backdrop close it;
	 * Tab is trapped inside; focus returns to the opener on close.
	 */
	function dialog( opts ) {
		opts = opts || {};
		var id        = 'tiagsspace-dialog-' + ( ++uid );
		var opener    = document.activeElement;
		var $backdrop = $( '<div>', { 'class': 'tiagsspace-dialog-backdrop' } );
		var $panel    = $( '<div>', {
			'class':           'tiagsspace-dialog' + ( opts.className ? ' ' + opts.className : '' ),
			role:              'dialog',
			'aria-modal':      'true',
			'aria-labelledby': id + '-title',
			tabindex:          -1
		} );
		var $head = $( '<div>', { 'class': 'tiagsspace-dialog-head' } )
			.append( $( '<h2>', { id: id + '-title' } ).text( opts.title || '' ) )
			.append( $( '<button>', { type: 'button', 'class': 'tiagsspace-dialog-close', 'aria-label': t( 'close', 'Close' ) } ).html( '&times;' ) );
		var $body = $( '<div>', { 'class': 'tiagsspace-dialog-body' } ).append( opts.$body || '' );
		var $foot = $( '<div>', { 'class': 'tiagsspace-dialog-foot' } );
		var closed = false;

		function close() {
			if ( closed ) {
				return;
			}
			closed = true;
			$backdrop.remove();
			$( document ).off( 'keydown.' + id );
			if ( opts.onClose ) {
				opts.onClose();
			}
			if ( opener && opener.focus && document.body.contains( opener ) ) {
				opener.focus();
			}
		}

		( opts.buttons || [] ).forEach( function ( b ) {
			var cls = 'button button-large';
			if ( b.primary ) {
				cls += ' button-primary';
			}
			if ( b.danger ) {
				cls += ' tiagsspace-dialog-danger';
			}
			var $b = $( '<button>', { type: 'button', 'class': cls } ).text( b.label );
			$b.on( 'click', function ( e ) {
				e.preventDefault();
				if ( b.onClick ) {
					b.onClick( close, $b );
				} else {
					close();
				}
			} );
			$foot.append( $b );
		} );

		$panel.append( $head, $body );
		if ( $foot.children().length ) {
			$panel.append( $foot );
		}
		$backdrop.append( $panel );
		$( 'body' ).append( $backdrop );
		$backdrop.css( 'z-index', ( topMediaZ() || 990000 ) + 10000 );

		$head.find( '.tiagsspace-dialog-close' ).on( 'click', close );
		$backdrop.on( 'mousedown', function ( e ) {
			if ( e.target === $backdrop[ 0 ] ) {
				close();
			}
		} );
		$( document ).on( 'keydown.' + id, function ( e ) {
			if ( 'Escape' === e.key ) {
				e.stopPropagation(); // ours only; the media modal stays open
				e.preventDefault();
				close();
				return;
			}
			if ( 'Tab' === e.key ) {
				var $f = $panel.find( 'button, input, [tabindex="0"]' ).filter( ':visible:not([disabled])' );
				if ( ! $f.length ) {
					return;
				}
				var first = $f[ 0 ], last = $f[ $f.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				} else if ( ! e.shiftKey && document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				} else if ( ! $panel[ 0 ].contains( document.activeElement ) ) {
					e.preventDefault();
					first.focus();
				}
			}
		} );

		var $autofocus = $panel.find( '[autofocus]' ).first();
		if ( $autofocus.length ) {
			$autofocus.trigger( 'focus' );
		} else {
			var $primary = $foot.find( '.button-primary, .tiagsspace-dialog-danger' ).first();
			( $primary.length ? $primary : $panel ).trigger( 'focus' );
		}

		return { $panel: $panel, $body: $body, close: close };
	}

	/**
	 * Confirm panel. opts: { title, text (string or array of paragraphs),
	 * confirmLabel, cancelLabel, danger }. Resolves true on confirm, false
	 * otherwise.
	 */
	function confirm( opts ) {
		opts = opts || {};
		var d     = $.Deferred();
		var $body = $( '<div>' );
		( $.isArray( opts.text ) ? opts.text : [ opts.text ] ).forEach( function ( p ) {
			if ( p ) {
				$body.append( $( '<p>' ).text( p ) );
			}
		} );
		var answered = false;
		dialog( {
			title:     opts.title,
			className: 'tiagsspace-dialog--confirm',
			$body:     $body,
			buttons: [
				{ label: opts.cancelLabel || t( 'cancel', 'Cancel' ) },
				{
					label:   opts.confirmLabel || t( 'ok', 'OK' ),
					primary: ! opts.danger,
					danger:  !! opts.danger,
					onClick: function ( close ) {
						answered = true;
						close();
						d.resolve( true );
					}
				}
			],
			onClose: function () {
				if ( ! answered ) {
					d.resolve( false );
				}
			}
		} );
		return d.promise();
	}

	/* ---------------------------------------------------------------------
	 * Popover menu
	 * ------------------------------------------------------------------ */

	var openMenu = null; // at most one open menu, across frames

	/**
	 * items: [ { header, title, danger } | { separator: true } |
	 *          { label, count, disabled, danger, onClick( e ) } ]
	 * opts.onOpen(): returns the items array or a promise of one.
	 */
	function buildMenu( $toolbar, $button, opts ) {
		var $menu = $( '<div>', { 'class': 'tiagsspace-menu', role: 'menu', hidden: true } );
		$menu.attr( 'aria-labelledby', $button.attr( 'id' ) );
		$toolbar.append( $menu );
		$button.attr( { 'aria-haspopup': 'menu', 'aria-expanded': 'false' } );

		var api = {};

		function render( items ) {
			$menu.empty();
			$.each( items || [], function ( _, item ) {
				if ( item.header ) {
					var $h = $( '<div>', { 'class': 'tiagsspace-menu-header' } ).text( item.header );
					if ( item.title ) {
						$h.addClass( 'tiagsspace-menu-title' );
					}
					if ( item.danger ) {
						$h.addClass( 'tiagsspace-menu-danger' );
					}
					$menu.append( $h );
					return;
				}
				if ( item.separator ) {
					$menu.append( $( '<div>', { 'class': 'tiagsspace-menu-sep', role: 'separator' } ) );
					return;
				}
				var $item = $( '<button>', { type: 'button', 'class': 'tiagsspace-menu-item', role: 'menuitem' } );
				$item.append( $( '<span>' ).text( item.label ) );
				if ( typeof item.count === 'number' ) {
					$item.append( $( '<span>', { 'class': 'tiagsspace-menu-count' } ).text( item.count ) );
				}
				if ( item.danger ) {
					$item.addClass( 'tiagsspace-menu-danger' );
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
			var $items = $menu.find( '.tiagsspace-menu-item:not([disabled])' );
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

		function closeAndFocus( e ) {
			e.preventDefault();
			e.stopPropagation(); // keep the media modal open
			api.close();
			$button.trigger( 'focus' );
		}

		$menu.on( 'keydown', function ( e ) {
			switch ( e.key ) {
				case 'Escape':
					closeAndFocus( e );
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
				render( [ { header: opts.loadingText || t( 'loading', 'Loading…' ) } ] );
				result.then( function ( items ) {
					if ( api.isOpen() ) {
						render( items );
						position();
						focusItem( 1 );
					}
				} );
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

		api.destroy = function () {
			api.close();
			$menu.remove();
		};

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
			} else if ( e.key === 'Escape' && api.isOpen() ) {
				closeAndFocus( e );
			}
		} );

		return api;
	}

	/* ---------------------------------------------------------------------
	 * Context
	 * ------------------------------------------------------------------ */

	function buildCtx( frame, inst ) {
		var selection = inst.selection;
		var library   = inst.library;
		var ctx = {
			frame:     frame,
			state:     inst.state,
			selection: selection,
			library:   library,
			postId:    wire.postId(),
			n:         selection ? selection.length : 0,
			total:     libraryTotal( library ),
			i18n:      i18n,
			format:    format,
			ids: function () {
				return wire.orderedIds( selection );
			},
			busy: function ( on ) {
				inst.busy( !! on );
			},
			/**
			 * After an action changed the post's media: clear the selection,
			 * flush caches and re-fetch the library (optionally re-sorted).
			 */
			refresh: function ( props ) {
				if ( selection ) {
					selection.reset();
				}
				wire.flushCaches();
				wire.refreshLibrary( frame, props );
			},
			confirm: confirm,
			dialog:  dialog,
			ensureLibraryLoaded: function () {
				return ensureLibraryLoaded( library );
			}
		};
		return ctx;
	}

	/* ---------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */

	function itemsFor( group, ctx ) {
		var def  = groupDefs[ group ];
		var list = def.headers( ctx ).slice();
		var regs = groupActions( group );
		regs.forEach( function ( a, i ) {
			if ( a.separatorBefore && i > 0 ) {
				list.push( { separator: true } );
			}
			var count = typeof a.count === 'function' ? a.count( ctx ) : undefined;
			list.push( {
				label:    a.label( ctx ),
				count:    typeof count === 'number' ? count : undefined,
				disabled: typeof a.enabled === 'function' ? ! a.enabled( ctx ) : false,
				danger:   !! ( def.danger || a.danger ),
				onClick:  function ( e ) {
					a.run( ctx, e );
				}
			} );
		} );
		if ( ! regs.length ) {
			list.push( { label: t( 'nothingHere', 'Nothing registered' ), disabled: true } );
		}
		return list;
	}

	/**
	 * Run every distinct prepare() of the groups, then build the items.
	 */
	function openGroups( groups, inst, build ) {
		var ctx   = buildCtx( inst.frame, inst );
		var preps = [];
		var seen  = [];
		groups.forEach( function ( g ) {
			groupActions( g ).forEach( function ( a ) {
				if ( typeof a.prepare === 'function' && seen.indexOf( a.prepare ) === -1 ) {
					seen.push( a.prepare );
					var p = null;
					try {
						p = a.prepare( ctx );
					} catch ( e ) {}
					if ( p && p.then ) {
						preps.push( p );
					}
				}
			} );
		} );
		if ( ! preps.length ) {
			return build( ctx );
		}
		return $.when.apply( $, preps ).then( function () {
			// The library may have grown; counts come from the fresh ctx.
			ctx.n     = ctx.selection ? ctx.selection.length : 0;
			ctx.total = libraryTotal( ctx.library );
			return build( ctx );
		}, function () {
			return build( ctx );
		} );
	}

	function teardown( frame ) {
		var inst = frame[ INST ];
		if ( ! inst ) {
			return;
		}
		inst.off();
		$.each( inst.menus, function ( _, m ) {
			m.destroy();
		} );
		if ( inst.observer ) {
			inst.observer.disconnect();
		}
		inst.$wrap.remove();
		frame[ INST ] = null;
	}

	function isPostFrame( frame ) {
		var Post = wp.media.view.MediaFrame.Post;
		return !! ( Post && frame instanceof Post );
	}

	function render( frame ) {
		var $root = frame && frame.$el ? frame.$el : null;
		if ( ! $root || ! $root.length ) {
			return;
		}
		var $toolbar = $root.find( '.media-frame-toolbar .media-toolbar' ).first();
		var $bar     = $toolbar.find( '.media-toolbar-primary' ).first();
		var state    = wire.state( frame );
		var multiple = false;
		try {
			multiple = !! ( state && state.get( 'multiple' ) );
		} catch ( e ) {}
		var selection = wire.selection( frame );
		var library   = wire.library( frame );
		var optOut    = frame.options && frame.options.tiagsspaceToolbar === false;

		if ( ! $bar.length || ! isPostFrame( frame ) || optOut || ! multiple || ! selection || ! wire.postId() ) {
			teardown( frame );
			return;
		}

		var inst = frame[ INST ];
		if ( inst && inst.state === state && inst.selection === selection && $.contains( $bar[ 0 ], inst.$wrap[ 0 ] ) ) {
			inst.sync();
			return;
		}
		teardown( frame );

		var $wrap = $( '<span>', { 'class': 'tiagsspace-mt', role: 'group', 'aria-label': t( 'groupLabel', 'Gallery actions' ) } );
		var buttons = {};
		var menus   = {};

		inst = {
			frame:     frame,
			state:     state,
			selection: selection,
			library:   library,
			$wrap:     $wrap,
			menus:     menus,
			buttons:   buttons,
			busyCount: 0
		};
		frame[ INST ] = inst;

		function makeButton( key, extraClass ) {
			var $b = $( '<button>', {
				type:    'button',
				id:      'tiagsspace-mt-' + key + '-' + ( ++uid ),
				'class': 'button media-button button-large ' + extraClass
			} );
			$b.append( $( '<span>', { 'class': 'tiagsspace-mt-text' } ) )
				.append( ' ' )
				.append( $( '<span>', { 'class': 'tiagsspace-mt-caret', 'aria-hidden': 'true' } ).text( '▾' ) );
			buttons[ key ] = $b;
			$wrap.append( $b );
			return $b;
		}

		GROUPS.forEach( function ( g ) {
			var $b = makeButton( g, 'tiagsspace-mt-group' + ( groupDefs[ g ].danger ? ' tiagsspace-mt-danger' : '' ) );
			menus[ g ] = buildMenu( $toolbar, $b, {
				onOpen: function () {
					return openGroups( [ g ], inst, function ( ctx ) {
						return itemsFor( g, ctx );
					} );
				}
			} );
		} );

		var $all = makeButton( 'all', 'tiagsspace-mt-all' );
		menus.all = buildMenu( $toolbar, $all, {
			onOpen: function () {
				return openGroups( GROUPS, inst, function ( ctx ) {
					var list = [];
					GROUPS.forEach( function ( g, i ) {
						if ( i ) {
							list.push( { separator: true } );
						}
						list.push( { header: groupDefs[ g ].label( ctx ), title: true, danger: !! groupDefs[ g ].danger } );
						list = list.concat( itemsFor( g, ctx ) );
					} );
					return list;
				} );
			}
		} );

		$bar.prepend( $wrap );

		function setLabel( $b, text ) {
			$b.find( '.tiagsspace-mt-text' ).text( text );
		}

		inst.sync = function () {
			var ctx = buildCtx( frame, inst );
			GROUPS.forEach( function ( g ) {
				setLabel( buttons[ g ], groupDefs[ g ].label( ctx ) );
			} );
			setLabel( $all, ctx.n ? format( t( 'actionsN', 'Actions (%d)' ), ctx.n ) : t( 'actions', 'Actions' ) );
			inst.layout();
		};

		/**
		 * Full row or one "Actions ▾": measure the four buttons against the
		 * room left beside the selection strip's text and the other primary
		 * buttons. Both measurements happen in one task, so nothing paints
		 * in between.
		 */
		inst.layout = function () {
			try {
				$wrap.removeClass( 'is-overflow' );
				var need = 0;
				GROUPS.forEach( function ( g ) {
					need += buttons[ g ].outerWidth( true ) + 6;
				} );
				need += 24; // group margins
				var others = 0;
				$bar.children().not( $wrap ).each( function () {
					others += $( this ).outerWidth( true );
				} );
				var info   = $toolbar.find( '.media-selection .selection-info' )[ 0 ];
				var infoW  = info ? info.offsetWidth + 26 : 0;
				var room   = $toolbar[ 0 ].clientWidth - 32 - infoW - others;
				if ( need > room ) {
					$wrap.addClass( 'is-overflow' );
				}
			} catch ( e ) {}
		};

		inst.busy = function ( on ) {
			inst.busyCount = Math.max( 0, inst.busyCount + ( on ? 1 : -1 ) );
			$wrap.find( 'button' ).prop( 'disabled', inst.busyCount > 0 );
		};

		var onChange = function () {
			inst.sync();
		};
		if ( selection && selection.on ) {
			selection.on( 'add remove reset', onChange );
		}
		if ( library && library.on ) {
			library.on( 'add remove reset', onChange );
		}
		inst.off = function () {
			if ( selection && selection.off ) {
				selection.off( 'add remove reset', onChange );
			}
			if ( library && library.off ) {
				library.off( 'add remove reset', onChange );
			}
		};

		if ( window.ResizeObserver ) {
			inst.observer = new ResizeObserver( function () {
				inst.layout();
			} );
			inst.observer.observe( $toolbar[ 0 ] );
		}

		inst.sync();
	}

	/* ---------------------------------------------------------------------
	 * Frame wiring
	 * ------------------------------------------------------------------ */

	function wireFrame( frame ) {
		if ( ! isPostFrame( frame ) ) {
			return;
		}
		frame.on( 'open', function () {
			setTimeout( function () {
				render( frame );
			}, 0 );
		} );
		// State switches (Create gallery and back) re-render the toolbar.
		frame.on( 'content:render', function () {
			setTimeout( function () {
				render( frame );
			}, 0 );
		} );
		frame.on( 'close', function () {
			if ( openMenu ) {
				openMenu.close();
			}
		} );
	}

	wire.onFrame( 'media-toolbar', wireFrame );

	/* ---------------------------------------------------------------------
	 * Grid: flag items hidden from the gallery (dimmed by admin-media.css)
	 * ------------------------------------------------------------------ */

	( function () {
		var Library = wp.media.view.Attachment && wp.media.view.Attachment.Library;
		if ( ! Library || ! Library.prototype.render ) {
			return;
		}
		var originalRender = Library.prototype.render;
		Library.prototype.render = function () {
			var out = originalRender.apply( this, arguments );
			try {
				if ( this.model && this.model.get( 'tiagsspaceHidden' ) ) {
					this.$el.attr( 'data-hidden', '1' );
				} else {
					this.$el.removeAttr( 'data-hidden' );
				}
			} catch ( e ) {}
			return out;
		};
	} )();

	window.tiagsspaceMediaToolbar = {
		register:            register,
		dialog:              dialog,
		confirm:             confirm,
		ensureLibraryLoaded: ensureLibraryLoaded,
		libraryTotal:        libraryTotal,
		topMediaZ:           topMediaZ
	};
} )( jQuery );
