/**
 * Clock: the played part moving at a constant speed, and the time as text.
 *
 * What it is for
 *   `timeupdate` comes about four times a second, and currentTime read per frame still jitters
 *   (14-19ms steps for a 16.7ms frame), so a progress fill driven by either jumps. smoothClock()
 *   moves the shown time by exactly each frame's elapsed time (x playbackRate) while the media
 *   really plays, and eases it onto currentTime (8% of the difference per frame; a jump of more
 *   than 0.3s, a seek, is taken at once). On pause, waiting, seeking or end the loop stops and the
 *   exact time is painted, so the fill never runs ahead of a file that has stopped.
 *   Decided on the design page (library/players/test.html), films and audio alike.
 *
 * Contract
 *   smoothClock( media, paint ) -> { now() }   paint( seconds ) is called per frame while playing
 *                                               and once on every stop; now() = the time shown.
 *   clock( seconds ) -> "04:07" or "01:02:03"
 */

export function smoothClock( media, paint ) {
	let raf = 0, base = 0, at = 0;
	const exact = () => { base = media.currentTime || 0; at = performance.now(); };
	const shown = now => base + ( now - at ) / 1000 * ( media.playbackRate || 1 );
	const live = () => ! media.paused && ! media.ended && ! media.seeking && media.readyState >= 3;
	const frame = now => {
		if ( ! live() ) { raf = 0; exact(); paint( base ); return; }
		const err = media.currentTime - shown( now );
		if ( Math.abs( err ) > 0.3 ) { base = media.currentTime; at = now; } else base += err * 0.08;
		paint( Math.max( 0, shown( now ) ) );
		raf = requestAnimationFrame( frame );
	};
	const start = () => { if ( ! raf && live() ) { exact(); raf = requestAnimationFrame( frame ); } };
	[ 'play', 'playing', 'canplay', 'seeked' ].forEach( t => media.addEventListener( t, start ) );
	[ 'pause', 'waiting', 'seeking', 'ended', 'loadedmetadata', 'timeupdate' ].forEach( t => media.addEventListener( t, () => {
		if ( ! raf ) { exact(); paint( base ); }
	} ) );
	return { now: () => raf ? Math.max( 0, shown( performance.now() ) ) : ( media.currentTime || 0 ) };
}

const two = n => String( Math.floor( n ) ).padStart( 2, '0' );

export function clock( s ) {
	s = Math.max( 0, s || 0 );
	const h = Math.floor( s / 3600 ), m = Math.floor( s % 3600 / 60 );
	return ( h ? two( h ) + ':' : '' ) + two( m ) + ':' + two( s % 60 );
}
