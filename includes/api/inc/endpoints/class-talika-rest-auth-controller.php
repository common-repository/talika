<?php
/**
 * Talika REST Auhtentication Controller.
 *
 * @since 1.0.0
 */
class Talika_REST_API_Auth_Controller {
	/**
	 * Authentication Route Register.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'talika/v1',
			'/auth',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'authorize' ),
					'permission_callback' => array( $this, 'is_valid_user' ),
				),
				// Register our schema callback.
				'schema' => null,
			)
		);

		register_rest_route('talika/v1', '/uid', [
			'methods'  => WP_REST_Server::READABLE,
			'callback' => [$this, 'currently_loggedin_user'],
			'permission_callback' => array( $this, 'is_valid_user' )
		]);
	}

	/**
	 * API Callback.
	 *
	 * @param WP_REST_REQUEST $request Current request.
	 * @return $response
	 */
	public function authorize( $request ) {
		$response = array(
			'code'    => 'valid_user',
			'message' => __( 'User Authentication Successful.', 'talika' ),
			'data'    => array(
				'status' => 200,
			),
		);
		return $response;
	}

	/**
	 * Check User Exist.
	 *
	 * @param WP_REST_Request $request Current request.
	 * @return mixed
	 */
	public function is_valid_user( $request ) {

		if ( ! wp_get_current_user()->ID ) {
			return new WP_Error( 'invalid_user', esc_html__( 'User Not Found.', 'talika' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
	}

	/**
	 * Sets Authorization Status Code.
	 *
	 * @return $status
	 */
	public function authorization_status_code() {
		$status = 401;

		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;
	}

	/**
	 * Check for authentication error.
	 *
	 * @param WP_Error|null|bool $error Error data.
	 * @return WP_Error|null|bool
	 */
	public function check_authentication_error( $error ) {
		// Pass through other errors.
		if ( ! empty( $error ) ) {
			return $error;
		}

	}
	
	function currently_loggedin_user($data) {

		// Get current user ID.
		$data = [
		  'uid' => get_current_user_id(),
		];
	
		$response = new WP_REST_Response($data, 200);
		// Set headers.
		$response->set_headers(['Cache-Control' => 'must-revalidate, no-cache, no-store, private']);
	
		return $response;
	  }	

}

/**
 * Initialize Auth Controller.
 *
 * @return void
 */
function talika_register_auth_rest_routes() {
	$controller = new Talika_REST_API_Auth_Controller();
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_auth_rest_routes' );
