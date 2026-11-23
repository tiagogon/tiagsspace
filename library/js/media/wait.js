/**
 * Waiting: the L1 wheel on videos, and the "Dance ahead" motion on every sound shape.
 *
 * Decided on the design page (library/players/test.html, Tiago 22 Nov). Three pieces:
 *
 * 1. watchWaiting( e, el ): toggles `.is-waiting` on el, which shows the wheel (videos only; the
 *    skin draws `.media__loading`, see loadingMarkup()). The wheel means one thing: the visitor
 *    asked for playback (or a seek) and time is not moving because data is missing. It is read
 *    from the playhead itself, never from events alone:
 *      show: playback wanted and the playhead has not moved for 400ms, or a seek has taken 400ms;
 *      hide: the frame the playhead moves again, on pause, at the end.
 *    `stalled` is NOT a reason: it only says no bytes came for 3s, and Safari sends it while
 *    playing happily from its buffer (it left the wheel turning over a playing film).
 *    `waiting`, `play`, `seeking` only wake the check.
 *
 * 2. waitMotion( { media, visible, waiting, paint, watch } ): while data is really arriving, the
 *    part of the sound shape still to come dances; played and buffered parts stay still.
 *      - In step with the wheel: time is the page timeline, the wheel's 1.2s turn (lockToClock
 *        starts the wheel's animation at 0); each point rises and falls once per turn, to at
 *        most 32% below its height.
 *      - One unbroken line: paint( k ) redraws the whole path with a height factor k( x ) per
 *        point (x = 0..1 along the width); neighbours share almost the same timing, so it ripples.
 *        Once all has settled, paint( null ) draws the still shape once.
 *      - Only stretches NOT downloaded yet dance, and only while data arrives (a fragment loading
 *        in hls.js: media._fragLoading; `progress` events; the buffer growing) or while the
 *        player is waiting (then the whole line after the playhead dances, with the wheel).
 *      - No kinks: the dance fades in over 3% of the width from every still edge, and each point
 *        eases in and out over time.
 *      - prefers-reduced-motion: still.
 *    visible(): the shape is on screen (the film's edge is open); waiting(): the player waits.
 *    watch: elements whose class changes wake the motion (the player, the edge).
 *
 * 3. lockToClock( el ): starts every endless animation inside el at time 0 of the page
 *    timeline, so the wheel and the dance share one beat however late each began.
 */

const TURN = 1200;
const BUCKETS = 1000, FADE = 0.03, EASE = 0.12;
const reduceMotion = matchMedia( '(prefers-reduced-motion: reduce)' );

/** The L1 wheel: the play cursor's lens disc with a sweeping arc. Videos only. */
export function loadingMarkup() {
	return '<div class="media__loading" aria-hidden="true"><svg viewBox="0 0 36 36">'
		+ '<circle cx="18" cy="18" r="16" fill="url(#tg-pagefill)" stroke="currentColor" stroke-width="1.9286" opacity=".9"/>'
		+ '<circle class="arc" cx="18" cy="18" r="11" fill="none" stroke="currentColor" stroke-width="1.9286" stroke-linecap="round" pathLength="100"/>'
		+ '</svg></div>';
}

export function watchWaiting( e, el ) {
	const m = e.media;
	let raf = 0, lastT = -1, movedAt = 0, seekSince = 0;
	const wanted = () => ! m.paused && ! m.ended;
	const check = now => {
		if ( m.currentTime !== lastT ) { lastT = m.currentTime; if ( ! m.seeking ) movedAt = now; }
		const starving = ( wanted() && now - movedAt > 400 ) || ( m.seeking && now - seekSince > 400 );
		el.classList.toggle( 'is-waiting', starving );
		raf = wanted() || m.seeking ? requestAnimationFrame( check ) : 0;
	};
	const wake = () => { if ( ! raf ) { movedAt = performance.now(); lastT = m.currentTime; raf = requestAnimationFrame( check ); } };
	e.wait = wake;
	[ 'play', 'waiting', 'timeupdate' ].forEach( t => m.addEventListener( t, wake ) );
	m.addEventListener( 'seeking', () => { seekSince = performance.now(); wake(); } );
	[ 'pause', 'ended', 'seeked' ].forEach( t => m.addEventListener( t, () => { if ( ! raf ) raf = requestAnimationFrame( check ); } ) );
}

/** Is data arriving for this media right now? */
function downloadWatch( media ) {
	let lastGrow = -1e9, lastProgress = -1e9, total = 0;
	media.addEventListener( 'progress', () => { lastProgress = performance.now(); } );
	return () => {
		const now = performance.now(), b = media.buffered;
		let sum = 0;
		for ( let i = 0; i < b.length; i++ ) sum += b.end( i ) - b.start( i );
		if ( sum > total + 0.01 ) lastGrow = now;
		total = sum;
		return !! media._fragLoading || now - lastProgress < 700 || now - lastGrow < 700;
	};
}

/** Where the shape may dance (0 still, 1 dancing), BUCKETS values across the width. */
function danceTarget( media, out, stalled ) {
	const d = media.duration;
	if ( ! d ) { out.fill( 0 ); return; }
	const still = [ [ 0, media.currentTime / d ] ], b = media.buffered;
	if ( ! stalled ) for ( let i = 0; i < b.length; i++ ) still.push( [ b.start( i ) / d, b.end( i ) / d ] );
	for ( let i = 0; i < BUCKETS; i++ ) {
		const x = i / ( BUCKETS - 1 );
		let dist = 1;
		for ( const [ a, z ] of still ) dist = Math.min( dist, x < a ? a - x : x > z ? x - z : 0 );
		const u = Math.min( 1, dist / FADE );
		out[ i ] = u * u * ( 3 - 2 * u );
	}
}

export function waitMotion( { media, visible, waiting, paint, watch = [] } ) {
	const arriving = downloadWatch( media ), w = new Float32Array( BUCKETS ), target = new Float32Array( BUCKETS );
	let raf = 0;
	const frame = () => {
		const stalled = waiting(), on = visible() && ! reduceMotion.matches && ( stalled || arriving() );
		if ( on ) danceTarget( media, target, stalled ); else target.fill( 0 );
		let any = false;
		for ( let i = 0; i < BUCKETS; i++ ) {
			w[ i ] += ( target[ i ] - w[ i ] ) * EASE;
			if ( w[ i ] < 0.002 ) w[ i ] = 0; else any = true;
		}
		if ( ! any && ! on ) { raf = 0; paint( null ); return; }
		const a = 2 * Math.PI * document.timeline.currentTime / TURN;
		paint( x => {
			const ph = 5.5 * Math.sin( 2.7 * x + 0.4 ) + 3.2 * Math.sin( 7.9 * x );   // timing drifts smoothly along the width
			return 1 - w[ Math.min( BUCKETS - 1, Math.max( 0, Math.round( x * ( BUCKETS - 1 ) ) ) ) ] * 0.16 * ( 1 + Math.sin( a + ph ) );
		} );
		raf = requestAnimationFrame( frame );
	};
	const start = () => { if ( ! raf ) raf = requestAnimationFrame( frame ); };
	watch.forEach( el => new MutationObserver( start ).observe( el, { attributes: true, attributeFilter: [ 'class' ] } ) );
	[ 'progress', 'waiting', 'stalled', 'play', 'seeking', 'tiagsspace:fragloading' ].forEach( t => media.addEventListener( t, start ) );
}

export function lockToClock( el ) {
	el.addEventListener( 'animationstart', () => requestAnimationFrame( () => el.getAnimations( { subtree: true } )
		.forEach( a => { if ( a.startTime !== 0 && a.effect && a.effect.getTiming().iterations === Infinity ) a.startTime = 0; } ) ) );
}
