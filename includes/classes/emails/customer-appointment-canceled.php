<?php
/**
 * Class Customer_Appointment_Canceled file.
 *
 * @package Talika
 * @since  1.0.0
 */

use Talika\EmailHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Customer_Appointment_Canceled', false ) ) :

	/**
	 * Customer Appointment Canceled.
	 *
	 * An email sent to the customer when the appointment is canceled.
	 *
	 * @class       Customer_Appointment_Canceled
	 * @extends     EmailHelpers
	 */
	class Customer_Appointment_Canceled extends EmailHelpers {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->template_html = 'emails/customer/customer-appointment-canceled.php';
			$this->placeholders  = array(
				'%appointment_id%'             => '',
				'%service_name%'               => '',
				'%appointment_start_date%'     => '',
				'%appointment_start_time%'     => '',
				'%appointment_duration%'       => '',
				'%appointment_status%'         => '',
				'%appointment_payment_method%' => '',
				'%appointment_payment_amount%' => '',
				'%appointment_price%'          => '',
				'%customer_full_name%'         => '',
				'%customer_first_name%'        => '',
				'%customer_last_name%'         => '',
				'%customer_email%'             => '',
				'%customer_phone%'             => '',
				'%customer_notes%'             => '',
				'%staff_full_name%'            => '',
				'%staff_first_name%'           => '',
				'%staff_last_name%'            => '',
				'%staff_email%'                => '',
				'%staff_phone%'                => '',
				'%location_name%'              => '',
				'%location_address%'           => '',
				'%location_description%'       => '',
				'%location_phone%'             => '',
			);

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			$subject = talika_get_array_values_by_index( $this->settings, 'customerNotification.canceled.subject', '' );
			$subject = empty( $subject ) ? __( '%service_name% Appointment Canceled', 'talika' ) : $subject;
			return $subject;
		}

		/**
		 * Trigger.
		 *
		 * @param int $id Appointment ID.
		 */
		public function trigger( $id ) {

			if ( $id ) {
				$appointment     = get_appointment_details( $id );
				$this->recipient = $appointment->customer_email;

				$this->placeholders['%appointment_id%']             = $appointment->appointment_id;
				$this->placeholders['%service_name%']               = $appointment->service_name;
				$this->placeholders['%appointment_start_date%']     = $appointment->start_date;
				$this->placeholders['%appointment_start_time%']     = $appointment->start_time;
				$this->placeholders['%appointment_duration%']       = $appointment->duration;
				$this->placeholders['%appointment_status%']         = $appointment->status;
				$this->placeholders['%appointment_payment_method%'] = $appointment->payment_method;
				$this->placeholders['%appointment_payment_amount%'] = $appointment->payment_amount;
				$this->placeholders['%appointment_price%']          = $appointment->price;
				$this->placeholders['%customer_full_name%']         = $appointment->customer_full_name;
				$this->placeholders['%customer_first_name%']        = $appointment->customer_first_name;
				$this->placeholders['%customer_last_name%']         = $appointment->customer_last_name;
				$this->placeholders['%customer_email%']             = $appointment->customer_email;
				$this->placeholders['%customer_phone%']             = $appointment->customer_phone;
				$this->placeholders['%customer_notes%']             = $appointment->customer_notes;
				$this->placeholders['%staff_full_name%']            = $appointment->staff_full_name;
				$this->placeholders['%staff_first_name%']           = $appointment->staff_first_name;
				$this->placeholders['%staff_last_name%']            = $appointment->staff_last_name;
				$this->placeholders['%staff_email%']                = $appointment->staff_email;
				$this->placeholders['%staff_phone%']                = $appointment->staff_phone;
				$this->placeholders['%location_name%']              = $appointment->location_name;
				$this->placeholders['%location_address%']           = $appointment->location_address;
				$this->placeholders['%location_description%']       = $appointment->location_description;
				$this->placeholders['%location_phone%']             = $appointment->location_phone;
			}

			if ( $this->get_recipient() ) {
				$this->send(
					$this->get_recipient(),
					$this->get_subject(),
					$this->get_content(),
					$this->get_headers(),
					$this->get_attachments()
				);
			}

		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			ob_start();

			$this->email_header();

			echo wp_kses_post( wpautop( wptexturize( talika_get_template_content( 'appointment_canceled', $this->template_html, 'customer' ) ) ) );

			$this->email_footer();

			return ob_get_clean();
		}

	}

endif;
