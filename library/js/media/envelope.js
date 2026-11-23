/**
 * Envelope: a file's loudness drawn as the `mix` cursor's shape, made long.
 *
 * What it is for
 *   Mixes and gallery audio draw their whole envelope from it; films draw their sound edge (the
 *   top half only) from the same numbers. The numbers are a file's peaks JSON, the output of
 *   bin/audio-peaks.py (reference) and library/audio/peaks.php (on upload): per slice the PEAK
 *   and RMS for the mono mix and each channel (`peak`, `rms`, `peakL`, `rmsL`, `peakR`, `rmsR`),
 *   and `lufs` for films.
 *
 * Decided values (Tiago, 22 Nov; the design page's defaults)
 *   measure dB (the RMS on a log scale, floor -48 dB); Stereo L/R (left above the spine, right
 *   below, scaled together so a quieter channel stays quieter); contrast 0.8; detail "Finer":
 *   a corner every 2px on a full-width shape (mix, film edge), 3px in a gallery tile.
 *
 * Contract (pure functions, no DOM)
 *   series( data, measure, ch )                     one side's values ('' mono, 'L', 'R')
 *   envelopeValues( top, bottom, w, step, contrast ) the costly part, once per size
 *   envelopeShape( values, w, h, k )                 the SVG path; k( x ) = height factor (wait.js)
 *   resample, contrasted, contrastedTogether          building blocks (the film edge uses them)
 */

export const DEFAULTS = { measure: 'db', contrast: 0.8, step: { full: 2, tile: 3 } };

/** Max of each bucket, so a short loud hit is never lost. */
export function resample( values, n ) {
	const out = new Array( n ).fill( 0 );
	for ( let i = 0; i < values.length; i++ ) {
		const k = Math.min( n - 1, Math.floor( i / values.length * n ) );
		if ( values[ i ] > out[ k ] ) out[ k ] = values[ i ];
	}
	return out;
}

/**
 * Contrast: a mastered mix is loud all the way through, so its raw envelope is a flat band.
 * c = 0 is the raw shape, c = 1 maps the quietest tenth to the spine, so breakdowns narrow and
 * drops swell. Floor and ceiling are taken over every list together (left and right share
 * them); the ceiling is the 98th percentile, so one stray peak cannot squash the whole shape.
 */
export function contrastedTogether( lists, c ) {
	if ( ! c ) return lists;
	const sorted = lists.flat().sort( ( a, b ) => a - b );
	const lo = sorted[ Math.floor( sorted.length * 0.1 ) ] * c, hi = sorted[ Math.floor( sorted.length * 0.98 ) ] || 1;
	return lists.map( values => values.map( v => Math.min( 1, Math.max( 0, ( v - lo ) / ( hi - lo ) ) ) ) );
}
export const contrasted = ( values, c ) => contrastedTogether( [ values ], c )[ 0 ];

const toDb = v => Math.max( 0, ( 20 * Math.log10( Math.max( v, 1e-6 ) ) + 48 ) / 48 );

/** What one side shows: 'peak' | 'rms' | 'db' | 'lufs', for channel '' (mono), 'L' or 'R'. */
export function series( data, measure = DEFAULTS.measure, ch = '' ) {
	if ( measure === 'lufs' ) return data.lufs || data[ 'rms' + ch ];
	const key = measure === 'peak' ? 'peak' : 'rms';
	const base = data[ key + ch ] || data[ key ];
	return measure === 'db' ? base.map( toDb ) : base;
}

export function envelopeValues( topValues, bottomValues, w, step, contrast = DEFAULTS.contrast ) {
	const n = Math.max( 6, Math.floor( w / step ) );
	const [ tc, bc ] = contrastedTogether( [ topValues, bottomValues ], contrast );
	return { n, vt: resample( tc, n ), vb: resample( bc, n ) };
}

export function envelopeShape( { n, vt, vb }, w, h, k ) {
	const mid = h / 2, amp = h / 2 - 2, min = 0.04, pad = 1;
	const xs = i => pad + i / ( n - 1 ) * ( w - 2 * pad ), f = i => ( k ? k( i / ( n - 1 ) ) : 1 );
	let top = '', bottom = '';
	for ( let i = 0; i < n; i++ ) {
		const a = Math.max( min, vt[ i ] ) * f( i ) * amp;
		top += ( i ? ' L' : 'M' ) + xs( i ).toFixed( 1 ) + ' ' + ( mid - a ).toFixed( 1 );
	}
	for ( let i = n - 1; i >= 0; i-- ) {
		const a = Math.max( min, vb[ i ] ) * f( i ) * amp;
		bottom += ' L' + xs( i ).toFixed( 1 ) + ' ' + ( mid + a ).toFixed( 1 );
	}
	return top + bottom + ' Z';
}
