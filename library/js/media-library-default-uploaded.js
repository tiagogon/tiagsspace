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
})();
