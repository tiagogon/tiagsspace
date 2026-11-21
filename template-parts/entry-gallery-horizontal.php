<?php
/*
 * Horizontal gallery (single works, and each entry of the Log listing).
 *
 * Two styles, from the ACF select `horizontal_gallery_style`:
 * - classic (or nothing stored): a row of pictures the visitor scrolls sideways. A real
 *   native scroller (overflow-x), so trackpads, touch screens and Shift+wheel use the
 *   system's own inertia; library/js/horizontal-gallery.js adds mouse dragging and ←/→.
 * - still: one picture at a time, swapped on a timer while the gallery is on screen;
 *   a click shows the next one. Any other stored value (the retired `insta`) is `still`.
 *
 * No library: Swiper was removed in Nov 2026 (its imitation of scrolling fought the
 * trackpad's inertia). Styles in _page-single-gallery.scss (.hgallery), cursors in
 * _component-cursors.scss.
 */

// This gallery is the page's first one when it comes first: no later gallery may take
// the eager first row (see entry-gallery.php).
$hgallery_is_lead = tiagsspace_once( 'lead-gallery' ) && ! is_paged();

$images = get_children( array(
    'numberposts'    => -1,
    'orderby'        => 'menu_order', // set in the page media manager
    'order'          => 'ASC',
    'post_mime_type' => 'image',
    'post_parent'    => $post->ID,
    'post_type'      => 'attachment',
) );

$images = array_values( array_filter( (array) $images, function ( $image ) {
    return ! get_field( 'remove_from_default_gallery', $image->ID );
} ) );

if ( ! $images ) {
    return;
}

$hgallery_style = get_field( 'horizontal_gallery_style' );
$hgallery_style = ( 'classic' === $hgallery_style || ! $hgallery_style ) ? 'classic' : 'still';

$hgallery_title = get_the_title();
$hgallery_id    = 'gallery-' . get_the_ID();

$track_attrs = '';
if ( 'classic' === $hgallery_style ) {
    // Focusable, so the keyboard can scroll it natively once it has focus
    $track_attrs = ' tabindex="0" role="region" aria-label="' . esc_attr( $hgallery_title . ', gallery' ) . '"';
} else {
    $still_delay = (int) get_field( 'still_duration' );
    $still_delay = $still_delay > 0 ? $still_delay : 1800;
    $track_attrs = ' data-hgallery-delay="' . (int) ( $still_delay + rand( -100, 100 ) ) . '"';
}

if ( tiagsspace_once( 'hgallery-js' ) ) { ?>
    <script src="<?php echo esc_url( get_template_directory_uri() . '/library/js/horizontal-gallery.js' ); ?>?ver=<?php echo esc_attr( tiagsspace_asset_ver( '/library/js/horizontal-gallery.js' ) ); ?>"></script>
<?php } ?>

<div class="container-fluid container-gallery hgallery hgallery--<?php echo $hgallery_style; ?>">

    <div id="<?php echo esc_attr( $hgallery_id ); ?>" class="gallery gallery-horizontal hgallery__track" data-hgallery="<?php echo $hgallery_style; ?>"<?php echo $track_attrs; ?> itemscope itemtype="http://schema.org/ImageGallery">

        <?php
        foreach ( $images as $index => $image ) {
            $count_item = $index + 1;

            $full   = wp_get_attachment_image_src( $image->ID, false );
            $width  = $full ? (int) $full[1] : 0;
            $height = $full ? (int) $full[2] : 0;

            // --- Source ---
            if ( get_field( 'insert_src_of_higher_resolution', $image->ID ) && $full ) {
                $source = 'src="' . esc_url( $full[0] ) . '"';
            } else {
                $set = tiagsspace_attachment_srcset( $image->ID );
                if ( 'classic' === $hgallery_style && $width && $height ) {
                    // Shown at most 85vh tall: as wide as that height times the ratio
                    $sizes = 'calc(' . round( 85 * $width / $height, 1 ) . 'vh)';
                } else {
                    $sizes = '100vw';
                }
                $source = 'src="' . $set['smallest'] . '" srcset="' . $set['srcset'] . '" sizes="' . $sizes . '"';
            }

            // --- Loading: the lead gallery shows its first two at once ---
            if ( $hgallery_is_lead && $count_item <= 2 ) {
                $loading = 1 === $count_item ? 'fetchpriority="high"' : '';
            } else {
                $loading = 'loading="lazy" decoding="async"';
            }

            // --- Alt ---
            $alt     = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
            $caption = $image->post_excerpt;
            if ( $alt ) {
                $image_alt = $alt;
            } elseif ( $caption ) {
                $image_alt = $caption . ' – ' . $hgallery_title;
            } else {
                $image_alt = $hgallery_title . ' – ' . $count_item;
            }

            $item_class = 'thumbnail item hgallery__item';
            if ( 'still' === $hgallery_style && 0 === $index ) {
                $item_class .= ' is-current'; // shown before (and without) the script
            }
            ?>
            <div class="<?php echo $item_class; ?>">
                <figure itemprop="associatedMedia" itemscope itemtype="http://schema.org/ImageObject">
                    <img <?php echo $source; ?>
                        <?php if ( $width && $height ) { ?>width="<?php echo $width; ?>" height="<?php echo $height; ?>"<?php } ?>
                        <?php echo $loading; ?>
                        draggable="false"
                        alt="<?php echo esc_attr( $image_alt ); ?>">
                    <?php if ( $caption ) { ?>
                        <figcaption itemprop="caption description"><?php echo wp_kses_post( $caption ); ?></figcaption>
                    <?php } ?>
                </figure>
            </div>
        <?php } ?>

    </div>

</div>

<?php
// A queue rather than a direct call: on an Infinite Scroll page the script tag above is
// inserted by script, which makes it async, so it may arrive after this line runs.
?>
<script>(window.tiagsspaceHGalleryQueue = window.tiagsspaceHGalleryQueue || []).push(document.getElementById(<?php echo wp_json_encode( $hgallery_id ); ?>));</script>
