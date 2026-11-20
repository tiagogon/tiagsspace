/**
 * The theme's Masonry setup, shared by the single-set galleries
 * (template-parts/entry-gallery.php) and the archive grid
 * (template-parts/archive-grid.php).
 *
 *   var msnry = tiagsspaceMasonry( element, options );
 *
 * Same options as Masonry. The instance is the jQuery one, so the scripts that
 * read $grid.data( 'masonry' ) (gallery editor, reorder keys) keep working.
 *
 * 1. Lays out at once. No imagesLoaded: every item reserves its height with
 *    the .imgcontainer `padding-bottom: <ratio>%` box, so the images are not
 *    needed to place it. Waiting for them held the layout until every image on
 *    the page had downloaded.
 *
 * 2. Exact columns. Masonry reads its column from the sizer's computed width,
 *    which the browser rounds to 1/64 px; on a fine sizer (1/48) the error
 *    grows with each column, up to ~0.25px at the right edge: hairlines in
 *    edge-to-edge galleries. The column is snapped to an exact fraction of the
 *    container (only without a gutter, which the theme never sets).
 *
 * 3. One ResizeObserver per grid relays out (instantly, once per frame) when
 *    an item's HEIGHT changes while its width does not: a 3D model or an audio
 *    player settling, a caption reflowing when the web font arrives. A width
 *    change is a window resize, which Masonry handles itself; reacting to it
 *    too would lay out twice. Items added later (Infinite Scroll) are observed
 *    after each layout.
 */
( function ( window, $ ) {
	'use strict';

	if ( ! $ || ! $.fn.masonry ) {
		return;
	}

	function snapColumns( msnry ) {
		var measure = msnry.measureColumns;
		msnry.measureColumns = function () {
			measure.call( this );
			if ( this.containerWidth > 0 && this.columnWidth > 0 && ! this.gutter ) {
				var cols = Math.max( 1, Math.round( this.containerWidth / this.columnWidth ) );
				this.cols = cols;
				this.columnWidth = this.containerWidth / cols;
			}
		};
	}

	function layoutInstant( msnry ) {
		msnry.options.layoutInstant = true;
		msnry.layout();
		delete msnry.options.layoutInstant;
	}

	function watchHeights( msnry ) {
		if ( ! ( 'ResizeObserver' in window ) ) {
			return;
		}
		var sizes = new WeakMap();
		var frame = 0;

		var ro = new ResizeObserver( function ( entries ) {
			var changed = false;
			for ( var i = 0; i < entries.length; i++ ) {
				var box = entries[ i ].borderBoxSize && entries[ i ].borderBoxSize[ 0 ];
				var w = box ? box.inlineSize : entries[ i ].contentRect.width;
				var h = box ? box.blockSize : entries[ i ].contentRect.height;
				var last = sizes.get( entries[ i ].target );
				sizes.set( entries[ i ].target, [ w, h ] );
				if ( last && Math.abs( last[ 0 ] - w ) < 0.5 && Math.abs( last[ 1 ] - h ) >= 0.5 ) {
					changed = true;
				}
			}
			if ( changed && ! frame ) {
				frame = window.requestAnimationFrame( function () {
					frame = 0;
					layoutInstant( msnry );
				} );
			}
		} );

		function observeItems() {
			for ( var i = 0; i < msnry.items.length; i++ ) {
				ro.observe( msnry.items[ i ].element ); // a no-op when already observed
			}
		}
		observeItems();
		msnry.on( 'layoutComplete', observeItems );
	}

	window.tiagsspaceMasonry = function ( element, options ) {
		var $grid = $( element );
		if ( ! $grid.length ) {
			return null;
		}
		$grid.masonry( $.extend( {}, options, { initLayout: false } ) );
		var msnry = $grid.data( 'masonry' );
		snapColumns( msnry );
		msnry.layout(); // the first layout is instant
		watchHeights( msnry );
		return msnry;
	};
} )( window, window.jQuery );
