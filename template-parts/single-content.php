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

                    echo 'Hyper#'.number_of_the_post($post->ID).', ';

                }

                the_title();

            }else{ ?>

                <a href="<?php echo get_permalink(); ?>" rel="bookmark">
                    <?php the_title();?>
                </a>
            <?php }?>

        </h1>
        <p class="meta">
            <?php echo taxonomy_list($post->ID,'with', 'with ', ' in ', ', ', ' & ', 'link'); ?><?php echo taxonomy_list($post->ID,'places', '', ' // ', ', ', ' & ', 'link'); ?><?php echo taxonomy_list($post->ID,'from', '', '', ', ', ' & ', 'link'); ?>
        </p>
    </div>

</header> <!-- end article header -->

<section class="post-content clearfix" itemprop="articleBody">
    <?php the_content(); ?>

</section> <!-- end article section -->

<?php if (is_singular() && !is_page()) {?>
    <footer>

        <p class= "footer-meta">
            <?php 

            $post_type = get_post_type( $post->ID );

            if ($post_type == "post") {

                $year = get_the_time("Y");
                
                ?>Published on <time datetime="<?php echo the_time('Y-m-j'); ?>"><?php the_time('F jS, Y'); ?></time><?php if ($year<="2014") {
                            echo ' (cityburns)';}?><?php
            } else {
                $obj = get_post_type_object( $post_type );
                $year = get_the_time("Y");
                $logs_branch = "";

                if( is_singular( 'log' )) {

                    $logs_branch = "'s branch ".taxonomy_list($post->ID,'log-branch','','',', ', ' & ', 'link');
                }

                ?>Published as part of <a href="<?php echo get_post_type_archive_link( $post_type ); ?>"><?php echo $obj->labels->name;?></a><?php echo $logs_branch;?>, by <span itemprop="author"><?php the_author(); ?></span>, on <time itemprop="datePublished" datetime="<?php the_time( 'c' ); ?>" content="<?php the_time( 'c' ); ?>"><?php the_time('F j, Y'); ?></time><?php
            }
            ?>
            
        <p>

        <?php 
        // Listar las taxonomías 'post_tag' y 'my_tax'
        $taxonomies = array(
            'from',
            'places',
            'medium',
            'with',
            'post_tag',
            //'category',
            'log-branch'
        );

        $args = array(  'orderby' => 'name', 
                        'order' => 'ASC', 
                        'fields' => 'all');

        $terms = wp_get_post_terms($post->ID, $taxonomies, $args);

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){

             echo '<ul>';

                 foreach ( $terms as $term ) {
                   echo '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . $term->name . '</li></a>';
                    
                 }

                 edit_post_link('edit', '<li>', '</li>');; // Edit post link for loged in users

             echo '</ul>';
         }
        ?>

        
    </footer> <!-- end article footer -->
<?php } ?>
            