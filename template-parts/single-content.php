<?php
/*
Single // log Archive pages >> Content
*/
?>


<header>

    <div class="page-header">
        <h1 class="single-title" itemprop="headline">
            <?php echo taxonomy_list_w_numbers($post->ID,'log-branch','',', ',', ', ' & ', 'link');?>
            <?php // Title with or without link
            if (is_single()|| is_page()) {

                // Hyper series number
                if (is_singular('hyper')) {

                    echo 'H'.sprintf("%02d", number_of_the_post($post->ID)).' ';

                }
                if (is_singular('4k-lento')) {

                    echo '4KL'.sprintf("%02d", number_of_the_post($post->ID)).' ';

                }

                the_title();

            }else{ ?>

                <a href="<?php echo get_permalink(); ?>" rel="bookmark">
                    <?php the_title();?>
                </a>
            <?php }?>

        </h1>
    </div>

</header> <!-- end article header -->

<section class="post-content clearfix" itemprop="articleBody">
    <?php the_content(); ?>

</section> <!-- end article section -->

<?php
// FOOTER
if (is_singular() && !is_page()) {

    // Get taxonamies string
    $taxonomies_string = '';

    // $taxonomies = array(
    //   //'from',
    //   //'places',
    //   'medium',
    //   'post_tag'
    //   //'category',
    //   //'log-branch'
    // );
    // // Error documented here:https://stackoverflow.com/questions/64174023/php-notice-array-to-string-conversion-in-taxonomy-php-on-line-3442-and-category

    $taxonomies = 'medium';

    $args = array(  'orderby' => 'name',
                  'order' => 'ASC',
                  'fields' => 'all');

    $terms = get_the_terms($post->ID, $taxonomies, $args);

    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){

        foreach ( $terms as $term ) {
            $taxonomies_string = $taxonomies_string.'<a href="'. esc_url( get_term_link( $term ) ) . '">±' . str_replace(" ","",$term->name) . '</a> ';
        }

    }


  ?>
    <footer>
            <?php

            $post_type = get_post_type( $post->ID );

            $obj = get_post_type_object( $post_type );
            $year = get_the_time("Y");
            $logs_branch = "";

            if( is_singular( 'log' )) {

                $logs_branch = "/".taxonomy_list($post->ID,'log-branch','','',', ', ' & ', 'link');
            }

            ?>
            <p class= "footer-meta">
                <?php echo taxonomy_list($post->ID,'places', '', ', ', ', ', ' & ', 'link'); ?><?php echo taxonomy_list($post->ID,'from', '', '. ', ', ', ' & ', 'link'); ?>
                <?php if ($obj->labels->name == "Posts") {
                    echo "Published ";
                } else {
                    echo 'Published under <a href="'.get_post_type_archive_link( $post_type ).'">'.$obj->labels->name.'</a>'.$logs_branch.' ';
                } ?>

                on <time itemprop="datePublished" datetime="<?php the_time( 'c' ); ?>" content="<?php the_time( 'c' ); ?>"><?php the_time('F j, Y');  //the_time('d/m/Y'); ?></time><?php echo ". "; ?>

            <!-- </p>
            <p class= "footer-meta"> -->
                <?php echo ''.$taxonomies_string.'';
                // Edit link
                if( is_user_logged_in() ) {

                    // Delete post button
                    echo ' <a href="'.get_delete_post_link( $id).'">±Trash </a> ';

                    // Edit post
                    edit_post_link('±Edit', '', '');

                }?>
            </p>

    </footer> <!-- end article footer -->
<?php } ?>
