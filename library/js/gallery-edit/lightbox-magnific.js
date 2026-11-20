/**
 * Front-end gallery editor: the Magnific Popup adapter for gallery-edit/lightbox.js.
 *
 * Only Magnific's public surface, no edit to the vendored plugin:
 *  - events it triggers on the gallery element: mfpOpen, mfpChange,
 *    mfpBeforeClose;
 *  - $.magnificPopup.instance: isOpen, ev (the gallery it opened from),
 *    currItem, items, index, wrap, close();
 *  - $( grid ).magnificPopup( 'open', i ): while open, Magnific rebuilds its
 *    item list from the `delegate` links and re-renders without re-opening.
 *    Its stored options keep the list of the first open (`items`), so that is
 *    cleared first: the list is then read from the grid as it is now.
 *
 * Scoped to its own grid (instance.ev[0] === grid): another gallery's
 * lightbox on the same page is left alone.
 */
( function ( $ ) {
	'use strict';

	var api = window.tiagsspaceGalleryEdit;
	if ( ! $ || ! $.magnificPopup || ! api || ! api.lightbox || ! api.grid ) return;

	var grid  = api.grid;
	var $grid = $( grid );
	var LINK  = 'a.magnific-popup-link';

	function inst() {
		return $.magnificPopup.instance;
	}

	function mine( m ) {
		return !! ( m && m.ev && m.ev[ 0 ] === grid );
	}

	function itemOf( node ) {
		while ( node && node.parentNode !== grid ) node = node.parentNode;
		return node && node.classList && node.classList.contains( 'item' ) ? node : null;
	}

	function linkOf( item ) {
		return item ? item.querySelector( LINK ) : null;
	}

	function elOf( it ) {
		return it && it.el ? it.el[ 0 ] : it;
	}

	function links() {
		return api.items().map( linkOf ).filter( Boolean );
	}

	api.lightbox.use( {
		name: 'magnific_popup',

		isOpen: function () {
			var m = inst();
			return !! ( m && m.isOpen && mine( m ) );
		},

		current: function () {
			var m = inst();
			return mine( m ) && m.currItem ? itemOf( elOf( m.currItem ) ) : null;
		},

		canShow: function ( item ) {
			return !! linkOf( item );
		},

		show: function ( item ) {
			var m = inst();
			if ( ! m || ! m.isOpen || ! mine( m ) ) return;
			var list = links();
			var i = list.indexOf( linkOf( item ) );
			if ( i < 0 ) return;
			var same = m.index === i && m.items.length === list.length && list.every( function ( a, k ) {
				return elOf( m.items[ k ] ) === a;
			} );
			if ( same ) return;
			var opts = $grid.data( 'magnificPopup' ) || grid.magnificPopup;
			if ( ! opts ) return;
			opts.items = undefined;
			$grid.magnificPopup( 'open', i );
		},

		close: function () {
			var m = inst();
			if ( m && m.isOpen && mine( m ) ) m.close();
		},

		mountPoint: function () {
			var m = inst();
			return m && m.wrap ? m.wrap[ 0 ] : null;
		},

		on: function ( event, fn ) {
			var name = { open: 'mfpOpen', change: 'mfpChange', close: 'mfpBeforeClose' }[ event ];
			if ( name ) $grid.on( name, function () { fn(); } );
		}
	} );
} )( window.jQuery );
