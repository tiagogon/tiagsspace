/**
 * Film: the module for kind `film` (library/video/player-film.php), the Edge player.
 *
 * Decided on the design page, library/players/test.html (Tiago, 22 Nov 2026):
 *   - The picture alone. A click (or Space / K) plays and pauses; on touch the play-large mark
 *     shows while paused. A visitor's play enters fullscreen first, every time (as the old
 *     fullscreen-on-play.js did); a pause never leaves it: only Esc or the exit icon do. Films that
 *     loop or autoplay are ambient: they never seize the screen.
 *   - The progress is the film's soundtrack (its stored loudness as heard, LUFS, as stored: no
 *     contrast, no top cut; Tiago 24 Nov; dB for a film computed before), rising from the bottom edge,
 *     there only while the pointer is in the controls band (or on keyboard focus, or dragging).
 *     A film without sound or peaks has a flat 1.5px line instead. At 00:00, untouched, there is
 *     no progress at all: the band belongs to the picture.
 *   - The time row (time, captions, AirPlay, fullscreen) shows while paused or while the mouse
 *     moves; 3s into play without movement everything hides (the cursor too, in fullscreen).
 *   - Captions are drawn by this layer, sized by the picture (3.1% of its width, 15-54px,
 *     never over 7.5% of its height), in the film's colour and box / shadow. The menu is the
 *     box that grows out of the captions icon, with the R1 marks.
 *   - The L1 wheel while the visitor waits for data; the sound edge dances where data is
 *     still to come.
 *
 * Playback (the device-tested path, unchanged; see "CAPTIONS ON HLS FILMS" in
 * .claude/rules/instructions AI.md):
 *   - iPhone + in-manifest subtitles: Apple's own player, no layer at all (only native playback
 *     paints those renditions there). iOS 17.1+ has ManagedMediaSource, so this check comes
 *     before hls.js's.
 *   - HLS elsewhere: hls.js wherever MediaSource exists (it caps the level to the player's size;
 *     Chrome's native HLS does not), native HLS otherwise. hls.js only feeds the cues
 *     (subtitleDisplay = false); the chosen language is told to it (subtitleTrack).
 *   - AirPlay cannot carry MediaSource playback: choosing AirPlay moves the film to native HLS.
 *   - iPhone fullscreen hands the video to Apple's player: the chosen caption track is shown
 *     ('showing') for that time and hidden again on return.
 *   - ?video_debug=1 logs the engine and level switches.
 *
 * Contract: mount( el ) — el is the `.media` element; it never looks outside it (the document
 * gets only the fullscreen and Escape listeners it needs).
 */

const V = new URL( import.meta.url ).search;
const { engine } = await import( './engine.js' + V );
const { smoothClock, clock } = await import( './clock.js' + V );
const { watchWaiting, waitMotion, lockToClock, loadingMarkup } = await import( './wait.js' + V );
const { DEFAULTS, series, resample, contrasted } = await import( './envelope.js' + V );
const { airplay } = await import( './airplay.js' + V );

const config = window.tiagsspaceMediaConfig || {};
const debug = /[?&]video_debug=1\b/.test( location.search );
const log = ( ...a ) => { if ( debug ) console.log( '[film]', ...a ); };
const IPHONE = /iPhone|iPod/i.test( navigator.userAgent );
const TOUCH = () => document.documentElement.classList.contains( 'tg-touch' );
const SND_H = 32;   // the sound edge's drawing height (the box scales it to --p-sound)

let hlsReady = null;
function needHls() {
	if ( window.Hls ) return Promise.resolve();
	return hlsReady ||= new Promise( ( resolve, reject ) => {
		const s = document.createElement( 'script' );
		s.src = config.hls;
		s.onload = resolve;
		s.onerror = reject;
		document.head.appendChild( s );
	} );
}

/** The R1 caption menu item (the same drawing as the design page). */
const LENS_RIM = 'M2 18a16 16 0 1 0 32 0a16 16 0 1 0-32 0ZM7.58 23.86A11.5 13.3 -25 1 0 28.42 12.14A11.5 13.3 -25 1 0 7.58 23.86Z';
const esc = s => String( s ).replace( /[&<>"]/g, c => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' } )[ c ] );
const ccItem = ( label, i ) => `<button type="button" class="cc-item" role="menuitemradio" aria-checked="false" data-i="${ i }"><span class="w">${ esc( label ) }</span><svg class="mk" viewBox="0 0 36 36" aria-hidden="true"><circle class="mk-ring" cx="18" cy="18" r="16" fill="none" stroke="currentColor" vector-effect="non-scaling-stroke"/><path class="mk-lens" fill="currentColor" fill-rule="evenodd" d="${ LENS_RIM }"/><circle class="mk-dot" cx="18" cy="18" r="6.5" fill="currentColor"/></svg></button>`;

/** Open or close the captions box: it grows from the icon's rectangle, then the words fade in. */
const ccIsOpen = menu => menu.classList.contains( 'grown' );
function ccOpen( menu, open ) {
	clearTimeout( menu._t );
	if ( open ) {
		menu.style.width = menu.style.height = '';
		menu.classList.add( 'open' );
		const list = menu.querySelector( '.cc-list' ), line = parseFloat( getComputedStyle( menu ).borderTopWidth ) * 2;
		const w = list.offsetWidth + line, h = list.offsetHeight + line;
		void menu.offsetWidth;
		menu.style.width = w + 'px';
		menu.style.height = h + 'px';
		menu.classList.add( 'grown' );
	} else if ( menu.classList.contains( 'open' ) ) {
		menu.classList.remove( 'grown' );
		menu.style.width = menu.style.height = '';
		menu._t = setTimeout( () => menu.classList.remove( 'open' ), 360 );
	}
}

export async function mount( el ) {
	const $ = s => el.querySelector( s );
	const video = $( 'video' );
	const layer = $( '.film__layer' );
	const ambient = el.dataset.ambient === '1';
	const title = el.dataset.title || '';
	const src = video.dataset.hlsSrc || '';

	// ----- iPhone + in-manifest subtitles: the Edge look at rest, Apple's player on a tap -----
	// Only a plain <video> (no Plyr, no hls.js) gets iOS to paint in-manifest subtitles, and only in
	// its own fullscreen player (device-tested). So the video stays plain, under the poster and the
	// resting layer (time, play mark, fullscreen icon); without playsinline, the tap's play() makes
	// iOS open its fullscreen player, captions included. Leaving it pauses, and the layer is back.
	// No seeking edge, captions box or AirPlay inline here: Apple's player has its own.
	if ( el.dataset.manifestSubs === '1' && IPHONE && video.canPlayType( 'application/vnd.apple.mpegurl' ) ) {
		log( 'engine: native HLS, Apple player on tap (manifest subs)' );
		el.classList.add( 'is-apple-tap' );
		video.controls = false;
		if ( ! ambient ) {
			video.removeAttribute( 'playsinline' );
			video.removeAttribute( 'webkit-playsinline' );
		}
		[ '.cc-wrap', '.film__airplay', '.film__rail', '.film__cc-text' ].forEach( s => { const n = $( s ); if ( n ) n.remove(); } );
		const hit = $( '.film__hit' ), fs = $( '.film__fs' ), time = $( '.film__time' );
		const go = () => { const p = video.paused ? video.play() : video.pause(); if ( p && p.catch ) p.catch( () => {} ); };
		hit.addEventListener( 'click', go );
		fs.addEventListener( 'click', ev => { ev.stopPropagation(); if ( video.paused ) go(); } );
		video.addEventListener( 'loadedmetadata', () => { if ( ! time.textContent && video.duration ) time.textContent = clock( video.duration ); } );
		const show = () => {
			const paused = video.paused;
			el.classList.toggle( 'is-paused', paused );
			el.classList.toggle( 'is-playing', ! paused );
			if ( ! paused ) el.classList.add( 'is-started' );
		};
		[ 'play', 'pause', 'playing', 'ended' ].forEach( t => video.addEventListener( t, show ) );
		video.addEventListener( 'webkitendfullscreen', () => { if ( ! ambient ) video.pause(); show(); } );
		show();
		return;
	}

	// ----- The engine: hls.js, native HLS, or the MP4 ladder -----
	let hls = null;
	if ( src && ! IPHONE ) {
		try { await needHls(); } catch ( err ) { log( 'hls.js failed to load', err ); }
	}
	if ( src && window.Hls && Hls.isSupported() && ! ( IPHONE && el.dataset.manifestSubs === '1' ) ) {
		log( 'engine: hls.js', src );
		hls = new Hls( { capLevelToPlayerSize: true, startLevel: -1 } );
		hls.subtitleDisplay = false;   // this layer paints the cues; hls.js only feeds them
		hls.on( Hls.Events.FRAG_LOADING, () => { video._fragLoading = true; video.dispatchEvent( new Event( 'tiagsspace:fragloading' ) ); } );
		[ Hls.Events.FRAG_LOADED, Hls.Events.FRAG_LOAD_EMERGENCY_ABORTED ].forEach( ev => hls.on( ev, () => { video._fragLoading = false; } ) );
		hls.on( Hls.Events.LEVEL_SWITCHED, ( ev, data ) => {
			const l = hls.levels[ data.level ];
			log( 'level', l ? `${ l.width }x${ l.height }` : data.level, Math.round( hls.bandwidthEstimate / 1000 ) + 'kbps' );
		} );
		hls.on( Hls.Events.ERROR, ( ev, data ) => {
			video._fragLoading = false;
			log( 'error', data.type, data.details, data.fatal ? '(fatal)' : '' );
			if ( ! data.fatal ) return;
			if ( data.type === Hls.ErrorTypes.NETWORK_ERROR ) hls.startLoad();
			else if ( data.type === Hls.ErrorTypes.MEDIA_ERROR ) hls.recoverMediaError();
			else hls.destroy();
		} );
		hls.loadSource( src );
		hls.attachMedia( video );
	} else {
		log( src ? 'engine: native HLS' : 'engine: MP4 ladder' );
	}

	video.controls = false;
	const e = engine( video, {
		ratio: null,
		muted: video.muted,
		loop: { active: video.loop },
		captions: { active: false, update: false },
		fullscreen: { enabled: true, fallback: true, iosNative: true, container: null },
	} );

	// ----- Layer parts -----
	layer.insertAdjacentHTML( 'beforeend', loadingMarkup() );
	lockToClock( layer );
	watchWaiting( e, el );
	const hit = $( '.film__hit' ), time = $( '.film__time' ), rail = $( '.film__rail' ), ptr = $( '.film__pointer' );
	const snd = $( '.film__snd' ), sh = $( '.film__snd-hollow' ), sf = $( '.film__snd-filled' );
	const fs = $( '.film__fs' );

	let peaks = null;
	try { const j = $( '.media__peaks' ); peaks = j ? JSON.parse( j.textContent ) : null; } catch ( err ) { peaks = null; }
	const duration = () => e.plyr.duration || video.duration || ( peaks && peaks.duration ) || 0;
	let aimAt = null;

	// ----- The sound edge: the soundtrack's top line, the played part filled under it -----
	let base = null;
	const paint = k => {
		if ( ! base ) return;
		const { w, v } = base, n = v.length;
		let top = '';
		for ( let i = 0; i < n; i++ ) {
			const x = i / ( n - 1 );
			top += `${ i ? ' L' : 'M' }${ ( x * w ).toFixed( 1 ) } ${ ( SND_H - 1 - Math.max( 0.04, v[ i ] ) * ( k ? k( x ) : 1 ) * ( SND_H - 2 ) ).toFixed( 1 ) }`;
		}
		sh.setAttribute( 'd', top );
		sf.setAttribute( 'd', `M0 ${ SND_H } L${ top.slice( 1 ) } L${ w } ${ SND_H } Z` );
	};
	const drawSnd = () => {
		const w = rail.getBoundingClientRect().width;
		if ( ! peaks || ! w ) return;
		snd.setAttribute( 'viewBox', `0 0 ${ w } ${ SND_H }` );
		const n = Math.max( 6, Math.floor( w / DEFAULTS.step.full ) );
		const measure = peaks.lufs ? DEFAULTS.filmMeasure : 'db';
		base = { w, v: resample( contrasted( series( peaks, measure ), DEFAULTS.filmContrast ), n ) };
		paint( null );
	};
	waitMotion( { media: video, visible: () => el.classList.contains( 'edge-open' ), waiting: () => el.classList.contains( 'is-waiting' ), paint, watch: [ el ] } );
	new ResizeObserver( () => requestAnimationFrame( drawSnd ) ).observe( rail );
	drawSnd();

	// ----- Time and progress -----
	const paintP = t => {
		const d = duration(), f = d ? Math.min( 1, t / d ) : 0;
		rail.style.setProperty( '--p', f * 100 + '%' );
	};
	const prog = smoothClock( video, paintP );
	const sync = () => {
		const d = duration(), t = video.currentTime || 0;
		paintP( prog.now() );
		const started = aimAt != null || t > 0 || ! video.paused;
		time.textContent = ! d ? ( started ? clock( aimAt ?? t ) : '' ) : started ? clock( aimAt ?? t ) + ' / ' + clock( d ) : clock( d );
		const atStart = t === 0 && video.paused;
		el.classList.toggle( 'at-start', atStart );
		rail.tabIndex = atStart ? -1 : 0;
		if ( atStart ) el.classList.remove( 'edge-open' );
		rail.setAttribute( 'aria-valuenow', d ? Math.round( t / d * 100 ) : 0 );
	};
	[ 'timeupdate', 'loadedmetadata', 'durationchange', 'seeked', 'play', 'pause' ].forEach( t => video.addEventListener( t, sync ) );
	const state = () => {
		const paused = video.paused;
		el.classList.toggle( 'is-paused', paused );
		el.classList.toggle( 'is-playing', ! paused );
		hit.setAttribute( 'aria-label', ( paused ? 'Play' : 'Pause' ) + ( title ? ' ' + title : '' ) );
		if ( ! paused ) el.classList.add( 'is-started' );
		open();   // declared further down; state() only runs on media events, after mount() has finished
	};
	[ 'play', 'pause', 'playing', 'ended' ].forEach( t => video.addEventListener( t, state ) );

	// ----- Fullscreen: the whole film box, on every visitor's play; kept on pause -----
	const fsEl = () => document.fullscreenElement || document.webkitFullscreenElement;
	const fsOn = () => fsEl() === el || el.classList.contains( 'sim-fs' );
	const fsShow = () => {
		const on = fsOn();
		fs.querySelector( 'use' ).setAttribute( 'href', on ? '#tg-exit-fullscreen' : '#tg-enter-fullscreen' );
		fs.setAttribute( 'aria-label', on ? 'Exit fullscreen' : 'Fullscreen' );
		requestAnimationFrame( drawSnd );
	};
	const fsEnter = () => {
		if ( fsOn() ) return;
		const req = el.requestFullscreen || el.webkitRequestFullscreen;
		if ( ! req ) { e.plyr.fullscreen.enter(); return; }   // iPhone: Apple's player (iosNative)
		try { const p = req.call( el ); if ( p && p.catch ) p.catch( () => {} ); } catch ( err ) { /* refused */ }
		hit.focus( { preventScroll: true } );   // so Space plays and pauses, not the icon just pressed
		// A host that neither grants nor refuses (an embedded preview): fill the window instead.
		setTimeout( () => { if ( ! fsEl() && document.hasFocus() && el.classList.contains( 'is-playing' ) && ! fsOn() ) { el.classList.add( 'sim-fs' ); fsShow(); } }, 400 );
	};
	const fsExit = () => {
		if ( fsEl() ) ( document.exitFullscreen || document.webkitExitFullscreen ).call( document );
		el.classList.remove( 'sim-fs' );
		fsShow();
	};
	fs.addEventListener( 'click', ev => { ev.stopPropagation(); fsOn() ? fsExit() : fsEnter(); } );
	[ 'fullscreenchange', 'webkitfullscreenchange' ].forEach( t => document.addEventListener( t, fsShow ) );
	document.addEventListener( 'keydown', ev => { if ( ev.key === 'Escape' && el.classList.contains( 'sim-fs' ) ) fsExit(); } );

	const visitorToggle = () => {
		if ( video.paused && ! ambient ) fsEnter();
		e.toggle();
	};
	hit.addEventListener( 'click', visitorToggle );
	// Plyr toggles fullscreen on a double click in its frame; the layer keeps double clicks to itself.
	layer.addEventListener( 'dblclick', ev => { ev.stopPropagation(); ev.preventDefault(); } );
	layer.addEventListener( 'keydown', ev => {
		if ( ( ev.key === ' ' || ev.key === 'k' ) && ! ev.target.closest( 'button:not(.film__hit)' ) ) { visitorToggle(); ev.preventDefault(); }
	} );

	// ----- Idle: 3s into play without movement, everything hides -----
	let idle = 0, inBand = false;
	const held = () => rail.classList.contains( 'drag' ) || ( document.activeElement === rail && rail.matches( ':focus-visible' ) );
	// The band sizes are container steps (clamp() on cqw, the tg-player-step mixin): reading the
	// custom property gives back that unresolved text, so a hidden probe in the frame resolves it.
	const probe = document.createElement( 'span' );
	probe.setAttribute( 'aria-hidden', 'true' );
	probe.style.cssText = 'position:absolute;left:0;top:0;height:0;visibility:hidden;pointer-events:none';
	$( '.media__frame' ).appendChild( probe );
	const px = name => { probe.style.width = `var(${ name }, 0px)`; return probe.getBoundingClientRect().width; };
	const bandH = () => px( '--p-gutter' ) * 2 + px( '--p-time' ) + ( el.classList.contains( 'edge-open' ) ? px( '--p-sound' ) : 0 );
	// On a touch screen there is no pointer to bring the edge up: while a film is paused (after it
	// has played) the edge stays up, so a finger can drag it; a tap on the picture plays again.
	const touchPaused = () => TOUCH() && video.paused && ! video.ended;
	const open = () => el.classList.toggle( 'edge-open', ! el.classList.contains( 'at-start' ) && ! el.classList.contains( 'is-idle' ) && ( inBand || held() || touchPaused() ) );
	const goIdle = () => {
		if ( video.paused ) return;
		if ( rail.classList.contains( 'drag' ) || el.classList.contains( 'menu-open' ) ) { wake(); return; }
		el.classList.add( 'is-idle' );
		el.classList.remove( 'is-active' );
		inBand = false;
		aimAt = null;
		open();
		sync();
	};
	const wake = () => { el.classList.remove( 'is-idle' ); clearTimeout( idle ); idle = setTimeout( goIdle, 3000 ); };
	layer.addEventListener( 'pointermove', ev => {
		el.classList.add( 'is-active' );
		wake();
		const r = el.getBoundingClientRect();
		inBand = r.bottom - ev.clientY <= bandH();
		open();
	} );
	layer.addEventListener( 'pointerdown', wake );
	el.addEventListener( 'pointerleave', () => { inBand = false; open(); } );
	el.addEventListener( 'keydown', () => { el.classList.add( 'is-active' ); wake(); } );
	video.addEventListener( 'play', wake );
	video.addEventListener( 'pause', () => { clearTimeout( idle ); el.classList.remove( 'is-idle' ); } );

	// ----- The edge: hover shows where a click lands, a drag seeks, keys step -----
	const frac = ev => { const r = rail.getBoundingClientRect(); return Math.max( 0, Math.min( 1, ( ev.clientX - r.left ) / r.width ) ); };
	rail.addEventListener( 'pointermove', ev => {
		const f = frac( ev );
		ptr.style.left = f * 100 + '%';
		aimAt = f * duration();
		sync();
		if ( rail.classList.contains( 'drag' ) ) e.seek( f * duration() );
	} );
	rail.addEventListener( 'pointerleave', () => { aimAt = null; sync(); } );
	rail.addEventListener( 'pointerdown', ev => { rail.setPointerCapture( ev.pointerId ); rail.classList.add( 'drag' ); e.seek( frac( ev ) * duration() ); } );
	rail.addEventListener( 'pointerup', () => { rail.classList.remove( 'drag' ); open(); } );
	rail.addEventListener( 'focus', open );
	rail.addEventListener( 'blur', open );
	rail.addEventListener( 'keydown', ev => {
		if ( ev.key === 'ArrowRight' || ev.key === 'ArrowLeft' ) {
			e.seek( video.currentTime + ( ev.key === 'ArrowRight' ? 1 : -1 ) * ( ev.shiftKey ? 60 : 10 ) );
			ev.preventDefault();
		}
	} );

	// ----- Captions: the box with the languages, the text drawn by this layer -----
	const ccWrap = $( '.cc-wrap' );
	const ccText = $( '.film__cc-text' );
	let current = null;
	if ( ccWrap ) {
		const cc = $( '.film__cc' ), menu = $( '.cc-menu' ), list = $( '.cc-list' );
		let items = [];
		const textTracks = () => [ ...video.textTracks ].filter( t => t.kind === 'subtitles' || t.kind === 'captions' );
		const showCue = () => {
			const cue = current && current.activeCues && current.activeCues[ 0 ];
			ccText.innerHTML = cue ? cue.text.replace( /<[^>]+>/g, '' ).split( '\n' ).map( l => `<span>${ esc( l ) }</span>` ).join( '<br>' ) : '';
		};
		const rendition = track => {
			if ( ! hls || ! hls.subtitleTracks ) return -1;
			const list = hls.subtitleTracks;
			const same = list.map( ( r, i ) => ( ( r.lang || '' ).toLowerCase() === ( track.language || '' ).toLowerCase() ? i : -1 ) ).filter( i => i >= 0 );
			return same.find( i => ( list[ i ].name || '' ) === track.label ) ?? ( same.length ? same[ 0 ] : -1 );
		};
		const choose = it => {
			textTracks().forEach( t => { if ( t !== it.track ) { t.mode = 'disabled'; t.oncuechange = null; } } );
			current = it.track;
			if ( hls ) hls.subtitleTrack = current ? rendition( current ) : -1;
			if ( current ) { current.mode = 'hidden'; current.oncuechange = showCue; }
			showCue();
			list.querySelectorAll( '.cc-item' ).forEach( ( b, i ) => b.setAttribute( 'aria-checked', items[ i ] === it ? 'true' : 'false' ) );
			cc.querySelector( 'use' ).setAttribute( 'href', current ? '#tg-captions-on' : '#tg-captions-off' );
			cc.setAttribute( 'aria-label', 'Captions: ' + it.label );
		};
		const build = () => {
			const tracks = textTracks();
			if ( tracks.length === items.length - 1 ) return;
			items = [ ...tracks.map( t => ( { label: t.label || t.language || 'Captions', track: t } ) ), { label: 'Off', track: null } ];
			list.innerHTML = items.map( ( it, i ) => ccItem( it.label, i ) ).join( '' );
			choose( items.find( it => it.track === current ) || items[ items.length - 1 ] );   // default: Off
		};
		video.textTracks.addEventListener( 'addtrack', () => setTimeout( build ) );
		if ( hls ) hls.on( Hls.Events.SUBTITLE_TRACKS_UPDATED, () => setTimeout( build ) );
		build();

		const openMenu = on => {
			ccOpen( menu, on );
			el.classList.toggle( 'menu-open', on );
			cc.setAttribute( 'aria-expanded', on );
			if ( on ) list.querySelector( '[aria-checked="true"]' )?.focus();
		};
		cc.addEventListener( 'click', ev => { ev.stopPropagation(); openMenu( ! ccIsOpen( menu ) ); } );
		menu.addEventListener( 'click', ev => {
			const b = ev.target.closest( '.cc-item' );
			if ( ! b ) return;
			ev.stopPropagation();
			choose( items[ +b.dataset.i ] );
			openMenu( false );
			cc.focus();
		} );
		menu.addEventListener( 'keydown', ev => {
			const bs = [ ...list.querySelectorAll( '.cc-item' ) ], i = bs.indexOf( document.activeElement );
			if ( ev.key === 'ArrowDown' ) { bs[ ( i + 1 ) % bs.length ].focus(); ev.preventDefault(); }
			if ( ev.key === 'ArrowUp' ) { bs[ ( i - 1 + bs.length ) % bs.length ].focus(); ev.preventDefault(); }
			if ( ev.key === 'Escape' ) { openMenu( false ); cc.focus(); ev.stopPropagation(); }
		} );
		document.addEventListener( 'click', ev => { if ( ! ccWrap.contains( ev.target ) && ccIsOpen( menu ) ) openMenu( false ); } );

		// iPhone fullscreen is Apple's player: it paints only a track that is 'showing'.
		video.addEventListener( 'webkitbeginfullscreen', () => { if ( current ) current.mode = 'showing'; } );
		video.addEventListener( 'webkitendfullscreen', () => { if ( current ) current.mode = 'hidden'; showCue(); } );
	}

	// ----- AirPlay: hls.js (MediaSource) cannot be sent, so the film moves to native HLS -----
	airplay( video, $( '.film__airplay' ), () => {
		if ( ! hls ) return;
		const t = video.currentTime, playing = ! video.paused;
		hls.destroy();
		hls = null;
		video.src = src;
		video.addEventListener( 'loadedmetadata', () => { video.currentTime = t; if ( playing ) e.play(); }, { once: true } );
		log( 'airplay: moved to native HLS' );
	} );

	// ----- The picture's shape (for the captions when fullscreen letterboxes it) -----
	const shape = () => { if ( video.videoWidth ) el.style.setProperty( '--ar', video.videoHeight / video.videoWidth ); };
	[ 'loadedmetadata', 'resize' ].forEach( t => video.addEventListener( t, shape ) );
	shape();

	// ----- Ambient films (loop / autoplay): play muted while in view -----
	if ( video.dataset.autoplay === '1' ) {
		video.muted = true;
		el.addEventListener( 'tiagsspace:media-view', ev => { ev.detail.inView ? e.play( { quiet: true } ) : e.pause(); } );
		if ( el.dataset.inView === '1' ) e.play( { quiet: true } );
	}

	if ( ! hls ) video.preload = 'metadata';
	sync();
	state();
}
