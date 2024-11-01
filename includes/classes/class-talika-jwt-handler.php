<?php
/**
 * JWT handler
 *
 * @package Talika
 */
namespace Talika;

use \Firebase\JWT\JWT;
/**
 * JWT Auth handler
 */
class Talika_JWT_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->admin_hooks();
		$this->public_hooks();
	}

	/**
	 * Admin Hooks
	 *
	 * @return void
	 */
	public function admin_hooks() {
		// add_action( 'admin_init', array( $this, 'generate_bearer_token' ) );
	}

	/**
	 * OPublic facing hooks
	 *
	 * @return void
	 */
	public function public_hooks() {
		add_filter( 'determine_current_user', array( $this, 'determine_current_user' ), 10 );
	}

	/**
	 * Generate bareer token
	 *
	 * @return void
	 */
	public static function generate_bearer_token() {
		// Retreve saved auth.
		$auth_token = get_option( 'talika_bearer_token', false );

		if ( ! $auth_token ) {

			$user = wp_get_current_user();

			/** Valid credentials, the user exists create the according Token */
			$issuedAt  = time();
			$notBefore = apply_filters( 'jwt_auth_not_before', $issuedAt, $issuedAt );
			$expire    = apply_filters( 'jwt_auth_expire', $issuedAt + ( DAY_IN_SECONDS * 7 ), $issuedAt );

			$secret_key = defined( 'TALIKA_AUTH_SECRET_KEY' ) ? TALIKA_AUTH_SECRET_KEY : false;

			$token = array(
				'iss'  => get_bloginfo( 'url' ),
				// 'iat' => $issuedAt,
				// 'nbf' => $notBefore,
				// 'exp' => $expire,
				'data' => array(
					'user' => array(
						'id' => $user->data->ID,
					),
				),
			);

			/** Let the user modify the token data before the sign. */
			$token = JWT::encode( apply_filters( 'jwt_auth_token_before_sign', $token, $user ), $secret_key, 'HS256' );

			// Save token to database.
			update_option( 'talika_bearer_token', $token );
		}
	}

	/**
	 * This is our Middleware to try to authenticate the user according to the
	 * token send.
	 *
	 * @param (int|bool) $user Logged User ID
	 *
	 * @return (int|bool)
	 */
	public function determine_current_user( $user ) {
		/**
		 * This hook only should run on the REST API requests to determine
		 * if the user in the Token (if any) is valid, for any other
		 * normal call ex. wp-admin/.* return the user.
		 *
		 * @since 1.2.3
		 */
		$rest_api_slug = rest_get_url_prefix();
		$valid_api_uri = null;
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$valid_api_uri = strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $rest_api_slug );
		}

		if ( ! $valid_api_uri ) {
			return $user;
		}

		/*
		 * if the request URI is for validate the token don't do anything,
		 * this avoid double calls to the validate_token function.
		 */
		$validate_uri = strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'token/validate' );
		if ( $validate_uri > 0 ) {
			return $user;
		}

		$token = $this->validate_token( false );

		if ( is_wp_error( $token ) ) {
			if ( $token->get_error_code() != 'jwt_auth_no_auth_header' ) {
				/** If there is a error, store it to show it after see rest_pre_dispatch */
				$this->jwt_error = $token;
				return $user;
			} else {
				return $user;
			}
		}
		/** Everything is ok, return the user ID stored in the token*/
		return $token->data->user->id;
	}

	/**
	 * Main validation function, this function try to get the Autentication
	 * headers and decoded.
	 *
	 * @param bool $output
	 *
	 * @return WP_Error | Object | Array
	 */
	public function validate_token( $output = true ) {
		/*
		 * Looking for the HTTP_AUTHORIZATION header, if not present just
		 * return the user.
		 */
		$auth = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : false;

		/* Double check for different auth header string (server dependent) */
		if ( ! $auth ) {
			$auth = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) : false;
		}

		if ( ! $auth ) {
			return new \WP_Error(
				'jwt_auth_no_auth_header',
				'Authorization header not found.',
				array(
					'status' => 403,
				)
			);
		}

		/*
		 * The HTTP_AUTHORIZATION is present verify the format
		 * if the format is wrong return the user.
		 */
		list($token) = sscanf( $auth, 'Bearer %s' );
		if ( ! $token ) {
			return new \WP_Error(
				'jwt_auth_bad_auth_header',
				'Authorization header malformed.',
				array(
					'status' => 403,
				)
			);
		}

		/** Get the Secret Key */
		$secret_key = defined( 'TALIKA_AUTH_SECRET_KEY' ) ? TALIKA_AUTH_SECRET_KEY : false;
		if ( ! $secret_key ) {
			return new \WP_Error(
				'jwt_auth_bad_config',
				'JWT is not configurated properly, please contact the admin',
				array(
					'status' => 403,
				)
			);
		}

		/** Try to decode the token */
		try {
			$token = JWT::decode( $token, $secret_key, array( 'HS256' ) );
			/** The Token is decoded now validate the iss */
			if ( $token->iss != get_bloginfo( 'url' ) ) {
				/** The iss do not match, return error */
				return new \WP_Error(
					'jwt_auth_bad_iss',
					'The iss do not match with this server',
					array(
						'status' => 403,
					)
				);
			}
			/** So far so good, validate the user id in the token */
			if ( ! isset( $token->data->user->id ) ) {
				/** No user id in the token, abort!! */
				return new \WP_Error(
					'jwt_auth_bad_request',
					'User ID not found in the token',
					array(
						'status' => 403,
					)
				);
			}
			/** Everything looks good return the decoded token if the $output is false */
			if ( ! $output ) {
				return $token;
			}
			/** If the output is true return an answer to the request to show it */
			return array(
				'code' => 'jwt_auth_valid_token',
				'data' => array(
					'status' => 200,
				),
			);
		} catch ( Exception $e ) {
			/** Something is wrong trying to decode the token, send back the error */
			return new \WP_Error(
				'jwt_auth_invalid_token',
				$e->getMessage(),
				array(
					'status' => 403,
				)
			);
		}
	}

}
