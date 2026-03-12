<?php
/**
 * Table Index — lists every post across all public post types in a minimal table.
 *
 * Columns: Title · Series · Mediums · Places · Year · Published · Last Edited
 */

// Post types to query
$table_post_types = array( 'post', 'films', 'dusk', 'hyper', '4k-lento', 'log', 'cityburns' );

// Query all posts (exclude hidden from archives)
$table_query = new WP_Query( array(
    'post_type'      => $table_post_types,
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => array(
        array(
            'key'     => 'hide_post_from_main_page_archives_and_feed',
            'compare' => 'NOT EXISTS',
        ),
    ),
) );

$total = $table_query->found_posts;
?>

<div class="table-index-wrapper">

    <div class="table-index-scroll">
        <table class="table-index">
            <thead>
                <tr>
                    <th class="ti-col-published">Years</th>
                    <th class="ti-col-type">Series</th>
                    <th class="ti-col-title">Entry</th>
                    <?php /* <th class="ti-col-medium">Medium</th> */ ?>
                    <?php /* <th class="ti-col-places">Place</th> */ ?>
                    <?php /* <th class="ti-col-year">Source</th> */ ?>
                    <th class="ti-col-items">#</th>
                </tr>
            </thead>
            <tbody>
            <?php if ( $table_query->have_posts() ) : while ( $table_query->have_posts() ) : $table_query->the_post();

                $post_id   = get_the_ID();
                $permalink = get_permalink();
                $title     = get_the_title();
                $post_type_obj = get_post_type_object( get_post_type() );
                $type_label    = $post_type_obj ? $post_type_obj->labels->singular_name : get_post_type();

                // Taxonomy terms (plain text)
                // $mediums_terms = get_the_terms( $post_id, 'medium' );
                $places_terms = get_the_terms( $post_id, 'places' );
                $years_terms  = get_the_terms( $post_id, 'from' );

                $places_text = '';
                if ( ! empty( $places_terms ) && ! is_wp_error( $places_terms ) ) {
                    $places_text = implode( ', ', wp_list_pluck( $places_terms, 'name' ) );
                }
                $years_text = '';
                if ( ! empty( $years_terms ) && ! is_wp_error( $years_terms ) ) {
                    $years_text = implode( ', ', wp_list_pluck( $years_terms, 'name' ) );
                }

                // Dates
                $publish_year = get_the_date( 'Y' );

                // Build year display: unique years from publish date and 'from' taxonomy
                $all_years = array( $publish_year );
                if ( ! empty( $years_terms ) && ! is_wp_error( $years_terms ) ) {
                    $from_years = wp_list_pluck( $years_terms, 'name' );
                    $all_years = array_merge( $all_years, $from_years );
                }
                $all_years = array_unique( array_map( 'trim', $all_years ) );
                sort( $all_years );
                $year_display = implode( ', ', $all_years );

                // Count attachments not excluded from gallery
                $items_query = get_posts( array(
                    'post_type'      => 'attachment',
                    'posts_per_page' => -1,
                    'post_parent'    => $post_id,
                    'post_status'    => 'any',
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'remove_from_default_gallery',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key'     => 'remove_from_default_gallery',
                            'value'   => '1',
                            'compare' => '!=',
                        ),
                    ),
                ) );
                $items_count = count( $items_query );

                // Thumbnail URL for hover preview
                $thumb_url = '';
                if ( has_post_thumbnail( $post_id ) ) {
                    $thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );
                    if ( $thumb ) $thumb_url = $thumb[0];
                }

            ?>
                <tr data-href="<?php echo esc_url( $permalink ); ?>"<?php if ( $thumb_url ) echo ' data-thumb="' . esc_url( $thumb_url ) . '"'; ?>>
                    <td class="ti-col-published"><?php echo esc_html( $year_display ); ?></td>
                    <td class="ti-col-type"><?php
                        $current_type = get_post_type();
                        if ( $type_label !== 'Post' ) {
                            $series_text = esc_html( $type_label );

                            if ( $current_type === 'log' ) {
                                $branches = get_the_terms( $post_id, 'log-branch' );
                                if ( ! empty( $branches ) && ! is_wp_error( $branches ) ) {
                                    $branch_names = array();
                                    foreach ( $branches as $branch ) {
                                        $branch_names[] = esc_html( $branch->slug );
                                    }
                                    echo $series_text . '/' . implode( ' &amp; ', $branch_names );
                                } else {
                                    echo $series_text;
                                }
                            } else {
                                echo $series_text;
                            }
                        }
                    ?></td>
                    <td class="ti-col-title"><?php echo esc_html( $title ); ?></td>
                    <?php /* <td class="ti-col-medium"></td> */ ?>
                    <?php /* <td class="ti-col-places"><?php echo $places_text; ?></td> */ ?>
                    <?php /* <td class="ti-col-year"><?php echo $years_text; ?></td> */ ?>
                    <td class="ti-col-items"><?php echo $items_count > 0 ? $items_count : ''; ?></td>
                </tr>
            <?php endwhile; endif; wp_reset_postdata(); ?>
            </tbody>
        </table>
    </div>

    <div class="ti-hover-preview" aria-hidden="true">
        <img src="" alt="">
    </div>

</div>

<script>
(function() {
    const preview = document.querySelector('.ti-hover-preview');
    const previewImg = preview ? preview.querySelector('img') : null;
    if (!preview || !previewImg) return;

    let active = false;
    let currentSrc = '';

    const table = document.querySelector('.table-index');
    if (!table) return;

    // Make rows clickable
    table.addEventListener('click', function(e) {
        const row = e.target.closest('tr[data-href]');
        if (!row) return;
        window.location.href = row.getAttribute('data-href');
    });

    // Thumbnail preview on row hover
    table.addEventListener('mouseenter', function(e) {
        const row = e.target.closest('tr[data-thumb]');
        if (!row) return;
        const src = row.getAttribute('data-thumb');
        if (!src) return;

        if (src !== currentSrc) {
            currentSrc = src;
            previewImg.src = src;
        }
        active = true;
        preview.classList.add('is-visible');
    }, true);

    table.addEventListener('mouseleave', function(e) {
        const row = e.target.closest('tr[data-thumb]');
        if (!row) return;
        active = false;
        preview.classList.remove('is-visible');
    }, true);

    document.addEventListener('mousemove', function(e) {
        if (!active) return;
        preview.style.left = (e.clientX + 20) + 'px';
        preview.style.top  = (e.clientY + 10) + 'px';
    });
})();
</script>
