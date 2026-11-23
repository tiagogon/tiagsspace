/**
 * AirPlay (Tiago, 22 Nov: keep it), films and mixes.
 *
 * The button shows only while Safari reports an AirPlay device nearby; elsewhere (Chrome,
 * Firefox) it never appears, so it is never a dead control. Pressing it opens Safari's own
 * device picker; while sending, the button carries `.is-on` (the skin dims it).
 *
 * Contract
 *   airplay( media, btn )   btn starts `hidden` in the markup.
 *
 * For films: hls.js plays through MediaSource, which AirPlay cannot carry; a film on hls.js must
 * switch to native HLS when AirPlay is chosen (Phase 6).
 */

export function airplay( media, btn ) {
	if ( ! btn || ! window.WebKitPlaybackTargetAvailabilityEvent ) return;
	media.addEventListener( 'webkitplaybacktargetavailabilitychanged', ev => { btn.hidden = ev.availability !== 'available'; } );
	media.addEventListener( 'webkitcurrentplaybacktargetiswirelesschanged', () => btn.classList.toggle( 'is-on', !! media.webkitCurrentPlaybackTargetIsWireless ) );
	btn.addEventListener( 'click', ev => { ev.stopPropagation(); media.webkitShowPlaybackTargetPicker(); } );
}
