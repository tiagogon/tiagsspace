/**
 * Gallery video: the module for kind `gallery-video` (library/video/player-gallery.php).
 *
 * Behaviour (Tiago, 22 Nov; library/media/behaviour.php sets the flags on the element)
 *   data-controls="1"  the whole picture plays and pauses (the play / pause cursor, the play mark
 *                      on touch while not playing). Nothing is drawn on the picture: no progress
 *                      line, no sound control.
 *   data-autoplay="1"  (only ever muted) plays by itself while in view and pauses out of view;
 *                      once the visitor pauses it, it stays paused until they play it again.
 *   Anything playing pauses when it leaves the view, and when another player starts (engine.js),
 *   except Ambient videos, which never pause the others.
 *   The L1 wheel shows only when the visitor asked for playback and the picture has not moved
 *   for 400ms (wait.js). The poster fades out once the video really plays.
 *
 * Contract: mount( el, ctx ) — el is the `.media` element; it never looks outside it.
 */

const V = new URL( import.meta.url ).search;
const { engine } = await import( './engine.js' + V );
const { watchWaiting, loadingMarkup, lockToClock } = await import( './wait.js' + V );

export function mount( el ) {
	const frame = el.querySelector( '.media__frame' );
	const video = el.querySelector( 'video' );
	const hit = el.querySelector( '.media__hit' );
	const controls = el.dataset.controls === '1';
	const autoplay = el.dataset.autoplay === '1';
	const ambient = el.dataset.behaviour === 'ambient';

	// Our layer replaces the browser's controls (they stay for the no-JavaScript case only).
	video.controls = false;
	const e = engine( video, {
		ambient,
		muted: video.muted,
		loop: { active: video.loop },
		ratio: null,
		fullscreen: { enabled: false },
	} );
	let heldByVisitor = false;

	video.preload = 'metadata';
	video.addEventListener( 'playing', () => el.classList.add( 'is-started' ) );
	const state = () => {
		const playing = ! video.paused;
		el.classList.toggle( 'is-playing', playing );
		if ( hit ) hit.setAttribute( 'aria-label', ( playing ? 'Pause' : 'Play' ) + hit.getAttribute( 'aria-label' ).replace( /^(Play|Pause)/, '' ) );
	};
	[ 'play', 'pause', 'ended' ].forEach( t => video.addEventListener( t, state ) );

	if ( controls ) {
		frame.insertAdjacentHTML( 'beforeend', loadingMarkup() );
		lockToClock( frame );
		watchWaiting( e, el );
		hit.addEventListener( 'click', () => {
			heldByVisitor = ! video.paused;
			e.toggle();
		} );
	}

	el.addEventListener( 'tiagsspace:media-view', ev => {
		if ( ev.detail.inView ) {
			if ( autoplay && ! heldByVisitor ) e.play( { quiet: true } );
		} else if ( ! video.paused ) {
			e.pause();
		}
	} );
	if ( autoplay && el.dataset.inView === '1' ) e.play( { quiet: true } );
}
