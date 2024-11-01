<?php

/**
 * REST API: Talika_Staffs_REST_Controller class
 *
 * @package WP Staffs API Core
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with Staffs.
 *
 * @since 1.0.0
 */

class Talika_Customers_REST_Controller extends Talika_API_Controller {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->base_name = TALIKA_CUSTOMERS;
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
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'  => 'POST',
					'callback' => array( $this, 'post_item' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',

			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/users',
			array(
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'get_all_users' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',

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
		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$allowed_roles = array( 'talika_customer', 'administrator' );
		/*
		* This array defines mappings between public API query parameters whose
		* values are accepted as-passed, and their internal WP_Query parameter
		* name equivalents (some are the same). Only values which are also
		* present in $registered will be set.
		*/
		$parameter_mappings = array(
			'exclude'  => 'exclude',
			'include'  => 'include',
			'order'    => 'order',
			'search'   => 'search',
			'slug'     => 'nicename__in',
			'per_page' => 'number',
			'page'     => 'paged',
		);
		$prepared_args      = array();
		/*
		* For each known parameter which is both registered and present in the request,
		* set the parameter's value on the query $prepared_args.
		*/
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		if ( isset( $registered['roles'] ) && ! empty( $request['roles'] ) ) {
			$prepared_args['role__in'] = $prepared_args['roles'];
		} else {
			$prepared_args['role__in'] = $allowed_roles;
		}

		if ( isset( $registered['orderby'] ) ) {
			$orderby_possibles        = array(
				'id'              => 'ID',
				'include'         => 'include',
				'name'            => 'display_name',
				'registered_date' => 'registered',
				'slug'            => 'user_nicename',
				'include_slugs'   => 'nicename__in',
				'email'           => 'user_email',
				'url'             => 'user_url',
			);
			$prepared_args['orderby'] = isset( $request['orderby'] ) && ! empty( $request['orderby'] ) ? $orderby_possibles[ $request['orderby'] ] : '';
		}

		$filterby = $request->get_param( 'filterby' );
		$filterby = $filterby ? (array) json_decode( $filterby ) : false;

		if( $filterby ) {
			if( isset( $filterby['keywordSearch'] ) && $filterby['keywordSearch'] ) {
				$prepared_args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'first_name',
						'value'   => $filterby['keywordSearch'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'last_name',
						'value'   => $filterby['keywordSearch'],
						'compare' => 'LIKE',
					),
				);
			} elseif( isset( $filterby['sortBy'] ) && $filterby['sortBy'] ) {
				switch( $filterby['sortBy'] ) {
					case 'firstNameAscending': 
						$prepared_args['meta_key'] = 'first_name';
						$prepared_args['orderby']  = 'meta_value';
						$prepared_args['order']    = 'ASC';
						break;
					case 'firstNameDescending': 
						$prepared_args['meta_key'] = 'first_name';
						$prepared_args['orderby']  = 'meta_value';
						$prepared_args['order']    = 'DESC';
						break;
					case 'lastNameAscending': 
						$prepared_args['meta_key'] = 'last_name';
						$prepared_args['orderby']  = 'meta_value';
						$prepared_args['order']    = 'ASC';
						break;
					case 'lastNameDescending': 
						$prepared_args['meta_key'] = 'last_name';
						$prepared_args['orderby']  = 'meta_value';
						$prepared_args['order']    = 'DESC';
						break;
				}
			}
		}

		/**
		 * Filters WP_User_Query arguments when querying users via the REST API.
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @since 4.7.0
		 *
		 * @param array           $prepared_args Array of arguments for WP_User_Query.
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( 'rest_user_query', $prepared_args, $request );

		$query = new WP_User_Query( $prepared_args );
		$users = array();
		$data  = array(
			'success'   => true,
			'message'   => __( 'Customers Found.', 'talika' ),
			'customers' => array(),
		);

		if ( empty( $query->results ) ) {
			return rest_ensure_response(
				array(
					'success'   => false,
					'message'   => __( 'No customers were found.', 'talika' ),
					'customers' => array(),
				)
			);
		}

		$total_users = $query->get_total();

		foreach ( $query->results as $user ) {
			$response            = $this->prepare_item_for_response( $user, $request );
			$data['customers'][] = $this->prepare_response_for_collection( $response );
			$data['total']       = (int) $total_users;
		}

		$response = rest_ensure_response( $data );

		$response->header( 'X-WP-Total', (int) $total_users );

		return $response;
	}

	/**
	 * Grabs the five most recent posts and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_all_users( $request ) {
		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		/*
		* This array defines mappings between public API query parameters whose
		* values are accepted as-passed, and their internal WP_Query parameter
		* name equivalents (some are the same). Only values which are also
		* present in $registered will be set.
		*/
		$parameter_mappings = array(
			'exclude'  => 'exclude',
			'include'  => 'include',
			'order'    => 'order',
			'per_page' => 'number',
			'search'   => 'search',
			'slug'     => 'nicename__in',
		);
		$prepared_args      = array();
		/*
		* For each known parameter which is both registered and present in the request,
		* set the parameter's value on the query $prepared_args.
		*/
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$prepared_args[ $wp_param ] = $request[ $api_param ];
			}
		}

		if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * isset( $prepared_args['number'] ) ? $prepared_args['number'] : '';
		}

		if ( isset( $registered['orderby'] ) ) {
			$orderby_possibles        = array(
				'id'              => 'ID',
				'include'         => 'include',
				'name'            => 'display_name',
				'registered_date' => 'registered',
				'slug'            => 'user_nicename',
				'include_slugs'   => 'nicename__in',
				'email'           => 'user_email',
				'url'             => 'user_url',
			);
			$prepared_args['orderby'] = isset( $request['orderby'] ) && ! empty( $request['orderby'] ) ? $orderby_possibles[ $request['orderby'] ] : '';
		}

		/**
		 * Filters WP_User_Query arguments when querying users via the REST API.
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @since 4.7.0
		 *
		 * @param array           $prepared_args Array of arguments for WP_User_Query.
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( 'rest_user_query', $prepared_args, $request );

		$query = new WP_User_Query( $prepared_args );
		$users = array();
		$data  = array(
			'success'   => true,
			'message'   => __( 'Customers Found.', 'talika' ),
			'customers' => array(),
		);

		if ( empty( $query->results ) ) {
			return rest_ensure_response(
				array(
					'success'   => false,
					'message'   => __( 'No customers were found.', 'talika' ),
					'customers' => array(),
				)
			);
		}

		foreach ( $query->results as $user ) {
			$response            = $this->prepare_item_for_response( $user, $request );
			$data['customers'][] = $this->prepare_response_for_collection( $response );
		}

		$response = rest_ensure_response( $data );

		$total_users = $query->get_total();

		$response->header( 'X-WP-Total', (int) $total_users );

		return $response;
	}

	/**
	 * Grabs a single Enquiry if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_item( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No any data was passed. Please try again.', 'talika' ),
					'customer' => array(),
				)
			);
		}
		if ( empty( $params['first_name'] ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'First name is a required field.', 'talika' ),
					'customer' => array(),
				)
			);
		}
		if ( empty( $params['user_email'] ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Email is a required field.', 'talika' ),
					'customer' => array(),
				)
			);
		}
		if ( email_exists( $params['user_email'] ) === true ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'This email is already registered.', 'talika' ),
					'customer' => array(),
				)
			);
		}
		$generatedUserName = $this->generate_unique_username( sanitize_text_field( $params['user_email'] ) );
		$userdata          = array(
			'role'       => 'talika_customer',
			'user_login' => $generatedUserName,
			'user_pass'  => '',
			'user_name'  => $generatedUserName,
			'user_email' => $params['user_email'],
			'first_name' => $params['first_name'],
			'last_name'  => $params['last_name'],
		);
		$user_id           = wp_insert_user( $userdata );
		if ( ! is_wp_error( $user_id ) ) {

			// Send email notification to admin and user.
			wp_new_user_notification( $user_id, null, 'both' );

			$user = new WP_User( $user_id );
			foreach ( $user->roles as $role ) {
				if ( $role === 'talika_customer' ) {
					if ( isset( $params['customerImage'] ) && ! empty( $params['customerImage'] ) ) {
						add_user_meta( $user_id, 'customerImage', $params['customerImage'], true );
					}
					if ( isset( $params['customerPhone'] ) && ! empty( $params['customerPhone'] ) ) {
						add_user_meta( $user_id, 'customerPhone', $params['customerPhone'], true );
					}
					if ( isset( $params['gender'] ) && ! empty( $params['gender'] ) ) {
						add_user_meta( $user_id, 'gender', $params['gender'], true );
					}
					if ( isset( $params['dateOfBirth'] ) && ! empty( $params['dateOfBirth'] ) ) {
						add_user_meta( $user_id, 'dateOfBirth', $params['dateOfBirth'], true );
					}
					if ( isset( $params['address'] ) && ! empty( $params['address'] ) ) {
						add_user_meta( $user_id, 'address', $params['address'], true );
					}
					if ( isset( $params['noteToCustomer'] ) && ! empty( $params['noteToCustomer'] ) ) {
						add_user_meta( $user_id, 'noteToCustomer', $params['noteToCustomer'], true );
					}
					if ( isset( $params['noteInternal'] ) && ! empty( $params['noteInternal'] ) ) {
						add_user_meta( $user_id, 'noteInternal', $params['noteInternal'], true );
					}
				}
			}

			if ( isset( $params['tab'] ) ) {
				wp_clear_auth_cookie();
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
			}
			$data = array(
				'success' => true,
				'message' => __( 'Customer was added successfully.', 'talika' ),
			);
		} else {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => $user_id->get_error_message(),
					'customer' => array(),
				)
			);
		}
		$user             = get_user_by( 'id', $user_id );
		$response         = $this->prepare_item_for_response( $user, $request );
		$data['customer'] = $this->prepare_response_for_collection( $response );
		return $data;
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
					'customer' => array(),
				)
			);
		} elseif ( ! empty( $id ) ) {
			$user = get_user_by( 'id', $id );
			if ( ! $user ) {
				return rest_ensure_response(
					array(
						'success'  => false,
						'message'  => __( 'Customer not found by Id. Please try again.', 'talika' ),
						'customer' => array(),
					)
				);
			}
		}
		$user_meta = array(
			'customerImage',
			'customerPhone',
			'gender',
			'dateOfBirth',
			'address',
			'noteToCustomer',
			'noteInternal',
		);
		$userdata  = array(
			'ID' => $id,
		);
		if ( isset( $params['user_email'] ) && ! empty( $params['user_email'] ) ) {
			$userdata['user_email'] = $params['user_email'];
		}
		if ( isset( $params['first_name'] ) && ! empty( $params['first_name'] ) ) {
			$userdata['first_name'] = $params['first_name'];
		}
		if ( isset( $params['last_name'] ) && ! empty( $params['last_name'] ) ) {
			$userdata['last_name'] = $params['last_name'];
		}
		$user_id = wp_update_user( $userdata );
		if ( is_wp_error( $user_id ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( $user_id->get_error_message(), 'talika' ),
					'customer' => array(),
				)
			);
		} elseif ( ! is_wp_error( $user_id ) ) {
			$user = new WP_User( $id );
			foreach ( $user->roles as $role ) {
				if ( $role === 'talika_customer' ) {
					foreach ( $user_meta as $meta ) {
						if ( isset( $params[ $meta ] ) ) {
							update_user_meta( $id, $meta, $params[ $meta ] );
						}
					}
				}
			}
			$data = array(
				'success' => true,
				'message' => __( 'Customer data updated successfully.', 'talika' ),
			);
		}
		$user             = get_user_by( 'id', $id );
		$response         = $this->prepare_item_for_response( $user, $request );
		$data['customer'] = $this->prepare_response_for_collection( $response );
		return $data;
	}

	/**
	 * Random username together
	 *
	 * @return void
	 */
	function generate_unique_username( $email ) {
		$username = sanitize_user( current( explode( '@', $email ) ), true );
		// Ensure username is unique.
		$append     = 1;
		$o_username = $username;
		while ( username_exists( $username ) ) {
			$username = $o_username . $append;
			$append++;
		}
		return $username;
	}

	/**
	 * Grabs a single Enquiry if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$user = get_user_by( 'id', $id );

		$data = array(
			'success' => true,
			'message' => __( 'Customer Found.', 'talika' ),
		);
		if ( ! $user ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Customer not found by given user ID.', 'talika' ),
					'data'    => array(),
				)
			);
		}

		$response         = $this->prepare_item_for_response( $user, $request );
		$data['customer'] = $this->prepare_response_for_collection( $response );
		// Return all of our post response data.
		return $data;
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
function talika_register_customers_rest_routes() {
	$controller = new Talika_Customers_REST_Controller( TALIKA_CUSTOMERS );
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_customers_rest_routes' );
