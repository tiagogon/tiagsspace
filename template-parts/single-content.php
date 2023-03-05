<?php
/*
Single // log Archive pages >> Content
*/
?>

<?php if ( get_the_content() ) { ?>

  <section class="post-content clearfix" itemprop="articleBody">
      <?php the_content(); ?>
  </section> <!-- end article section -->

<?php } ?>


<?php // get variables

  $post_type = get_post_type( $post->ID );
  $obj = get_post_type_object( $post_type );

  $year = get_the_time("Y");

  $logs_branch = "";
  $logs_branch = "".taxonomy_list($post->ID,'log-branch',' / ','',', ', ' & ', 'link');

?>

<header>

    <div class="page-header">
        <h1 class="single-title" itemprop="headline">
            <?php //echo taxonomy_list_w_numbers($post->ID,'log-branch','',', ',', ', ' & ', 'link');?>
            <?php // Title with or without link

                if (is_singular('')) {
                  echo get_the_title();
                }else {
                  echo '<a href="'.get_permalink().'">'.get_the_title().'</a>';
                }

                if (is_singular('4k-lento')) {
                    echo '4KL'.sprintf("%02d", number_of_the_post($post->ID)).' ';
                }

                echo ' | <a href="'.get_post_type_archive_link( $post_type ).'">'.$obj->labels->name.'</a>  '.$logs_branch.' '; ?>

                <time itemprop="datePublished" datetime="<?php the_time( 'c' ); ?>" content="<?php the_time( 'c' ); ?>"><?php the_time('ymd');  //the_time('d/m/Y'); ?></time><?php

                echo str_repeat('&nbsp;', 1).'<a data-toggle="collapse" href="#collapsePostFooter'.$post->ID.'" role="button" aria-expanded="false" aria-controls="collapsePostFooter'.$post->ID.'">±</a>';
                ?>

        </h1>
    </div>

</header> <!-- end article header -->

<?php

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
          $taxonomies_string = $taxonomies_string.'#<a href="'. esc_url( get_term_link( $term ) ) . '">' . str_replace(" ","",$term->name) . '</a> ';
      }

  }


?>
  <footer class="collapse" id="collapsePostFooter<?php echo $post->ID; ?>">
          <?php



          ?>
          <p class= "footer-meta">
              <?php
              /* // Old Footer
                if ($obj->labels->name == "Posts") {
                    echo "Published ";
                } else {
                    echo 'Published under <a href="'.get_post_type_archive_link( $post_type ).'">'.$obj->labels->name.'</a>'.$logs_branch.' ';
                } ?>

                on <time itemprop="datePublished" datetime="<?php the_time( 'c' ); ?>" content="<?php the_time( 'c' ); ?>"><?php the_time('F j, Y');  //the_time('d/m/Y'); ?></time><?php echo ". ";

                echo taxonomy_list($post->ID,'places', '', ', ', ', ', ' & ', 'link');
                echo taxonomy_list($post->ID,'from', '', '. ', ', ', ' & ', 'link');
             */

            echo taxonomy_list($post->ID,'from', ' #', ' ', ' #', ' #', 'link');
            echo taxonomy_list($post->ID,'places', '#', ' ', ' #', ' #', 'link');
            echo ' '.$taxonomies_string.'';

            // Edit post links
            if( is_user_logged_in() ) {

                // Delete post button
                // echo ' <a href="'.get_delete_post_link( $id).'">#Trash </a> ';

                // Edit post
                edit_post_link('#Edit', '', '');

            }?>
          </p>

  </footer> <!-- end article footer -->
