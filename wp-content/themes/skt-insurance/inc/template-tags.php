<?php
/**
 * Custom template tags for this theme.
 *
 * Eventually, some of the functionality here could be replaced by core features
 *
 * @package SKT Insurance
 */
if ( ! function_exists( 'skt_insurance_content_nav' ) ) :
/**
 * Display navigation to next/previous pages when applicable
 */
function skt_insurance_content_nav( $nav_id ) {
	global $wp_query, $post;
	// Don't print empty markup on single pages if there's nowhere to navigate.
	if ( is_single() ) {
		$previous = ( is_attachment() ) ? get_post( $post->post_parent ) : get_adjacent_post( false, '', true );
		$next = get_adjacent_post( false, '', false );
		if ( ! $next && ! $previous )
			return;
	}
	// Don't print empty markup in archives if there's only one page.
	if ( $wp_query->max_num_pages < 2 && ( is_home() || is_archive() || is_search() ) )
		return;
	$nav_class = ( is_single() ) ? 'post-navigation' : 'paging-navigation';
	?>
	<nav role="navigation" id="<?php echo esc_attr( $nav_id ); ?>" class="<?php echo esc_attr($nav_class); ?>">
		<h1 class="screen-reader-text"><?php esc_html_e( 'Post navigation', 'skt-insurance' ); ?></h1>
	<?php if ( is_single() ) : // navigation links for single posts 
	previous_post_link( '<div class="nav-previous">%link</div>', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link', 'skt-insurance' ) . '</span> %title' ); ?>
		<?php next_post_link( '<div class="nav-next">%link</div>', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link', 'skt-insurance' ) . '</span>' ); ?>
	<?php elseif ( $wp_query->max_num_pages > 1 && ( is_home() || is_archive() || is_search() ) ) : // navigation links for home, archive, and search pages ?>
		<?php if ( get_next_posts_link() ) : ?>
		<div class="nav-previous"><?php next_posts_link( esc_html( '<span class="meta-nav">&larr;</span> Older posts', 'skt-insurance' ) ); ?></div>
		<?php endif; 
		if ( get_previous_posts_link() ) : ?>
		<div class="nav-next"><?php previous_posts_link( esc_html( 'Newer posts <span class="meta-nav">&rarr;</span>', 'skt-insurance' ) ); ?></div>
		<?php endif;
		endif; ?>
		<div class="clear"></div>
	</nav><!-- #<?php echo esc_html( $nav_id ); ?> -->
	<?php
}
endif; // skt_insurance_content_nav
if ( ! function_exists( 'skt_insurance_comment' ) ) :
/**
 * Template for comments and pingbacks.
 *
 * Used as a callback by wp_list_comments() for displaying the comments.
 */
function skt_insurance_comment( $comment, $args, $depth ) {
	if ( 'pingback' == $comment->comment_type || 'trackback' == $comment->comment_type ) : ?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
		<div class="comment-body">
			<?php esc_html_e( 'Pingback:', 'skt-insurance' ); ?> <?php comment_author_link(); ?> <?php edit_comment_link( esc_html( 'Edit', 'skt-insurance' ), '<span class="edit-link">', '</span>' ); ?>
		</div>
	<?php else : ?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ); ?>>
		<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
			<footer class="comment-meta">
				<div class="comment-author vcard">
					<?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, 34 ); ?>
				</div><!-- .comment-author -->
				<div class="comment-metadata">
					<?php printf( '<cite class="fn">%s</cite> on', get_comment_author_link() );
					printf('<a class="comment-time" href="%1$s"><time datetime="%2$s">%3$s</time></a>',
                          esc_url(get_comment_link($comment->comment_ID)),
                          esc_attr(get_comment_time('c')),
                          esc_html(get_comment_date()));
                    edit_comment_link( esc_html( 'Edit', 'skt-insurance' ), '<span class="edit-link">', '</span>' ); ?>
				</div><!-- .comment-metadata -->
				<?php if ( '0' == $comment->comment_approved ) : ?>
				<p class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'skt-insurance' ); ?></p>
				<?php endif; ?>
			</footer><!-- .comment-meta -->
			<div class="comment-content">
				<?php comment_text(); ?>
			</div><!-- .comment-content -->
			<?php
				comment_reply_link( array_merge( $args, array(
					'add_below' => 'div-comment',
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'before'    => '<div class="reply">',
					'after'     => '</div>',
				) ) );
			?>
		</article><!-- .comment-body -->
	<?php     
	endif;
}
endif; // ends check for skt_insurance_comment()
if ( ! function_exists( 'skt_insurance_the_attached_image' ) ) :
/**
 * Prints the attached image with a link to the next attached image.
 */
function skt_insurance_the_attached_image() {
	$post                = get_post();
	$attachment_size     = apply_filters( 'skt_insurance_attachment_size', array( 1200, 1200 ) );
	$next_attachment_url = wp_get_attachment_url();
	/**
	 * Grab the IDs of all the image attachments in a gallery so we can get the
	 * URL of the next adjacent image in a gallery, or the first image (if
	 * we're looking at the last image in a gallery), or, in a gallery of one,
	 * just the link to that image file.
	 */
	$attachment_ids = get_posts( array(
		'post_parent'    => $post->post_parent,
		'fields'         => 'ids',
		'numberposts'    => PHP_INT_MAX,
		'post_status'    => 'inherit',
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'order'          => 'ASC',
		'orderby'        => 'menu_order ID'
	) );
	// If there is more than 1 attachment in a gallery...
	if ( count( $attachment_ids ) > 1 ) {
		foreach ( $attachment_ids as $attachment_id ) {
			if ( $attachment_id == $post->ID ) {
				$next_id = current( $attachment_ids );
				break;
			}
		}
		// get the URL of the next image attachment...
		if ( $next_id )
			$next_attachment_url = get_attachment_link( $next_id );
		// or get the URL of the first image attachment.
		else
			$next_attachment_url = get_attachment_link( array_shift( $attachment_ids ) );
	}
	wp_reset_postdata();
	printf( '<a href="%1$s" rel="attachment">%2$s</a>',
		esc_url( $next_attachment_url ),
		wp_get_attachment_image( $post->ID, $attachment_size )
	);
}
endif;
if ( ! function_exists( 'skt_insurance_posted_on' ) ) :
/**
 * Prints HTML with meta information for the current post-date/time and author.
 */
function skt_insurance_posted_on() {
	$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) )
		$time_string .= '<time class="updated" datetime="%3$s">%4$s</time>';
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		get_the_date(),
		esc_attr( get_the_modified_date( 'c' ) ),
		get_the_modified_date()
	);
	printf( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped)
                '<span class="posted-on">%1$s<a href="%2$s" rel="bookmark">%3$s</a></span>',
                wp_kses_post( _x( '<span class="screen-reader-text">Posted on</span>', 'Used before publish date.', 'skt-insurance' ) ),
                esc_url( get_permalink() ),
                esc_html($time_string)
            );
}
endif;
/**
 * Returns true if a blog has more than 1 category
 */
function skt_insurance_categorized_blog() {
	if ( false === ( $count_category = get_transient( 'skt_insurance_categories' ) ) ) {
		// Create an array of all the categories that are attached to posts
		$categories = get_categories( array(
			'hide_empty' => 1,
		) );
		// Count the number of categories that are attached to the posts
		$count_category = count( $categories );
		set_transient( 'skt_insurance_categories', $count_category );
	}
	if ( '1' != $count_category ) {
		// This blog has more than 1 category so skt_insurance_categorized_blog should return true
		return true;
	} else {
		// This blog has only 1 category so skt_insurance_categorized_blog should return false
		return false;
	}
}
/**
 * Flush out the transients used in skt_insurance_categorized_blog
 */
function skt_insurance_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'skt_insurance_categories' );
}
add_action( 'edit_category', 'skt_insurance_category_transient_flusher' );
add_action( 'save_post',     'skt_insurance_category_transient_flusher' );