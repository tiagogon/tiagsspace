/**
 * "Add to a duplicated post" — media modal call-to-action.
 *
 * In the post editor's "Add media" modal, the user selects N attachments and
 * clicks this button. It duplicates the current post, re-parents the selected
 * media onto the copy (keeping their gallery order), and opens the new draft's
 * editor in a new tab.
 *
 * Re-parenting moves the media OFF the original post — an attachment can only
 * have one parent.
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! window.tiagsspaceMediaDuplicate ) {
		return;
	}

	var cfg       = window.tiagsspaceMediaDuplicate;
	var BUTTON_ID = 'tiagsspace-duplicate-to-post';

	/**
	 * Resolve the post being edited.
	 */
	function getSourcePostId( frame ) {
		if ( wp.media.view.settings.post && wp.media.view.settings.post.id ) {
			return parseInt( wp.media.view.settings.post.id, 10 );
		}

		if ( window.wp.data && wp.data.select( 'core/editor' ) ) {
			var id = wp.data.select( 'core/editor' ).getCurrentPostId();
			if ( id ) {
				return parseInt( id, 10 );
			}
		}

		return 0;
	}

	/**
	 * Current selection collection of the frame (or null).
	 */
	function getSelection( frame ) {
		if ( frame && frame.state && frame.state() && frame.state().get ) {
			return frame.state().get( 'selection' );
		}
		return null;
	}

	function performDuplicate( frame, $button ) {
		var selection = getSelection( frame );
		if ( ! selection || ! selection.length ) {
			return;
		}

		var sourceId = getSourcePostId( frame );
		if ( ! sourceId ) {
			window.alert( 'Could not determine the current post to duplicate.' );
			return;
		}

		// Selected attachments sorted by their existing gallery order (menu_order),
		// so the copy keeps the original order rather than the click order.
		var ids = selection.models
			.slice()
			.sort( function ( a, b ) {
				return ( a.get( 'menuOrder' ) || 0 ) - ( b.get( 'menuOrder' ) || 0 );
			} )
			.map( function ( model ) {
				return model.get( 'id' );
			} );

		// Open the tab synchronously inside the click handler to avoid popup
		// blockers; its URL is filled in once the AJAX call returns.
		var tab = window.open( 'about:blank', '_blank' );

		$button.prop( 'disabled', true );

		$.post( cfg.ajaxurl, {
			action:         'tiagsspace_duplicate_to_post',
			_nonce:         cfg.nonce,
			source_id:      sourceId,
			attachment_ids: ids
		} )
			.done( function ( res ) {
				if ( res && res.success && res.data && res.data.edit_url ) {
					if ( tab ) {
						tab.location = res.data.edit_url;
					} else {
						window.location.href = res.data.edit_url;
					}
					if ( frame && frame.close ) {
						frame.close();
					}
				} else {
					if ( tab ) {
						tab.close();
					}
					var msg = res && res.data && res.data.message ? res.data.message : 'Could not duplicate the post.';
					window.alert( msg );
				}
			} )
			.fail( function () {
				if ( tab ) {
					tab.close();
				}
				window.alert( 'Could not duplicate the post.' );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
			} );
	}

	/**
	 * Inject the button into the modal footer and wire selection state.
	 */
	function injectButton( frame ) {
		var $toolbar = $( '.media-frame-toolbar .media-toolbar-secondary' );

		if ( ! $toolbar.length || document.getElementById( BUTTON_ID ) ) {
			return;
		}

		var $button = $( '<button>', {
			type:  'button',
			id:    BUTTON_ID,
			class: 'button media-button button-large',
			text:  cfg.label
		} );

		$button.css( 'margin-left', '8px' );

		$button.on( 'click', function ( e ) {
			e.preventDefault();
			performDuplicate( frame, $button );
		} );

		$toolbar.append( $button );

		var selection = getSelection( frame );

		function sync() {
			$button.prop( 'disabled', ! selection || ! selection.length );
		}

		if ( selection ) {
			selection.on( 'add remove reset', sync );
		}
		sync();
	}

	// Hook every media frame's open event (mirrors media-library-default-uploaded.js).
	var originalMedia = wp.media;
	wp.media = function () {
		var frame = originalMedia.apply( this, arguments );
		if ( frame && frame.on ) {
			frame.on( 'open', function () {
				// Defer so the toolbar DOM exists.
				setTimeout( function () {
					injectButton( frame );
				}, 0 );
			} );
			// Re-inject when switching states (e.g. back from Create gallery).
			frame.on( 'content:render', function () {
				setTimeout( function () {
					injectButton( frame );
				}, 0 );
			} );
		}
		return frame;
	};
	$.extend( wp.media, originalMedia );
} )( jQuery );
