/**
 * Custom Gutenberg editor header buttons:
 *
 * 1. "Preview in new tab" icon for non-published posts (drafts, pending, etc.)
 *    — published posts already get a native "View Post" icon.
 *
 * 2. "Open Media Library" icon for all posts — quick access without
 *    scrolling to a gallery block or media inserter.
 */
( function() {
	if ( ! wp || ! wp.data ) {
		return;
	}

	var PREVIEW_ID = 'draft-preview-new-tab';
	var MEDIA_ID   = 'editor-open-media-library';
	var pollTimer  = null;

	/* ---- Preview helpers ---- */

	function getPreviewLink() {
		var select = wp.data.select( 'core/editor' );

		if ( typeof select.getEditedPostPreviewLink === 'function' ) {
			var preview = select.getEditedPostPreviewLink();
			if ( preview ) {
				return preview;
			}
		}

		var post = select.getCurrentPost();
		if ( post && post.link ) {
			var sep = post.link.indexOf( '?' ) > -1 ? '&' : '?';
			return post.link + sep + 'preview=true';
		}

		return null;
	}

	function isPublished() {
		var status = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
		return status === 'publish' || status === 'private';
	}

	/* ---- Inject / update buttons ---- */

	function getContainer() {
		return document.querySelector(
			'.edit-post-header__settings, .editor-header__settings'
		);
	}

	function updatePreviewButton( container ) {
		var existing = document.getElementById( PREVIEW_ID );

		// Published posts already have the native "View Post" icon — remove ours.
		if ( isPublished() || document.querySelector( '.editor-post-view-link' ) ) {
			if ( existing ) {
				existing.remove();
			}
			return;
		}

		var link = getPreviewLink();
		if ( ! link ) {
			return;
		}

		if ( existing && document.contains( existing ) ) {
			existing.href = link;
			return;
		}

		var a       = document.createElement( 'a' );
		a.id        = PREVIEW_ID;
		a.href      = link;
		a.target    = '_blank';
		a.rel       = 'noopener noreferrer';
		a.className = 'components-button has-icon';
		a.setAttribute( 'aria-label', 'Preview in new tab' );
		a.title     = 'Preview in new tab';

		// External-link SVG (same as WP's "View Post" icon).
		a.innerHTML =
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" ' +
			'width="24" height="24" aria-hidden="true" focusable="false">' +
			'<path d="M19.5 4.5h-7V6h4.44l-5.97 5.97 1.06 1.06L18 7.06v4.44h1.5v-7Z' +
			'm-13 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4.5H17v4.5a.5.5 ' +
			'0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5H11V5.5H6.5Z"></path>' +
			'</svg>';

		container.insertBefore( a, container.firstChild );
	}

	function injectMediaButton( container ) {
		if ( document.getElementById( MEDIA_ID ) ) {
			return;
		}

		var btn       = document.createElement( 'button' );
		btn.id        = MEDIA_ID;
		btn.type      = 'button';
		btn.className = 'components-button has-icon';
		btn.setAttribute( 'aria-label', 'Open Media Library' );
		btn.title     = 'Open Media Library';

		// Dashicon "admin-media" — same icon as the WP admin Media menu.
		btn.innerHTML =
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" ' +
			'width="24" height="24" aria-hidden="true" focusable="false">' +
			'<path d="M13 11V4c0-.55-.45-1-1-1h-1.67L9 1H5L3.67 3H2c-.55 0-1 ' +
			'.45-1 1v7c0 .55.45 1 1 1h10c.55 0 1-.45 1-1zM7 4.5c1.38 0 2.5 ' +
			'1.12 2.5 2.5S8.38 9.5 7 9.5 4.5 8.38 4.5 7 5.62 4.5 7 4.5zM14 ' +
			'6h5v10.5c0 1.38-1.12 2.5-2.5 2.5S14 17.88 14 16.5s1.12-2.5 ' +
			'2.5-2.5c.17 0 .34.02.5.05V9h-3V6zm-4 8.05V13h2v3.5c0 1.38-1.12 ' +
			'2.5-2.5 2.5S7 17.88 7 16.5 8.12 14 9.5 14c.17 0 .34.02.5.05z"></path>' +
			'</svg>';

		btn.addEventListener( 'click', function( e ) {
			e.preventDefault();

			var postId = wp.data.select( 'core/editor' ).getCurrentPostId();

			// Use the native WP editor media modal — same behaviour as the
			// block inserter → Media → "Open media library" pathway.
			wp.media.editor.open( 'editor-toolbar-' + postId, {
				frame:    'post',
				state:    'insert',
				multiple: true
			} );
		} );

		// Insert as first child — preview button will then go before it.
		container.insertBefore( btn, container.firstChild );
	}

	/* ---- Main update loop ---- */

	function update() {
		var container = getContainer();
		if ( ! container ) {
			return;
		}
		injectMediaButton( container );
		updatePreviewButton( container );
	}

	wp.data.subscribe( function() {
		requestAnimationFrame( update );
	} );

	pollTimer = setInterval( function() {
		if ( getContainer() ) {
			clearInterval( pollTimer );
			update();
		}
	}, 500 );
} )();
