<?php

/**
 * Admin area settings and hooks.
 *
 * @package Talika
 * @subpackage  Talika
 */

namespace Talika;

defined( 'ABSPATH' ) || exit;

/**
 * Global Settings.
 */
class TalikaAPPAdmin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'talika_add_primary_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Admin Script Translations
		add_action( 'admin_enqueue_scripts', array( $this, 'set_script_translations' ), 99999 );

		// admin init
		if ( empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action( 'admin_init', array( $this, 'refresh_terms_columns' ) );
		}

		add_action( 'in_admin_header', array( $this, 'hide_unrelated_notices' ) );
	}

	function refresh_terms_columns() {
		global $wpdb;
		$objects = array( '0' => TALIKA_SERVICES_POST_TYPE );
		$tags    = array( '0' => 'wpa-service-category' );

		if ( ! empty( $objects ) ) {
			foreach ( $objects as $object ) {
				
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT count(*) as cnt, max(menu_order) as max, min(menu_order) as min
						FROM $wpdb->posts
						WHERE post_type = %s 
						AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')",
						$object
					)
				);
				if ( $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max ) {
					continue;
				}

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID FROM $wpdb->posts 
						WHERE post_type = %s 
						AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') 
						ORDER BY menu_order ASC",
						$object
					)
				);
				foreach ( $results as $key => $result ) {
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $key + 1 ), array( 'ID' => $result->ID ) );
				}
			}
		}

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $taxonomy ) {
				
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT count(*) as cnt, max(term_order) as max, min(term_order) as min
						FROM $wpdb->terms AS terms
						INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
						WHERE term_taxonomy.taxonomy = %s",
						$taxonomy
					)
				);
				if ( $result[0]->cnt == 0 || $result[0]->cnt == $result[0]->max ) {
					continue;
				}

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT terms.term_id 
						FROM $wpdb->terms AS terms
						INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id ) 
						WHERE term_taxonomy.taxonomy = %s
						ORDER BY term_order ASC",
						$taxonomy
					)
				);
				foreach ( $results as $key => $result ) {
					$wpdb->update( $wpdb->terms, array( 'term_order' => $key + 1 ), array( 'term_id' => $result->term_id ) );
				}
			}
		}
	}

	/**
	 * Register a custom menu page.
	 */
	function talika_add_primary_menu() {
		$ADMIN_ICON = base64_encode( '<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path fill-rule="evenodd" clip-rule="evenodd" d="M30.8333 38.3333C34.9755 38.3333 38.3333 34.9755 38.3333 30.8333C38.3333 26.6912 34.9755 23.3333 30.8333 23.3333C26.6912 23.3333 23.3333 26.6912 23.3333 30.8333C23.3333 34.9755 26.6912 38.3333 30.8333 38.3333ZM33.9226 29.7559C34.248 29.4305 34.248 28.9028 33.9226 28.5774C33.5971 28.252 33.0695 28.252 32.7441 28.5774L30 31.3215L28.9226 30.2441C28.5971 29.9186 28.0695 29.9186 27.7441 30.2441C27.4186 30.5695 27.4186 31.0971 27.7441 31.4226L29.4107 33.0893C29.7362 33.4147 30.2638 33.4147 30.5893 33.0893L33.9226 29.7559Z" fill="#33DA80"/>
		<path d="M13.8333 3.18182C13.8333 2.34503 13.1618 1.66667 12.3333 1.66667C11.5049 1.66667 10.8333 2.34503 10.8333 3.18182V4.69697H7.83333C5.34805 4.69697 3.33333 6.73204 3.33333 9.24243V30.4546C3.33333 32.9649 5.34805 35 7.83333 35H22.6662C22.191 34.0705 21.8699 33.0492 21.7364 31.9697H7.83333C7.0049 31.9697 6.33333 31.2913 6.33333 30.4546V16.8182H30.3333V21.6801C30.4989 21.6712 30.6656 21.6667 30.8333 21.6667C31.7 21.6667 32.5386 21.787 33.3333 22.0117V9.24243C33.3333 6.73204 31.3186 4.69697 28.8333 4.69697H25.8333V3.18182C25.8333 2.34503 25.1618 1.66667 24.3333 1.66667C23.5049 1.66667 22.8333 2.34503 22.8333 3.18182V4.69697H13.8333V3.18182Z" fill="white"/>
		</svg>' );

		add_menu_page( esc_html__( 'Analytics Appointments', 'talika' ), esc_html__( 'Talika', 'talika' ), 'manage_options', 'talika', array( &$this, 'talika_analytic_page' ), 'data:image/svg+xml;base64,' . $ADMIN_ICON, 40 );

		add_submenu_page( 'talika', esc_html__( 'Dashboard', 'talika' ), esc_html__( 'Dashboard', 'talika' ), 'manage_options', 'talika', array( &$this, 'talika_analytic_page' ) );

		add_submenu_page( 'talika', esc_html__( 'Appointments', 'talika' ), esc_html__( 'Appointments', 'talika' ), 'manage_options', 'talika-appointments', array( &$this, 'talika_all_appointments' ) );

		add_submenu_page( 'talika', esc_html__( 'Services', 'talika' ), esc_html__( 'Services', 'talika' ), 'manage_options', 'talika-services', array( &$this, 'talika_services_page' ) );

		add_submenu_page( 'talika', esc_html__( 'Locations', 'talika' ), esc_html__( 'Locations', 'talika' ), 'manage_options', 'talika-locations', array( &$this, 'talika_locations_page' ) );

		add_submenu_page( 'talika', esc_html__( 'Staffs', 'talika' ), esc_html__( 'Staffs', 'talika' ), 'manage_options', 'talika-staffs', array( &$this, 'talika_staffs_page' ) );

		add_submenu_page( 'talika', esc_html__( 'Customers', 'talika' ), esc_html__( 'Customers', 'talika' ), 'manage_options', 'talika-customers', array( &$this, 'talika_customer_page' ) );

		add_submenu_page( 'talika', esc_html__( 'Global Settings', 'talika' ), esc_html__( 'Global Settings', 'talika' ), 'manage_options', 'talika-settings', array( &$this, 'talika_global_setting_page' ) );

		add_submenu_page( 'talika', esc_html__( 'Notifications', 'talika' ), esc_html__( 'Notifications', 'talika' ), 'manage_options', 'talika-notifications', array( &$this, 'talika_notifications_page' ) );
	}

	/**
	 * Enqueue Admin Scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen  = get_current_screen();
		$page_ids = array(
			'toplevel_page_talika',
			'talika_page_talika-appointments',
			'talika_page_talika-services',
			'talika_page_talika-calendar',
			'talika_page_talika-staffs',
			'talika_page_talika-customers',
			'talika_page_talika-locations',
			'talika_page_talika-settings',
			'talika_page_talika-notifications',
		);
		if ( in_array( $screen->id, $page_ids ) ) {
			$ajax_nonce = wp_create_nonce( 'talika_ajax_nonce' );
			wp_enqueue_style( 'admin-style', plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/app/build/admin.css' );
			wp_enqueue_style( 'google-font-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap' );
			wp_enqueue_style( 'toastr', plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/assets/admin/css/toastr.min.css', array(), '2.1.3', 'all' );

			$talika_js_object_array = array(
				'admin_url'                    => admin_url( 'admin.php' ),
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'                   => $ajax_nonce,
				'talika_curr_user'      => get_current_user_id(),
				'talika_admin_base_url' => admin_url( 'admin.php?page=' ),
				'talika_base_path'      => plugin_dir_url( TALIKA_PLUGIN_FILE ),
				'wpaRestNonce'                 => wp_create_nonce( 'wp_rest' ),
				'wpaRestRootUrl'               => esc_url_raw( rest_url() ),
			);
			wp_enqueue_media();
			wp_enqueue_editor();
			wp_enqueue_script( 'talika-backend-script', plugin_dir_url( TALIKA_PLUGIN_FILE ) . 'assets/admin/js/admin.js', array( 'jquery' ), '1.0.0', true );
			wp_localize_script( 'talika-backend-script', 'talika_backend_script_object', $talika_js_object_array );
			$app_deps = include_once plugin_dir_path( TALIKA_PLUGIN_FILE ) . 'app/build/app.asset.php';
			wp_enqueue_script( 'talika-app', plugin_dir_url( TALIKA_PLUGIN_FILE ) . 'app/build/app.js', $app_deps['dependencies'], $app_deps['version'], true );
			wp_localize_script( 'talika-app', 'WPA_VARS', $talika_js_object_array );
		}
	}

	/**
	 * Set Script Translations
	 *
	 * @return void
	 */
	public function set_script_translations() {
		wp_set_script_translations( 'talika-app', 'talika' );
	}

	/**
	 * Display a Analytic Dashboard page
	 */
	function talika_analytic_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display a All Services page
	 */
	function talika_services_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Calendar Page.
	 *
	 * @return void
	 */
	function talika_calendar_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display a All Appointment page
	 */
	function talika_all_appointments() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display a All Staffs/Employee Page
	 */
	function talika_staffs_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display a All Customer Page
	 */
	function talika_customer_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display a Locations Page
	 */
	function talika_locations_page() {
		echo '<div id="appointmentRootApp"></div>';
	}
	/**
	 * Display a Global Setting Page
	 */
	function talika_global_setting_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display a Global Setting Page
	 */
	function talika_notifications_page() {
		echo '<div id="appointmentRootApp"></div>';
	}

	/**
	 * Display only Talika notices in Talika pages.
	 *
	 * @since 1.0.0
	 */
	public function hide_unrelated_notices() {
		global $wp_filter;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Bail if we're not on a Talika screen or page.
		if ( empty( $_REQUEST['page'] ) || false === strpos( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'talika' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
			if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
				foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
					foreach ( $hooks as $name => $arr ) {
						if ( is_object( $arr['function'] ) && $arr['function'] instanceof Closure ) {
							unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
							continue;
						}
						if ( ! empty( $name ) && false === strpos( strtolower( $name ), 'talika' ) ) {
							unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}
		}
	}
}
