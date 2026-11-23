/**
 * Engine: Plyr as the playback engine only, and the one play bus.
 *
 * What it is for
 *   Every player kind talks to its <video> / <audio> through engine(), never to Plyr directly.
 *   Plyr runs with no controls of its own (everything visible is our markup) and with the
 *   theme's rules applied once, here: nothing kept in the visitor's browser (storage off), no
 *   Picture-in-Picture, no Plyr sprite from cdn.plyr.io, no tooltips, no keyboard shortcuts
 *   (each kind handles its own keys).
 *
 * Contract
 *   engine( media, options ) -> { plyr, media, play(), pause(), toggle(), seek( t ), wait() }
 *     options are Plyr options merged over PLYR_BASE, plus `ambient: true` for a player that
 *     plays by itself and never pauses the others (gallery Ambient videos).
 *   play() tells the bus; every other non-ambient player pauses (one sound at a time).
 *   play( { quiet: true } ) for a player starting by itself: it does not tell the bus.
 *   wait() is set by wait.js (watchWaiting) to wake its check at once.
 *
 * Do not
 *   - add Plyr controls, iconUrl or loadSprite here;
 *   - call new Plyr() anywhere else in the media modules.
 */

export const PLYR_BASE = {
	controls: [],
	storage: { enabled: false },
	loadSprite: false,
	iconUrl: '',
	tooltips: { controls: false, seek: false },
	pip: false,
	keyboard: { focused: false, global: false },
	clickToPlay: false,
	fullscreen: { enabled: true, fallback: true, iosNative: true },
};

// ----- The play bus: one listener for the whole document -----
const EVENT = 'tiagsspace:media-play';
const players = new Set();
let listening = false;

function join( e ) {
	players.add( e );
	if ( listening ) return;
	listening = true;
	document.addEventListener( EVENT, ev => players.forEach( p => {
		if ( p !== ev.detail && ! p.ambient && ! p.media.paused ) p.pause();
	} ) );
}

export function engine( media, options = {} ) {
	const { ambient = false, ...plyrOptions } = options;
	const plyr = new window.Plyr( media, { ...PLYR_BASE, ...plyrOptions } );
	const e = {
		plyr,
		media,
		ambient,
		// play( { quiet: true } ): a player starting by itself (muted, in view) does not stop
		// the others; only a play the visitor asked for does.
		play( { quiet = false } = {} ) {
			const p = plyr.play();
			if ( p && p.catch ) p.catch( () => {} );   // a refused autoplay is not an error
			if ( ! ambient && ! quiet ) document.dispatchEvent( new CustomEvent( EVENT, { detail: e } ) );
		},
		pause() { plyr.pause(); },
		toggle() { media.paused ? e.play() : e.pause(); },
		seek( t ) { plyr.currentTime = Math.max( 0, Math.min( t, ( plyr.duration || 0 ) - 0.05 ) ); },
		wait() {},
	};
	join( e );
	return e;
}
