/**
 * Shared media-frame hook for the theme's media-modal scripts.
 *
 * WordPress 7.1+ block editor (@wordpress/media-utils) builds
 * MediaFrame.Select / MediaFrame.Post subclasses directly instead of calling
 * wp.media(), so a wp.media() wrapper alone never sees the Image block's or
 * the featured-image modal. This file patches the frame constructor ONCE
 * (every frame class extends Select) and keeps a wp.media() wrapper for
 * anything else; the sibling scripts register a callback and get each new
 * frame exactly once, whichever path built it.
 *
 *   window.tiagsspaceMediaFrame.onFrame( 'my-key', function ( frame ) {
 *       frame.on( 'open', … );
 *   } );
 *
 * The key makes the callback idempotent per frame, so a script that loads
 * twice, or a frame constructed through both paths, is still wired once.
 * Callbacks must bind their own frame events (open, content:render, close).
 *
 * Load order: this is a script dependency of media-gallery-actions,
 * media-duplicate-to-post, media-attach-to-post and
 * media-library-default-uploaded (see their enqueues), so it always runs
 * first. Other plugins' frames (Instagram, Tumblr, ACF, Yoast…) pass through
 * untouched unless a callback injects something, which is each callback's
 * responsibility to scope to its frame.
 */
( function ( $ ) {
	if ( ! window.wp || ! wp.media || ! wp.media.view || ! wp.media.view.MediaFrame || ! wp.media.view.MediaFrame.Select ) {
		return;
	}
	if ( window.tiagsspaceMediaFrame ) {
		return;
	}

	var FLAG     = '__tiagsspaceWired';
	var handlers = [];

	function runOne( frame, handler ) {
		var done = frame[ FLAG ] || ( frame[ FLAG ] = {} );
		if ( done[ handler.key ] ) {
			return;
		}
		done[ handler.key ] = true;
		try {
			handler.fn( frame );
		} catch ( e ) {
			if ( window.console && console.error ) {
				console.error( 'tiagsspaceMediaFrame: handler "' + handler.key + '" failed', e );
			}
		}
	}

	function wire( frame ) {
		if ( ! frame || ! frame.on ) {
			return;
		}
		for ( var i = 0; i < handlers.length; i++ ) {
			runOne( frame, handlers[ i ] );
		}
	}

	var SelectFrame        = wp.media.view.MediaFrame.Select;
	var originalSelectInit = SelectFrame.prototype.initialize;
	SelectFrame.prototype.initialize = function () {
		originalSelectInit.apply( this, arguments );
		wire( this );
	};

	var originalMedia = wp.media;
	wp.media = function () {
		var frame = originalMedia.apply( this, arguments );
		wire( frame );
		return frame;
	};
	// Copy the static namespace (model, view, controller, Attachment, …).
	$.extend( wp.media, originalMedia );

	window.tiagsspaceMediaFrame = {
		/**
		 * Register a per-frame callback. `key` must be unique per script.
		 */
		onFrame: function ( key, fn ) {
			if ( ! key || typeof fn !== 'function' ) {
				return;
			}
			handlers.push( { key: String( key ), fn: fn } );
		}
	};
} )( jQuery );
