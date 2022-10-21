<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package SKT Insurance
 */
?>
<div id="sidebar">    
    <?php if ( ! dynamic_sidebar( 'sidebar-1' ) ) : ?>
        <aside id="categories" class="widget"> 
	        <h3 class="widget-title titleborder"><span><?php esc_html_e( 'Categories', 'skt-insurance' ); ?></span></h3>
            <ul>
                <?php wp_list_categories('title_li=');  ?>
            </ul>
        </aside>
         <aside id="meta" class="widget">    
         	<h3 class="widget-title titleborder"><span><?php esc_html_e( 'Meta', 'skt-insurance' ); ?></span></h3>
            <ul>
                <?php wp_register(); ?>
                <li><?php wp_loginout(); ?></li>
                <?php wp_meta(); ?>
            </ul>
        </aside>
    <?php endif; // end sidebar widget area ?>	
</div><!-- sidebar -->