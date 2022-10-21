<?php
//about theme info
add_action( 'admin_menu', 'skt_insurance_abouttheme' );
function skt_insurance_abouttheme() {    	
	add_theme_page( esc_html__('About Theme', 'skt-insurance'), esc_html__('About Theme', 'skt-insurance'), 'edit_theme_options', 'skt_insurance_guide', 'skt_insurance_mostrar_guide');   
} 
//guidline for about theme
function skt_insurance_mostrar_guide() { 
	//custom function about theme customizer
	$return = add_query_arg( array()) ;
?>
<div class="wrapper-info">
	<div class="col-left">
   		   <div class="col-left-area">
			  <?php esc_html_e('Theme Information', 'skt-insurance'); ?>
		   </div>
          <p><?php esc_html_e('SKT Insurance WordPress theme is responsive extendable flexible scalable and customizable theme which can be applied to industries like financial consultation, consulting, agency, agent, sales, mutual fund, brokers, finance recruitment, stock market investments, portfolio management, accounting, balance sheet, assurance, risk cover, indemnity bonds, precaution against mitigated risks, accident cover, medical and health ailments safeguard, guarantee and warranty of your goods and services. It is SEO friendly, WooCommerce friendly and works nicely with block editor of WordPress.','skt-insurance'); ?></p>
          <a href="<?php echo esc_url(SKT_INSURANCE_SKTTHEMES_PRO_THEME_URL); ?>"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/free-vs-pro.png" alt="" /></a>
	</div><!-- .col-left -->
	<div class="col-right">			
			<div class="centerbold">
				<hr />
				<a href="<?php echo esc_url(SKT_INSURANCE_SKTTHEMES_LIVE_DEMO); ?>" target="_blank"><?php esc_html_e('Live Demo', 'skt-insurance'); ?></a> | 
				<a href="<?php echo esc_url(SKT_INSURANCE_SKTTHEMES_PRO_THEME_URL); ?>"><?php esc_html_e('Buy Pro', 'skt-insurance'); ?></a> | 
				<a href="<?php echo esc_url(SKT_INSURANCE_SKTTHEMES_THEME_DOC); ?>" target="_blank"><?php esc_html_e('Documentation', 'skt-insurance'); ?></a>
                <div class="space5"></div>
				<hr />                
                <a href="<?php echo esc_url(SKT_INSURANCE_SKTTHEMES_THEMES); ?>" target="_blank"><img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/sktskill.jpg" alt="" /></a>
			</div>		
	</div><!-- .col-right -->
</div><!-- .wrapper-info -->
<?php } ?>