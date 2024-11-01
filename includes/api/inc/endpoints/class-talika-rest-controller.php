<?php
/**
 * REST API: Talika_API_Controller class
 *
 * @package Talika
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with REST API items.
 *
 * @since 1.0.0
 */
abstract class Talika_API_Controller {

    /**
	 * The namespace of this controller's route.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $namespace = 'talika/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @since 1.0.0
	 * @var string
	 */
    protected $rest_base;

    /**
	 * Check permissions for the posts.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.', 'talika' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
    }

    /**
	 * Check permissions for the posts.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.', 'talika' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
	}
	
	/**
	 * Check permissions for the posts.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function post_item_permissions_check( $request ) {
		// Check edit prevlages.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.', 'talika' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
	}

	/**
	 * Check permissions for the settings update.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function update_settings_permissions_check( $request ) {
		// Check manage_options prevlages.
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot update the site settings.', 'talika' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
	}
	
    /**
     * Sets Authorization Status Code.
     *
     * @return void
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

		return $this->get_error();
    }

    /**
	 * Gets an array of fields to be included on the response.
	 *
	 * Included fields are based on item schema and `_fields=` request argument.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Fields to be included in the response.
	 */
	public function get_fields_for_response( $request ) {
		$schema = $this->get_item_schema();
		$fields = isset( $schema['properties'] ) ? array_keys( $schema['properties'] ) : array();

		return $fields;
	}
}
