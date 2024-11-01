<?php
namespace Talika;
/**
 * Fired during plugin deactivation
 *
 * 
 * @since      1.0.0
 *
 * @package    Talika
 * @subpackage Talika/includes
 */
class Talika_Deactivator {

	/**
	 * Deactivation hook added.
	 *
	 * @return void
	 */
	public static function deactivate() {
		global $wpdb;
		$result = $wpdb->query( $wpdb->prepare( "DESCRIBE $wpdb->terms `term_order`" ) );
		if ( $result ) {
			$query  = "ALTER TABLE $wpdb->terms DROP `term_order`";
			$result = $wpdb->query( $wpdb->prepare( $query ) );
		}
    }

}
