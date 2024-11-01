<?php
/**
 * Shortcodes Class
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!!' );

if ( ! class_exists( 'TalikaAPP_Shortcode' ) ) {

	/**
	 * Frontend Gallery Shortcode
	 */
	class TalikaAPP_Shortcode {

		function __construct() {
			add_shortcode( 'talika_booking_form', array( $this, 'talika_booking_form_manager' ) );
			add_shortcode( 'talika_service_list', array( $this, 'talika_service_list' ) );
			add_shortcode( 'talika_staff_list', array( $this, 'talika_staff_list' ) );
			add_shortcode( 'talika_location_list', array( $this, 'talika_location_list' ) );
		}

		/**
		 * Function
		 * Booking form for the appointment
		 *
		 * @param [type] $atts
		 * @return void
		 */
		function talika_booking_form_manager( $atts = array() ) {
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );

			// override default attributes with user attributes
			$wpa_atts = shortcode_atts(
				array(
					'label' => __( 'Book an Appointment', 'talika' ),
				),
				$atts
			);

			ob_start();
				echo '<div class="appointmentRootApp" data-btn-label="' . esc_attr( $wpa_atts['label'] ) . '"></div>';
			return ob_get_clean();
		}

		/**
		 * Function
		 * List of the services
		 *
		 * @param [type] $atts
		 * @return void
		 */
		function talika_service_list( $atts ) {
			// override default attributes with user attributes
			$wpa_atts = shortcode_atts(
				array(
					'columns' => '3',
				),
				$atts
			);

			ob_start();
				echo '<div class="appointmentServiceApp" data-columns="' . esc_attr( $wpa_atts['columns'] ) . '"></div>';
			return ob_get_clean();
		}

		/**
		 * Function
		 * List of the staffs
		 *
		 * @param [type] $atts
		 * @return void
		 */
		function talika_staff_list( $atts ) {
			// override default attributes with user attributes
			$wpa_atts = shortcode_atts(
				array(
					'columns' => '3',
				),
				$atts
			);
			ob_start();
				echo '<div class="appointmentStaffScreen" data-columns="' . esc_attr( $wpa_atts['columns'] ) . '"></div>';
			return ob_get_clean();
		}

		/**
		 * List of the locations
		 *
		 * @param [type] $atts
		 * @return void
		 */
		function talika_location_list( $atts = array() ) {
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );

			// override default attributes with user attributes
			$wpa_atts = shortcode_atts(
				array(
					'layout'  => 'grid',
					'columns' => '3',
				),
				$atts
			);

			ob_start();
				echo '<div class="appointmentLocationApp" data-layout="' . esc_attr( $wpa_atts['layout'] ) . '" data-columns="' . esc_attr( $wpa_atts['columns'] ) . '"></div>';
			return ob_get_clean();
		}


	}

	new TalikaAPP_Shortcode();
}
