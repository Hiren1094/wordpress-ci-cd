<?php
/**
Template Name: Full Wide
* @package SKT Insurance
*/
get_header(); ?>
<div class="container">
      <div id="content_navigator">		
      <div class="page_content">
    		 <section id="sitefull">               
            		<?php if( have_posts() ) :
							while( have_posts() ) : the_post(); ?>
                                <div class="entry-content">
<?php
                                        the_content();
                                        wp_link_pages( array(
                                            'before'      => '<div class="page-links"><span class="page-links-title">' . esc_html__( 'Pages:', 'skt-insurance' ) . '</span>',
                                            'after'       => '</div>',
                                            'link_before' => '<span>',
                                            'link_after'  => '</span>',
                                            'pagelink'    => '<span class="screen-reader-text">' . esc_html__( 'Page', 'skt-insurance' ) . ' </span>%',
                                            'separator'   => '<span class="screen-reader-text">, </span>',
                                        ) );
												//If comments are open or we have at least one comment, load up the comment template
												if ( comments_open() || '0' != get_comments_number() )
													comments_template();
												?>
                                </div><!-- entry-content -->
                      		<?php endwhile; endif; ?>
            </section><!-- section-->
    <div class="clear"></div>
    </div><!-- .page_content --> 
    </div>
 </div><!-- .container --> 
<?php get_footer(); ?>