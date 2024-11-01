<?php

/**
 * REST API: Talika_Notifications_REST_Controller class
 *
 * @package WP Notification API Core
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with Staffs.
 *
 * @since 1.0.0
 */

class Talika_Notifications_REST_Controller extends Talika_API_Controller {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->base_name = TALIKA_NOTIFICATIONS;
	}

	// Register our routes.
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name,
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_item' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',

			)
		);
	}

	/**
	 * Grabs the five most recent posts and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_items( $request ) {

		$data = get_option( 'talika_notifications', '' );

		if ( empty( $data ) ) {
			return rest_ensure_response(
				array(
					'success'       => false,
					'message'       => __( 'No notification were found.', 'talika' ),
					'notifications' => array(),
				)
			);
		}
		return rest_ensure_response(
			array(
				'success'       => true,
				'message'       => __( 'Notifications were found.', 'talika' ),
				'notifications' => $data,
			)
		);
	}

	/**
	 * Grabs a single Enquiry if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_item( $request ) {

		$params = $request->get_json_params();
		$data   = get_option( 'talika_notifications', '' );
		$sanitized_params = talika_sanitize_editor_values( $params );
		$update = update_option( 'talika_notifications', $sanitized_params );
		if ( $data == $params ) {
			$update = true;
		}
		if ( $update ) {
			return rest_ensure_response(
				array(
					'success'      => true,
					'message'      => __( 'Notification saved.', 'talika' ),
					'notification' => $params,
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success'      => false,
					'message'      => __( 'Couldnot save Notification. Please try again.', 'talika' ),
					'notification' => array(),
				)
			);
		}
	}

	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param WP_Post $post The comment object whose response is being prepared.
	 */
	function prepare_item_for_response( $post, $request ) {
		$schema = $this->get_item_schema( $request );
		foreach ( $schema['properties'] as $schema_properties_k => $schema_properties_v ) {
			$data[ $schema_properties_k ] = $post->$schema_properties_k;
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a response for inserting into a collection of responses.
	 *
	 * This is copied from WP_REST_Controller class in the wp REST API v2 plugin.
	 *
	 * @param WP_REST_Response $response Response object.
	 * @return array Response data, ready for insertion into collection data.
	 */
	public function prepare_response_for_collection( $response ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$data   = (array) $response->get_data();
		$server = rest_get_server();

		if ( method_exists( $server, 'get_compact_response_links' ) ) {
			$links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
		} else {
			$links = call_user_func( array( $server, 'get_response_links' ), $response );
		}

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @since 4.7.0
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'talika' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'talika' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Checks the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $date_gmt GMT publication time.
	 * @param string|null $date     Optional. Local publication time. Default null.
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date );
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt );
	}

	/**
	 * Get our sample schema for a post.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_item_schema( $request = null ) {
		$schema = array(
			// This tells the spec of JSON Schema we are using which is draft 4.
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			// The title property marks the identity of the resource.
			'title'      => TALIKA_CUSTOMERS,
			'type'       => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the object.', 'talika' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'date'               => array(
					'description' => __( "The date the object was published, in the site's timezone.", 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'username'           => array(
					'description' => __( 'Login name for the user.', 'talika' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
				),
				'first_name'         => array(
					'description' => __( 'first_name to the user.', 'talika' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'last_name'          => array(
					'description' => __( 'The last_name of the user.', 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_email'         => array(
					'description' => __( 'The email address the user.', 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'url'                => array(
					'description' => __( 'A URL to the site for the user site.', 'talika' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'description'        => array(
					'description' => __( 'Description of the user', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'link'               => array(
					'description' => __( 'Author URL of the user', 'talika' ),
					'type'        => 'string',
					'minItems'    => 1,
					'uniqueItems' => true,
				),
				'locale'             => array(
					'description' => __( 'Locale for the user.', 'talika' ),
					'type'        => 'string',
					'minimum'     => 1,
				),
				'nickname'           => array(
					'description' => __( 'The nickname for the user.', 'talika' ),
					'type'        => 'string',
				),
				'slug'               => array(
					'description' => __( 'An alphanumeric identifier for the user.', 'talika' ),
					'type'        => 'string',
				),
				'registered_date'    => array(
					'description' => __( 'registered date of the user.', 'talika' ),
					'type'        => 'string',
				),
				'roles'              => array(
					'description' => __( 'Roles assigned to the user.', 'talika' ),
					'type'        => 'integer',
				),
				'password'           => array(
					'description' => __( 'Password for the user (never included).', 'talika' ),
					'type'        => 'integer',
				),
				'capabilities'       => array(
					'description' => __( 'All capabilities assigned to the user.', 'talika' ),
					'type'        => 'object',
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the user.', 'talika' ),
					'type'        => 'integer',
				),
				'avatar_urls'        => array(
					'description' => __( 'Avatar urls on the site.', 'talika' ),
					'type'        => 'object',
				),
				'meta'               => array(
					'description' => __( 'Additional image gallery for the service.', 'talika' ),
					'type'        => 'object',
				),
				'customerImage'      => array(
					'description' => __( 'Customer Image.', 'talika' ),
					'type'        => 'array',
				),
				'customerPhone'      => array(
					'description' => __( 'Customer Phone number.', 'talika' ),
					'type'        => 'string',
				),
				'gender'             => array(
					'description' => __( 'Customer gender.', 'talika' ),
					'type'        => 'string',
				),
				'dateOfBirth'        => array(
					'description' => __( 'Customer date of birth.', 'talika' ),
					'type'        => 'string',
				),
				'address'            => array(
					'description' => __( 'Customer address.', 'talika' ),
					'type'        => 'string',
				),
				'noteToCustomer'     => array(
					'description' => __( 'Customer note.', 'talika' ),
					'type'        => 'string',
				),
				'noteInternal'       => array(
					'description' => __( 'Customer note Internal.', 'talika' ),
					'type'        => 'string',
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Adds the schema from additional fields to a schema array.
	 *
	 * The type of object is inferred from the passed schema.
	 *
	 * @since 1.0.0
	 *
	 * @param array $schema Schema array.
	 * @return array Modified Schema array.
	 */
	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		$object_type = $schema['title'];

		return $schema;
	}

	/**
	 * Check if a given request has access to update a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to delete a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		// return true; <--use to make readable by all
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if a given request has access to post a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function post_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}



// Function to register our new routes from the controller.
function talika_register_notifications_rest_routes() {
	$controller = new Talika_Notifications_REST_Controller( TALIKA_NOTIFICATIONS );
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_notifications_rest_routes' );
