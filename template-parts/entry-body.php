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
  $logs_branch = "".taxonomy_list($post->ID,'log-branch',' ',' /',', ', ' & ', 'link');

  // Metadata footer (date, terms, Edit, Download) is editor-only and only on the
  // single post itself — on Log listings the title already links to the post, so
  // editors open the post to reach it. Visitors never get the footer in the DOM.
  $is_single = is_singular();
  $show_meta = $is_single && current_user_can( 'edit_post', $post->ID );

?>

<header>

    <div class="page-header">
        <?php
        // The heading element holds ONLY the work's title (one <h1> per page; <h2> on listings).
        // The "<Type> / <branch> /" breadcrumb prefix sits beside it in the same inline flow,
        // so the rendering is unchanged (see .single-title in _page-single-content.scss).
        $heading_tag = $is_single ? 'h1' : 'h2';
        $prefix = '';
        // "<Type> / " prefix — skipped for films, which are not an artistic series.
        if ( 'films' !== $post_type && !is_post_type_archive() AND !is_tax( 'log-branch' )) {
          $prefix .= '<a href="'.get_post_type_archive_link( $post_type ).'">'.$obj->labels->name.'</a> / ';
        }
        if (!is_tax( 'log-branch' ) && $logs_branch ) {
          $prefix .= trim( $logs_branch ).' ';
        }
        ?>
        <div class="single-title">
           <?php
           if ( $prefix ) {
             echo '<span class="single-title-prefix">'.$prefix.'</span>';
           }
           echo '<'.$heading_tag.' class="single-title-heading" itemprop="headline">';
          if ( $is_single ) {
            if ( $show_meta ) {
              // Editors: the title itself toggles the metadata footer below.
              echo '<a data-toggle="collapse" href="#collapsePostFooter'.$post->ID.'" role="button" aria-expanded="false" aria-controls="collapsePostFooter'.$post->ID.'">'.get_the_title().'</a>';
            } else {
              echo get_the_title();
            }
          } else {
            echo '<a href="'.get_permalink().'">'.get_the_title().'</a>';
          }
          echo '</'.$heading_tag.'>';
          ?>
        </div>
    </div>

</header> <!-- end article header -->

<?php if ( $show_meta ) {

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


    // Edit post link (the footer only renders for users who can edit the post)

    // Delete post button
    // echo ' <a href="'.get_delete_post_link( $id).'">#Trash </a> ';

    echo ' • ';
    edit_post_link('Edit', '', '');

    //Download attachements
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'numberposts' => -1,
        'post_parent' => $post->ID,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ));


    if ($attachments) {
        echo ' • ';
        echo '<a id="download-all-attachments-'.$post->ID.'" href="#" download-all-'.$post->ID.'>Download</a>';
        echo '<div style="display: none;" id="download-links-'.$post->ID.'">';

        // Zip of all attachments (streamed server-side via admin-ajax).
        $zip_url = add_query_arg( array(
            'action'  => 'download_all_attachments',
            'post_id' => $post->ID,
            '_nonce'  => wp_create_nonce( 'download_all_attachments' ),
        ), admin_url( 'admin-ajax.php' ) );
        echo '<a href="' . esc_url( $zip_url ) . '">All attachments</a>';

        $position = 0;
        foreach ($attachments as $attachment) {
            $position++;
            $file_url = wp_get_attachment_url($attachment->ID);
            $file_name = get_the_title() . ' - attachement-' . sprintf('%03d', $position) . ' - ' . $attachment->post_title;
            echo '</br><a href="' . esc_url($file_url) . '" download="' . sanitize_file_name($file_name) . '">' . $attachment->post_title . '</a>';
        }

        echo '</div>';
        ?>
        <script>
        document.getElementById('download-all-attachments<?php echo "-".$post->ID; ?>').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('download-links<?php echo "-".$post->ID; ?>').style.display = 'block';
        });
        </script>
        <?php
    }
    ?>

  </p>

</footer> <!-- end article footer -->
<?php } // $show_meta ?>
