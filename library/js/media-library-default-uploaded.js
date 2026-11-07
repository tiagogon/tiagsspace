/**
 * Media modal defaults: open on "Uploaded to this post" and re-fetch the
 * library on every open, so attachments deleted or moved in another tab (or
 * by the toolbar actions) never show stale.
 */
( function () {
	if ( ! window.wp || ! wp.media || ! wp.media.view || ! wp.media.view.AttachmentFilters || ! wp.media.view.AttachmentFilters.Uploaded ) {
		return;
	}

	var Original = wp.media.view.AttachmentFilters.Uploaded;

	wp.media.view.AttachmentFilters.Uploaded = Original.extend( {
		initialize: function () {
			Original.prototype.initialize.apply( this, arguments );

			// Force the "uploaded" filter (Uploaded to this post) as default.
			var uploadedFilter = this.filters.uploaded;
			if ( uploadedFilter && uploadedFilter.props ) {
				this.model.set( uploadedFilter.props );
				this.select();
			}
		}
	} );

	// Re-mirror a fresh Query on each open. reset()+more() alone leaves the
	// grid empty on reopen, because a Query that considers itself "complete"
	// won't re-fetch; bumping a throwaway prop changes the query cache key
	// (tiagsspaceMediaFrame.refreshLibrary). The attachment cache itself is
	// left alone here: the selection holds model instances from it.
	function refetchOnOpen( frame ) {
		frame.on( 'open', function () {
			if ( window.tiagsspaceMediaFrame ) {
				window.tiagsspaceMediaFrame.refreshLibrary( frame );
			}
		} );
	}

	if ( window.tiagsspaceMediaFrame ) {
		window.tiagsspaceMediaFrame.onFrame( 'default-uploaded', refetchOnOpen );
	}
} )();
