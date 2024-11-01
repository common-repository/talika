<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!!' );

if ( ! class_exists( 'TalikaAPP_Admin_Functions' ) ) {

	class TalikaAPP_Admin_Functions {

		/**
		 * Prints array in pre format
		 *
		 * @since 1.0.0
		 *
		 * @param array $array
		 */
		function print_array( $array ) {
			echo '<pre>';
			print_r( $array );
			echo '</pre>';
		}
	}
}
