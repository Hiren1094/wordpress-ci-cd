<?php
/**
 * The template for displaying home page.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package SKT Insurance
 */
get_header(); 
?>
<div class="container">
	<div id="content_navigator">
     <div class="page_content">
        <section class="site-main">
        	 <div class="blog-post">
					<?php
                    if ( have_posts() ) :
                        // Start the Loop.
                        while ( have_posts() ) : the_post();
                            /*
                             * Include the post format-specific template for the content. If you want to
                             * use this in a child theme, then include a file called called content-___.php
                             * (where ___ is the post format) and that will be used instead.
                             */
                            get_template_part( 'content', get_post_format() );
                        endwhile;
                        // Previous/next post navigation.
                        the_posts_pagination();
                    else :
                        // If no content, include the "No posts found" template.
                         get_template_part( 'no-results');
                    endif;
                    ?>
                    </div><!-- blog-post -->
             </section>
        <?php get_sidebar();?>     
        <div class="clear"></div>
    </div><!-- site-aligner -->
    </div>
</div><!-- content -->
<?php get_footer(); ?>