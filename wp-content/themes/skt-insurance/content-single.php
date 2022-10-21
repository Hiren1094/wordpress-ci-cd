<?php
/**
 * @package SKT Insurance
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>
     <div class="postmeta">
            <div class="post-date"><?php the_date(); ?></div><!-- post-date -->
            <div class="post-comment"> &nbsp;|&nbsp; <a href="<?php comments_link(); ?>"><?php comments_number(); ?></a></div> 
            <div class="clear"></div>         
    </div><!-- postmeta -->
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
		?>     
        <div class="postmeta">           
            <div class="post-tags"><?php the_tags(); ?> </div>
            <div class="clear"></div>
        </div><!-- postmeta -->
    </div><!-- .entry-content -->
    <footer class="entry-meta">
      <?php edit_post_link(); ?>
    </footer><!-- .entry-meta -->
</article>