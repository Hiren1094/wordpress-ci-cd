<?php 
/**
 * SKT Insurance functions and definitions
 *
 * @package SKT Insurance
 */

 
/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! function_exists( 'skt_insurance_setup' ) ) : 
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 */
function skt_insurance_setup() {
	$GLOBALS['content_width'] = apply_filters( 'skt_insurance_content_width', 640 );
	load_theme_textdomain( 'skt-insurance', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support('woocommerce');
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_post_type_support( 'page', 'excerpt' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_theme_support( 'custom-logo', array(
		'height'      => 50,
		'width'       => 175,
		'flex-height' => true,
	) );	
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Menu', 'skt-insurance' )				
	) );
	add_theme_support( 'custom-background', array(
		'default-color' => 'ffffff'
	) );
	add_editor_style( 'editor-style.css' );
} 
endif; // skt_insurance_setup
add_action( 'after_setup_theme', 'skt_insurance_setup' );
function skt_insurance_widgets_init() { 	
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'skt-insurance' ),
		'description'   => esc_html__( 'Appears on sidebar', 'skt-insurance' ),
		'id'            => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',		
		'before_title'  => '<h3 class="widget-title titleborder"><span>',
		'after_title'   => '</span></h3>',
		'after_widget'  => '</aside>',
	) ); 
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 1', 'skt-insurance' ),
		'description'   => esc_html__( 'Appears on page footer', 'skt-insurance' ),
		'id'            => 'fc-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',		
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
		'after_widget'  => '</aside>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 2', 'skt-insurance' ),
		'description'   => esc_html__( 'Appears on page footer', 'skt-insurance' ),
		'id'            => 'fc-2',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',		
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
		'after_widget'  => '</aside>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 3', 'skt-insurance' ),
		'description'   => esc_html__( 'Appears on page footer', 'skt-insurance' ),
		'id'            => 'fc-3',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',		
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
		'after_widget'  => '</aside>',
	) );
	register_sidebar( array(
		'name'          => esc_html__( 'Footer Column 4', 'skt-insurance' ),
		'description'   => esc_html__( 'Appears on page footer', 'skt-insurance' ),
		'id'            => 'fc-4',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',		
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
		'after_widget'  => '</aside>',
	) );	
}
add_action( 'widgets_init', 'skt_insurance_widgets_init' );


/**
* Enqueue theme fonts.
*/
function skt_insurance_fonts() {
	$fonts_url = skt_insurance_get_fonts_url();

	// Load Fonts if necessary.
	if ( $fonts_url ) {
		require_once get_theme_file_path( 'inc/wptt-webfont-loader.php' );
		wp_enqueue_style( 'skt-insurance-fonts', wptt_get_webfont_url( $fonts_url ), array(), '20201110' );
	}
}
add_action( 'wp_enqueue_scripts', 'skt_insurance_fonts', 1 );
add_action( 'enqueue_block_editor_assets', 'skt_insurance_fonts', 1 );

/**
 * Retrieve webfont URL to load fonts locally.
 */
function skt_insurance_get_fonts_url() {
	$font_families = array(
		'Poppins:100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic',
		'Playfair Display:400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic'
	);

	$query_args = array(
		'family'  => urlencode( implode( '|', $font_families ) ),
		'subset'  => urlencode( 'latin,latin-ext' ),
		'display' => urlencode( 'swap' ),
	);

	return apply_filters( 'skt_insurance_get_fonts_url', add_query_arg( $query_args, 'https://fonts.googleapis.com/css' ) );
}
	
	
function skt_insurance_scripts() {
	if ( !is_rtl() ) {
		wp_enqueue_style( 'skt-insurance-basic-style', get_stylesheet_uri() );
		wp_enqueue_style( 'skt-insurance-main-style', get_template_directory_uri()."/css/responsive.css" );		
	}
	if ( is_rtl() ) {
		wp_enqueue_style( 'skt-insurance-rtl', get_template_directory_uri() . "/rtl.css");
	}	
	wp_enqueue_style( 'skt-insurance-editor-style', get_template_directory_uri()."/editor-style.css" );
	wp_enqueue_style( 'skt-insurance-base-style', get_template_directory_uri()."/css/style_base.css" );
	wp_enqueue_script( 'skt-insurance-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '01062020', true );
	wp_enqueue_script( 'skt-insurance-customscripts', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery') );
	wp_localize_script( 'skt-insurance-navigation', 'sktinsuranceScreenReaderText', array(
		'expandMain'   => __( 'Open main menu', 'skt-insurance' ),
		'collapseMain' => __( 'Close main menu', 'skt-insurance' ),
		'expandChild'   => __( 'Expand submenu', 'skt-insurance' ),
		'collapseChild' => __( 'Collapse submenu', 'skt-insurance' ),
	) );	
	
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'skt_insurance_scripts' );

function skt_insurance_admin_style() {
  wp_enqueue_style('skt-insurance-admin-style', get_template_directory_uri()."/css/skt-insurance-admin-style.css");
}
add_action('admin_enqueue_scripts', 'skt_insurance_admin_style');

define('SKT_INSURANCE_SKTTHEMES_URL','https://www.sktthemes.org');
define('SKT_INSURANCE_SKTTHEMES_PRO_THEME_URL','https://www.sktthemes.org/shop/insurance-agency-wordpress-theme/');
define('SKT_INSURANCE_SKTTHEMES_FREE_THEME_URL','https://www.sktthemes.org/shop/free-insurance-company-wordpress-theme');
define('SKT_INSURANCE_SKTTHEMES_THEME_DOC','https://www.sktthemesdemo.net/documentation/skt-insurance-doc/');
define('SKT_INSURANCE_SKTTHEMES_LIVE_DEMO','https://www.sktperfectdemo.com/themepack/gbinsurance/');
define('SKT_INSURANCE_SKTTHEMES_THEMES','https://www.sktthemes.org/themes');
define('SKT_INSURANCE_SKTTHEMES_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );

/**
 * Custom template for about theme.
 */
require get_template_directory() . '/inc/about-themes.php';
/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';
/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';
/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

// get slug by id
function skt_insurance_get_slug_by_id($id) {
	$post_data = get_post($id, ARRAY_A);
	$slug = $post_data['post_name'];
	return $slug; 
}
if ( ! function_exists( 'skt_insurance_the_custom_logo' ) ) :
/**
 * Displays the optional custom logo.
 *
 * Does nothing if the custom logo is not available.
 *
 */
function skt_insurance_the_custom_logo() {
	if ( function_exists( 'the_custom_logo' ) ) {
		the_custom_logo();
	}
}
endif;
require_once get_template_directory() . '/customize-pro/example-1/class-customize.php';

add_filter( 'body_class','skt_insurance_body_class' );
function skt_insurance_body_class( $classes ) {
	if ( skt_insurance_is_woocommerce_activated() ) {
		$classes[] = 'woocommerce';
	}
	
    return $classes;
}

/**
 * Filter the except length to 21 words.
 *
 * @param int $length Excerpt length.
 * @return int (Maybe) modified excerpt length.
 */
function skt_insurance_custom_excerpt_length( $length ) {
    if ( is_admin() ) return $length;
    return 25;
}
add_filter( 'excerpt_length', 'skt_insurance_custom_excerpt_length', 999 );
 
/**
 *
 * Style For About Theme Page
 *
 */
function skt_insurance_admin_about_page_css_enqueue($hook) {
   if ( 'appearance_page_skt_insurance_guide' != $hook ) {
        return;
    }
    wp_enqueue_style( 'skt-insurance-about-page-style', get_template_directory_uri() . '/css/skt-insurance-about-page-style.css' );
}
add_action( 'admin_enqueue_scripts', 'skt_insurance_admin_about_page_css_enqueue' );

/**
 * Check if WooCommerce is activated
 */
if ( ! function_exists( 'skt_insurance_is_woocommerce_activated' ) ) {
	function skt_insurance_is_woocommerce_activated() {
		if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
	}
}

/**
 * Show notice on theme activation
 */
if ( is_admin() && isset($_GET['activated'] ) && $pagenow == "themes.php" ) {
	add_action( 'admin_notices', 'skt_insurance_activation_notice' );
}
function skt_insurance_activation_notice(){
    ?>
    <div class="notice notice-info is-dismissible"> 
        <div class="skt-insurance-notice-container">
        	<div class="skt-insurance-notice-img"><img src="<?php echo esc_url( SKT_INSURANCE_SKTTHEMES_THEME_URI . 'images/icon-skt-templates.png' ); ?>" alt="<?php echo esc_attr('SKT Templates');?>" ></div>
            <div class="skt-insurance-notice-content">
            <div class="skt-insurance-notice-heading"><?php echo esc_html__('Thank you for installing SKT Insurance!', 'skt-insurance'); ?></div>
            <p class="largefont"><?php echo esc_html__('SKT Insurance comes with 150+ ready to use Elementor templates. Install the SKT Templates plugin to get started.', 'skt-insurance'); ?></p>
            </div>
            <div class="skt-insurance-clear"></div>
        </div>
    </div>
    <?php
}

function skt_insurance_wp_admin_style($hook) {
	 	if ( 'themes.php' != $hook ) {
			return;
		}
		wp_enqueue_style( 'skt-insurance-admin-style', get_template_directory_uri() . '/css/skt-insurance-admin-style.css' );
}
add_action( 'admin_enqueue_scripts', 'skt_insurance_wp_admin_style' );

// WordPress wp_body_open backward compatibility
if ( ! function_exists( 'wp_body_open' ) ) {
    function wp_body_open() {
        do_action( 'wp_body_open' );
    }
}

/**
 * Fix skip link focus in IE11.
 *
 * This does not enqueue the script because it is tiny and because it is only for IE11,
 * thus it does not warrant having an entire dedicated blocking script being loaded.
 *
 * @link https://git.io/vWdr2
 */
function skt_insurance_skip_link_focus_fix() {  
	// The following is minified via `terser --compress --mangle -- js/skip-link-focus-fix.js`.
	?>
	<script>
	/(trident|msie)/i.test(navigator.userAgent)&&document.getElementById&&window.addEventListener&&window.addEventListener("hashchange",function(){var t,e=location.hash.substring(1);/^[A-z0-9_-]+$/.test(e)&&(t=document.getElementById(e))&&(/^(?:a|select|input|button|textarea)$/i.test(t.tagName)||(t.tabIndex=-1),t.focus())},!1);
	</script>
	<?php       
}
add_action( 'wp_print_footer_scripts', 'skt_insurance_skip_link_focus_fix' );

function skt_insurance_load_dashicons(){
   wp_enqueue_style('dashicons');
}
add_action('wp_enqueue_scripts', 'skt_insurance_load_dashicons', 999);

/**
 * Include the Plugin_Activation class.
 */

require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';
add_action( 'tgmpa_register', 'skt_insurance_register_required_plugins' );
 
function skt_insurance_register_required_plugins() {
	$plugins = array(
		array(
			'name'      => 'SKT Templates',
			'slug'      => 'skt-templates',
			'required'  => false,
		)
	);

	$config = array(
		'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'skt-install-plugins',   // Menu slug.
		'parent_slug'  => 'themes.php',            // Parent menu slug.
		'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
	);

	tgmpa( $plugins, $config );
}