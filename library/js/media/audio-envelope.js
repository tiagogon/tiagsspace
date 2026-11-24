/**
 * Audio envelope: the module for kinds `gallery-audio` (a tile) and `content-audio` (a mix in a
 * post's text), printed by library/audio/player.php.
 *
 * Behaviour (Tiago, 22 Nov; the design page library/players/test.html)
 *   - No title, no sound control (the visitor's system sets the level; to silence, pause).
 *   - With a mouse, a click on the envelope plays or pauses (play / pause cursor). Only the
 *     playhead's edge can be dragged: within 8px of it (22px for a finger) the cursor turns to
 *     scrub and a fine line shows; the drag only moves the edge (a preview, the mix's time says the
 *     time there), and the one real seek happens on release. The edge stays at the drop point
 *     until that seek has landed (at most 4s), so it never jumps back on screen.
 *   - Keyboard: ←/→ 10s (Shift 60s), Space / Enter play and pause.
 *   - Tile: no time; on touch the plain play mark while not playing.
 *     Mix: the play lens (touch only, by CSS), AirPlay where Safari has a device, the time;
 *     Media Session (title, album, cover on the lock screen).
 *   - The played part fills with ink at a constant speed (clock.js). Audio never shows a wheel:
 *     while it waits, the envelope still to come dances (wait.js).
 *
 * Contract: mount( el ) — el is the `.media` element; it never looks outside it.
 */

const V = new URL( import.meta.url ).search;
const { engine } = await import( './engine.js' + V );
const { smoothClock, clock } = await import( './clock.js' + V );
const { watchWaiting, waitMotion, lockToClock } = await import( './wait.js' + V );
const { DEFAULTS, series, envelopeValues, envelopeShape } = await import( './envelope.js' + V );
const { airplay } = await import( './airplay.js' + V );

const minutes = s => Math.round( ( s || 0 ) / 60 );

export function mount( el ) {
	const tile = el.classList.contains( 'media--tile' );
	const title = el.dataset.title || '';
	const wave = el.querySelector( '.env__wave' );
	const svg = el.querySelector( '.env__shape' );
	const hollow = el.querySelector( '.env__hollow' );
	const filled = el.querySelector( '.env__filled' );
	const pointer = el.querySelector( '.env__pointer' );
	const timeEl = el.querySelector( '.env__time' );
	const lens = el.querySelector( '.env__lens' );
	const audio = el.querySelector( 'audio' );
	const json = el.querySelector( '.media__peaks' );

	let data = null;
	try { data = json ? JSON.parse( json.textContent ) : null; } catch ( err ) { data = null; }
	let duration = data ? data.duration : 0;

	// ----- The shape, at the real size -----
	let shape = null;   // { vals, w, h }
	const paint = k => {
		if ( ! shape ) return;
		const d = envelopeShape( shape.vals, shape.w, shape.h, k );
		hollow.setAttribute( 'd', d );
		filled.setAttribute( 'd', d );
	};
	const draw = () => {
		const r = wave.getBoundingClientRect();
		if ( ! r.width || ! r.height ) return;
		svg.setAttribute( 'viewBox', `0 0 ${ r.width } ${ r.height }` );
		const step = tile ? DEFAULTS.step.tile : DEFAULTS.step.full;
		const top = data ? series( data, DEFAULTS.measure, 'L' ) : [ 0, 0 ];
		const bottom = data ? series( data, DEFAULTS.measure, 'R' ) : [ 0, 0 ];
		shape = { vals: envelopeValues( top, bottom, r.width, step, DEFAULTS.contrast, tile ? DEFAULTS.tileCap : DEFAULTS.cap ), w: r.width, h: r.height };
		paint( null );
	};
	let raf = 0;
	new ResizeObserver( () => { cancelAnimationFrame( raf ); raf = requestAnimationFrame( draw ); } ).observe( wave );
	draw();

	// ----- The engine -----
	audio.controls = false;
	const e = engine( audio, { muted: false } );
	watchWaiting( e, wave );   // no wheel is drawn for audio; .is-waiting drives the dance only
	waitMotion( { media: audio, visible: () => true, waiting: () => wave.classList.contains( 'is-waiting' ), paint, watch: [ wave ] } );
	lockToClock( wave );
	airplay( audio, el.querySelector( '.env__airplay' ) );

	// scrubTo: while the edge is dragged (and until the seek it ends with has landed), the edge is
	// drawn there instead of at the playhead.
	let scrubTo = null, aimAt = null;
	const paintP = t => {
		if ( scrubTo != null ) t = scrubTo;
		const d = audio.duration || duration, f = d ? Math.min( 1, t / d ) : 0;
		el.style.setProperty( '--progress', f * 100 + '%' );
		pointer.style.left = f * 100 + '%';
	};
	const prog = smoothClock( audio, paintP );
	const sync = () => {
		const t = audio.currentTime || 0, d = audio.duration || duration;
		paintP( prog.now() );
		const started = aimAt != null || t > 0 || ! audio.paused;
		if ( timeEl && d ) timeEl.textContent = started ? clock( aimAt ?? scrubTo ?? t ) + ' / ' + clock( d ) : clock( d );
		wave.setAttribute( 'aria-valuenow', Math.round( t ) );
		wave.setAttribute( 'aria-valuemax', Math.round( d ) );
		wave.setAttribute( 'aria-valuetext', `${ minutes( t ) } minutes of ${ minutes( d ) }` );
	};
	audio.addEventListener( 'timeupdate', sync );
	audio.addEventListener( 'loadedmetadata', () => { if ( ! duration ) duration = audio.duration; sync(); } );

	let edgeDrag = false, swallowClick = false;
	const cursor = () => {
		wave.classList.toggle( 'on-scrub', wave.classList.contains( 'on-edge' ) || edgeDrag );
	};
	[ 'play', 'pause', 'ended' ].forEach( t => audio.addEventListener( t, () => {
		const playing = ! audio.paused;
		el.classList.toggle( 'is-playing', playing );
		const word = ( playing ? 'Pause' : 'Play' ) + ( title ? ' ' + title : '' );
		wave.setAttribute( 'aria-label', word );
		if ( lens ) {
			lens.setAttribute( 'aria-label', word );
			lens.querySelector( 'use' ).setAttribute( 'href', playing ? '#tg-pause-large' : '#tg-play-large' );
		}
		cursor();
	} ) );
	if ( lens ) lens.addEventListener( 'click', ev => { ev.stopPropagation(); e.toggle(); } );

	// ----- The edge: hover, drag, one seek on release -----
	const frac = ev => { const r = wave.getBoundingClientRect(); return Math.max( 0, Math.min( 1, ( ev.clientX - r.left ) / r.width ) ); };
	const nearEdge = ev => {
		const d = audio.duration || duration;
		if ( ! d || ( audio.currentTime === 0 && audio.paused ) ) return false;   // no edge before it has played
		const r = wave.getBoundingClientRect(), x = r.left + prog.now() / d * r.width;
		return Math.abs( ev.clientX - x ) <= ( ev.pointerType === 'touch' ? 22 : 8 );
	};
	wave.addEventListener( 'pointermove', ev => {
		if ( edgeDrag ) { scrubTo = frac( ev ) * ( audio.duration || duration ); aimAt = scrubTo; sync(); return; }
		wave.classList.toggle( 'on-edge', ev.pointerType !== 'touch' && nearEdge( ev ) );
		cursor();
	} );
	wave.addEventListener( 'pointerleave', () => { if ( ! edgeDrag ) { wave.classList.remove( 'on-edge' ); cursor(); } } );
	wave.addEventListener( 'pointerdown', ev => {
		swallowClick = false;
		if ( ! nearEdge( ev ) ) return;
		edgeDrag = true;
		scrubTo = prog.now();
		wave.setPointerCapture( ev.pointerId );
		wave.classList.add( 'dragging' );
		cursor();
		ev.preventDefault();
	} );
	let landed = 0;
	const endDrag = ev => {
		if ( ! edgeDrag ) return;
		edgeDrag = false;
		swallowClick = true;
		wave.classList.remove( 'dragging' );
		cursor();
		aimAt = null;
		if ( ev.type === 'pointercancel' ) { scrubTo = null; sync(); return; }
		const target = frac( ev ) * ( audio.duration || duration );
		scrubTo = target;
		sync();
		const done = () => {
			if ( Math.abs( audio.currentTime - target ) < 1 ) { scrubTo = null; audio.removeEventListener( 'seeked', done ); clearTimeout( landed ); }
		};
		audio.addEventListener( 'seeked', done );
		clearTimeout( landed );
		landed = setTimeout( () => { audio.removeEventListener( 'seeked', done ); scrubTo = null; paintP( prog.now() ); }, 4000 );
		e.seek( target );
	};
	wave.addEventListener( 'pointerup', endDrag );
	wave.addEventListener( 'pointercancel', endDrag );
	wave.addEventListener( 'click', () => { if ( swallowClick ) { swallowClick = false; return; } e.toggle(); } );
	wave.addEventListener( 'keydown', ev => {
		const s = ev.shiftKey ? 60 : 10;
		if ( ev.key === 'ArrowRight' ) { e.seek( audio.currentTime + s ); ev.preventDefault(); }
		if ( ev.key === 'ArrowLeft' ) { e.seek( audio.currentTime - s ); ev.preventDefault(); }
		if ( ev.key === ' ' || ev.key === 'Enter' ) { e.toggle(); ev.preventDefault(); }
	} );

	// ----- Off screen: a playing tile or mix keeps playing (sound is listened to, not watched) -----

	// ----- The lock screen (mixes) -----
	if ( ! tile && 'mediaSession' in navigator ) {
		audio.addEventListener( 'play', () => {
			const art = el.dataset.artwork;
			navigator.mediaSession.metadata = new MediaMetadata( {
				title,
				artist: "Tiags' Space",
				album: el.dataset.album || '',
				artwork: art ? [ { src: new URL( art, location.href ).href } ] : [],
			} );
		} );
	}
	sync();
}
