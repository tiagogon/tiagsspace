<?php
/*

Social links block

*/

// inpired at http://tympanus.net/Development/CreativeLinkEffects/

if (!is_singular( 'log' )) { ?>

<div id="social-block" class="front-block">
	<div class="container">
		<div class="clearfix row">
			<div class="<?php content_wrap() ?>">
				<h3>Elsewhere</h3>
				<ul class="social-links">
					<li>
						<a href="https://www.instagram.com/tiagsssss/" data-hover="Facebook" target="_blank">Instagram</a>
					</li>
					<li>
						<a href="https://facebook.com/trouble.place" data-hover="Facebook" target="_blank">FB</a>
					</li>
					<li>
						<a href="https://twitter.com/troubleplace" data-hover="Twitter" target="_blank">Twitter</a>
					</li>
					<li>
						<a href="https://vimeo.com/troubleplace" data-hover="Tumbler" target="_blank">Vimeo</a>
					</li>
					<li>
						<a href="https://soundcloud.com/tiagsssss" data-hover="SoundCloud" target="_blank">Soundcloud</a>
					</li>
					<li>
						<a href="https://troubleplace.tumblr.com" data-hover="SoundCloud" target="_blank">Tumblr I</a>
					</li>
					<li>
						<a href="https://imtakingtwo.tumblr.com" data-hover="SoundCloud" target="_blank">Tumblr II</a>
					</li>
					<li>
						<a href="https://www.flickr.com/photos/cityburns/" data-hover="Flickr" target="_blank">Flickr</a>
					</li>
					<li>
						<a href="https://trouble.place/feed" data-hover="Feed" target="_blank">Feed</a>
					</li>
				</ul>
				<h3>Bimonthly</h3>
				<div class="email-form">
					<form action="https://tinyletter.com/trouble-letter" method="post" target="popupwindow" onsubmit="window.open('https://tinyletter.com/trouble-letter', 'popupwindow', 'scrollbars=yes,width=800,height=600');return true">
						<input class="input-field" type="text" name="email" id="tlemail" placeholder="your email address"/>
						<input type="hidden" value="1" name="embed"/>
						<input class="input-button" type="submit" value="Subscribe" />
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
// End IF
} ?>
