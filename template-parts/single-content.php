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
  $logs_branch = "".taxonomy_list($post->ID,'log-branch',' ',' /',', ', ' & ', 'no-link');

?>

<header>

    <div class="page-header post-title-line">
        <h1 class="single-title" itemprop="headline">
           <?php
           // Build plain-text breadcrumb prefix (no taxonomy archive links)
           $title_prefix = '';
           if (!is_post_type_archive() AND !is_tax( 'log-branch' )) {
             $title_prefix .= $obj->labels->name.' /  ';
           }
           if (!is_tax( 'log-branch' ) ) {
             $title_prefix .= $logs_branch.' ';
           }

          if (is_singular()) {
            echo $title_prefix.get_the_title();
          } else {
            echo '<a href="'.get_permalink().'" class="post-title-full-link">'.$title_prefix.get_the_title().'</a>';
          }

          echo '&nbsp;<a class="post-collapse-toggle" data-toggle="collapse" href="#collapsePostFooter'.$post->ID.'" role="button" aria-expanded="false" aria-controls="collapsePostFooter'.$post->ID.'" aria-label="Toggle post details">+</a>';
          ?>
        </h1>
        <?php if (has_post_thumbnail()) : ?>
        <div class="post-title-thumb">
            <?php the_post_thumbnail( 'thumbnail' ); ?>
        </div>
        <?php endif; ?>
    </div>

</header> <!-- end article header -->

<?php

  // Get taxonamies string
  $taxonomies_string = '';

  $taxonomies = 'medium';
  $args = array(  'orderby' => 'name',
                'order' => 'ASC',
                'fields' => 'all');
  $terms = get_the_terms($post->ID, $taxonomies, $args);
  if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
    $taxonomies_string = $taxonomies_string.' • ';
    foreach ( $terms as $term ) {
        $taxonomies_string = $taxonomies_string.'<a href="'. esc_url( get_term_link( $term ) ) . '">' . str_replace(" "," ",$term->name) . '</a> ';
    }
  }

  $taxonomies = 'tags';
  $args = array(  'orderby' => 'name',
                'order' => 'ASC',
                'fields' => 'all');
  $terms = get_the_tags($post->ID, $args);
  if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
    $taxonomies_string = $taxonomies_string.' • ';
    foreach ( $terms as $term ) {
        $taxonomies_string = $taxonomies_string.'<a href="'. esc_url( get_term_link( $term ) ) . '">' . str_replace(" "," ",$term->name) . '</a> ';
    }
  }


?>
<footer class="collapse" id="collapsePostFooter<?php echo $post->ID; ?>">
  <p class= "footer-meta">
    <time itemprop="datePublished" datetime="<?php the_time( 'c' ); ?>" content="<?php the_time( 'c' ); ?>"><?php the_time('Ymd');  //the_time('d/m/Y'); ?></time>
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

    echo taxonomy_list($post->ID,'from', ' • ', ' ', ' ', ' ', 'link');
    echo taxonomy_list($post->ID,'places', ' • ', ' ', ' ', ' ', 'link');
    echo $taxonomies_string;


    // Edit post links
    if( is_user_logged_in() ) {

        // Delete post button
        // echo ' <a href="'.get_delete_post_link( $id).'">#Trash </a> ';

        // Edit post
        echo ' • ';
        edit_post_link('Edit', '', '');

        echo ' • ';

        //Download attachements
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_parent' => $post->ID
        ));


        if ($attachments) {
            echo '<a id="download-all-attachments-'.$post->ID.'" href="#" download-all-'.$post->ID.'>Download</a>';
            echo '<div style="display: none;" id="download-links-'.$post->ID.'">';

            foreach ($attachments as $attachment) {
                $file_url = wp_get_attachment_url($attachment->ID);
                $file_name = get_the_title() . ' - ' . $attachment->post_title;
                echo '</br><a href="' . esc_url($file_url) . '" download="' . sanitize_file_name($file_name) . '">' . $attachment->post_title . '</a>';
            }

            echo '</div>';
        }?>
        <script>
        document.getElementById('download-all-attachments<?php echo "-".$post->ID; ?>').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('download-links<?php echo "-".$post->ID; ?>').style.display = 'block';
        });
        </script>
        <?php

    }?>

    <?php



?>




  </p>

</footer> <!-- end article footer -->
