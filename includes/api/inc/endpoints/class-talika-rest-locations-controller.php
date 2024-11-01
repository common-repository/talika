<?php

/**
 * REST API: Talika_Locations_REST_Controller class
 *
 * @package Talika API Core
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with Appointment.
 *
 * @since 1.0.0
 */
class Talika_Locations_REST_Controller extends Talika_API_Controller {


	/**
	 * Constructor
	 */
	public function __construct( $post_type ) {
		$this->base_name = $post_type;
	}

	// Register our routes.
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name,
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_items' ),
					'args'     => array(),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_item' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( 'POST' ),

				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/(?P<id>[\d]+)',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( 'PUT' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( 'DELETE' ),
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => function () {
					return true;
				},
			)
		);
	}

	/**
	 * Grab Latest Create Location Post Type
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_items( $request ) {
		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$mappings   = array(
			'per_page' => 'posts_per_page',
			'page'     => 'paged',
		);
		$args       = array(
			'posts_per_page' => 10,
			'post_type'      => $this->base_name,
		);
		foreach ( $mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}
		$posts_query = new WP_Query( $args );

		$posts = $posts_query->get_posts();
		$data  = array(
			'success'   => true,
			'message'   => __( 'Locations Found.', 'talika' ),
			'locations' => array(),
		);

		if ( empty( $posts ) ) {
			return rest_ensure_response(
				array(
					'success'   => false,
					'message'   => __( 'No Locations Found.', 'talika' ),
					'locations' => array(),
				)
			);
		}

		foreach ( $posts as $post ) {
			$response            = $this->prepare_item_for_response( $post, $request );
			$data['locations'][] = $this->prepare_response_for_collection( $response );
		}

		$response = rest_ensure_response( $data );

		// Return all of our Location response data.
		return $response;
	}

	/**
	 * Grabs a single Enquiry if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		$data = array(
			'success' => true,
			'message' => __( 'Location Found.', 'talika' ),
		);
		if ( empty( $post ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Location not found by ID.', 'talika' ),
					'location' => array(),
				)
			);
		}

		$response         = $this->prepare_item_for_response( $post, $request );
		$data['location'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Settings data sanitization.
	 *
	 * @param [type] $settings_data
	 * @return void
	 */
	public function talika_locations_sanitize_array( $settings_data ) {
		if ( ! is_array( $settings_data ) || count( $settings_data ) == 0 ) {
			return array();
		}
		foreach ( $settings_data as $key => $value ) {
			if ( ! is_array( $value ) ) {
				switch ( $key ) {
					case 'locationDescription':
						$allowed_html          = wp_kses_allowed_html( 'post' );
						$settings_data[ $key ] = wp_kses( $value, $allowed_html );
						break;
					case 'locationMapEmbed':
						// preg_match('/src="([^"]+)"/', $value, $match);
						// $url = $match[1];
						// $settings_data[$key] = sanitize_text_field($url);
						$settings_data[ $key ] = $value;
						break;
					default:
						$settings_data[ $key ] = sanitize_text_field( $value );
						break;
				}
			} elseif ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					switch ( $k ) {
						default:
							$settings_data[ $key ][ $k ] = sanitize_text_field( $v );
							break;
					}
				}
			}
		}
		return $settings_data;
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
	 * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
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
			'title'      => TALIKA_LOCATIONS_POST_TYPE,
			'type'       => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'ID'                        => array(
					'description' => __( 'Unique identifier for the object.', 'talika' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'post_title'                => array(
					'description' => __( 'The title for the object.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
				),
				'post_content'              => array(
					'description' => __( 'The content for the Location.', 'talika' ),
					'type'        => 'html',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => null,
					),
				),
				'locationStatus'            => array(
					'description' => __( 'Meta field to toggle location field status visible or hidden.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'locationColor'            => array(
					'description' => __( 'Option for pulling location color.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'post_date'                 => array(
					'description' => __( "The date the object was published, in the site's timezone.", 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'locationAddress'           => array(
					'description' => __( 'Meta field to storing address of location.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'locationGallery'           => array(
					'description' => __( 'Meta field to storing image gallery data of location.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'locationFeaturedImage'     => array(
					'description' => __( 'Custom Metafield for pulling featured image.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'locationAdditionalDetails' => array(
					'description' => __( 'Meta field to storing all other additional data of location.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
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
	 * @since  1.0.0
	 *
	 * @param array $schema Schema array.
	 * @return array Modified Schema array.
	 */
	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		$object_type = $schema['title'];

		$additional_fields = $this->get_additional_fields( $object_type );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['schema'] ) {
				continue;
			}

			$schema['properties'][ $field_name ] = $field_options['schema'];
		}

		return $schema;
	}

	function get_additional_fields( $object_type = null ) {

		if ( ! $object_type ) {
			$object_type = $this->get_object_type();
		}

		if ( ! $object_type ) {
			return array();
		}

		global $wp_rest_additional_fields;

		if ( ! $wp_rest_additional_fields || ! isset( $wp_rest_additional_fields[ $object_type ] ) ) {
			return array();
		}

		return $wp_rest_additional_fields[ $object_type ];
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function post_item( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params['post_title'] ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Location Title is a required field.', 'talika' ),
					'location' => array(),
				)
			);
		}
		if ( empty( $params['locationAddress'] ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Location Address is a required field.', 'talika' ),
					'location' => array(),
				)
			);
		}
		if ( empty( $params ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No any data was passed. Please try again.', 'talika' ),
					'location' => array(),
				)
			);
		}
		$post_id = wp_insert_post(
			array(
				'post_type'   => $this->base_name,
				'post_title'  => isset( $params['post_title'] ) ? sanitize_text_field( $params['post_title'] ) : '',
				'post_status' => 'publish',
			)
		);
		if ( empty( $post_id ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Couldnot add Location. Please try again.', 'talika' ),
					'location' => array(),
				)
			);
		}
		$data = array(
			'success' => true,
			'message' => __( 'Location was added successfully.', 'talika' ),
		);
		if ( $post_id ) {
			if ( isset( $params['locationAddress'] ) ) {
				add_post_meta( $post_id, 'locationAddress', sanitize_text_field( $params['locationAddress'] ) );
			}
			if ( isset( $params['locationFeaturedImage'] ) ) {
				add_post_meta( $post_id, 'locationFeaturedImage', $this->talika_locations_sanitize_array( $params['locationFeaturedImage'] ) );
			}
			if ( isset( $params['locationGallery'] ) ) {
				add_post_meta( $post_id, 'locationGallery', $this->talika_locations_sanitize_array( $params['locationGallery'] ) );
			}
			if ( isset( $params['locationAdditionalDetails'] ) ) {
				add_post_meta( $post_id, 'locationAdditionalDetails', $this->talika_locations_sanitize_array( $params['locationAdditionalDetails'] ) );
			}
			if ( isset( $params['locationColor'] ) ) {
				add_post_meta( $post_id, 'locationColor', sanitize_hex_color( $params['locationColor'] ) );
			}
			add_post_meta( $post_id, 'locationStatus', 'visible' );
			$post             = get_post( $post_id );
			$response         = $this->prepare_item_for_response( $post, $request );
			$data['location'] = $this->prepare_response_for_collection( $response );
		}

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Retrieves an array of endpoint arguments from the item schema for the controller.
	 *
	 * @since 4.7.0
	 *
	 * @param string $method Optional. HTTP method of the request. The arguments for `CREATABLE`  requests are checked for required values and may fall-back to a given default, this is not done
	 *   on `EDITABLE` requests. Default WP_REST_Server::CREATABLE.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = 'POST' ) {
		return rest_get_endpoint_args_for_schema( $this->get_item_schema(), $method );
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$params = $request->get_json_params();
		$id     = isset( $request['id'] ) ? (int) $request['id'] : '';

		if ( empty( $id ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'location' => array(),
				)
			);
		} elseif ( ! empty( $id ) ) {
			$post = get_post( $id );
			if ( ! $post ) {
				return rest_ensure_response(
					array(
						'success'  => false,
						'message'  => __( 'No location found by given Id. Please try again.', 'talika' ),
						'location' => array(),
					)
				);
			} else {
			}
		}
		$post_id = wp_update_post(
			array(
				'ID'          => $id,
				'post_type'   => $this->base_name,
				'post_title'  => isset( $params['post_title'] ) ? sanitize_text_field( $params['post_title'] ) : '',
				'post_status' => 'publish',
			)
		);
		if ( ! is_wp_error( $post_id ) ) {
			if ( isset( $params['locationAddress'] ) ) {
				update_post_meta( $id, 'locationAddress', sanitize_text_field( $params['locationAddress'] ) );
			}
			if ( isset( $params['locationGallery'] ) ) {
				update_post_meta( $id, 'locationGallery', $this->talika_locations_sanitize_array( $params['locationGallery'] ) );
			}
			if ( isset( $params['locationFeaturedImage'] ) ) {
				update_post_meta( $id, 'locationFeaturedImage', $this->talika_locations_sanitize_array( $params['locationFeaturedImage'] ) );
			}
			if ( isset( $params['locationAdditionalDetails'] ) ) {
				update_post_meta( $id, 'locationAdditionalDetails', $this->talika_locations_sanitize_array( $params['locationAdditionalDetails'] ) );
			}
			if ( isset( $params['locationStatus'] ) ) {
				update_post_meta( $id, 'locationStatus', isset( $params['locationStatus'] ) ? sanitize_text_field( $params['locationStatus'] ) : 'visible' );
			}
			if ( isset( $params['locationColor'] ) ) {
				update_post_meta( $id, 'locationColor', sanitize_hex_color( $params['locationColor'] ) );
			}
			$data = array(
				'success' => true,
				'message' => __( 'Location settings updated.', 'talika' ),
			);
		}
		$post             = get_post( $id );
		$response         = $this->prepare_item_for_response( $post, $request );
		$data['location'] = $this->prepare_response_for_collection( $response );

		return $data;
	}

	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$params = $request->get_json_params();
		$id     = (int) $request['id'];

		if ( empty( $id ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'location' => array(),
				)
			);
		} else {
			$post = get_post( $id );
			if ( ! $post ) {
				return rest_ensure_response(
					array(
						'success'  => false,
						'message'  => __( 'No location found by given Id. Please try again.', 'talika' ),
						'location' => array(),
					)
				);
			}
		}

		$post_id = wp_delete_post( $id, true );
		if ( is_wp_error( $post_id ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( $post_id->get_error_message(), 'talika' ),
					'location' => array(),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success'  => true,
					'message'  => __( 'Location deleted successfully.', 'talika' ),
					'location' => array(),
				)
			);
		}
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
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
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database( $request ) {
		return array();
	}
}

// Function to register our new routes from the controller.
function talika_register_locations_rest_routes() {
	$controller = new Talika_Locations_REST_Controller( TALIKA_LOCATIONS_POST_TYPE );
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_locations_rest_routes' );
