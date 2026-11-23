/**
 * AirPlay (Tiago, 22 Nov: keep it), films and mixes.
 *
 * The button shows only while Safari reports an AirPlay device nearby; elsewhere (Chrome,
 * Firefox) it never appears, so it is never a dead control. Pressing it opens Safari's own
 * device picker; while sending, the button carries `.is-on` (the skin dims it).
 *
 * Contract
 *   airplay( media, btn, beforePick )   btn starts `hidden` in the markup; beforePick(), if
 *   given, runs inside the click before the picker opens (a film on hls.js switches to native
 *   HLS there, since AirPlay cannot carry MediaSource playback).
 *
 */

export function airplay( media, btn, beforePick ) {
	if ( ! btn || ! window.WebKitPlaybackTargetAvailabilityEvent ) return;
	media.addEventListener( 'webkitplaybacktargetavailabilitychanged', ev => { btn.hidden = ev.availability !== 'available'; } );
	media.addEventListener( 'webkitcurrentplaybacktargetiswirelesschanged', () => btn.classList.toggle( 'is-on', !! media.webkitCurrentPlaybackTargetIsWireless ) );
	btn.addEventListener( 'click', ev => {
		ev.stopPropagation();
		if ( beforePick ) beforePick();
		media.webkitShowPlaybackTargetPicker();
	} );
}
