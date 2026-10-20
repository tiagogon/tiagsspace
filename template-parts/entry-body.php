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

  // Metadata footer (date, terms, Edit, Download) is editor-only and only on the
  // single post itself — on Log listings the title already links to the post, so
  // editors open the post to reach it. Visitors never get the footer in the DOM.
  $is_single = is_singular();
  $show_meta = $is_single && current_user_can( 'edit_post', $post->ID );

?>

<header>

    <div class="page-header">
        <?php
        // The title line: "<Series> / <branch> / <Title>", built by library/page-title/.
        // The heading element (h1 on the single post, h2 on listings) holds only the
        // work's title; the series and branch crumbs sit beside it in the same inline
        // flow (see .single-title in _page-single-content.scss). A listing leaves out
        // what its page header already says: the Log archive the type, a branch
        // listing the type and the branch.
        $render_args = array(
            'class'         => 'single-title',
            'responsive'    => false,
            'heading_attrs' => array( 'itemprop' => 'headline' ),
        );
        if ( $is_single ) {
            $crumbs = tiagsspace_entry_crumbs( $post->ID );
            $render_args['heading'] = 'h1';
            if ( $show_meta ) {
                // Editors: the title itself toggles the metadata footer below.
                $toggle_id = 'collapsePostFooter' . $post->ID;
                $crumbs[ count( $crumbs ) - 1 ]['url'] = '#' . $toggle_id;
                echo str_replace(
                    '<a href="#' . $toggle_id . '">',
                    '<a data-toggle="collapse" href="#' . $toggle_id . '" role="button" aria-expanded="false" aria-controls="' . $toggle_id . '">',
                    tiagsspace_render_crumbs( $crumbs, $render_args )
                );
            } else {
                echo tiagsspace_render_crumbs( $crumbs, $render_args );
            }
        } else {
            $omit = is_tax( 'log-branch' ) ? array( 'type', 'branch' ) : ( is_post_type_archive() ? array( 'type' ) : array() );
            $render_args['heading'] = 'h2';
            echo tiagsspace_render_crumbs( tiagsspace_entry_crumbs( $post->ID, array( 'omit' => $omit, 'link_title' => true ) ), $render_args );
        }
        ?>
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
