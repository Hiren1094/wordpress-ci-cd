<?php
/**
 * The template for displaying all category pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package SKT Insurance
 */
get_header(); ?>
<div class="container">
	<div id="content_navigator">
     <div class="page_content">
        <section class="site-main">
             <!-- .page-header -->
			<?php if ( have_posts() ) : ?>
                <div class="blog-post">
                    <?php /* Start the Loop */ 
						while ( have_posts() ) : the_post(); 
						get_template_part( 'content', get_post_format() ); 
						endwhile; 
					?>
                </div>
                <?php the_posts_pagination(); 
				else : 
				get_template_part( 'no-results');  
				endif; 
				?>
       </section>
       <?php get_sidebar();?>       
        <div class="clear"></div>
    </div><!-- site-aligner -->
    </div>
</div><!-- container -->
<?php get_footer(); ?>