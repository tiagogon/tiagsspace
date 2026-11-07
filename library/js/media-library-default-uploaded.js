(function () {
	if (!wp || !wp.media || !wp.media.view || !wp.media.view.AttachmentFilters || !wp.media.view.AttachmentFilters.Uploaded) {
		return;
	}

	var Original = wp.media.view.AttachmentFilters.Uploaded;

	wp.media.view.AttachmentFilters.Uploaded = Original.extend({
		initialize: function () {
			Original.prototype.initialize.apply(this, arguments);

			// Force the "uploaded" filter (Uploaded to this post) as default
			var uploadedFilter = this.filters.uploaded;
			if (uploadedFilter && uploadedFilter.props) {
				this.model.set(uploadedFilter.props);
				this.select();
			}
		}
	});

	// Force media library to re-fetch from server every time it opens,
	// so attachments deleted in another tab or by automated cleanup are not shown as stale.
	function refetchOnOpen( frame ) {
		frame.on( 'open', function() {
			// Clear the global attachment cache (stale items deleted elsewhere).
			if ( wp.media.Attachment && wp.media.Attachment.all ) {
				wp.media.Attachment.all.reset();
			}
			// Force the library to re-mirror a fresh Query and refetch from the
			// server. reset()+more() alone leaves the grid empty on reopen,
			// because a Query that considers itself "complete" won't re-fetch;
			// bumping a throwaway prop changes the query cache key so a fresh
			// Query is mirrored and pulled from the server (same pattern the
			// media-modal CTA scripts use in forceLibraryRefresh()).
			if ( frame.state && frame.state() ) {
				var library = frame.state().get( 'library' );
				if ( library && library.props && library.props.set ) {
					library.props.set( 'ignore', ( + new Date() ) );
				}
			}
		} );
	}

	// Called once per frame by media-frame-wire.js (which hooks the frame
	// constructor, so the block editor's directly built frames are covered).
	if ( window.tiagsspaceMediaFrame ) {
		window.tiagsspaceMediaFrame.onFrame( 'default-uploaded', refetchOnOpen );
	}
})();
