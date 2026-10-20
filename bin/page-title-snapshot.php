<?php
/**
 * Page-title snapshot extractor (companion of bin/page-title-snapshot.sh).
 *
 * Reads one HTML document on STDIN and prints the parts of it that the
 * page-title module is responsible for, in a stable text form that two runs
 * can be diffed:
 *
 *   title:     the <title> text
 *   h1-count:  how many <h1> the page has (one per page is the rule)
 *   header:    inner HTML of the fixed top-left block (#site-header .topbar-left)
 *   label:     every thumbnail hover label: the data-label attribute, or the
              showText('…') argument of the old inline handler
 *   entry:     every .single-title block (the title/date line after a gallery)
 *
 * Whitespace runs inside the HTML fragments are collapsed to one space so that
 * indentation changes in a template never show up as a diff; attribute values
 * are printed as they are.
 *
 * Run with the MAMP php binary; there is no php on the PATH of this Mac.
 */

$html = stream_get_contents( STDIN );
if ( $html === '' ) {
    fwrite( STDERR, "empty document\n" );
    exit( 1 );
}

libxml_use_internal_errors( true );
$doc = new DOMDocument();
$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR );
$xp = new DOMXPath( $doc );

function tidy( $s ) {
    $s = preg_replace( '/\s+/u', ' ', $s );
    $s = preg_replace( '/>\s+</u', '><', $s ); // indentation between tags is not a change
    return trim( $s );
}
function inner_html( DOMNode $node ) {
    $out = '';
    foreach ( $node->childNodes as $child ) {
        $out .= $node->ownerDocument->saveHTML( $child );
    }
    return tidy( $out );
}
function has_class( $class ) {
    return "contains(concat(' ', normalize-space(@class), ' '), ' $class ')";
}

$title = $xp->query( '//title' );
echo 'title: ', $title->length ? tidy( $title->item( 0 )->textContent ) : '(none)', "\n";
echo 'h1-count: ', $xp->query( '//h1' )->length, "\n";

foreach ( $xp->query( '//*[@id="site-header"]//*[' . has_class( 'topbar-left' ) . ']' ) as $node ) {
    echo 'header: ', inner_html( $node ), "\n";
}
foreach ( $xp->query( '//figure[@onmouseenter or @data-label]' ) as $fig ) {
    // Before the module: the label was the first argument of showText('…') in the
    // inline handler. After: it is the data-label attribute. Both print the same text.
    if ( $fig->hasAttribute( 'data-label' ) ) {
        $label = $fig->getAttribute( 'data-label' );
    } elseif ( preg_match( "/showText\\('(.*?)'\\);/su", $fig->getAttribute( 'onmouseenter' ), $m ) ) {
        $label = $m[1];
    } else {
        $label = '(unparsed) ' . $fig->getAttribute( 'onmouseenter' );
    }
    echo 'label: ', $label, "\n";
}
foreach ( $xp->query( '//*[' . has_class( 'single-title' ) . ']' ) as $node ) {
    echo 'entry: ', tidy( $doc->saveHTML( $node ) ), "\n";
}
