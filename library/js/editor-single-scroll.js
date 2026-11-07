/**
 * Single scroll for the block editor: canvas + meta boxes in one flow.
 *
 * Since WordPress 6.7 the post editor shows the "Meta Boxes" pane as a
 * resizable split below the canvas, each with its own scroll. In 7.x the
 * canvas is always an iframe and there is no setting or hook for a single
 * flow, so this script restores it from the outside:
 *
 *  - the canvas iframe is sized to its own content (a ResizeObserver inside
 *    the iframe document measures the body and writes the height on
 *    `.editor-visual-editor`), so the iframe never scrolls;
 *  - the companion CSS (inline on `wp-edit-post`, see library/admin.php)
 *    lets the canvas and the pane take their natural height, so the
 *    skeleton's content area scrolls everything.
 *
 * Everything is keyed on `body.tiagsspace-editor-flow`, which this script
 * adds only while the flow layout applies. It backs off, and core's split
 * returns, when the pane is not rendered (distraction free, zoom out, no
 * visible box) or a device preview is on (core constrains the canvas height
 * itself then). Every lookup may fail on a core update: then nothing
 * happens and the editor is core's own.
 */
( function () {
	'use strict';

	if ( ! window.wp || ! wp.data ) {
		return;
	}

	var ACTIVE_CLASS = 'tiagsspace-editor-flow';
	var STYLE_ID     = 'tiagsspace-editor-flow-canvas';

	// Inside the iframe: the root must not be a scroll container at all
	// (`clip`, not `hidden`: the browser would still scroll a hidden root to
	// keep the caret in view, and that scroll has to reach the page instead),
	// and replace core's 40vh padding appender (vh of the iframe itself,
	// which would feed back into the measured height) with a fixed gap
	// before the meta boxes.
	var CANVAS_CSS =
		'html.block-editor-iframe__html { overflow: clip; }' +
		'html .editor-styles-wrapper::after { height: 4rem !important; }';

	var current      = null; // { iframe, doc, observer, onLoad }
	var lastHeight   = 0;
	var lastSelected = null;
	var rafPending   = false;

	function getVisualEditor() {
		return document.querySelector( '.editor-visual-editor' );
	}

	function getContent() {
		return document.querySelector( '.interface-interface-skeleton__content' );
	}

	function getIframe() {
		var editor = getVisualEditor();
		return editor ? editor.querySelector( 'iframe[name="editor-canvas"]' ) : null;
	}

	function hasMetaBoxPane() {
		return !! document.querySelector(
			'.edit-post-layout.has-metaboxes .interface-interface-skeleton__content .edit-post-meta-boxes-main'
		);
	}

	function isDesktop() {
		try {
			var editor = wp.data.select( 'core/editor' );
			if ( ! editor || typeof editor.getDeviceType !== 'function' ) {
				return true;
			}
			return editor.getDeviceType() === 'Desktop';
		} catch ( e ) {
			return true;
		}
	}

	function measure() {
		if ( ! current ) {
			return;
		}
		var doc    = current.doc;
		var editor = getVisualEditor();
		if ( ! doc || ! doc.body || ! editor || doc !== current.iframe.contentDocument ) {
			return;
		}
		// The body's outer height, independent of the iframe viewport
		// (documentElement.scrollHeight never shrinks below the viewport).
		var rect   = doc.body.getBoundingClientRect();
		var style  = doc.defaultView.getComputedStyle( doc.body );
		var height = Math.ceil(
			rect.bottom + ( doc.defaultView.scrollY || 0 ) + ( parseFloat( style.marginBottom ) || 0 )
		);
		if ( height <= 0 || height === lastHeight ) {
			return;
		}
		lastHeight = height;
		editor.style.height = height + 'px';
	}

	function injectCanvasStyle( doc ) {
		if ( ! doc.head || doc.getElementById( STYLE_ID ) ) {
			return;
		}
		var style = doc.createElement( 'style' );
		style.id = STYLE_ID;
		style.textContent = CANVAS_CSS;
		doc.head.appendChild( style );
	}

	function removeCanvasStyle( doc ) {
		var style = doc && doc.getElementById ? doc.getElementById( STYLE_ID ) : null;
		if ( style && style.parentNode ) {
			style.parentNode.removeChild( style );
		}
	}

	function detach() {
		if ( ! current ) {
			return;
		}
		if ( current.observer ) {
			current.observer.disconnect();
		}
		current.iframe.removeEventListener( 'load', current.onLoad );
		try {
			removeCanvasStyle( current.iframe.contentDocument );
		} catch ( e ) {}
		var editor = getVisualEditor();
		if ( editor ) {
			editor.style.height = '';
		}
		current      = null;
		lastHeight   = 0;
		lastSelected = null;
	}

	// Core scrolls a newly selected block into view inside the iframe, which
	// no longer scrolls, so List View clicks and keyboard block navigation
	// would leave the block off screen. Scroll the page instead, only when
	// the block is not already fully visible.
	function keepSelectionInView() {
		if ( ! current || ! current.doc ) {
			return;
		}
		var blockEditor = wp.data.select( 'core/block-editor' );
		if ( ! blockEditor ) {
			return;
		}
		var id = blockEditor.getSelectedBlockClientId();
		if ( id === lastSelected ) {
			return;
		}
		lastSelected = id;
		if ( ! id ) {
			return;
		}
		window.requestAnimationFrame( function () {
			if ( ! current || ! current.doc ) {
				return;
			}
			var block   = current.doc.getElementById( 'block-' + id );
			var content = getContent();
			if ( ! block || ! content ) {
				return;
			}
			var frame  = current.iframe.getBoundingClientRect();
			var rect   = block.getBoundingClientRect();
			var bounds = content.getBoundingClientRect();
			if ( frame.top + rect.top < bounds.top || frame.top + rect.bottom > bounds.bottom ) {
				block.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
			}
		} );
	}

	// Observe the iframe's current document; called again if it is replaced.
	function observeDocument() {
		if ( ! current ) {
			return;
		}
		var iframe = current.iframe;
		var doc    = iframe.contentDocument;
		var win    = iframe.contentWindow;
		if ( ! doc || ! win || ! doc.body || typeof win.ResizeObserver !== 'function' ) {
			return;
		}
		if ( current.observer ) {
			current.observer.disconnect();
		}
		current.doc = doc;
		injectCanvasStyle( doc );
		// The observer must come from the iframe's own realm, or Firefox
		// refuses the cross-document element.
		current.observer = new win.ResizeObserver( measure );
		current.observer.observe( doc.body );
		current.observer.observe( doc.documentElement );
		lastHeight = 0;
		measure();
	}

	function attach( iframe ) {
		if ( current && current.iframe === iframe ) {
			if ( current.doc !== iframe.contentDocument ) {
				observeDocument();
			}
			return;
		}
		detach();
		current = {
			iframe:   iframe,
			doc:      null,
			observer: null,
			onLoad:   observeDocument
		};
		iframe.addEventListener( 'load', current.onLoad );
		observeDocument();
	}

	function update() {
		var iframe = getIframe();
		var flow   = !! iframe && hasMetaBoxPane() && isDesktop();
		document.body.classList.toggle( ACTIVE_CLASS, flow );
		if ( flow ) {
			attach( iframe );
		} else {
			detach();
		}
	}

	function scheduleUpdate() {
		if ( rafPending ) {
			return;
		}
		rafPending = true;
		window.requestAnimationFrame( function () {
			rafPending = false;
			update();
		} );
	}

	function start() {
		if ( ! document.body ) {
			return;
		}
		// The editor mounts after DOM ready and remounts the canvas when
		// switching code/visual editor, zoom out or device previews.
		new MutationObserver( scheduleUpdate ).observe( document.body, {
			childList: true,
			subtree:   true
		} );
		// Device type and meta box visibility live in the stores.
		wp.data.subscribe( function () {
			scheduleUpdate();
			keepSelectionInView();
		} );
		scheduleUpdate();
	}

	if ( wp.domReady ) {
		wp.domReady( start );
	} else if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
