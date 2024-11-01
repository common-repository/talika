<?php
/**
 * API core class
 *
 * @package Talika/API
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Talika_API_Core' ) ) :
	/**
	 * Talika API Core.
	 */
	class Talika_API_Core {

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Abspath
		 *
		 * @var [type]
		 */
		protected static $abspath;

		/**
		 * The single instance of the class.
		 *
		 * @var Talika_API
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main Talika_API_Core Instance.
		 * Ensures only one instance of Talika_API_Core is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @see Talika_API_Core()
		 * @return Talika_API_Core - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Init core.
		 *
		 * @param Array $params Core class init paramerters.
		 */
		public static function init() {
			self::$abspath = plugin_dir_path( __FILE__ );
			include_once self::$abspath . 'inc/class-talika-rest-authentication.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-services-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-locations-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-notifications-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-staffs-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-customers-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-appointment-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-global-controller.php';
			include_once self::$abspath . 'inc/endpoints/class-talika-rest-analytics-controller.php';
		}
	}
endif;

// Init core API.
/**
 * Return the main instance of Talika_API_Core.
 *
 * @since 1.0.0
 * @return Talika_API_Core
 */
function talika_run_api_core() {     // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return Talika_API_Core::instance();
}

// Run.
talika_run_api_core();
