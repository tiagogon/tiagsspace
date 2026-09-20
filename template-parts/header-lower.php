<?php
/*
Header >> Lower header (fixed bottom bar, desktop only)
Shows post year-month on thumbnail hover via showYear()/hideYear() in header-helpers.js.
Only rendered on home, paginated, and archive pages.
*/

if (is_home() || is_paged() || is_archive()) : ?>

<div id="lower-header" class="left-side side-padding d-none d-sm-inline" role="banner">
	<div class="row">
		<div class="left-side col-32 multi-collapse show">
			<p class="site-title"><span id="over-text-year-published"></span></p>
		</div>
	</div>
</div>

<?php endif; ?>
