<?php

/**
 * REST API: Talika_Appointment_REST_Controller class
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
class Talika_Appointment_REST_Controller extends Talika_API_Controller {
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
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_appointment' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
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
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$params    = $request->get_json_params();
		$id        = isset( $request['id'] ) ? (int) $request['id'] : '';
		$post_meta = array(
			'customers',
			'category',
			'service',
			'location',
			'staff',
			'appointmentStatus',
			'appointmentDate',
			'appointmentTime',
			'notifyCustomers',
			'noteToCustomer',
			'noteInternal',
			'appointmentPayment',
		);
		if ( empty( $id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'service' => array(),
				)
			);
		} elseif ( ! empty( $id ) ) {
			$post = get_post( $id );
			if ( ! $post ) {
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'No appointment found by given Id. Please try again.', 'talika' ),
						'service' => array(),
					)
				);
			} else {
			}
		}
		if ( isset( $params['customers'] ) && empty( $params['customers'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'customer is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( isset( $params['category'] ) && empty( $params['category'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Service Category is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( isset( $params['service'] ) && empty( $params['service'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Service is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if (isset( $params['location'] ) && empty( $params['location'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Location is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if (isset( $params['staff'] ) && empty( $params['staff'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Staff is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if (isset( $params['appointmentStatus'] ) && empty( $params['appointmentStatus'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Status is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if (isset( $params['appointmentDate'] ) && empty( $params['appointmentDate'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Date is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if (isset( $params['appointmentTime'] ) && empty( $params['appointmentTime'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Time is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		$post_id = wp_update_post(
			array(
				'ID'          => $id,
				'post_type'   => $this->base_name,
				'post_status' => 'publish',
			)
		);
		if ( ! is_wp_error( $post_id ) ) {

			$appointment_status = get_post_meta( $post_id, 'appointmentStatus', true ); // saving the old status.
			
			foreach ( $post_meta as $meta ) {
				if ( isset( $params[ $meta ] ) ) {
					update_post_meta( $id, $meta, $params[ $meta ] );
				}
			}

			do_action( 'talika_status_notifications', $post_id, $appointment_status, $params['appointmentStatus'] );

			$data = array(
				'success' => true,
				'message' => __( 'Appointment data updated.', 'talika' ),
			);
		}
		$post                = get_post( $id );
		$response            = $this->prepare_appointment_for_response( $post, $request );
		$data['appointment'] = $this->prepare_response_for_collection( $response );

		return $data;
	}

	/**
	 * Grabs the five most recent posts and outputs them as a rest response.
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

		$start    = $request->get_param( 'start' ) ? $request->get_param( 'start' ) : false;
		$end      = $request->get_param( 'end' ) ? $request->get_param( 'end' ) : false;
		$filterby = $request->get_param( 'filterby' );
		$filterby = $filterby ?  (array) json_decode( $filterby ) : false;

		if( $start && $end ) {
			$args['orderby']    = 'meta_value';
			$args['order']      = 'DESC';
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => 'appointmentDate',
					'value'   => array( $start, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			);
		}

		if( $filterby ) {
			if( isset( $filterby['staffId'] ) && $filterby['staffId'] ) {
				$args['meta_query'][] = array(
					'key'     => 'staff',
					'value'   => $filterby['staffId'],
					'compare' => 'LIKE',
				);
			} elseif( isset( $filterby['customerId'] ) && $filterby['customerId'] ) {
				$args['meta_query'][] = array(
					'key'     => 'customers',
					'value'   => $filterby['customerId'],
					'compare' => 'LIKE',
				);
			} elseif( isset( $filterby['serviceId'] ) && $filterby['serviceId'] ) {
				$args['meta_query'][] = array(
					'key'     => 'service',
					'value'   => $filterby['serviceId'],
					'compare' => 'LIKE',
				);
			} elseif( isset( $filterby['action'] ) && $filterby['action'] ) {
				$args['meta_query'][] = array(
					'key'     => 'appointmentStatus',
					'value'   => $filterby['action'],
					'compare' => 'LIKE',
				);
			} elseif( isset( $filterby['keywordSearch'] ) && $filterby['keywordSearch'] ) {
				$args['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'     => 'customers',
						'value'   => $filterby['keywordSearch'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'service',
						'value'   => $filterby['keywordSearch'],
						'compare' => 'LIKE',
					),
					array(
						'key'     => 'staff',
						'value'   => $filterby['keywordSearch'],
						'compare' => 'LIKE',
					),
				);
			}
		}

		foreach ( $mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		$posts_query = new WP_Query( $args );

		$posts = $posts_query->get_posts();

		$data = array(
			'success'      => true,
			'message'      => __( 'Appointments Found.', 'talika' ),
			'appointments' => array(),
		);

		if ( empty( $posts ) ) {
			return rest_ensure_response(
				array(
					'success'      => false,
					'message'      => __( 'No Appointments Found.', 'talika' ),
					'appointments' => array(),
				)
			);
		}

		foreach ( $posts as $post ) {
			$response               = $this->prepare_appointment_for_response( $post, $request );
			$data['appointments'][] = $this->prepare_response_for_collection( $response );
			$data['totalPages']     = (int) $posts_query->max_num_pages;
		}

		$response = rest_ensure_response( $data );

		$response->header( 'X-WP-Total', (int) $posts_query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $posts_query->max_num_pages );

		// Return all of our comment response data.
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
			'message' => __( 'Appointment Found.', 'talika' ),
		);
		if ( empty( $post ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Appointment not found by ID.', 'talika' ),
					'data'    => array(),
				)
			);
		}

		$response     = $this->prepare_appointment_for_response( $post, $request );
		$data['data'] = $this->prepare_response_for_collection( $response );
		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs a single Enquiry if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_appointment( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params['customers'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Customer is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( empty( $params['category'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Service Category is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( empty( $params['service'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Service is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
	
		if ( empty( $params['location'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Location is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( empty( $params['staff'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Staff is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( empty( $params['appointmentDate'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Date is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( empty( $params['appointmentTime'] ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Time is a required field', 'talika' ),
					'appointment' => array(),
				)
			);
		}
		if ( empty( $params ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'No any data was passed. Please try again.', 'talika' ),
					'appointment' => array(),
				)
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => $this->base_name,
				'post_title'  => isset( $params['appointmentDate'] ) ? sanitize_text_field( $params['appointmentDate'] ) : '',
				'post_status' => 'publish',
			)
		);

		if ( empty( $post_id ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( 'Couldnot add appointment. Please try again.', 'talika' ),
					'appointment' => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'Appointment was added successfully.', 'talika' ),
		);

		if ( $post_id ) {

			$to_be_update = array(
				'ID'         => $post_id,
				'post_title' => 'wpa-appointment-' . $post_id,
			);
			wp_update_post( $to_be_update );
			if ( isset( $params['customers'] ) ) {
				add_post_meta( $post_id, 'customers', $params['customers'] );
			}
			if ( isset( $params['category'] ) ) {
				add_post_meta( $post_id, 'category', $params['category'] );
			}
			if ( isset( $params['service'] ) ) {
				add_post_meta( $post_id, 'service', $params['service'] );
			}
			if ( isset( $params['location'] ) ) {
				add_post_meta( $post_id, 'location', $params['location'] );
			}
			if ( isset( $params['staff'] ) ) {
				add_post_meta( $post_id, 'staff', $params['staff'] );
			}
			if ( isset( $params['appointmentDate'] ) ) {
				add_post_meta( $post_id, 'appointmentDate', sanitize_text_field( $params['appointmentDate'] ) );
			}
			if ( isset( $params['appointmentTime'] ) ) {
				add_post_meta( $post_id, 'appointmentTime', $params['appointmentTime'] );
			}
			if ( isset( $params['notifyCustomers'] ) ) {
				add_post_meta( $post_id, 'notifyCustomers', $params['notifyCustomers'] );
			}
			if ( isset( $params['noteToCustomer'] ) ) {
				add_post_meta( $post_id, 'noteToCustomer', sanitize_text_field( $params['noteToCustomer'] ) );
			}
			if ( isset( $params['noteInternal'] ) ) {
				add_post_meta( $post_id, 'noteInternal', sanitize_text_field( $params['noteInternal'] ) );
			}
			if ( isset( $params['appointmentPayment'] ) ) {
				add_post_meta( $post_id, 'appointmentPayment', $params['appointmentPayment'] );
			}

			$generalSettings = talika_get_global_general_settings();
			$defaultStatus = isset( $generalSettings['appointmentStatus'] ) ? $generalSettings['appointmentStatus'] : ['value' => 'approved', 'label' => 'Approved'];
			$appointmentStatus = isset( $params['appointmentStatus'] ) && ! empty( $params['appointmentStatus'] ) ? $params['appointmentStatus'] : $defaultStatus;
			add_post_meta( $post_id, 'appointmentStatus', $appointmentStatus );

			$post                = get_post( $post_id );
			$response            = $this->prepare_appointment_for_response( $post, $request );
			$data['appointment'] = $this->prepare_response_for_collection( $response );
		}

		// Return all of our post response data.
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
		$id     = $request['id'];

		if ( empty( $id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'appointment' => array(),
				)
			);
		} else {
			$post = get_post( $id );
			if ( ! $post ) {
				return rest_ensure_response(
					array(
						'success'     => false,
						'message'     => __( 'No appointment found by given Id. Please try again.', 'talika' ),
						'appointment' => array(),
					)
				);
			}
		}

		$post_id = wp_delete_post( $id, true );
		if ( is_wp_error( $post_id ) ) {
			return rest_ensure_response(
				array(
					'success'     => false,
					'message'     => __( $post_id->get_error_message(), 'talika' ),
					'appointment' => array(),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success'     => true,
					'message'     => __( 'Appointment deleted successfully.', 'talika' ),
					'appointment' => array(),
				)
			);
		}
	}

	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param WP_Post $post The comment object whose response is being prepared.
	 */
	public function prepare_appointment_for_response( $post, $request ) {

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
			'title'      => TALIKA_POST_TYPE,
			'type'       => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'ID'                 => array(
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
				'appointmentDate'    => array(
					'description' => __( 'The appointment date.', 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'appointmentTime'    => array(
					'description' => __( 'The appointment time.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'modified'           => array(
					'description' => __( "The date the object was last modified, in the site's timezone.", 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt'       => array(
					'description' => __( 'The date the object was last modified, as GMT.', 'talika' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'             => array(
					'description' => __( 'A named status for the object.', 'talika' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'type'               => array(
					'description' => __( 'Type of Post for the object.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'customers'          => array(
					'description' => __( 'Customer(s) who have requested for service.', 'talika' ),
					'type'        => 'array',
				),
				'service'            => array(
					'description' => __( 'Service that was requested.', 'talika' ),
					'type'        => 'array',
				),
				'location'           => array(
					'description' => __( 'Location that was requested.', 'talika' ),
					'type'        => 'array',
				),
				'category'           => array(
					'description' => __( 'Category that was requested.', 'talika' ),
					'type'        => 'array',
				),
				'staff'              => array(
					'description' => __( 'Employee who has been assigned the service to.', 'talika' ),
					'type'        => 'array',
				),
				'notifyCustomers'    => array(
					'description' => __( 'Control either to send email notification to user about appointment added.', 'talika' ),
					'type'        => 'string',
				),
				'appointmentStatus'  => array(
					'description' => __( 'Status about the appointment based on accepted or payment.', 'talika' ),
					'type'        => 'string',
				),
				'noteToCustomer'     => array(
					'description' => __( 'Note to the customer or special request on information by the customer.', 'talika' ),
					'type'        => 'string',
				),
				'noteInternal'       => array(
					'description' => __( 'Note to the internally customer or special request on information by the customer.', 'talika' ),
					'type'        => 'string',
				),
				'appointmentPayment' => array(
					'description' => __( 'Payment Details of appointment.', 'talika' ),
					'type'        => 'array',
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
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
	 * Check if a given request has access to create items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		// return current_user_can( 'manage_options' );
		// Check edit prevlages.
		if ( ! current_user_can( 'talika_edit_appointments' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot edit the post resource.', 'talika' ), array( 'status' => $this->authorization_status_code() ) );
		}
		return true;
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

		// Can't use $this->get_object_type otherwise we cause an inf loop.
		$object_type = $schema['title'];

		return $schema;
	}
}

// Function to register our new routes from the controller.
function talika_register_appointment_rest_routes() {
	$controller = new Talika_Appointment_REST_Controller( TALIKA_POST_TYPE );
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_appointment_rest_routes' );
