/**
 * Front-end gallery editor: tooltips.
 *
 * Any element with data-tip="…" gets a small inverted label above it (below
 * when there is no room): after 300ms on hover, at once on keyboard focus,
 * gone on leave, blur, Esc, scroll or click. Single capital letters and the
 * names ⌘A / Ctrl+A / Esc in the text are drawn as keys, so a tip like
 * "Select  S · Shift-click a range" shows the S as a key cap.
 *
 * One shared node, one delegated listener per event; nothing per element, so
 * controls added later (a revealed image, a toolbar re-render) work as they come.
 * Styles: .tge-tip in library/styles/gallery-edit.css.
 */
( function () {
	'use strict';

	if ( window.tiagsspaceGalleryEditTips ) return;

	var tip = document.createElement( 'div' );
	tip.className = 'tge-tip';
	tip.id = 'tge-tip';
	tip.setAttribute( 'role', 'tooltip' );
	tip.hidden = true;

	var timer = null;
	var current = null;

	function mounted() {
		if ( ! tip.parentNode && document.body ) document.body.appendChild( tip );
		return !! tip.parentNode;
	}

	var KEY = /(^|\s)(⌘A|Ctrl\+A|Esc|[A-Z])(?=\s|$)/g;

	function render( text ) {
		tip.textContent = '';
		var last = 0;
		String( text ).replace( KEY, function ( match, space, key, offset ) {
			var start = offset + space.length;
			tip.appendChild( document.createTextNode( text.slice( last, start ) ) );
			var k = document.createElement( 'kbd' );
			k.textContent = key;
			tip.appendChild( k );
			last = start + key.length;
			return match;
		} );
		tip.appendChild( document.createTextNode( text.slice( last ) ) );
	}

	function place( el ) {
		var r = el.getBoundingClientRect();
		var w = tip.offsetWidth;
		var h = tip.offsetHeight;
		var left = Math.max( 6, Math.min( r.left + r.width / 2 - w / 2, window.innerWidth - w - 6 ) );
		var top = r.top - h - 6;
		if ( top < 6 ) top = r.bottom + 6;
		tip.style.left = Math.round( left ) + 'px';
		tip.style.top = Math.round( top ) + 'px';
	}

	function show( el ) {
		var text = el.getAttribute( 'data-tip' );
		if ( ! text || ! mounted() ) return;
		current = el;
		render( text );
		tip.hidden = false;
		place( el );
		el.setAttribute( 'aria-describedby', 'tge-tip' );
	}

	function hide() {
		clearTimeout( timer );
		timer = null;
		if ( current ) current.removeAttribute( 'aria-describedby' );
		current = null;
		tip.hidden = true;
	}

	function target( node ) {
		return node && node.closest ? node.closest( '[data-tip]' ) : null;
	}

	document.addEventListener( 'mouseover', function ( e ) {
		var el = target( e.target );
		if ( el === current ) return;
		hide();
		if ( ! el ) return;
		timer = setTimeout( function () { show( el ); }, 300 );
	} );

	document.addEventListener( 'mouseout', function ( e ) {
		var el = target( e.target );
		if ( el && ( ! e.relatedTarget || ! el.contains( e.relatedTarget ) ) ) hide();
	} );

	document.addEventListener( 'focusin', function ( e ) {
		var el = target( e.target );
		if ( ! el ) return;
		// Only for keyboard focus: a mouse click also focuses, and the hover tip covers it.
		if ( el.matches && ! el.matches( ':focus-visible' ) ) return;
		hide();
		show( el );
	} );

	document.addEventListener( 'focusout', hide );
	document.addEventListener( 'mousedown', hide, true );
	window.addEventListener( 'scroll', hide, true );
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && current ) hide();
	}, true );

	window.tiagsspaceGalleryEditTips = { show: show, hide: hide };
} )();
