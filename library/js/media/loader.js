/**
 * Loader: the one entry point of every media player on a page.
 *
 * What it is for
 *   The renderers print each player's resting look as plain markup, inside
 *   <div class="media" data-media-kind="…" data-media-module="…">. Nothing else of a player is
 *   loaded until it is needed: 600px before a player comes into view, the loader makes sure Plyr
 *   is there (once per page), imports the kind's module (library/js/media/<module>.js) and calls
 *   its mount( el, ctx ). Then `.is-mounted` reveals the native element under our layer.
 *
 * Contract
 *   - Printed once per page by library/media/enqueue.php, as <script type="module">, with the
 *     version of the whole folder in its query: every module is imported with that same query,
 *     so a deploy that changes any media file changes every URL (the 1-year cache never serves
 *     a stale module). window.tiagsspaceMediaConfig = { plyr: '<plyr.js URL>' }.
 *   - A kind module exports mount( el, ctx ), ctx = { version }. It never looks outside its own el.
 *   - One IntersectionObserver mounts (600px ahead); one more says when a player is in view: it
 *     sets el.dataset.inView = '1' | '0' and sends `tiagsspace:media-view` on el (detail.inView),
 *     for kinds that play or pause by visibility. Elements added later (Infinite Scroll, the
 *     gallery editor's reveal) are found by one MutationObserver; window.tiagsspaceMedia.scan( root )
 *     does the same on demand.
 *   - If anything fails, the player falls back to the browser's own controls (`.is-native`):
 *     a broken module must never leave a hidden, dead player.
 *
 * Do not
 *   - add kind-specific code here (it belongs in the kind's module);
 *   - add a second observer or a scroll handler for players anywhere else.
 */

const VERSION = new URL( import.meta.url ).search;
const config = window.tiagsspaceMediaConfig || {};
const MODULE_NAME = /^[a-z][a-z-]*$/;

let plyrReady = null;
function needPlyr() {
	if ( window.Plyr ) return Promise.resolve();
	return plyrReady ||= new Promise( ( resolve, reject ) => {
		const s = document.createElement( 'script' );
		s.src = config.plyr;
		s.onload = resolve;
		s.onerror = reject;
		document.head.appendChild( s );
	} );
}

function native( el ) {
	el.classList.add( 'is-mounted', 'is-native' );
	el.querySelectorAll( 'video, audio' ).forEach( m => { m.controls = true; } );
}

async function mount( el ) {
	const name = el.dataset.mediaModule || '';
	if ( ! MODULE_NAME.test( name ) ) { native( el ); return; }
	try {
		await needPlyr();
		const mod = await import( `./${ name }.js${ VERSION }` );
		await mod.mount( el, { version: VERSION } );
		el.classList.add( 'is-mounted' );
	} catch ( err ) {
		console.error( 'media player', name, err );
		native( el );
	}
}

const ahead = new IntersectionObserver( entries => entries.forEach( en => {
	if ( ! en.isIntersecting ) return;
	ahead.unobserve( en.target );
	mount( en.target );
} ), { rootMargin: '600px 0px' } );

const view = new IntersectionObserver( entries => entries.forEach( en => {
	const el = en.target, inView = en.isIntersecting;
	if ( el.dataset.inView === ( inView ? '1' : '0' ) ) return;
	el.dataset.inView = inView ? '1' : '0';
	el.dispatchEvent( new CustomEvent( 'tiagsspace:media-view', { detail: { inView } } ) );
} ), { threshold: 0.15 } );   // in view = at least 15% of it on screen (the design page's rule)

function scan( root = document ) {
	const found = root.matches && root.matches( '.media' ) ? [ root ] : [];
	( root.querySelectorAll ? root.querySelectorAll( '.media' ) : [] ).forEach( el => found.push( el ) );
	found.forEach( el => {
		if ( el.dataset.mediaSeen ) return;
		el.dataset.mediaSeen = '1';
		ahead.observe( el );
		view.observe( el );
	} );
}

let queued = false;
new MutationObserver( records => {
	if ( queued ) return;
	if ( ! records.some( r => r.addedNodes.length ) ) return;
	queued = true;
	requestAnimationFrame( () => { queued = false; scan(); } );
} ).observe( document.body, { childList: true, subtree: true } );

window.tiagsspaceMedia = { scan };
scan();
