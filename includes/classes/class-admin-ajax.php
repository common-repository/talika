<?php
/**
 * Talika TalikaAPPAdmin_Ajax. AJAX Event Handlers.
 *
 * @class   WC_AJAX
 * @package Talika\Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'TalikaAPPAdmin_Ajax' ) ) {

	class TalikaAPPAdmin_Ajax {

		/**
		 * All the ajax related tasks are hooked
		 *
		 * @since 1.0.0
		 */
		function __construct() {

			// Send Test Email
			add_action( 'wp_ajax_sendTestMail', array( $this, 'wpa_send_test_mail' ) );

			// Public ajax functions
			$this->add_ajax( 'checkloggedInStatus', array( $this, 'check_if_logged' ) );
			$this->add_ajax( 'logOutUser', array( $this, 'logout_user' ) );
			$this->add_ajax( 'loginUser', array( $this, 'login_user' ) );
			$this->add_ajax( 'getBearerToken', array( $this, 'get_bearer_token' ) );
		}

		/**
		 * Make Ajax handler
		 *
		 * @param [type] $action
		 * @param [type] $callback
		 * @return void
		 */
		public function add_ajax( $action = false, $callback = false ) {
			if ( ! $action || ! $callback ) {
				return;
			}

			add_action( "wp_ajax_{$action}", $callback );
			add_action( "wp_ajax_nopriv_{$action}", $callback );
		}

		/**
		 * Send Test Email Function
		 *
		 *  @since 1.0.0
		 */
		public function wpa_send_test_mail() {
			check_ajax_referer( 'talika_ajax_nonce', 'security' );

			$recipient_emails = isset( $_POST['recipientEmail'] ) ? talika_sanitize_values( wp_unslash( $_POST['recipientEmail'] ) ) : get_option( 'admin_email' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$subject          = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : 'The subject'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$message          = isset( $_POST['message'] ) ? talika_sanitize_values( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$headers          = array( 'Content-Type: text/html; charset=UTF-8' );

			if ( strpos( $recipient_emails, ',' ) !== false ) {
				$recipient_emails = str_replace( ' ', '', $recipient_emails );
				$to_emails        = explode( ',', $recipient_emails );
				foreach ( $to_emails as $key => $email ) {
					$email      = sanitize_email( $email );
					$mailResult = wp_mail( $email, $subject, $message, $headers );
				}
			} else {
				$recipient_email = str_replace( ' ', '', $recipient_emails );
				$recipient_email = sanitize_email( $recipient_email );
				if ( ! is_email( $recipient_email ) ) {
					wp_send_json_error(
						array(
							'error' => esc_html__( 'Please enter a valid email.', 'talika' ),
						)
					);
				}
				$mailResult = wp_mail( $recipient_email, $subject, $message, $headers );
			}

			if ( empty( $message ) ) {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'Message is empty.', 'talika' ),
					)
				);
			}

			if ( $mailResult ) {
				wp_send_json_success(
					esc_html__( 'Test Email Sent.', 'talika' )
				);
			} else {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'Something went wrong, please try again later', 'talika' ),
					)
				);
			}
		}

		/**
		 * LoggedIn user check.
		 */
		public function check_if_logged() {
			// check_ajax_referer( 'talika_ajax_nonce', 'security' );
			$allowed_roles = array( 'talika_customer', 'administrator' );
			if ( is_user_logged_in() ) {
				$user = wp_get_current_user();
				if ( array_intersect( $allowed_roles, $user->roles ) ) {
					wp_send_json_success(
						array(
							'display_name' => $user->display_name,
							'id'           => $user->id,
						)
					);
				} else {
					wp_send_json_error(
						array(
							'error' => esc_html__( 'Not Logged In', 'talika' ),
						)
					);
				}
			} else {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'Not Logged In', 'talika' ),
					)
				);
			}

			wp_die();
		}

		/**
		 * Logout User
		 */
		public function logout_user() {
			// check_ajax_referer( 'talika_ajax_nonce', 'security' );
			wp_logout();
			wp_send_json_success( true );
			die();
		}

		/**
		 * Login User
		 */
		public function login_user() {
			$username = sanitize_user( wp_unslash( $_POST['username'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$password = sanitize_text_field( wp_unslash( $_POST['password'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$auth     = wp_authenticate( $username, $password );
			if ( is_wp_error( $auth ) ) {
				wp_send_json_error(
					array(
						'error' => $auth->get_error_message(),
					)
				);
			} else {
				wp_set_auth_cookie( $auth->ID );
				$user          = wp_get_current_user();
				$allowed_roles = array( 'talika_customer', 'administrator' );
				if ( ! empty( $user->roles ) && ! array_intersect( $allowed_roles, $user->roles ) ) {
					wp_send_json_error(
						array(
							'error' => __( 'Current user doesnot have enough priviledge. Please check and try again.', 'talika' ),
						)
					);
					wp_logout();
				} else {
					wp_send_json_success(
						array(
							'display_name' => $auth->data->display_name,
							'id'           => $auth->ID,
						)
					);
				}
			}
			wp_die();
		}

		/**
		 * Get Bearer Token.
		 */
		public function get_bearer_token() {
			$token = get_option( 'talika_bearer_token', false );
			wp_send_json_success( $token );
			wp_die();
		}

	}
	new TalikaAPPAdmin_Ajax();
}
