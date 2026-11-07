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
 * Load order: this is a script dependency of media-toolbar,
 * media-gallery-actions, media-duplicate-to-post, media-attach-to-post and
 * media-library-default-uploaded (see their enqueues), so it always runs
 * first. Other plugins' frames (Instagram, Tumblr, ACF, Yoast…) pass through
 * untouched unless a callback injects something, which is each callback's
 * responsibility to scope to its frame.
 *
 * It also carries the helpers every media-modal script needs (the post being
 * edited, the frame's state / selection / library, ordered selection ids,
 * cache flush, library refresh, %d formatting), so none of them keeps a copy.
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

	/* ---------------------------------------------------------------------
	 * Shared helpers
	 * ------------------------------------------------------------------ */

	/**
	 * The post being edited (classic editor settings first, block editor next).
	 */
	function postId() {
		if ( wp.media.view.settings.post && wp.media.view.settings.post.id ) {
			return parseInt( wp.media.view.settings.post.id, 10 );
		}
		if ( window.wp.data && wp.data.select && wp.data.select( 'core/editor' ) ) {
			var id = wp.data.select( 'core/editor' ).getCurrentPostId();
			if ( id ) {
				return parseInt( id, 10 );
			}
		}
		return 0;
	}

	function state( frame ) {
		try {
			return frame && frame.state && frame.state();
		} catch ( e ) {
			return null;
		}
	}

	function stateGet( frame, key ) {
		var s = state( frame );
		return s && s.get ? s.get( key ) : null;
	}

	/**
	 * Selected attachment ids in gallery order (menu_order), not click order.
	 */
	function orderedIds( selection ) {
		if ( ! selection || ! selection.length ) {
			return [];
		}
		return selection.models
			.slice()
			.sort( function ( a, b ) {
				return ( a.get( 'menuOrder' ) || 0 ) - ( b.get( 'menuOrder' ) || 0 );
			} )
			.map( function ( model ) {
				return model.get( 'id' );
			} );
	}

	/**
	 * Invalidate WP's global media query cache and attachment cache.
	 */
	function flushCaches() {
		try {
			if ( wp.media.model && wp.media.model.Query ) {
				wp.media.model.Query.queries = [];
			}
			var all = wp.media.model && wp.media.model.Attachment && wp.media.model.Attachment.all;
			if ( all && all.reset ) {
				all.reset();
			}
		} catch ( e ) {}
	}

	/**
	 * Make the frame's library re-fetch from the server.
	 *
	 * The reused frame keeps a library that mirrors a cached Query; more() won't
	 * re-hit the server once that Query is "complete". Bumping a throwaway prop
	 * changes the query cache key, so the library mirrors a fresh Query and
	 * pulls the post's current media. `props` can add e.g.
	 * { orderby: 'menuOrder', order: 'ASC' } so a reorder is visible.
	 */
	function refreshLibrary( frame, props ) {
		try {
			var library = stateGet( frame, 'library' );
			if ( library && library.props && library.props.set ) {
				if ( props ) {
					library.props.set( props );
				}
				library.props.set( 'ignore', ( + new Date() ) );
			}
		} catch ( e ) {}
	}

	/**
	 * printf-style: replaces the first %d / %s with `value`.
	 */
	function format( str, value ) {
		return String( str ).replace( /%[ds]/, value );
	}

	window.tiagsspaceMediaFrame = {
		/**
		 * Register a per-frame callback. `key` must be unique per script.
		 */
		onFrame: function ( key, fn ) {
			if ( ! key || typeof fn !== 'function' ) {
				return;
			}
			handlers.push( { key: String( key ), fn: fn } );
		},
		postId:         postId,
		state:          state,
		selection:      function ( frame ) { return stateGet( frame, 'selection' ); },
		library:        function ( frame ) { return stateGet( frame, 'library' ); },
		orderedIds:     orderedIds,
		flushCaches:    flushCaches,
		refreshLibrary: refreshLibrary,
		format:         format
	};
} )( jQuery );
