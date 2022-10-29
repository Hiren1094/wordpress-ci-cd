<?php
/**
 * This class handles for Bulk Installs.
 *
 * @package wpcd Test
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCD_BULK_INSTALLS
 */
class WPCD_BULK_INSTALLS extends WPCD_APP {

	/**
	 * Return instance of self.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WPCD_BULK_INSTALLS constructor.
	 */
	public function __construct() {
		$this->hooks(); // register hooks to make things happen.
	}

	/**
	 * Return a requested provider object
	 *
	 * @param string $provider name of provider.
	 *
	 * @return VPN_API_Provider_{provider}()
	 */
	public function api( $provider ) {

		return WPCD()->get_provider_api( $provider );

	}

	/**
	 * Add all the hook inside the this private/public method.
	 */
	public function hooks() {

		// Add main menu page.
		add_action( 'admin_menu', array( &$this, 'add_main_menu_page' ) );

		// Include Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'wpcd_bulk_installs_enqueue_scripts' ) );

		// Bulk Installs Server Action.
		add_action( 'wp_ajax_wpcd_bulk_installs_server_action', array( $this, 'wpcd_bulk_installs_server_action' ) );
		add_action( 'wp_ajax_nopriv_wpcd_bulk_installs_server_action', array( $this, 'wpcd_bulk_installs_server_action' ) );

		// Bulk Installs WordPress Action.
		add_action( 'wp_ajax_wpcd_bulk_installs_wp_site_action', array( $this, 'wpcd_bulk_installs_wp_site_action' ) );
		add_action( 'wp_ajax_nopriv_wpcd_bulk_installs_wp_site_action', array( $this, 'wpcd_bulk_installs_wp_site_action' ) );

		/* Trigger installation of a new WP site */
		add_action( 'wpcd_wordpress-app_bulk_installs_wp', array( $this, 'wpcd_bulk_installs_install_wp' ), 10, 3 );

		/* When WP install is complete, send email and possibly auto-issue ssl. */
		add_action( 'wpcd_command_wordpress-app_completed_after_cleanup', array( $this, 'wpcd_bulk_installs_wpapp_install_complete' ), 10, 4 );

		/* Add to the list of fields that will be automatically stamped on the WordPress App Post for a new site */
		add_filter( 'wpcd_wordpress-app_add_wp_app_post_fields', array( &$this, 'add_wp_app_post_fields' ), 10, 1 );

		/* Update pending task entry after server is completed. */
		add_action( 'wpcd_command_wordpress-app_prepare_server_completed', array( $this, 'wpcd_wpapp_prepare_server_completed' ), 10, 2 );

	}

	/**
	 * Main Menu Item: Make the wpcd_app_server post type the main menu item
	 * by adding another option below it
	 */
	public function add_main_menu_page() {

		// Create new screen for bulk installs.
		add_submenu_page(
			'edit.php?post_type=wpcd_app_server',
			__( 'Bulk Deploys', 'wpcd' ),
			__( 'Bulk Deploys', 'wpcd' ),
			'manage_options',
			'wpcd_bulk_installs',
			array( $this, 'wpcd_bulk_installs_server_callback' ),
			17
		);

	}

	/**
	 * Enqueue Scripts
	 */
	public function wpcd_bulk_installs_enqueue_scripts() {

		WPCD_WORDPRESS_APP()->add_provider_support();

		$screen = get_current_screen();

		if ( 'wpcd_app_server_page_wpcd_bulk_installs' !== $screen->id ) {

			return;
		}

		wp_enqueue_style( 'wpcd-bulk-install-admin-css', WPCDPT_URL . 'assets/css/wpcd-bulk-install-admin.css', array(), WPCD_SCRIPTS_VERSION );
		wp_enqueue_script( 'wpcd-bulk-installs-admin', WPCDPT_URL . 'assets/js/wpcd-bulk-installs-admin.js', array( 'jquery' ), WPCD_SCRIPTS_VERSION, true );
		wp_localize_script(
			'wpcd-bulk-installs-admin',
			'wpcd_bulk_installs_params',
			array(
				'wpcd_remove_btn'                  => __( 'Remove', 'wpcd' ),
				'bulk_installs_server_action'      => 'wpcd_bulk_installs_server_action',
				'bulk_installs_wp_action'          => 'wpcd_bulk_installs_wp_site_action',
				'nonce'                            => wp_create_nonce( 'wpcd-bulk-installs' ),
				'ajaxurl'                          => admin_url( 'admin-ajax.php' ),
				'loading'                          => __( 'Loading..', 'wpcd' ),
				'chars_validation_msg'             => __( 'The following characters are invalid in "name of server" fields:  \' " ; \ | < > ` @ $ & ( ) / and spaces, carriage returns, linefeeds.', 'wpcd' ),
				'domain_validation'                => __( 'Please enter a valid domain name or IP Address.', 'wpcd' ),
				'all_fields_characters_validation' => __( 'The following characters are invalid in all field:  \' " ; \ | < > ` @ $ ( ) / and spaces, carriage returns, linefeeds.', 'wpcd' ),
				'password_validation'              => __( 'The following characters are invalid in the password field:  \' " ; \ | < > & ( ) ` (single-quote, double-quote, semi-colon, backslash, pipe, angled-brackets, backtics, spaces, carriage returns, linefeeds.)', 'wpcd' ),
				'wp_email_validation'              => __( 'Please enter a valid email address in "Admin Email Address".', 'wpcd' ),
				'email_characters'                 => __( 'The following characters are invalid in email field:  \' " ; \ | < > & ( ) ` and spaces, carriage returns, linefeeds.', 'wpcd' ),
				'all_fields_validation'            => __( 'All fields should not to empty (except "owner email & notfication checkbox") for WordPress Site.', 'wpcd' ),
			)
		);
	}

	/**
	 * Call back function of bulk installs for WordPress site
	 */
	public function wpcd_bulk_installs_server_callback() {

		if ( ! wpcd_is_admin() ) {

			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'wpcd' ) ) );
		}

		// Server Fields.
		$wpcd_bulk_installs_server_fields  = $this->wpcd_bulk_installs_server_fields();
		$wpcd_bulk_installs_server_heading = __( 'Deploy New WordPress Servers', 'wpcd' );
		$this->wpcd_create_bulk_installs_fields( $wpcd_bulk_installs_server_fields, 'deploy-server', $wpcd_bulk_installs_server_heading );

		// WordPress Site Fields.
		$active_server = $this->wpcd_bulk_installs_get_active_server();
		if ( ! empty( $active_server ) ) {

			$wpcd_bulk_installs_wp_fields  = $this->wpcd_bulk_installs_wp_fields();
			$wpcd_bulk_installs_wp_heading = __( 'Deploy New WordPress Sites', 'wpcd' );
			$this->wpcd_create_bulk_installs_fields( $wpcd_bulk_installs_wp_fields, 'deploy-wp-site', $wpcd_bulk_installs_wp_heading );
		}
	}

	/**
	 * Call back function of bulk installs fields for server
	 */
	public function wpcd_bulk_installs_server_fields() {

		$webserver_list   = WPCD()->get_webserver_list();
		$oslist           = WPCD()->get_os_list();
		$provider_regions = $this->add_provider_support();
		$dir_path         = wpcd_path . 'includes/core/apps/wordpress-app/scripts';
		$dir_list         = wpcd_get_dir_list( $dir_path );
		$current_user     = wp_get_current_user();

		$fields = array(
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_web_server',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_web_server[]',
				'label' => __( 'Web Server', 'wpcd' ),
				'value' => $webserver_list,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_os',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_os[]',
				'label' => __( 'OS', 'wpcd' ),
				'value' => $oslist,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_provider',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_provider[]',
				'label' => __( 'Provider', 'wpcd' ),
				'value' => $provider_regions['providers'],
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_region',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_region[]',
				'label' => __( 'Region', 'wpcd' ),
				'value' => $provider_regions['regions'],
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_size',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_size[]',
				'label' => __( 'Size', 'wpcd' ),
				'value' => $provider_regions['sizes'],
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_script_version',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_script_version[]',
				'label' => __( 'Script Version', 'wpcd' ),
				'value' => $dir_list,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_name_of_server',
				'type'  => 'text',
				'name'  => 'wpcd_bulk_installs_name_of_server[]',
				'label' => __( 'Name Of Server', 'wpcd' ),
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_owner_email',
				'type'  => 'text',
				'name'  => 'wpcd_bulk_installs_owner_email[]',
				'label' => __( 'Owner Email', 'wpcd' ),
				'value' => $current_user->data->user_email,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields wpcd_blk_inst_email_chk',
				'id'    => 'wpcd_bulk_installs_email_notification',
				'type'  => 'checkbox',
				'name'  => 'wpcd_bulk_installs_email_notification[]',
				'label' => __( 'Send email to admin when the server is ready', 'wpcd' ),
			),
		);

		$button = array(
			'id'    => 'wpcd_bulk_installs_server_button',
			'type'  => 'button',
			'name'  => 'wpcd_bulk_installs_server',
			'label' => '',
			'std'   => __( 'Deploy Servers', 'wpcd' ),
		);

		return array(
			'thefields' => $fields,
			'button'    => $button,
		);
	}

	/**
	 * Call back function of bulk installs fields for WordPress site
	 */
	public function wpcd_bulk_installs_wp_fields() {

		$active_server   = $this->wpcd_bulk_installs_get_active_server();
		$version_options = WPCD_WORDPRESS_APP()->get_wp_versions();
		$current_user    = wp_get_current_user();
		$lang            = wp_dropdown_languages(
			array(
				'name'     => 'wpcd_bulk_installs_wp_lang[]',
				'echo'     => false,
				'selected' => 'en_US',
				'id'       => 'wpcd_bulk_installs_wp_lang',
			)
		);
		$fields          = array(
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_domain',
				'type'  => 'text',
				'name'  => 'wpcd_bulk_installs_wp_domain[]',
				'label' => __( 'Domain', 'wpcd' ),
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_lang',
				'type'  => 'custom_html',
				'html'  => $lang,
				'label' => __( 'Language', 'wpcd' ),
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_user',
				'type'  => 'text',
				'name'  => 'wpcd_bulk_installs_wp_user[]',
				'label' => __( 'Admin Username', 'wpcd' ),
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_pass',
				'type'  => 'password',
				'name'  => 'wpcd_bulk_installs_wp_pass[]',
				'label' => __( 'Admin Password', 'wpcd' ),
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_email',
				'type'  => 'text',
				'name'  => 'wpcd_bulk_installs_wp_email[]',
				'label' => __( 'Admin Email Address', 'wpcd' ),
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_version',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_wp_version[]',
				'label' => __( 'WordPress Version', 'wpcd' ),
				'value' => $version_options,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_server',
				'type'  => 'dropdown',
				'name'  => 'wpcd_bulk_installs_wp_server[]',
				'label' => __( 'Server', 'wpcd' ),
				'value' => $active_server,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields',
				'id'    => 'wpcd_bulk_installs_wp_owner_email',
				'type'  => 'text',
				'name'  => 'wpcd_bulk_installs_wp_owner_email[]',
				'label' => __( 'Owner Email', 'wpcd' ),
				'value' => $current_user->data->user_email,
			),
			array(
				'class' => 'wpcd_bulk_installs_form_fields wpcd_blk_inst_email_chk',
				'id'    => 'wpcd_bulk_installs_wp_email_notification',
				'type'  => 'checkbox',
				'name'  => 'wpcd_bulk_installs_wp_email_notification[]',
				'label' => __( 'Send email to admin when site is ready', 'wpcd' ),
			),
		);

		$button = array(
			'id'    => 'wpcd_bulk_installs_wp_site_button',
			'type'  => 'button',
			'name'  => 'wpcd_bulk_installs_wp_site',
			'label' => '',
			'std'   => __( 'Install Sites', 'wpcd' ),
		);

		return array(
			'thefields' => $fields,
			'button'    => $button,
		);
	}

	/**
	 * Create bulk installs fields
	 *
	 * @param array  $fields - fileds array.
	 * @param string $screen - Return screen type.
	 * @param string $heading - Return heading.
	 */
	public function wpcd_create_bulk_installs_fields( $fields, $screen, $heading ) {

		$wpcd_bulk_installs = '<div class="wpcd-bulk-installs-fields-wrap">';

		if ( ! empty( $fields['thefields'] ) ) {

			$wpcd_bulk_installs .= '<div class="wpcd-bulk-installs-action-title-wrap">';
			$wpcd_bulk_installs .= '<div class="wpcd-bulk-installs-action-title">';
			$wpcd_bulk_installs .= '<h1>' . $heading . '</h1>';
			$wpcd_bulk_installs .= '</div>';
			$wpcd_bulk_installs .= '</div>';

			$wpcd_bulk_installs .= '<form id="wpcd-bulk-installs-' . $screen . '" class="wpcd-blk-inst-frm">';
			$wpcd_bulk_installs .= '<div class="wpcd-bulk-installs-form-fields">';

			foreach ( $fields['thefields'] as $generate_fileds ) {

				$bulk_installs_id    = isset( $generate_fileds['id'] ) ? $generate_fileds['id'] : '';
				$bulk_installs_class = isset( $generate_fileds['class'] ) ? $generate_fileds['class'] : '';
				$bulk_installs_type  = isset( $generate_fileds['type'] ) ? $generate_fileds['type'] : '';
				$bulk_installs_name  = isset( $generate_fileds['name'] ) ? $generate_fileds['name'] : '';
				$bulk_installs_label = isset( $generate_fileds['label'] ) ? $generate_fileds['label'] : '';
				$bulk_installs_value = isset( $generate_fileds['value'] ) ? $generate_fileds['value'] : '';
				$bulk_installs_html  = isset( $generate_fileds['html'] ) ? $generate_fileds['html'] : '';

				if ( 'wpcd_bulk_installs_email_notification' == $bulk_installs_id || 'wpcd_bulk_installs_wp_email_notification' == $bulk_installs_id ) {

					$wpcd_extra_div_cls      = ' wpcd-blk-instl-email-ntf-div';
					$wpcd_extra_lbl_wrap_cls = ' wpcd-blk-instl-email-ntf-lbl';

				} else {

					$wpcd_extra_div_cls      = '';
					$wpcd_extra_lbl_wrap_cls = '';
				}

				$wpcd_bulk_installs .= '<div class="wpcd-bulk-installs-div-fields' . $wpcd_extra_div_cls . '">';
				if ( ! empty( $bulk_installs_label ) ) {

					$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-label-wrap' . $wpcd_extra_lbl_wrap_cls . '">';
					$wpcd_bulk_installs .= '<label class="wpcd-create-bulk-installs-label" for="' . sanitize_title( $bulk_installs_label ) . '">' . $bulk_installs_label . '</label>';
					$wpcd_bulk_installs .= '</div>';
				}

				switch ( $bulk_installs_type ) {

					case 'dropdown':
						if ( ! empty( $bulk_installs_value ) ) {
							$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-input-wrap">';
							$wpcd_bulk_installs .= '<select data-wpcd-name="' . $bulk_installs_id . '" name="' . $bulk_installs_name . '" class="' . $bulk_installs_class . '">';
							foreach ( $bulk_installs_value as $installation_key => $installation_val ) {

								if ( 'wpcd_bulk_installs_script_version' === $bulk_installs_id || 'wpcd_bulk_installs_wp_version' === $bulk_installs_id ) {

									$bulk_installs_val = $installation_val;

								} else {

									$bulk_installs_val = $installation_key;
								}

								$wpcd_bulk_installs .= '<option value="' . $bulk_installs_val . '">' . esc_html( $installation_val ) . '</option>';

							}
							$wpcd_bulk_installs .= '</select>';
							$wpcd_bulk_installs .= '</div>';
						}
						break;
					case 'text':
						if ( ! empty( $bulk_installs_value ) ) {
							$get_text_val = $bulk_installs_value;
						} else {
							$get_text_val = '';
						}
						$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-input-wrap">';
						$wpcd_bulk_installs .= '<input value="' . $get_text_val . '" data-wpcd-name="' . $bulk_installs_id . '" type="text" name="' . $bulk_installs_name . '" class="' . $bulk_installs_class . '" />';
						$wpcd_bulk_installs .= '</div>';
						break;
					case 'password':
						$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-input-wrap">';
						$wpcd_bulk_installs .= '<input data-wpcd-name="' . $bulk_installs_id . '" type="password" name="' . $bulk_installs_name . '" class="' . $bulk_installs_class . '" />';
						$wpcd_bulk_installs .= '</div>';
						break;
					case 'checkbox':
						$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-input-wrap wpcd-bulk-installs-notification">';
						$wpcd_bulk_installs .= '<input data-wpcd-name="' . $bulk_installs_id . '" type="checkbox" value="yes" checked name="' . $bulk_installs_name . '" class="' . $bulk_installs_class . '" />';
						$wpcd_bulk_installs .= '</div>';
						break;
					case 'custom_html':
						$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-input-wrap">';
						$wpcd_bulk_installs .= $bulk_installs_html;
						$wpcd_bulk_installs .= '</div>';
						break;

				}
				$wpcd_bulk_installs .= '</div>';
			}

			$wpcd_bulk_installs .= '</div>';
			$wpcd_bulk_installs .= '<div class="wpcd-bulk-install-appends-' . $screen . ' wpcd_bulk_install_appends"></div>';
			$wpcd_bulk_installs .= '<div>';
			$wpcd_bulk_installs .= '<button data-append="wpcd-bulk-install-appends-' . $screen . '" type="button" class="button wpcd_bulk_installs_add_more">' . __( 'Add More', 'wpcd' ) . '</button>';
			$wpcd_bulk_installs .= '<button data-append="wpcd-bulk-install-appends-' . $screen . '" type="button" class="button wpcd_bulk_installs_duplicate">' . __( 'Duplicate', 'wpcd' ) . '</button>';
			$wpcd_bulk_installs .= '</div>';

			if ( ! empty( $fields['button'] ) ) {

				$bulk_installs_btn_id   = isset( $fields['button']['id'] ) ? $fields['button']['id'] : '';
				$bulk_installs_btn_name = isset( $fields['button']['name'] ) ? $fields['button']['name'] : '';
				$bulk_installs_btn_std  = isset( $fields['button']['std'] ) ? $fields['button']['std'] : '';

				$wpcd_bulk_installs .= '<div class="wpcd-create-bulk-installs-input-wrap wpcd-create-bulk-installs-btn wpcd-blk-instl-btn">';
				$wpcd_bulk_installs .= '<button type="submit" id="' . $bulk_installs_btn_id . '" class="button wpcd_blk_inst_btn" name="' . $bulk_installs_btn_name . '">' . $bulk_installs_btn_std . '</button>';
				$wpcd_bulk_installs .= '</div>';
			}

			$wpcd_bulk_installs .= '</form>';
		}

		$wpcd_bulk_installs .= '</div>';

		echo $wpcd_bulk_installs; // phpcs:ignore

	}

	/**
	 * Ajax action for bulk install server.
	 */
	public function wpcd_bulk_installs_server_action() {

		check_ajax_referer( 'wpcd-bulk-installs', 'security' );

		$wpcd_bulk_installs_params = ! empty( $_POST['params'] ) ? array_map( 'wp_unslash', wp_parse_args( $_POST['params'] ) ) : ''; // phpcs:ignore

		$wpcd_bulk_installs_error = '';
		$wpcd_bulk_installs_sucs  = '';

		if ( ! empty( $wpcd_bulk_installs_params ) ) {

			// validate and organize parameters.
			$check_validation = $this->validate_required_parameters_for_server( $wpcd_bulk_installs_params );

			if ( ! empty( $check_validation ) ) {

				$wpcd_bulk_installs_error = $check_validation;

			} else {

				// Loop through each server and attempt to deploy them.
				foreach ( $wpcd_bulk_installs_params['wpcd_bulk_installs_web_server'] as $web_server_key => $get_bulk_installs_web_server ) {

					// Get all fields values.
					$wpcd_bulk_installs_os                 = $wpcd_bulk_installs_params['wpcd_bulk_installs_os'][ $web_server_key ];
					$wpcd_bulk_installs_provider           = $wpcd_bulk_installs_params['wpcd_bulk_installs_provider'][ $web_server_key ];
					$wpcd_bulk_installs_region             = $wpcd_bulk_installs_params['wpcd_bulk_installs_region'][ $web_server_key ];
					$wpcd_bulk_installs_size               = $wpcd_bulk_installs_params['wpcd_bulk_installs_size'][ $web_server_key ];
					$wpcd_bulk_installs_script_version     = $wpcd_bulk_installs_params['wpcd_bulk_installs_script_version'][ $web_server_key ];
					$wpcd_bulk_installs_name_of_server     = $wpcd_bulk_installs_params['wpcd_bulk_installs_name_of_server'][ $web_server_key ];
					$wpcd_bulk_installs_owner_email        = $wpcd_bulk_installs_params['wpcd_bulk_installs_owner_email'][ $web_server_key ];
					$wpcd_bulk_installs_email_notification = $wpcd_bulk_installs_params['wpcd_bulk_installs_email_notification'][ $web_server_key ];
					// handle optional owner email.
					$author_email = filter_var( $wpcd_bulk_installs_owner_email, FILTER_SANITIZE_EMAIL );
					if ( ! empty( $author_email ) ) {

						$get_user_by_email = get_user_by( 'email', $author_email );

						if ( ! empty( $get_user_by_email ) ) {

							$author_email = $get_user_by_email->user_email;

						} else {

							$get_current_login_id = get_current_user_id();
							$get_user_by_id       = get_user_by( 'id', $get_current_login_id );
							$author_email         = $get_user_by_id->user_email;
						}
					} else {

						$get_current_login_id = get_current_user_id();
						$get_user_by_id       = get_user_by( 'id', $get_current_login_id );
						$author_email         = $get_user_by_id->user_email;
					}
					// Admin notification.
					if ( 'yes' != $wpcd_bulk_installs_email_notification ) {
						$wpcd_bulk_installs_email_notification = 'no';
					}
					// handle optional server type.
					$webserver_type        = sanitize_text_field( $get_bulk_installs_web_server );
					$valid_webserver_types = WPCD()->get_webserver_list();

					if ( ! array_key_exists( $webserver_type, $valid_webserver_types ) ) {
						$webserver_type = WPCD_WORDPRESS_APP()->get_default_webserver();
					}

					// setup server attributes array needed to create the server.
					$attributes = array(
						'initial_os'                    => $wpcd_bulk_installs_os,
						'initial_app_name'              => WPCD_WORDPRESS_APP()->get_app_name(),
						'server-type'                   => 'wordpress-app',
						'scripts_version'               => sanitize_text_field( $wpcd_bulk_installs_script_version ),
						'region'                        => sanitize_text_field( $wpcd_bulk_installs_region ),
						'size_raw'                      => sanitize_text_field( $wpcd_bulk_installs_size ),
						'name'                          => sanitize_text_field( $wpcd_bulk_installs_name_of_server ),
						'provider'                      => sanitize_text_field( $wpcd_bulk_installs_provider ),
						'author_email'                  => ! empty( $author_email ) ? $author_email : '',
						'init'                          => true,
						'webserver_type'                => $webserver_type,
						'wp_bulk_installs_server'       => 'yes',
						'wp_bulk_installs_admin_notify' => $wpcd_bulk_installs_email_notification,
					);

					/* Create server */
					$instance = WPCD_SERVER()->create_server( 'create', $attributes );

					/* Check for errors */
					if ( empty( $instance ) || is_wp_error( $instance ) ) {
						do_action( 'wpcd_log_error', 'Unable to create new server', 'error', __FILE__, __LINE__, $instance );
						continue; // move to the next server since there's an error on this one. @TODO: Attempt to delete the server record or mark it as bad in some way.
					}

					/* Make sure the other fields in the server post type entry gets created/updated. */
					$instance = WPCD_WORDPRESS_APP()->add_app( $instance );

					/* Check for errors */
					if ( empty( $instance ) || is_wp_error( $instance ) ) {
						do_action( 'wpcd_log_error', 'Unable to create new server', 'error', __FILE__, __LINE__, $instance );
						continue; // move to the next server since there's an error on this one. @TODO: Attempt to delete the app record or mark it as bad in some way.
					}

					/**
					 * Create pending log entry - we'll update this entry in an action hook after the server is complete.
					 * Note that unlike other pending log entries, this one will go directly to 'in-process' because
					 * the create_server function above is already creating the server.
					 * We're providing a pending logs entry for consistency with creating sites.
					*/
					$server_id = ! empty( $instance['post_id'] ) ? $instance['post_id'] : '';

					if ( ! empty( $server_id ) ) {

						$task_id = WPCD_POSTS_PENDING_TASKS_LOG()->add_pending_task_log_entry( $server_id, 'bulk_installs_server', $server_id, $instance, 'not-ready', $server_id, __( 'Bulk Installs: Server is being created', 'wpcd' ) );

						/* Check for errors */
						if ( empty( $task_id ) ) {
							do_action( 'wpcd_log_error', 'Unable to create new server - task id or server is is still blank.', 'error', __FILE__, __LINE__, $task_id );
						} else {
							// Mark the new task record as in-process since we've already started to create the server.
							WPCD_POSTS_PENDING_TASKS_LOG()->update_task_by_id( $task_id, array(), 'in-process' );
						}
					}

					$wpcd_bulk_installs_sucs = __( 'Process successfully completed. Please check status in pending task log.', 'wpcd' );
				}
			}
		}

		wp_send_json(
			array(
				'sucs'  => $wpcd_bulk_installs_sucs,
				'error' => $wpcd_bulk_installs_error,
			)
		);
		die();
	}

	/**
	 * Ajax action for bulk install WordPress Site.
	 */
	public function wpcd_bulk_installs_wp_site_action() {

		check_ajax_referer( 'wpcd-bulk-installs', 'security' );

		$wpcd_bulk_installs_params = ! empty( $_POST['params'] ) ? array_map( 'wp_unslash', wp_parse_args( $_POST['params'] ) ) : ''; // phpcs:ignore

		$wpcd_bulk_installs_error = '';
		$wpcd_bulk_installs_sucs  = '';

		if ( ! empty( $wpcd_bulk_installs_params ) ) {

			// validate and organize parameters.
			$check_validation        = $this->validate_required_parameters_for_wp_site( $wpcd_bulk_installs_params );
			$check_domain_validation = $this->wpcd_bulk_installs_check_domain_duplicate( $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_domain'] );

			if ( ! empty( $check_validation ) ) {

				$wpcd_bulk_installs_error = $check_validation;

			} elseif ( ! empty( $check_domain_validation ) ) {

				$wpcd_bulk_installs_error = $check_domain_validation;

			} else {

				foreach ( $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_domain'] as $web_app_key => $get_bulk_installs_wp_app ) {

					// Get all fields values.
					$wpcd_bulk_installs_wp_lang      = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_lang'][ $web_app_key ];
					$wpcd_bulk_installs_wp_user      = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_user'][ $web_app_key ];
					$wpcd_bulk_installs_wp_pass      = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_pass'][ $web_app_key ];
					$wpcd_bulk_installs_wp_email     = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_email'][ $web_app_key ];
					$wpcd_bulk_installs_wp_version   = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_version'][ $web_app_key ];
					$wpcd_bulk_installs_wp_server    = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_server'][ $web_app_key ];
					$wpcd_bulk_installs_owner_email  = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_owner_email'][ $web_app_key ];
					$wpcd_bulk_installs_email_notify = $wpcd_bulk_installs_params['wpcd_bulk_installs_wp_email_notification'][ $web_app_key ];
					// handle optional owner email.
					$author_email = filter_var( $wpcd_bulk_installs_owner_email, FILTER_SANITIZE_EMAIL );
					if ( ! empty( $author_email ) ) {

						$get_user_by_email = get_user_by( 'email', $author_email );

						if ( ! empty( $get_user_by_email ) ) {

							$author_email = $get_user_by_email->user_email;

						} else {

							$get_current_login_id = get_current_user_id();
							$get_user_by_id       = get_user_by( 'id', $get_current_login_id );
							$author_email         = $get_user_by_id->user_email;
						}
					} else {

						$get_current_login_id = get_current_user_id();
						$get_user_by_id       = get_user_by( 'id', $get_current_login_id );
						$author_email         = $get_user_by_id->user_email;
					}

					if ( empty( $wpcd_bulk_installs_wp_lang ) ) {
						$wpcd_bulk_installs_wp_lang = 'en_US';
					}

					if ( 'yes' !== $wpcd_bulk_installs_email_notify ) {

						$wpcd_bulk_installs_email_notify = 'no';
					}

					// Create an args array from the parameters to insert into the pending tasks table.
					$server_id = (int) $wpcd_bulk_installs_wp_server;
					// @codingStandardsIgnoreLine - added to ignore the misspelling in 'wordpress' below when linting with PHPcs. Otherwise linting will automatically uppercase the first letter.
					$args['wpcd_app_type']   			   = 'wordpress';
					$args['wp_domain']                     = sanitize_text_field( $get_bulk_installs_wp_app );
					$args['wp_user']                       = sanitize_text_field( $wpcd_bulk_installs_wp_user );
					$args['wp_password']                   = sanitize_text_field( $wpcd_bulk_installs_wp_pass );
					$args['wp_email']                      = $wpcd_bulk_installs_wp_email;
					$args['wp_version']                    = $wpcd_bulk_installs_wp_version;
					$args['wp_locale']                     = $wpcd_bulk_installs_wp_lang;
					$args['id']                            = $server_id;
					$args['wp_bulk_installs_app']          = 'yes';
					$args['action_hook']                   = 'wpcd_wordpress-app_bulk_installs_wp';
					$args['author_email']                  = $author_email;
					$args['wp_bulk_installs_admin_notify'] = $wpcd_bulk_installs_email_notify;
					// Create new install task.
					$task_id = WPCD_POSTS_PENDING_TASKS_LOG()->add_pending_task_log_entry( $server_id, 'bulk_installs_wp', $args['wp_domain'], $args, 'ready', $server_id, __( 'Bulk Installs: Waiting To Install New WP Site', 'wpcd' ) );

					// Throw some exceptions if for some reason there's no $task_id.
					if ( empty( $task_id ) ) {

						do_action( 'wpcd_log_error', __( 'Unable to insert a new task', 'wpcd' ), 'error', __FILE__, __LINE__, $task_id );

					}
					if ( is_wp_error( $task_id ) ) {

						do_action( 'wpcd_log_error', $task_id->get_error_message(), 'error', __FILE__, __LINE__, $task_id );
					}

					$wpcd_bulk_installs_sucs = __( 'Process successfully completed. Please check status in pending task log.', 'wpcd' );
				}
			}
		}

		wp_send_json(
			array(
				'sucs'  => $wpcd_bulk_installs_sucs,
				'error' => $wpcd_bulk_installs_error,
			)
		);
		die();
	}

	/**
	 * Used within an action method to check required parameters and throw an appropriate exception if one is missing
	 *
	 * @param array $parameters - request parameters.
	 *
	 * @throws Exception - if a required parameter is missing.
	 */
	protected function validate_required_parameters_for_server( array $parameters ) {

		unset( $parameters['wpcd_bulk_installs_owner_email'] );
		unset( $parameters['wpcd_bulk_installs_email_notification'] );

		// Check validation of all fields should not empty.
		foreach ( $parameters as $key => $parameter ) {

			foreach ( $parameter as $parameter_key => $check_parameter ) {

				if ( empty( $parameter[ $parameter_key ] ) ) {

					return __( 'All fields should not to empty (except "owner email & notfication checkbox") for server deploy.', 'wpcd' );
				}
			}
		}

		// Validate the server name and return right away if invalid format.
		foreach ( $parameters['wpcd_bulk_installs_name_of_server'] as $name_key => $name_of_server ) {

			$name_pattern                = '/^[a-z0-9-_]+$/i';
			$wpcd_bulk_installs_provider = $parameters['wpcd_bulk_installs_provider'][ $name_key ];
			if ( false !== strpos( mb_strtolower( $wpcd_bulk_installs_provider ), 'hivelocity' ) ) {
				// special check for hivelocity server names - periods are allowed because their names must be in xxx.yyy.zzz format
				// @TODO: We need to have a hook for validation and move this check into the HIVELOCITY plugin.
				$name_pattern = '/^[a-z0-9-_.]+$/i';
			}

			if ( ! empty( $name_of_server ) && ! preg_match( $name_pattern, $name_of_server ) ) {
				return __( 'Invalid name of server.', 'wpcd' );
			}
		}
		// End validate the server name.

		/* Validate the os */
		foreach ( $parameters['wpcd_bulk_installs_os'] as $key => $os ) {
			$oslist = WPCD()->get_os_list();
			if ( ! $oslist[ $os ] ) {
				return __( 'Invalid OS - security issue?', 'wpcd' );
			}

			/**
			 * Certain combinations of webservers and os's aren't allowed.
			 */
			$get_webserver = $parameters['wpcd_bulk_installs_web_server'][ $key ];
			if ( 'ubuntu1804lts' === $os && ( 'ols' === $get_webserver || 'ols-enterprise' === $get_webserver ) ) {
				return __( 'OpenLiteSpeed is not yet supported on Ubuntu 18.04 LTS.', 'wpcd' );
			}
		}

		/* Validate the webserver type */
		foreach ( $parameters['wpcd_bulk_installs_web_server'] as $webserver ) {
			$webserver_list = WPCD()->get_webserver_list();
			if ( ! $webserver_list[ $webserver ] ) {
				return __( 'Invalid Webserver type - security issue?', 'wpcd' );
			}
		}

		return false;

	}

	/**
	 * Used within an action method to check required parameters and throw an appropriate exception if one is missing
	 *
	 * @param array $parameters - request parameters.
	 *
	 * @throws Exception - if a required parameter is missing.
	 */
	protected function validate_required_parameters_for_wp_site( array $parameters ) {

		unset( $parameters['wpcd_bulk_installs_wp_owner_email'] );
		unset( $parameters['wpcd_bulk_installs_wp_email_notification'] );
		unset( $parameters['wpcd_bulk_installs_wp_lang'] );

		foreach ( $parameters as $key => $parameter ) {

			foreach ( $parameter as $parameter_key => $check_parameter ) {

				if ( empty( $parameter[ $parameter_key ] ) ) {

					return __( 'All fields should not to empty (except "owner email & notfication checkbox") for WordPress Site.', 'wpcd' );
				}
			}
		}
		return false;

	}

	/**
	 * Get active server by key & value.
	 */
	protected function wpcd_bulk_installs_get_active_server() {

		// Get all active servers by meta key value.
		$active_server_arr      = array();
		$get_active_server_args = array(
			'post_type'      => 'wpcd_app_server',
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => 'wpcd_server_current_state',
					'value'   => 'active',
					'compare' => '=',
				),
			),
		);

		$get_active_server = get_posts( $get_active_server_args );

		if ( ! empty( $get_active_server ) ) {

			foreach ( $get_active_server as $active_server ) {

				$active_server_arr[ $active_server->ID ] = $active_server->post_title;
			}
		}

		return $active_server_arr;
	}

	/**
	 * Get domain and check to make sure it's not a duplicate or otherwise in use in WPCD.
	 *
	 * @param array $get_domain - domain name.
	 */
	protected function wpcd_bulk_installs_check_domain_duplicate( $get_domain ) {

		$exist_domain_list = array();

		if ( ! defined( 'WPCD_ALLOW_DUPLICATE_DOMAINS' ) || ( defined( 'WPCD_ALLOW_DUPLICATE_DOMAINS' ) && ! WPCD_ALLOW_DUPLICATE_DOMAINS ) ) {

			foreach ( $get_domain as $domain ) {

				$existing_app_id = $this->get_app_id_by_domain_name( $domain );
				if ( $existing_app_id > 0 ) {
					$exist_domain_list[] = $domain;
				}
			}
		}

		if ( ! empty( $exist_domain_list ) ) {
			$domain_list = implode( ',', $exist_domain_list );
			return sprintf( __('These %s domains already exists in our system.', 'wpcd'), $domain_list );  // phpcs:ignore
		}
		return false;
	}

	/**
	 * Install WordPress on a server.
	 *
	 * Called from an action hook from the pending logs background process - WPCD_POSTS_PENDING_TASKS_LOG()->do_tasks()
	 *
	 * Action Hook: wpcd_wordpress-app_bulk_installs_wp
	 *
	 * @param int   $task_id    Id of pending task that is firing this thing.
	 * @param int   $server_id  Id of server on which to install the new website.
	 * @param array $args       All the data needed to install the WP site on the server.
	 */
	public function wpcd_bulk_installs_install_wp( $task_id, $server_id, $args ) {

		/* Install standard wp app on the designated server */
		$additional = WPCD_WORDPRESS_APP()->install_wp_validate( $args );

	}

	/**
	 * Send an email and possibly auto-issue SSL after a site has been installed.
	 *
	 * Action Hook: wpcd_command_wordpress-app_completed_after_cleanup
	 *
	 * @param int    $id                 post id of server.
	 * @param int    $app_id             post id of wp app.
	 * @param string $name               command name executed for new site.
	 * @param string $base_command       basename of command.
	 * @param string $pending_task_type  Task type to update when we're done. This is not part of the action hook definition - it's only passed in explicitly when this is called as a function.
	 */
	public function wpcd_bulk_installs_wpapp_install_complete( $id, $app_id, $name, $base_command, $pending_task_type = 'bulk_installs_wp' ) {

		// If not installing an app, return.
		if ( 'install_wp' !== $base_command ) {
			return;
		}

		$app_post = get_post( $app_id );

		// Bail if not a post object.
		if ( ! $app_post || is_wp_error( $app_post ) ) {
			return;
		}

		// Bail if not a WordPress app.
		if ( 'wordpress-app' !== WPCD_WORDPRESS_APP()->get_app_type( $app_id ) ) {
			return;
		}

		// If the site wasn't the result of a restapi command, then bail.
		if ( empty( get_post_meta( $app_id, 'wpapp_bulk_installs_app' ) ) ) {
			return;
		}

		// Get app instance array.
		$instance = WPCD_WORDPRESS_APP()->get_app_instance_details( $app_id );

		// If the app install was done via a background pending tasks process then get that pending task post data here.
		// We do that by checking the pending tasks table for a record where the key=domain and type='bulk_installs_wp' and state='in-process'.
		$pending_task_posts = WPCD_POSTS_PENDING_TASKS_LOG()->get_tasks_by_key_state_type( WPCD_WORDPRESS_APP()->get_domain_name( $app_id ), 'in-process', $pending_task_type );

		/**
		 * This is the spot where we would send emails and enable ssl and such if necessary.
		 * We're not doing that now but might later.
		 * Check the sell sites with woocommerce add-on for examples of what can go in this spot.
		 */

		// Finally update pending tasks table if applicable...
		if ( $pending_task_posts ) {
			$data_to_save                = WPCD_POSTS_PENDING_TASKS_LOG()->get_data_by_id( $pending_task_posts[0]->ID );
			$data_to_save['wp_password'] = '***removed***';  // remove the password data from the pending log table.
			WPCD_POSTS_PENDING_TASKS_LOG()->update_task_by_id( $pending_task_posts[0]->ID, $data_to_save, 'complete' );
			$wp_app_notify = get_post_meta( $app_id, 'wpapp_bulk_installs_admin_notify', true );

			if ( 'yes' == $wp_app_notify ) {

				if ( ! empty( $data_to_save ) ) {
					$server_id             = get_post_meta( $app_id, 'parent_post_id', true );
					$wpapp_original_domain = get_post_meta( $app_id, 'wpapp_original_domain', true );
					$wpcd_server_provider  = get_post_meta( $server_id, 'wpcd_server_provider', true );
					$wpcd_server_ipv4      = get_post_meta( $server_id, 'wpcd_server_ipv4', true );
					$wpcd_server_name      = get_post_meta( $server_id, 'wpcd_server_name', true );

					$summary  = __( 'Hello Admin,', 'wpcd' ) . '<br /><br />';
					$summary .= __( 'Thank you for your patience - your site is now ready for use.', 'wpcd' ) . '<br />';
					$summary .= __( '<h2>About your site</h2>', 'wpcd' );
					$summary .= __( 'Name: ', 'wpcd' ) . $wpapp_original_domain . '<br />';
					$summary .= __( 'Provider: ', 'wpcd' ) . $wpcd_server_provider . '<br />';
					$summary .= __( 'Server IP: ', 'wpcd' ) . $wpcd_server_ipv4 . '<br />';
					$summary .= __( 'Server Name: ', 'wpcd' ) . $wpcd_server_name . '<br />';
					wp_mail(
						get_option( 'admin_email' ),
						__( 'Your new site is ready', 'wpcd' ),
						$summary,
						array( 'Content-Type: text/html; charset=UTF-8' )
					);
				}
			}
		}
	}

	/**
	 * Add to the list of fields that will be automatically stamped on the WordPress App Post.
	 *
	 * Filter Hook: wpcd_{get_app_name()}_add_wp_app_post_fields | wpcd_wordpress-app_add_wp_app_post_fields
	 * The filter hook is located in the wordpress-app class.
	 *
	 * @param array $flds string array of existing field names.
	 */
	public function add_wp_app_post_fields( $flds ) {

		return array_merge( $flds, array( 'bulk_installs_app', 'bulk_installs_admin_notify' ) );

	}

	/**
	 * Mark the pending log entry as completed.
	 * Perhaps do other things such as marking the server as 'delete protected'.
	 *
	 * Action hook: wpcd_command_{$this->get_app_name()}_{$base_command}_{$status} || wpcd_wordpress-app_prepare_server_completed
	 *
	 * @param int    $server_id      The post id of the server record.
	 * @param string $command_name   The full name of the command that triggered this action.
	 */
	public function wpcd_wpapp_prepare_server_completed( int $server_id, $command_name ) {

		$server_post = get_post( $server_id );

		// Bail if not a post object.
		if ( ! $server_post || is_wp_error( $server_post ) ) {
			return;
		}

		// Bail if not a WordPress app.
		if ( 'wordpress-app' <> WPCD_WORDPRESS_APP()->get_server_type( $server_id ) ) {
			return;
		}

		// If the server wasn't the result of a REST API command, then bail.
		if ( empty( get_post_meta( $server_id, 'wpcd_server_wp_bulk_installs_server' ) ) ) {
			return;
		}

		// Get server instance array.
		$instance = WPCD_WORDPRESS_APP()->get_instance_details( $server_id );

		if ( 'wpcd_app_server' === get_post_type( $server_id ) ) {

			// If the app install was done via a background pending tasks process then get that pending task post data here.
			// We do that by checking the pending tasks table for a record where the key=domain and type='rest_api_install_wp' and state='in-process'.
			$pending_task_posts = WPCD_POSTS_PENDING_TASKS_LOG()->get_tasks_by_key_state_type( $server_id, 'in-process', 'bulk_installs_server' );
			if ( $pending_task_posts ) {
				/* Now update the log entry to mark it as complete. */
				$data_to_save = WPCD_POSTS_PENDING_TASKS_LOG()->get_data_by_id( $pending_task_posts[0]->ID );
				WPCD_POSTS_PENDING_TASKS_LOG()->update_task_by_id( $pending_task_posts[0]->ID, $data_to_save, 'complete' );
				$notify = get_post_meta( $server_id, 'wpcd_server_wp_bulk_installs_admin_notify', true );

				if ( 'yes' === $notify ) {

					$wpcd_server_provider = get_post_meta( $server_id, 'wpcd_server_provider', true );
					$wpcd_server_ipv4     = get_post_meta( $server_id, 'wpcd_server_ipv4', true );
					$wpcd_server_name     = get_post_meta( $server_id, 'wpcd_server_name', true );
					// Size.
					$size = get_post_meta( $server_id, 'wpcd_server_size', true );
					if ( empty( $size ) ) {
						$size = get_post_meta( $server_id, 'wpcd_server_size_raw', true );
					}

					$summary  = __( 'Hello Admin,', 'wpcd' ) . '<br /><br />';
					$summary .= __( 'Your server is ready for use.', 'wpcd' ) . '<br />';
					$summary .= __( '<h2>About your server</h2>', 'wpcd' );
					$summary .= __( 'Name: ', 'wpcd' ) . $wpcd_server_name . '<br />';
					$summary .= __( 'Provider: ', 'wpcd' ) . $wpcd_server_provider . '<br />';
					$summary .= __( 'Server IP: ', 'wpcd' ) . $wpcd_server_ipv4 . '<br />';
					$summary .= __( 'Size: ', 'wpcd' ) . $size . '<br />';
					wp_mail(
						get_option( 'admin_email' ),
						__( 'Your new server is ready', 'wpcd' ),
						$summary,
						array( 'Content-Type: text/html; charset=UTF-8' )
					);
				}
			}
		}

	}

}
