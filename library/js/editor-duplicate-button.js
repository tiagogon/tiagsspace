/**
 * "Duplicate" icon in the Gutenberg editor header.
 *
 * Injects a copy icon into the editor header settings area (next to the
 * theme's Preview / Media Library icons). Clicking it navigates to the signed
 * admin-post.php duplicate URL for the current post, which creates a new draft
 * and redirects into its editor.
 *
 * The URL (with its per-post nonce) is provided server-side via
 * wp_localize_script as `tiagsspaceDuplicate.url`.
 */
( function() {
	if ( ! window.wp || ! wp.data || ! window.tiagsspaceDuplicate || ! tiagsspaceDuplicate.url ) {
		return;
	}

	var BUTTON_ID = 'editor-duplicate-post';
	var pollTimer = null;

	function getContainer() {
		return document.querySelector(
			'.edit-post-header__settings, .editor-header__settings'
		);
	}

	// Brand-new, never-saved posts have nothing meaningful to duplicate yet.
	function isFreshAutoDraft() {
		var editor = wp.data.select( 'core/editor' );

		if ( ! editor ) {
			return false;
		}

		if ( typeof editor.isCleanNewPost === 'function' && editor.isCleanNewPost() ) {
			return true;
		}

		return editor.getCurrentPostAttribute( 'status' ) === 'auto-draft';
	}

	function injectButton( container ) {
		var existing = document.getElementById( BUTTON_ID );

		if ( isFreshAutoDraft() ) {
			if ( existing ) {
				existing.remove();
			}
			return;
		}

		if ( existing && document.contains( existing ) ) {
			return;
		}

		var btn       = document.createElement( 'button' );
		btn.id        = BUTTON_ID;
		btn.type      = 'button';
		btn.className = 'components-button has-icon';
		btn.setAttribute( 'aria-label', 'Duplicate to new draft' );
		btn.title     = 'Duplicate to new draft';

		// Dashicon "admin-page" (copy/duplicate pages icon).
		btn.innerHTML =
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" ' +
			'width="24" height="24" aria-hidden="true" focusable="false">' +
			'<path d="M6 15V3c0-.55.45-1 1-1h6.59L17 5.41V15c0 .55-.45 1-1 ' +
			'1H7c-.55 0-1-.45-1-1zm7-11v2h2l-2-2zM4 6v13c0 .55.45 1 1 1h9v-1.5H5.5V6H4z">' +
			'</path></svg>';

		btn.addEventListener( 'click', function( e ) {
			e.preventDefault();
			window.location.href = tiagsspaceDuplicate.url;
		} );

		container.insertBefore( btn, container.firstChild );
	}

	function update() {
		var container = getContainer();
		if ( ! container ) {
			return;
		}
		injectButton( container );
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
