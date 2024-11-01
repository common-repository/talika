<?php

/**
 * Main Talika class
 *
 * @package Talika
 */

namespace Talika;

defined( 'ABSPATH' ) || exit;

/**
 * Main Talika Cass.
 *
 * @class Talika
 */
final class TalikaAPP {

	/**
	 * Talika verison.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var TalikaAPP
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main Talika Instance.
	 *
	 * Ensures only one instance of Talika is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Talika - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Talika Constructor.
	 */
	public function __construct() {
		$this->_defineConstants();
		$this->includes();
		$this->init_hooks();

		$this->admin_settings              = new TalikaAPPAdmin();
		$this->register_required_post_type = new TalikaAPPRegisterRequiredPostType();
		if ( $this->is_request( 'frontend' ) ) {
			$this->public_settings = new TalikaAPPPublic();
		}
		$this->jwt_validator = new Talika_JWT_Handler();
	}

	public function init_hooks() {
		// reorder post types
		add_action( 'pre_get_posts', array( $this, 'wpa_pre_get_posts' ) );

		// reorder taxonomies
		add_filter( 'get_terms_orderby', array( $this, 'get_terms_orderby' ), 10, 3 );
		add_filter( 'wp_get_object_terms', array( $this, 'get_object_terms' ), 10, 3 );
		add_filter( 'get_terms', array( $this, 'get_object_terms' ), 10, 3 );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * When WP has loaded all plugins, trigger the 'Talika_loaded; hook.
	 *
	 * This ensures 'Talika_loaded' is called only after all the other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 * the load order.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function onPluginLoaded() {
		do_action( 'Talika_loaded' );
	}

	/**
	 * Define WTE_FORM_EDITOR Constants.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function _defineConstants() {
		$this->define( 'TALIKA_PLUGIN_NAME', 'talika' );
		$this->define( 'TALIKA_ABSPATH', dirname( TALIKA_PLUGIN_FILE ) . '/' );
		$this->define( 'TALIKA_VERSION', $this->version );
		$this->define( 'TALIKA_PLUGIN_URL', $this->plugin_url() );
		$this->define( 'TALIKA_POST_TYPE', 'wpa-appointments' );
		$this->define( 'TALIKA_LOCATIONS_POST_TYPE', 'wpa-locations' );
		$this->define( 'TALIKA_SERVICES_POST_TYPE', 'wpa-services' );
		$this->define( 'TALIKA_STAFFS', 'wpa-staffs' );
		$this->define( 'TALIKA_CUSTOMERS', 'wpa-customers' );
		$this->define( 'TALIKA_NOTIFICATIONS', 'wpa-notifications' );
		$this->define( 'TALIKA_GLOBAL_SETTINGS', 'wpa-global-settings' );
		$this->define( 'TALIKA_TEMPLATE_DEBUG_MODE', false );
		$this->define( 'TALIKA_ANALYTICS', 'wpa-analytics' );
		$this->define( 'TALIKA_TABLET_BREAKPOINT', '1024' );
		$this->define( 'TALIKA_MOBILE_BREAKPOINT', '767' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name       Constant name.
	 * @param string|bool $value      Constant value.
	 * @return void
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required files.
	 *
	 * @return void
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			include plugin_dir_path( __FILE__ ) . '/api/class-talika-api-core.php';
			include plugin_dir_path( __FILE__ ) . '/classes/class-admin-functions.php';
			include plugin_dir_path( __FILE__ ) . '/classes/class-admin-ajax.php';
			require plugin_dir_path( __FILE__ ) . '/classes/class-shortcode.php';
			require plugin_dir_path( __FILE__ ) . '/classes/class-blocks.php';
			require plugin_dir_path( __FILE__ ) . '/helpers/class-talika-helpers.php';
			require plugin_dir_path( __FILE__ ) . '/helpers/class-talika-block-helpers.php';
			require plugin_dir_path( __FILE__ ) . '/classes/class-talika-fonts-manager.php';
			require_once plugin_dir_path( __FILE__ ) . '/classes/class-talika-jwt-handler.php';
		}
	}

	/**
	 * Init Talika when WordPress initializes.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function init() {
		// Set up localization.
		$this->loadPluginTextdomain();
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 *
	 * Note: the first-loaded translation file overrides any following ones -
	 * - if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/talika/talika-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/talika-LOCALE.mo
	 */
	public function loadPluginTextdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'talika' );

		unload_textdomain( 'talika' );
		load_textdomain( 'talika', WP_LANG_DIR . '/talika/talika-' . $locale . '.mo' );
		load_plugin_textdomain(
			'talika',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Get the plugin URL.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', TALIKA_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( TALIKA_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'talika_template_path', '/talika/' );
	}

	/**
	 * Output error message and disable plugin if requirements are not met.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			// Deactivate our plugin
			deactivate_plugins( TALIKA_PLUGIN_FILE );
		}
	}

	/**
	 * Check if all plugin requirements are met.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if requirements are met, otherwise false.
	 */
	private function meets_requirements() {
		return true;
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Sort posts
	 *
	 * @param [type] $wp_query
	 * @return void
	 */
	public function wpa_pre_get_posts( $wp_query ) {
		$objects = array( '0' => TALIKA_SERVICES_POST_TYPE );
		if ( empty( $objects ) ) {
			return false;
		}

		/**
		 * for Admin
		 *
		 * @default
		 * post cpt: [order] => null(desc) [orderby] => null(date)
		 * page: [order] => asc [orderby] => menu_order title
		 */

		if ( is_admin() ) {

			if ( isset( $wp_query->query['post_type'] ) && ! isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				if ( in_array( $wp_query->query['post_type'], $objects ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
					$wp_query->set( 'order', 'ASC' );
				}
			}

			/**
			 * for Front End
			 */
		} else {

			$active = false;

			// page or custom post types
			if ( isset( $wp_query->query['post_type'] ) ) {
				// exclude array()
				if ( ! is_array( $wp_query->query['post_type'] ) ) {
					if ( in_array( $wp_query->query['post_type'], $objects ) ) {
						$active = true;
					}
				}
				// post
			} else {
				if ( in_array( 'post', $objects ) ) {
					$active = true;
				}
			}

			if ( ! $active ) {
				return false;
			}

			// get_posts()
			if ( isset( $wp_query->query['suppress_filters'] ) ) {
				if ( $wp_query->get( 'orderby' ) == 'date' || $wp_query->get( 'orderby' ) == 'menu_order' ) {
					$wp_query->set( 'orderby', 'menu_order' );
					$wp_query->set( 'order', 'ASC' );
				} elseif ( $wp_query->get( 'orderby' ) == 'default_date' ) {
					$wp_query->set( 'orderby', 'date' );
				}
				// WP_Query( contain main_query )
			} else {
				if ( ! $wp_query->get( 'orderby' ) ) {
					$wp_query->set( 'orderby', 'menu_order' );
				}
				if ( ! $wp_query->get( 'order' ) ) {
					$wp_query->set( 'order', 'ASC' );
				}
			}
		}
	}

	/**
	 * Terms orderby
	 *
	 * @param [type] $orderby
	 * @param [type] $args
	 * @return void
	 */
	public function get_terms_orderby( $orderby, $args ) {
		if ( is_admin() ) {
			return $orderby;
		}

		$tags = array( '0' => 'wpa-service-category' );

		if ( ! isset( $args['taxonomy'] ) ) {
			return $orderby;
		}

		$taxonomy = $args['taxonomy'];
		if ( ! in_array( $taxonomy, $tags ) ) {
			return $orderby;
		}

		$orderby = 't.term_order';
		return $orderby;
	}

	/**
	 * Get terms object
	 *
	 * @param [type] $terms
	 * @return void
	 */
	public function get_object_terms( $terms ) {
		$tags = array( '0' => 'wpa-service-category' );

		if ( is_admin() && isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $terms;
		}

		foreach ( $terms as $key => $term ) {
			if ( is_object( $term ) && isset( $term->taxonomy ) ) {
				$taxonomy = $term->taxonomy;
				if ( ! in_array( $taxonomy, $tags ) ) {
					return $terms;
				}
			} else {
				return $terms;
			}
		}

		usort( $terms, array( $this, 'taxcmp' ) );
		return $terms;
	}

	/**
	 * Sort helper
	 *
	 * @param [type] $a
	 * @param [type] $b
	 * @return void
	 */
	public function taxcmp( $a, $b ) {
		if ( $a->term_order == $b->term_order ) {
			return 0;
		}
		return ( $a->term_order < $b->term_order ) ? -1 : 1;
	}
}
