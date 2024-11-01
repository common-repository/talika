<?php
namespace Talika;
/**
 * Fired during plugin activation
 *
 * 
 * @since      1.0.0
 *
 * @package    Talika
 * @subpackage Talika/includes/classes
 */
class Talika_Activator {

	/**
	 * Activation hook for Talika plugin.
	 *
	 * @return void
	 */

	public static function activate() {
        // Create user roles
        self::create_roles();

        // Generate bearer token.
        self::generate_bearer_token();
    }

    /**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
        global $wpdb;

		// add term_order COLUMN to $wpdb->terms TABLE
		$result = $wpdb->query( $wpdb->prepare( "DESCRIBE $wpdb->terms `term_order`" ) );
		if ( ! $result ) {
			$query  = "ALTER TABLE $wpdb->terms ADD `term_order` INT( 4 ) NULL DEFAULT '0'";
			$result = $wpdb->query( $wpdb->prepare( $query ) );
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// add menu_order COLUMN to $wpdb->blogs TABLE
			$result = $wpdb->query( $wpdb->prepare( "DESCRIBE $wpdb->blogs `menu_order`" ) );
			if ( ! $result ) {
				$query  = "ALTER TABLE $wpdb->blogs ADD `menu_order` INT( 4 ) NULL DEFAULT '0'";
				$result = $wpdb->query( $wpdb->prepare( $query ) );
			}
		}

        include_once plugin_dir_path( __FILE__ ) . 'class-required-user-roles.php';
		TALIKA_USER_ROLES::activate();
    }
    
	/**
     * Generate Bearer Token
     *
     * @return void
     */
    public static function generate_bearer_token() {
		include_once plugin_dir_path( __FILE__ ) . 'class-talika-jwt-handler.php';
		Talika_JWT_Handler::generate_bearer_token();
	}
}
