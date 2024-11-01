<?php

/**
 * Public area settings and hooks.
 *
 * @package Talika
 * @subpackage  Talika
 */

namespace Talika;

defined( 'ABSPATH' ) || exit;

/**
 * Global Settings.
 */
class TalikaAPPPublic {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function init() {
		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function init_hooks() {
		$wpa = new \Talika_Fonts_Manager();
		// Enqueue Frontend Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'wp', array( $wpa, 'generate_assets' ), 99 );
		add_action( 'wp_head', array( $this, 'generate_stylesheet' ), 80 );
	}

	/**
	 * Enqueue frontend scripts
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts() {
		$ajax_nonce = wp_create_nonce( 'talika_ajax_nonce' );

		$app_frontend_deps = include_once plugin_dir_path( TALIKA_PLUGIN_FILE ) . 'app/build/appPublic.asset.php';
		wp_enqueue_script( 'talika-app', plugin_dir_url( TALIKA_PLUGIN_FILE ) . 'app/build/appPublic.js', $app_frontend_deps['dependencies'], $app_frontend_deps['version'], true );
		wp_enqueue_style( 'wpapp-public-style', plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/app/build/appPublicCSS.css' );
		wp_enqueue_style( 'toastr', plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/assets/admin/css/toastr.min.css', array(), '2.1.3', 'all' );
		$talika_wpapp_object_array = array(
			'wpaBasePath'    => plugin_dir_url( TALIKA_PLUGIN_FILE ),
			'wpaRestNonce'   => wp_create_nonce( 'wp_rest' ),
			'wpaRestRootUrl' => esc_url_raw( rest_url() ),
			'admin_url'      => admin_url( 'admin.php' ),
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'     => $ajax_nonce,
			'ajax_loader'    => plugin_dir_url( TALIKA_PLUGIN_FILE ) . 'assets/images/ajax-loader.gif',
			'wpLabels'       => talika_get_global_label_settings(),
		);
		wp_localize_script( 'talika-app', 'wpaAppVariables', $talika_wpapp_object_array );

		if ( is_talika_block() ) {
			$wpa = new \Talika_Fonts_Manager();
			$wpa->load_dynamic_google_fonts();
		}

		wp_set_script_translations( 'talika-app', 'talika' );
	}

	/**
	 * Generates stylesheet and appends in head tag.
	 *
	 * @since 0.0.1
	 */
	public function generate_stylesheet() {

		$wpa        = new \Talika_Fonts_Manager();
		$stylesheet = $wpa::$stylesheet;

		if ( is_null( $stylesheet ) || '' === $stylesheet ) {
			return;
		}
		ob_start();
		?>
			<style id="talika-styles-frontend"><?php echo wp_filter_nohtml_kses( $stylesheet ); ?></style>
		<?php
		ob_end_flush();
	}

}
