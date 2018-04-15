<?php
/*

Index of posts for Home and Archives

*/ ?>

    <div class="container-fluid series-block front-block " id="index">
        
        <div id="main" role="main" >
            
            <ul class="" id="thumb-container"  class="clearfix row no-pad">

                <?php 

                // set up the counter variable as 0
                $count = 0;

                if (have_posts()) : while (have_posts()) : the_post(); ?>

                    <?php get_template_part( 'template-parts/content', 'index-loop' ); ?>
                
                <?php endwhile; ?>  
            </ul>
            
            
            <?php  // --- Masory ----  ?>
            <script src="<?php bloginfo('template_url'); ?>/library/js/imagesloaded/imagesloaded.pkgd.min.js"></script>
            <script src="<?php bloginfo('template_url'); ?>/library/js/masonry/dist/masonry.pkgd.min.js"></script>

            <script>
                // call Masonry
                var container = document.querySelector('#thumb-container');
                var msnry;
                // initialize Masonry after all images have loaded
                imagesLoaded( container, function() {

                    msnry = new Masonry( container, {
                        percentPosition: true,
                         // columnWidth: '.item-sizer',
                         itemSelector: '.item-post',

                    });
                });
            </script>
        
            <nav class="wp-prev-next">
                <div class="nav-previous alignleft"><?php next_posts_link( 'Older posts' ); ?></div>
                <div class="nav-next alignright"><?php previous_posts_link( 'Newer posts' ); ?></div>
            </nav>  
            
            
            <?php else : ?>
            
            <article id="post-not-found">
                <header>
                    <h1><?php _e("Not Found", "wpbootstrap"); ?></h1>
                </header>
                <section class="post_content">
                    <p><?php _e("Sorry, but the requested resource was not found on this site.", "wpbootstrap"); ?></p>
                </section>
                <footer>
                </footer>
            </article>
            
            <?php endif; ?>
        
        </div> <!-- end #main -->

    </div> <!-- end #container -->