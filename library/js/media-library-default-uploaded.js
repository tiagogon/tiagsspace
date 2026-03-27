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
	// Override wp.media() to hook into every frame's "open" event.
	var originalMedia = wp.media;
	wp.media = function() {
		var frame = originalMedia.apply( this, arguments );
		if ( frame && frame.on ) {
			frame.on( 'open', function() {
				// Clear the global attachment cache
				if ( wp.media.Attachment && wp.media.Attachment.all ) {
					wp.media.Attachment.all.reset();
				}
				// Force the frame's library to re-query from server
				if ( frame.state && frame.state() ) {
					var library = frame.state().get( 'library' );
					if ( library ) {
						library.reset();
						library.more();
					}
				}
			} );
		}
		return frame;
	};
	// Copy all static properties (Attachment, view, etc.)
	jQuery.extend( wp.media, originalMedia );
})();
