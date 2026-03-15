<?php
/*
Header >> Lower header (fixed bottom bar, desktop only)
Shows post year-month on thumbnail hover via showYear()/hideYear() in header-helpers.js.
Only rendered on home, paginated, and archive pages.
*/

if (is_home() || is_paged() || is_archive()) : ?>

<div id="lower-header" class="left-side col-32 side-padding Fixed d-none d-sm-inline" role="banner">
	<div class="row">
		<div class="left-side col-32 multi-collapse show">
			<h1><span id="over-text-year-published"></span></h1>
		</div>
		<div class="right-side col-16"></div>
	</div>
</div>

<?php endif; ?>
