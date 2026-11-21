/**
 * Gallery lightbox: PhotoSwipe 5 (library/js/photoswipe/, vendor, never edited).
 * Replaced Magnific Popup in Nov 2026. It looks and behaves as Magnific did:
 * the image alone on the theme background (no arrows, no close button, no
 * counter), it grows out of its thumbnail and shrinks back, a click beside
 * the image closes, ← → Esc. A click on the image's right half shows the next
 * one and, since Nov 2026, a mouse click on its left half the previous one (the
 * cursor says which: .pswp--img-left, _component-cursors.scss); a tap on a
 * touch screen always shows the next. What changed is the loading:
 *  - srcset: the file the screen needs, never the original (3 MB on Ossos);
 *    one image before and two after are loaded ahead (preload [1, 2]);
 *  - the grid thumbnail shows at once as placeholder, and the sharp image
 *    fades in over it once loaded AND decoded (`is-loaded`, styles in
 *    _component-lightbox.scss), never painting itself in strips.
 * One addition: swipe between images on touch screens.
 * NO zooming into the image, anywhere (Tiago, Nov 2026): no zoom button,
 * content never zoomable (pinch, double tap, ctrl+wheel, the Z key).
 *
 * Printed once per page as a module by template-parts/entry-gallery.php for
 * galleries with the lightbox (.gallery-lightbox, links a.lightbox-link from
 * template-parts/gallery-item.php). ONE delegated click listener on the
 * document opens any of them, so galleries added later (Infinite Scroll on
 * the Log) need nothing. A click with a modifier key, or before this module
 * has loaded, follows the link to the original file.
 *
 * The gallery editor works inside it through gallery-edit/lightbox-photoswipe.js,
 * which reads window.tiagsspaceLightbox (set below, then the document event
 * `tiagsspace:lightbox-ready`).
 */

// The vendor files get this module's own ?v=, so a deploy reloads them too.
const VERSION = new URL( import.meta.url ).search;
const { default: PhotoSwipeLightbox } = await import( './photoswipe/photoswipe-lightbox.esm.min.js' + VERSION );

const GALLERY = '.gallery-lightbox';
const LINK = 'a.lightbox-link';

const lg = window.matchMedia( '(min-width: 992px)' );
const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

function darkPage() {
	return document.body.classList.contains( 'background-w-dark-color' );
}

// Is the pointer on the left half of the image on screen? (Left half = previous.)
function onLeftHalf( x, target ) {
	const img = target && target.closest ? target.closest( '.pswp__img' ) : null;
	if ( ! img ) return false;
	const r = img.getBoundingClientRect();
	return x < r.left + r.width / 2;
}

// Mouse click on the image: the previous picture on its left half, the next on its right half.
function step( point, event ) {
	const pswp = lightbox.pswp;
	if ( ! pswp ) return;
	const x = event && typeof event.clientX === 'number' ? event.clientX : point.x;
	if ( onLeftHalf( x, event && event.target ) ) {
		pswp.prev();
	} else {
		pswp.next();
	}
}

// The theme margin on every side: 12px below lg, 16px from lg. Dark pages
// show the title under the image and keep 40px for it.
function margins() {
	const m = lg.matches ? 16 : 12;
	return { top: m, right: m, bottom: darkPage() ? 40 : m, left: m };
}

const lightbox = new PhotoSwipeLightbox( {
	children: LINK,
	pswpModule: () => import( './photoswipe/photoswipe.esm.min.js' + VERSION ),

	// Grows out of the thumbnail and back, as Magnific's zoom did (500ms).
	showHideAnimationType: reducedMotion.matches ? 'none' : 'zoom',
	showAnimationDuration: 500,
	hideAnimationDuration: 500,
	easing: 'ease-in-out',
	bgOpacity: 0.95,
	preload: [ 1, 2 ],
	paddingFn: margins,

	// Today's look: no buttons, no counter.
	arrowPrev: false,
	arrowNext: false,
	close: false,
	zoom: false,
	counter: false,

	// No zoom levels above "fit" (and never above the file's own size).
	initialZoomLevel: 'fit',
	secondaryZoomLevel: 'fit',
	maxZoomLevel: 'fit',

	// Mouse click on the image: previous on its left half, next on its right
	// half (the cursor shows which). A tap on a touch screen, where no cursor
	// explains the halves, always goes to the next. Beside it: close
	// (Magnific's behaviour).
	imageClickAction: step,
	bgClickAction: 'close',
	tapAction: ( point, event ) => {
		const pswp = lightbox.pswp;
		if ( ! pswp ) return;
		if ( event && event.target && event.target.classList.contains( 'pswp__img' ) ) {
			pswp.next();
		} else {
			pswp.close();
		}
	},
	doubleTapAction: false,
	pinchToClose: false,
	errorMsg: 'The image could not be loaded.',
} );

// Never zoomable: blocks pinch, ctrl+wheel (trackpad pinch), Z and any toggle.
lightbox.addFilter( 'isContentZoomable', () => false );

// The grid thumbnail as placeholder on every slide, not only the first one
// (PhotoSwipe's default): it is already loaded, so something shows at once.
lightbox.addFilter( 'placeholderSrc', ( src, content ) => content.data.msrc || src );

// Fade-in: the sharp image is shown once loaded and decoded. A file the
// browser already holds (the grid's own size, a neighbour loaded ahead, a
// slide rebuilt by the gallery editor) loads within a few ms: it shows at
// once (is-instant), no fade and no blink.
const INSTANT_MS = 50;

function reveal( img, instant ) {
	if ( ! img || img.classList.contains( 'is-loaded' ) ) return;
	if ( instant ) {
		img.classList.add( 'is-instant', 'is-loaded' );
		return;
	}
	const show = () => img.classList.add( 'is-loaded' );
	if ( img.decode ) {
		img.decode().then( show, show );
	} else {
		show();
	}
}

lightbox.on( 'contentLoadImage', ( { content } ) => {
	if ( content ) content.tgLoadStart = performance.now();
} );

lightbox.on( 'loadComplete', ( { content, isError } ) => {
	if ( isError || ! content || ! content.isImageContent() ) return;
	const quick = content.tgLoadStart !== undefined && performance.now() - content.tgLoadStart < INSTANT_MS;
	reveal( content.element, quick );
} );

// The first image is loaded by the lightbox before PhotoSwipe has a slide for
// it, and PhotoSwipe only announces loadComplete for content with a slide: so
// also check whenever an image is put on screen.
function revealIfLoaded( { content } ) {
	const img = content && content.isImageContent() ? content.element : null;
	if ( img && img.complete && img.naturalWidth > 0 ) reveal( img );
}
lightbox.on( 'contentAppend', revealIfLoaded );
lightbox.on( 'contentActivate', revealIfLoaded );

// Title (dark pages only, as with Magnific): the gallery's data-lightbox-title
// and the link's caption, under the image, aligned to its left edge.
lightbox.on( 'uiRegister', () => {
	const pswp = lightbox.pswp;
	if ( ! pswp || ! darkPage() ) return;
	pswp.ui.registerElement( {
		name: 'tiagsspace-title',
		className: 'pswp__tiagsspace-title',
		appendTo: 'root',
		isButton: false,
		order: 9,
		onInit: ( el ) => {
			const update = () => {
				const slide = pswp.currSlide;
				const link = slide && slide.data && slide.data.element;
				if ( ! link ) return;
				const gallery = link.closest( GALLERY );
				el.innerHTML = ( gallery ? gallery.dataset.lightboxTitle || '' : '' ) + ( link.getAttribute( 'caption' ) || '' );
				const shown = slide.width * slide.currZoomLevel;
				el.style.left = Math.max( 0, Math.round( ( pswp.viewportSize.x - shown ) / 2 ) ) + 'px';
			};
			pswp.on( 'change', update );
			pswp.on( 'resize', update );
			pswp.on( 'afterInit', update );
		},
	} );
} );

// The cursor says what a click does: .pswp--img-left while the pointer is on
// the image's left half (previous), none on its right half (next). Checked
// again after a change of picture, since the image under a still pointer
// moves: once at the next frame and once the slide has settled (the new image
// slides in for about 300ms, and elementFromPoint may still find the old one).
lightbox.on( 'afterInit', () => {
	const pswp = lightbox.pswp;
	const root = pswp && pswp.element;
	if ( ! root ) return;
	let last = null;
	const update = ( x, target ) => root.classList.toggle( 'pswp--img-left', onLeftHalf( x, target ) );
	const onMove = ( e ) => {
		if ( e.pointerType && e.pointerType !== 'mouse' && e.pointerType !== 'pen' ) return;
		last = { x: e.clientX, y: e.clientY };
		update( e.clientX, e.target );
	};
	root.addEventListener( 'pointermove', onMove );
	let settle = 0;
	const recheck = () => { if ( last ) update( last.x, document.elementFromPoint( last.x, last.y ) ); };
	pswp.on( 'change', () => {
		requestAnimationFrame( recheck );
		clearTimeout( settle );
		settle = setTimeout( recheck, 450 );
	} );
	pswp.on( 'destroy', () => {
		clearTimeout( settle );
		root.removeEventListener( 'pointermove', onMove );
	} );
} );

// One delegated listener for every lightbox gallery on the page, now or later.
document.addEventListener( 'click', ( e ) => {
	const link = e.target instanceof Element ? e.target.closest( LINK ) : null;
	if ( ! link ) return;
	const gallery = link.closest( GALLERY );
	if ( ! gallery || e.defaultPrevented || window.pswp ) return;
	if ( e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ) return;
	const index = Array.from( gallery.querySelectorAll( LINK ) ).indexOf( link );
	if ( index < 0 ) return;
	e.preventDefault();
	// No pointer position for a keyboard "click" (Enter on the link).
	const point = e.clientX || e.clientY ? { x: e.clientX, y: e.clientY } : null;
	lightbox.loadAndOpen( index, { gallery }, point );
} );

window.tiagsspaceLightbox = { lightbox, GALLERY, LINK };
document.dispatchEvent( new CustomEvent( 'tiagsspace:lightbox-ready' ) );
