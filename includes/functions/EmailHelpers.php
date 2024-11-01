<?php
/**
 * Email Template and functions.
 *
 * @package Talika
 * @subpackage  Talika
 * @since  1.0.0
 */

namespace Talika;

defined( 'ABSPATH' ) || exit;

/**
 * Email Helpers.
 */
class EmailHelpers {

	/**
	 * Global Settings.
	 *
	 * @var object
	 */
	public $settings;

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	public $template_html;

	/**
	 * Recipients for the email.
	 *
	 * @var string
	 */
	public $recipient;

	/**
	 * Object this email is for, for example a customer, product, or email.
	 *
	 * @var object|bool
	 */
	public $object;

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending;

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @var array
	 */
	protected $placeholders = array();

	private static $_instance = null;

	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->init_hooks();

	}

	/**
	 * Init email classes.
	 */
	public function init_hooks() {

		$this->settings   = get_option( 'talika_notifications', array() );
		$company_settings = get_option( 'talika_company_settings', true );
		$company_name     = isset( $company_settings['companyName'] ) ? $company_settings['companyName'] : '';
		$company_address  = isset( $company_settings['companyAddress'] ) ? $company_settings['companyAddress'] : '';
		$company_phone    = isset( $company_settings['companyPhone'] ) ? $company_settings['companyPhone'] : '';

		$this->placeholders = array_merge(
			array(
				'{site_title}'      => $this->get_blogname(),
				'{site_address}'    => wp_parse_url( home_url(), PHP_URL_HOST ),
				'{site_url}'        => wp_parse_url( home_url(), PHP_URL_HOST ),
				'{admin_email}'     => $this->get_from_name(),
				'%company_address%' => $company_address ? $company_address : wp_parse_url( home_url(), PHP_URL_HOST ),
				'%company_name%'    => $company_name ? $company_name : $this->get_blogname(),
				'%company_phone%'   => $company_phone ? $company_phone : get_bloginfo( 'admin_phone' ),
				'%company_website%' => wp_parse_url( home_url(), PHP_URL_HOST ),
			),
			$this->placeholders
		);

		// Hooks for sending emails.
		add_action( 'talika_status_notifications', array( $this, 'appointment_status_notification' ), 20, 3 );
	}

	/**
	 * Format email string.
	 *
	 * @param mixed $string Text to replace placeholders in.
	 * @return string
	 */
	public function format_string( $string ) {
		$find    = array_keys( $this->placeholders );
		$replace = array_values( $this->placeholders );

		// Filter for main find/replace.
		return apply_filters( 'talika_email_format_string', str_replace( $find, $replace, $string ) );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->format_string( $this->get_default_subject() );
	}

	/**
	 * Get valid recipients.
	 *
	 * @return string
	 */
	public function get_recipient() {
		$recipient  = apply_filters( 'talika_email_recipients', $this->recipient );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );
		return implode( ', ', $recipients );
	}

	/**
	 * Get email headers.
	 *
	 * @return string
	 */
	public function get_headers() {
		$header = 'Content-Type: ' . $this->get_content_type() . "\r\n";

		if ( $this->get_from_address() && $this->get_from_name() ) {
			$header .= 'Reply-to: ' . $this->get_from_name() . ' <' . $this->get_from_address() . ">\r\n";
		}

		return apply_filters( 'talika_email_headers', $header );
	}

	/**
	 * Get email attachments.
	 *
	 * @return array
	 */
	public function get_attachments() {
		return apply_filters( 'talika_email_attachments', array() );
	}

	/**
	 * Get email content type.
	 *
	 * @return string
	 */
	public function get_content_type() {
		$content_type = 'text/html';

		return apply_filters( 'talika_email_content_type', $content_type );
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get the from name for outgoing emails.
	 *
	 * @param string $from_name Default wp_mail() name associated with the "from" email address.
	 * @return string
	 */
	public function get_from_name( $from_name = '' ) {
		return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}

	/**
	 * Get the from address for outgoing emails.
	 *
	 * @param string $from_email Default wp_mail() email address to send from.
	 * @return string
	 */
	public function get_from_address( $from_email = '' ) {
		return sanitize_email( get_option( 'admin_email' ) );
	}

	/**
	 * Get the email header.
	 *
	 * @param mixed $email_heading Heading for the email.
	 */
	public function email_header() {
		talika_get_template( 'emails/email-header.php' );
	}

	/**
	 * Get the email footer.
	 */
	public function email_footer() {
		talika_get_template( 'emails/email-footer.php' );
	}

	/**
	 * Get the email content in HTML format.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return '';
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$this->sending = true;

		$email_content = $this->format_string( $this->get_content_html() );

		return $email_content;
	}

	/**
	 * Wraps a message in the delicious recipes mail template.
	 *
	 * @param string $email_heading Heading text.
	 * @param string $message       Email message.
	 *
	 * @return string
	 */
	public function wrap_message( $email_heading, $message ) {
		// Buffer.
		ob_start();

		$this->email_header();

		echo wp_kses_post( wpautop( wptexturize( $message ) ) );

		$this->email_footer();

		// Get contents.
		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Send an email.
	 *
	 * @param string $to Email to.
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 * @param string $headers Email headers.
	 * @param array  $attachments Email attachments.
	 * @return bool success
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {
		wp_mail( $to, $subject, $message, $headers, $attachments );
	}

	/**
	 * Appointment Status Notifications email.
	 *
	 * @param int   $appointment_id        Appointment ID.
	 * @param array $old_status  Old Appointment Status.
	 * @param bool  $new_status  New Appointment Status.
	 */
	public function appointment_status_notification( $appointment_id, $old_status, $new_status ) {
		if ( ! $appointment_id || ( $old_status['value'] === $new_status['value'] ) ) {
			return;
		}

		switch ( $new_status['value'] ) {
			case 'approved':
				$notify_customer = talika_get_array_values_by_index( $this->settings, 'customerNotification.approved.enabled', true );
				if ( $notify_customer ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/customer-appointment-approved.php';
					$email = new \Customer_Appointment_Approved();
					$email->trigger( $appointment_id );
				}

				$notify_employee = talika_get_array_values_by_index( $this->settings, 'staffNotification.approved.enabled', true );
				if ( $notify_employee ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/employee-appointment-approved.php';
					$email = new \Employee_Appointment_Approved();
					$email->trigger( $appointment_id );
				}
				break;

			case 'pending':
				$notify_customer = talika_get_array_values_by_index( $this->settings, 'customerNotification.pending.enabled', true );
				if ( $notify_customer ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/customer-appointment-pending.php';
					$email = new \Customer_Appointment_Pending();
					$email->trigger( $appointment_id );
				}

				$notify_employee = talika_get_array_values_by_index( $this->settings, 'staffNotification.pending.enabled', true );
				if ( $notify_employee ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/employee-appointment-pending.php';
					$email = new \Employee_Appointment_Pending();
					$email->trigger( $appointment_id );
				}
				break;

			case 'cancelled':
				$notify_customer = talika_get_array_values_by_index( $this->settings, 'customerNotification.canceled.enabled', true );
				if ( $notify_customer ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/customer-appointment-canceled.php';
					$email = new \Customer_Appointment_Canceled();
					$email->trigger( $appointment_id );
				}

				$notify_employee = talika_get_array_values_by_index( $this->settings, 'staffNotification.canceled.enabled', true );
				if ( $notify_employee ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/employee-appointment-canceled.php';
					$email = new \Employee_Appointment_Canceled();
					$email->trigger( $appointment_id );
				}
				break;

			case 'rejected':
				$notify_customer = talika_get_array_values_by_index( $this->settings, 'customerNotification.rejected.enabled', true );
				if ( $notify_customer ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/customer-appointment-rejected.php';
					$email = new \Customer_Appointment_Rejected();
					$email->trigger( $appointment_id );
				}

				$notify_employee = talika_get_array_values_by_index( $this->settings, 'staffNotification.rejected.enabled', true );
				if ( $notify_employee ) {
					include plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/classes/emails/employee-appointment-rejected.php';
					$email = new \Employee_Appointment_Rejected();
					$email->trigger( $appointment_id );
				}
				break;

			default:
				break;
		}
	}

}

EmailHelpers::get_instance();
