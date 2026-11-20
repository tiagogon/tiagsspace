<?php
/*
Single // Log Archive pages >> Content

On a single work: the text, then the date line ("20250913 +") whose "+" expands the
terms (from, places, medium, tags) and, for editors, Edit and Download. The work's
title is in the fixed header (template-parts/header-title.php), not here.
On a listing (the Log archive, a Log branch): the entry's title after its gallery,
linked to the entry; no date line, no footer.
*/
?>

<?php if ( get_the_content() ) { ?>

  <section class="post-content clearfix" itemprop="articleBody">
      <?php the_content(); ?>
  </section> <!-- end article section -->

<?php } ?>


<?php
  $post_type = get_post_type( $post->ID );
  $is_single = is_singular();
  $can_edit  = $is_single && current_user_can( 'edit_post', $post->ID );
  $toggle_id = 'collapsePostFooter' . $post->ID;

  // What the "+" expands: one item per taxonomy (its terms, linked, space-separated),
  // then Edit and Download for editors. A page has none of it.
  $footer_items = array();
  $attachments  = array();
  if ( $is_single && 'page' !== $post_type ) {
      foreach ( array( 'from', 'places', 'medium', 'post_tag' ) as $taxonomy ) {
          $list = taxonomy_list( $post->ID, $taxonomy, '', '', ' ', ' ', 'link' );
          if ( $list ) {
              $footer_items[] = $list;
          }
      }
      if ( $can_edit ) {
          $footer_items[] = '<a class="post-edit-link" href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Edit</a>';
          // Switches the front-end gallery editor on / off for this one post; Theme
          // Settings only sets the default (library/gallery-functions.php).
          if ( function_exists( 'tiagsspace_gallery_edit_available' ) && tiagsspace_gallery_edit_available( $post->ID ) ) {
              $gallery_edit_on = tiagsspace_gallery_edit_mode( $post->ID );
              $footer_items[]  = sprintf(
                  '<a class="gallery-edit-toggle" href="%s" title="%s">%s</a>',
                  esc_url( tiagsspace_gallery_edit_toggle_url( $post->ID ) ),
                  esc_attr( $gallery_edit_on ? __( 'Switch the gallery editor off for this post', 'tiagsspace' ) : __( 'Switch the gallery editor on for this post', 'tiagsspace' ) ),
                  esc_html( $gallery_edit_on ? __( 'Gallery edit: on', 'tiagsspace' ) : __( 'Gallery edit: off', 'tiagsspace' ) )
              );
          }
          // In gallery edit mode the editor bar has the Download button (all, or
          // the selected), so the footer link and its list are left out.
          $attachments = ( function_exists( 'tiagsspace_gallery_edit_mode' ) && tiagsspace_gallery_edit_mode( $post->ID ) ) ? array() : get_posts( array(
              'post_type'   => 'attachment',
              'numberposts' => -1,
              'post_parent' => $post->ID,
              'orderby'     => 'menu_order',
              'order'       => 'ASC',
          ) );
          if ( $attachments ) {
              $footer_items[] = '<a id="download-all-attachments-' . $post->ID . '" href="#">Download</a>';
          }
      }
  }
?>

<header>

    <div class="page-header">
        <?php if ( $is_single ) {

          if ( 'page' !== $post_type ) {
            // The date, in the place and size the title had; the "+" is a real button so
            // Space and Enter work, named for screen readers, switched by the global
            // [aria-expanded] .reveal/.hide rule (Bootstrap sets aria-expanded on the trigger).
            ?>
        <div class="single-title entry-date">
          <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>" itemprop="datePublished"><span aria-hidden="true"><?php echo esc_html( get_the_date( 'Ymd' ) ); ?></span><span class="sr-only"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></span></time><?php
          if ( $footer_items ) {
            ?> <button type="button" class="entry-date-toggle" data-toggle="collapse" data-target="#<?php echo $toggle_id; ?>" aria-expanded="false" aria-controls="<?php echo $toggle_id; ?>"><span class="reveal">+<span class="sr-only"> Show details</span></span><span class="hide">&minus;<span class="sr-only"> Hide details</span></span></button><?php
          } ?>

        </div>
            <?php
          }

        } else {
          // Listing: "<branch> / <Title>", the heading an <h2> linked to the entry. It
          // leaves out what the page header already says (the Log archive the type, a
          // branch listing the type and the branch). See library/page-title/README.md.
          $omit = is_tax( 'log-branch' ) ? array( 'type', 'branch' ) : ( is_post_type_archive() ? array( 'type' ) : array() );
          echo tiagsspace_render_crumbs(
            tiagsspace_entry_crumbs( $post->ID, array( 'omit' => $omit, 'link_title' => true ) ),
            array( 'heading' => 'h2', 'responsive' => false, 'class' => 'single-title', 'heading_attrs' => array( 'itemprop' => 'headline' ) )
          );
        } ?>
    </div>

</header> <!-- end article header -->

<?php if ( $footer_items ) { ?>
<footer class="collapse" id="<?php echo $toggle_id; ?>">
  <p class="footer-meta"><?php echo implode( ' • ', $footer_items ); ?></p>
  <?php if ( $can_edit && function_exists( 'tiagsspace_gallery_edit_mode' ) && tiagsspace_gallery_edit_mode( $post->ID ) ) {
      // The gallery toolbar (Delete / Select / Sort / Move to) mounts here: library/js/gallery-edit/toolbar.js.
      ?>
  <div class="gallery-edit-toolbar" data-tge-mount="<?php echo (int) $post->ID; ?>"></div>
  <?php } ?>
  <?php if ( $attachments ) {
      // Editors: every attachment, and a zip of all of them (streamed via admin-ajax).
      $zip_url = add_query_arg( array(
          'action'  => 'download_all_attachments',
          'post_id' => $post->ID,
          '_nonce'  => wp_create_nonce( 'download_all_attachments' ),
      ), admin_url( 'admin-ajax.php' ) );
      ?>
  <div style="display: none;" id="download-links-<?php echo $post->ID; ?>">
    <a href="<?php echo esc_url( $zip_url ); ?>">All attachments</a>
    <?php
    $position = 0;
    foreach ( $attachments as $attachment ) {
        $position++;
        $file_url  = wp_get_attachment_url( $attachment->ID );
        $file_name = get_the_title() . ' - attachement-' . sprintf( '%03d', $position ) . ' - ' . $attachment->post_title;
        echo '<br><a href="' . esc_url( $file_url ) . '" download="' . sanitize_file_name( $file_name ) . '">' . esc_html( $attachment->post_title ) . '</a>';
    }
    ?>
  </div>
  <script>
  document.getElementById('download-all-attachments-<?php echo $post->ID; ?>').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('download-links-<?php echo $post->ID; ?>').style.display = 'block';
  });
  </script>
  <?php } ?>
</footer> <!-- end article footer -->
<?php } ?>
