<?php

/**
 * REST API: Talika_Services_REST_Controller class
 *
 * @package WP Services API Core
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with Services.
 *
 * @since 1.0.0
 */
class Talika_Services_REST_Controller extends Talika_API_Controller {

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
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_service' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_service_schema' ),
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
					'callback'            => array( $this, 'get_service' ),
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
				'schema'              => array( $this, 'get_service_schema' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/categories',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_categories' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_category' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/categories' . '/(?P<id>[\d]+)',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_category' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_category' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_category' ),
					'permission_callback' => array( $this, 'post_item_permissions_check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_category' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);
	}

	public function register_talika_service_meta_rest() {
		register_rest_field(
			$this->namespace,
			'wpa_services_metadata',
			array(
				'get_callback'    => 'talika_service_meta_callback',
				'update_callback' => null,
				'schema'          => null,
			)
		);
		$meta_args = array( // Validate and sanitize the meta value.

			'type'         => 'string',
			// Shown in the schema for the meta key.
			'description'  => 'A meta key associated with a string meta value.',
			// Return a single value of the type.
			'single'       => true,
			// Show in the WP REST API response. Default: false.
			'show_in_rest' => true,
		);
		register_meta( $this->base_name, 'wpa_services_metadata', $meta_args );
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

	/**
	 * Return user meta object.
	 *
	 * @param array  $user User.
	 * @param string $field_name Registered custom field name ( In this case 'user_meta' )
	 * @param object $request Request object.
	 *
	 * @return mixed
	 */
	function talika_service_meta_callback( $postid, $field_name, $request ) {
		return get_post_meta( $postid );
	}
	/**
	 * Grabs the  most recent services and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $requests Current request.
	 */
	public function get_items( $request ) {
		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$mappings   = array(
			'posts_per_page' => -1,
			'page'           => 'paged',
		);
		$args       = array(
			'orderby'   => 'menu_order',
			'post_type' => $this->base_name,
		);
		if ( isset( $request['cat'] ) && ! empty( $request['cat'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'wpa-service-category',
					'field'    => 'term_id',
					'terms'    => absint( $request['cat'] ),
				),
			);
		}

		foreach ( $mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}
		$posts_query = new WP_Query( $args );

		$posts = $posts_query->get_posts();

		$data = array(
			'success'  => true,
			'message'  => __( 'Services Found.', 'talika' ),
			'services' => array(),
		);

		if ( empty( $posts ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No Services Found.', 'talika' ),
					'services' => array(),
				)
			);
		}

		$metas     = array();
		$post_meta = array(
			'serviceFeaturedImage',
			'serviceGallery',
			'serviceCategory',
			'serviceColor',
			'serviceDuration',
			'servicePrice',
			'serviceStatus',
			'serviceDepositeMode',
			'serviceDepositeType',
			'serviceDepositeAmount',
			'serviceBufferTimeBefore',
			'serviceBufferTimeAfter',
			'serviceMaxCapacity',
			'serviceMinCapacity',
			'serviceStaff',
			'serviceStaffListing',
			'serviceHours',
		);

		foreach ( $posts as $post ) {
			foreach ( $post_meta as $meta ) {
				if ( 'serviceStaffListing' === $meta ) {
					$serviceStaffList = array();
					$staff            = get_post_meta( $post->ID, 'serviceStaff', true );
					if ( ! empty( $staff ) ) {
						foreach ( $staff as $id => $value ) {
							$user_meta = get_user_meta( $id, 'additionalInformations', true );
							if ( ! empty( $user_meta['staffProfileImage'] ) ) {
								$serviceStaffList[ $id ] = $user_meta['staffProfileImage']['0']['thumb'];
							}
						}
					}
					$metas[ $meta ] = $serviceStaffList;
				} else {
					$metas[ $meta ] = get_post_meta( $post->ID, $meta, true );
				}
			}

			$post = (object) array_merge( $metas, (array) $post );

			$response           = $this->prepare_service_for_response( $post, $request );
			$data['services'][] = $this->prepare_response_for_collection( $post );
		}

		$response = rest_ensure_response( $data );

		// Return all of our comment response data.
		return $response;
	}

	/**
	 * Grabs a single Service if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_service( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );

		$data = array(
			'success' => true,
			'message' => __( 'Service Found.', 'talika' ),
		);
		if ( empty( $post ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Service not found by ID.', 'talika' ),
					'data'    => array(),
				)
			);
		}

		$metas     = array();
		$post_meta = array(
			'serviceFeaturedImage',
			'serviceGallery',
			'serviceCategory',
			'serviceColor',
			'serviceDuration',
			'servicePrice',
			'serviceStatus',
			'serviceDepositeMode',
			'serviceDepositeType',
			'serviceDepositeAmount',
			'serviceBufferTimeBefore',
			'serviceBufferTimeAfter',
			'serviceMaxCapacity',
			'serviceMinCapacity',
			'serviceStaff',
			'serviceStaffListing',
			'serviceHours',
		);

		foreach ( $post_meta as $meta ) {
			if ( 'serviceStaffListing' === $meta ) {
				$serviceStaffList = array();
				$staff            = get_post_meta( $post->ID, 'serviceStaff', true );
				if ( ! empty( $staff ) ) {
					foreach ( $staff as $id => $value ) {
						$user_meta = get_user_meta( $id, 'additionalInformations', true );
						if ( ! empty( $user_meta['staffProfileImage'] ) ) {
							$serviceStaffList[ $id ] = $user_meta['staffProfileImage']['0']['thumb'];
						}
					}
				}
				$metas[ $meta ] = $serviceStaffList;
			} else {
				$metas[ $meta ] = get_post_meta( $post->ID, $meta, true );
			}
		}

		$post = (object) array_merge( $metas, (array) $post );

		$response        = $this->prepare_service_for_response( $post, $request );
		$data['service'] = $this->prepare_response_for_collection( $post );

		$response = rest_ensure_response( $data );

		// Return all of our post response data.
		return $response;
	}

	/**
	 * Update Service categories
	 *
	 * @param [type] $request
	 */
	public function update_items( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) || empty( $params['sortedServices'] ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No any data was passed. Please try again.', 'talika' ),
					'services' => array(),
				)
			);
		}

		if ( isset( $params['updateServiceOrder'] ) && $params['updateServiceOrder'] ) {

			global $wpdb;

			if ( ! isset( $params['sortedServices'] ) || ! is_array( $params['sortedServices'] ) ) {
				return false;
			}

			$id_arr = array();
			foreach ( $params['sortedServices'] as $key => $service ) {
				$id_arr[] = $service['ID'];
			}

			$menu_order_arr = array();
			foreach ( $id_arr as $key => $id ) {
				
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT menu_order FROM $wpdb->posts WHERE ID = %d",
						intval( $id )
					)
				);
				foreach ( $results as $result ) {
					$menu_order_arr[] = $result->menu_order;
				}
			}
			sort( $menu_order_arr );

			foreach ( $params['sortedServices'] as $position => $post ) {
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order_arr[ $position ] ), array( 'ID' => intval( $post['ID'] ) ) );
			}

			// same number check
			$post_type = get_post_type( $post['ID'] );
			
			$results = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT COUNT(menu_order) AS mo_count, post_type, menu_order 
					FROM $wpdb->posts
					WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
					AND menu_order > 0 GROUP BY post_type, menu_order HAVING (mo_count) > 1",
					$post_type
				)
			);

			if ( count( $results ) > 0 ) {
				// menu_order refresh
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID, menu_order FROM $wpdb->posts
						WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
						AND menu_order > 0 ORDER BY menu_order",
						$post_type
					)
				);

				foreach ( $results as $key => $result ) {
					$view_posi = array_search( $result->ID, $id_arr, true );
					if ( $view_posi === false ) {
						$view_posi = 999;
					}
					$sort_key              = ( $result->menu_order * 1000 ) + $view_posi;
					$sort_ids[ $sort_key ] = $result->ID;
				}
				ksort( $sort_ids );
				$oreder_no = 0;
				foreach ( $sort_ids as $key => $id ) {
					$oreder_no = $oreder_no + 1;
					$wpdb->update( $wpdb->posts, array( 'menu_order' => $oreder_no ), array( 'ID' => intval( $id ) ) );
				}
			}

			$data = array(
				'success'  => true,
				'message'  => __( 'Service Order Updated.', 'talika' ),
				'post_url' => get_the_permalink( $id ),
			);
		}

		return $data;
	}

	/**
	 * Create one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function post_service( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params['post_title'] ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Service Title is a required field', 'talika' ),
					'service' => array(),
				)
			);
		}
		if ( empty( $params ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'No any data was passed. Please try again.', 'talika' ),
					'service' => array(),
				)
			);
		}
		$post_id = wp_insert_post(
			array(
				'post_type'    => $this->base_name,
				'post_title'   => isset( $params['post_title'] ) ? sanitize_text_field( $params['post_title'] ) : '',
				'post_content' => isset( $params['post_content'] ) ? sanitize_text_field( $params['post_content'] ) : '',
				'post_status'  => 'publish',
			)
		);
		if ( empty( $post_id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Couldnot add Service. Please try again.', 'talika' ),
					'service' => array(),
				)
			);
		}
		$data = array(
			'success' => true,
			'message' => __( 'Service was added successfully.', 'talika' ),
		);
		if ( $post_id ) {
			if ( isset( $params['serviceFeaturedImage'] ) ) {
				add_post_meta( $post_id, 'serviceFeaturedImage', $params['serviceFeaturedImage'] );
			}
			if ( isset( $params['serviceGallery'] ) ) {
				add_post_meta( $post_id, 'serviceGallery', $params['serviceGallery'] );
			}
			if ( isset( $params['serviceCategory'] ) ) {
				add_post_meta( $post_id, 'serviceCategory', $params['serviceCategory'] );
			}
			if ( isset( $params['serviceColor'] ) ) {
				add_post_meta( $post_id, 'serviceColor', sanitize_hex_color( $params['serviceColor'] ) );
			}
			if ( isset( $params['serviceDuration'] ) ) {
				add_post_meta( $post_id, 'serviceDuration', $params['serviceDuration'] );
			}
			if ( isset( $params['servicePrice'] ) ) {
				add_post_meta( $post_id, 'servicePrice', sanitize_text_field( $params['servicePrice'] ) );
			}
			if ( isset( $params['serviceDepositeMode'] ) ) {
				add_post_meta( $post_id, 'serviceDepositeMode', sanitize_text_field( $params['serviceDepositeMode'] ) );
			}
			if ( isset( $params['serviceDepositeType'] ) ) {
				add_post_meta( $post_id, 'serviceDepositeType', $params['serviceDepositeType'] );
			}
			if ( isset( $params['serviceDepositeAmount'] ) ) {
				add_post_meta( $post_id, 'serviceDepositeAmount', sanitize_text_field( $params['serviceDepositeAmount'] ) );
			}
			if ( isset( $params['serviceBufferTimeBefore'] ) ) {
				add_post_meta( $post_id, 'serviceBufferTimeBefore', $params['serviceBufferTimeBefore'] );
			}
			if ( isset( $params['serviceBufferTimeAfter'] ) ) {
				add_post_meta( $post_id, 'serviceBufferTimeAfter', $params['serviceBufferTimeAfter'] );
			}
			if ( isset( $params['serviceMaxCapacity'] ) ) {
				add_post_meta( $post_id, 'serviceMaxCapacity', sanitize_text_field( $params['serviceMaxCapacity'] ) );
			}
			if ( isset( $params['serviceMinCapacity'] ) ) {
				add_post_meta( $post_id, 'serviceMinCapacity', sanitize_text_field( $params['serviceMinCapacity'] ) );
			}
			if ( isset( $params['serviceStaff'] ) ) {
				add_post_meta( $post_id, 'serviceStaff', $params['serviceStaff'] );
			}
			if ( isset( $params['serviceHours'] ) ) {
				add_post_meta( $post_id, 'serviceHours', $params['serviceHours'] );
			}
			add_post_meta( $post_id, 'serviceStatus', 'visible' );
			$post            = get_post( $post_id );
			$response        = $this->prepare_service_for_response( $post, $request );
			$data['service'] = $this->prepare_response_for_collection( $response );
		}

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs the five most recent posts and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_categories( $request ) {

		$terms = get_terms(
			array(
				'taxonomy'   => 'wpa-service-category',
				'hide_empty' => false,
			)
		);

		$data = array(
			'success'            => true,
			'message'            => __( 'Services Categories Found.', 'talika' ),
			'servicesCategories' => array(),
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return rest_ensure_response(
				array(
					'success'            => false,
					'message'            => __( 'Service categories not found.', 'talika' ),
					'servicesCategories' => array(),
				)
			);
		}

		if ( ! empty( $terms ) || ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term->catImage               = get_term_meta( $term->term_id, 'catImage', true );
				$term->catColor               = get_term_meta( $term->term_id, 'catColor', true );
				$term->catStatus              = get_term_meta( $term->term_id, 'catStatus', true );
				$data['servicesCategories'][] = $this->prepare_response_for_collection( $term );
			}
		}

		$response = rest_ensure_response( $data );

		// Return all of our comment response data.
		return $response;
	}

	/**
	 * Grabs the single service category as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_category( $request ) {
		$id   = (int) $request['id'];
		$term = get_term_by( 'ID', $id, 'wpa-service-category' );

		$data = array(
			'success' => true,
			'message' => __( 'Service Category Found by ID.', 'talika' ),
		);
		if ( empty( $term ) ) {
			return rest_ensure_response(
				array(
					'success'         => false,
					'message'         => __( 'Service category not found by ID.', 'talika' ),
					'serviceCategory' => array(),
				)
			);
		}

		if ( ! empty( $term ) || ! is_wp_error( $term ) ) {
			$term->catImage          = get_term_meta( $id, 'catImage', true );
			$term->catColor          = get_term_meta( $id, 'catColor', true );
			$term->catStatus         = get_term_meta( $id, 'catStatus', true );
			$data['serviceCategory'] = $this->prepare_response_for_collection( $term );
		}
		$response = rest_ensure_response( $data );
		// Return all of our post response data.
		return $response;
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
			'serviceFeaturedImage',
			'serviceGallery',
			'serviceCategory',
			'serviceStatus',
			'serviceColor',
			'serviceDuration',
			'servicePrice',
			'serviceDepositeMode',
			'serviceDepositeType',
			'serviceDepositeAmount',
			'serviceBufferTimeBefore',
			'serviceBufferTimeAfter',
			'serviceMaxCapacity',
			'serviceMinCapacity',
			'serviceStaff',
			'serviceHours',
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
						'message' => __( 'No service found by given Id. Please try again.', 'talika' ),
						'service' => array(),
					)
				);
			} else {
			}
		}
		$post_id = wp_update_post(
			array(
				'ID'           => $id,
				'post_type'    => $this->base_name,
				'post_title'   => isset( $params['post_title'] ) ? sanitize_text_field( $params['post_title'] ) : '',
				'post_content' => isset( $params['post_content'] ) ? sanitize_text_field( $params['post_content'] ) : '',
				'post_status'  => 'publish',
			)
		);
		if ( ! is_wp_error( $post_id ) ) {
			foreach ( $post_meta as $meta ) {
				if ( isset( $params[ $meta ] ) ) {
					if ( 'serviceStatus' === $meta ) {
						update_post_meta( $id, 'serviceStatus', isset( $params['serviceStatus'] ) ? sanitize_text_field( $params['serviceStatus'] ) : 'visible' );
					} else {
						update_post_meta( $id, $meta, $params[ $meta ] );
					}
				}
			}
			$data = array(
				'success' => true,
				'message' => __( 'Service settings updated.', 'talika' ),
			);
		}
		$post            = get_post( $id );
		$response        = $this->prepare_service_for_response( $post, $request );
		$data['service'] = $this->prepare_response_for_collection( $response );

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
					'success' => false,
					'message' => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'service' => array(),
				)
			);
		} else {
			$post = get_post( $id );
			if ( ! $post ) {
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'No service found by given Id. Please try again.', 'talika' ),
						'service' => array(),
					)
				);
			}
		}

		$post_id = wp_delete_post( $id, true );
		if ( is_wp_error( $post_id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( $post_id->get_error_message(), 'talika' ),
					'service' => array(),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'Service deleted successfully.', 'talika' ),
					'service' => array(),
				)
			);
		}
	}

	/**
	 * Grabs a single Enquiry if vald id is provided.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_category( $request ) {
		$formdata = $request->get_json_params();
		if ( empty( $formdata ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No any data was passed. Please try again.', 'talika' ),
					'category' => array(),
				)
			);
		}
		require_once ABSPATH . '/wp-admin/includes/taxonomy.php';

		$cat_id = wp_insert_category(
			array(
				'taxonomy'             => 'wpa-service-category',
				'cat_name'             => isset( $formdata['title'] ) ? sanitize_text_field( $formdata['title'] ) : '',
				'category_description' => isset( $formdata['catDescription'] ) ? sanitize_text_field( $formdata['catDescription'] ) : '',
			)
		);
		if ( empty( $cat_id ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'Couldnot Add Service Category. Please try again.', 'talika' ),
					'category' => array(),
				)
			);
		}
		$data = array(
			'success' => true,
			'message' => __( 'Service category added successfully.', 'talika' ),
		);

		if ( $cat_id ) {
			add_term_meta( $cat_id, 'catImage', $formdata['catImage'] );
			add_term_meta( $cat_id, 'catColor', isset( $formdata['catColor'] ) ? sanitize_hex_color( $formdata['catColor'] ) : '' );
			add_term_meta( $cat_id, 'catStatus', isset( $formdata['catStatus'] ) ? sanitize_text_field( $formdata['catStatus'] ) : 'visible' );

			$category            = get_term( $cat_id );
			$category->catImage  = get_term_meta( $cat_id, 'catImage', true );
			$category->catColor  = get_term_meta( $cat_id, 'catColor', true );
			$category->catStatus = get_term_meta( $cat_id, 'catStatus', true );
			$response            = $this->prepare_item_for_response( $category, $request );
			$data['category']    = $this->prepare_response_for_collection( $response );
		}

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Update one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_category( $request ) {
		$params      = $request->get_json_params();
		$id          = isset( $params['term_id'] ) ? $params['term_id'] : $request['id'];
		$name        = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : $params['name'];
		$description = isset( $params['catDescription'] ) ? sanitize_text_field( $params['catDescription'] ) : term_description( $id );

		if ( empty( $id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'data'    => array(),
				)
			);
		} elseif ( ! empty( $id ) ) {
			$cat = get_term( $id );
			if ( ! $cat ) {
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'No category found by given Id. Please try again.', 'talika' ),
						'data'    => array(),
					)
				);
			}
		}
		if ( ! empty( $name ) ) {
			$cat_id = wp_update_term(
				$id,
				'wpa-service-category',
				array(
					'name'        => $name,
					'description' => $description,
				)
			);
			if ( is_array( $cat_id ) ) {
				$cat_id = $cat_id['term_id'];
			}
		} else {
			$cat_id = $id;
		}
		if ( is_wp_error( $cat_id ) && ! empty( $cat_id->errors ) ) {

			$data = array(
				'success' => false,
				'message' => $cat_id->errors,
			);
		} else {
			if ( isset( $params['catImage'] ) ) {
				update_term_meta( $cat_id, 'catImage', $params['catImage'] );
			}
			if ( isset( $params['catColor'] ) ) {
				update_term_meta( $cat_id, 'catColor', isset( $params['catColor'] ) ? sanitize_hex_color( $params['catColor'] ) : '' );
			}
			if ( isset( $params['catStatus'] ) ) {
				update_term_meta( $cat_id, 'catStatus', isset( $params['catStatus'] ) ? sanitize_text_field( $params['catStatus'] ) : 'visible' );
			}

			$data = array(
				'success' => true,
				'message' => __( 'Category updated successfully!', 'talika' ),
			);
		}
		$category            = get_term( $id );
		$category->catImage  = isset( $params['catImage'] ) ? $params['catImage'] : get_term_meta( $cat_id, 'catImage', true );
		$category->catColor  = isset( $params['catColor'] ) ? sanitize_text_field( $params['catColor'] ) : get_term_meta( $cat_id, 'catColor', true );
		$category->catStatus = isset( $params['catStatus'] ) ? sanitize_text_field( $params['catStatus'] ) : get_term_meta( $cat_id, 'catStatus', true );
		$response            = $this->prepare_item_for_response( $category, $request );
		$data['category']    = $this->prepare_response_for_collection( $response );

		return $data;
	}

	/**
	 * Delete one category from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_category( $request ) {
		$id = $request->get_json_params();

		if ( empty( $id ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Id not valid or empty. Please try again.', 'talika' ),
					'service' => array(),
				)
			);
		} else {
			$cat = get_term( $id );
			if ( ! $cat ) {
				return rest_ensure_response(
					array(
						'success' => false,
						'message' => __( 'No category found by given Id. Please try again.', 'talika' ),
						'data'    => array(),
					)
				);
			}
		}

		$delete = wp_delete_term( (int) $id, 'wpa-service-category' );
		if ( is_wp_error( $delete ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( $delete->get_error_message(), 'talika' ),
					'service' => array(),
				)
			);
		} else {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => __( 'Category deleted successfully.', 'talika' ),
					'service' => array(),
				)
			);
		}
	}

	/**
	 * Update Service categories
	 *
	 * @param [type] $request
	 */
	public function update_categories( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) || empty( $params['sortedCats'] ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No any data was passed. Please try again.', 'talika' ),
					'category' => array(),
				)
			);
		}

		if ( isset( $params['updateCatOrder'] ) && $params['updateCatOrder'] ) {

			global $wpdb;

			if ( ! isset( $params['sortedCats'] ) || ! is_array( $params['sortedCats'] ) ) {
				return false;
			}

			$id_arr = array();
			foreach ( $params['sortedCats'] as $key => $cat ) {
				$id_arr[] = $cat['term_id'];
			}

			$menu_order_arr = array();
			foreach ( $id_arr as $key => $id ) {
				
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT term_order FROM $wpdb->terms WHERE term_id = %d",
						intval( $id )
					)
				);
				foreach ( $results as $result ) {
					$menu_order_arr[] = $result->term_order;
				}
			}
			sort( $menu_order_arr );

			foreach ( $params['sortedCats'] as $position => $term ) {
				$wpdb->update( $wpdb->terms, array( 'term_order' => $menu_order_arr[ $position ] ), array( 'term_id' => intval( $term['term_id'] ) ) );
			}

			// same number check
			$term     = get_term( $id );
			$taxonomy = $term->taxonomy;
			
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(term_order) AS to_count, term_order
					FROM $wpdb->terms AS terms
					INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
					WHERE term_taxonomy.taxonomy = %s GROUP BY taxonomy, term_order HAVING (to_count) > 1",
					$taxonomy
				)
			);

			if ( count( $results ) > 0 ) {
				// term_order refresh
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT terms.term_id, term_order
						FROM $wpdb->terms AS terms
						INNER JOIN $wpdb->term_taxonomy AS term_taxonomy ON ( terms.term_id = term_taxonomy.term_id )
						WHERE term_taxonomy.taxonomy = %s
						ORDER BY term_order ASC",
						$taxonomy
					)
				);
				
				foreach ( $results as $key => $result ) {
					$view_posi = array_search( $result->term_id, $id_arr, true );
					if ( $view_posi === false ) {
						$view_posi = 999;
					}
					$sort_key              = ( $result->term_order * 1000 ) + $view_posi;
					$sort_ids[ $sort_key ] = $result->term_id;
				}
				ksort( $sort_ids );
				$oreder_no = 0;
				foreach ( $sort_ids as $key => $id ) {
					$oreder_no = $oreder_no + 1;
					$wpdb->update( $wpdb->terms, array( 'term_order' => $oreder_no ), array( 'term_id' => $id ) );
				}
			}

			$data = array(
				'success'  => true,
				'message'  => __( 'Categories Order Updated.', 'talika' ),
				'post_url' => get_the_permalink( $id ),
			);
		}

		return $data;
	}
	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param WP_Post $post The comment object whose response is being prepared.
	 */
	public function prepare_item_for_response( $post, $request ) {

		$schema = $this->get_item_schema( $request );
		foreach ( $schema['properties'] as $schema_properties_k => $schema_properties_v ) {
			$data[ $schema_properties_k ] = $post->$schema_properties_k;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param WP_Post $post The comment object whose response is being prepared.
	 */
	public function prepare_service_for_response( $post, $request ) {

		$schema = $this->get_service_schema( $request );
		foreach ( $schema['properties'] as $schema_properties_k => $schema_properties_v ) {
			if ( 'serviceStaff' === $schema_properties_k ) {
				$serviceStaffList = array();
				$staff            = get_post_meta( $post->ID, 'serviceStaff', true );
				if ( ! empty( $staff ) ) {
					foreach ( $staff as $id => $value ) {
						$user_meta = get_user_meta( $id, 'additionalInformations', true );
						if ( ! empty( $user_meta['staffProfileImage'] ) ) {
							$serviceStaffList[ $id ] = $user_meta['staffProfileImage']['0']['thumb'];
						}
					}
				}
				$data['serviceStaffListing']  = $serviceStaffList;
				$data[ $schema_properties_k ] = $post->$schema_properties_k;
			} else {
				$data[ $schema_properties_k ] = $post->$schema_properties_k;
			}
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
	public function get_service_schema( $request = null ) {
		$schema = array(
			// This tells the spec of JSON Schema we are using which is draft 4.
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			// The title property marks the identity of the resource.
			'title'      => TALIKA_SERVICES_POST_TYPE,
			'type'       => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'ID'                      => array(
					'description' => __( 'Unique identifier for the object.', 'talika' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'post_title'              => array(
					'description' => __( 'The title for the object.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
				),
				'post_content'            => array(
					'description' => __( 'The content for the service.', 'talika' ),
					'type'        => 'html',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => null,
					),
				),
				'serviceStatus'           => array(
					'description' => __( 'Meta field to toggle service field status visible or hidden.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceColor'            => array(
					'description' => __( 'Option for pulling service color.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceCategory'         => array(
					'description' => __( 'Option for pulling service category.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceDuration'         => array(
					'description' => __( 'Option for pulling service duration.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'servicePrice'            => array(
					'description' => __( 'Option for pulling service price.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceDepositeMode'     => array(
					'description' => __( 'Option for pulling service deposite mode.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceDepositeType'     => array(
					'description' => __( 'Option for pulling service deposite type.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceDepositeAmount'   => array(
					'description' => __( 'Option for pulling service deposite amount.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceBufferTimeBefore' => array(
					'description' => __( 'Option for pulling service buffer time before.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceBufferTimeAfter'  => array(
					'description' => __( 'Option for pulling service buffer time after.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceMaxCapacity'      => array(
					'description' => __( 'Option for pulling service max capacity.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceMinCapacity'      => array(
					'description' => __( 'Option for pulling service min capacity.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceGallery'          => array(
					'description' => __( 'Meta field to storing image gallery data of service.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceFeaturedImage'    => array(
					'description' => __( 'Custom Metafield for pulling featured image.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceStaff'            => array(
					'description' => __( 'Custom Metafield for pulling service staff.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'serviceHours'            => array(
					'description' => __( 'Custom Metafield for saving hour for service availability.', 'talika' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
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
			'title'      => TALIKA_SERVICES_POST_TYPE,
			'type'       => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'term_id'     => array(
					'description' => __( 'Unique identifier for the object.', 'talika' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'name'        => array(
					'description' => __( 'The title for the object.', 'talika' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Category description.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'catImage'    => array(
					'description' => __( 'Option for pulling featured image.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'catColor'    => array(
					'description' => __( 'Option for pulling category color.', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'catStatus'   => array(
					'description' => __( 'Option for pulling category status.', 'talika' ),
					'type'        => 'string',
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
}

// Function to register our new routes from the controller.
function talika_register_services_rest_routes() {
	$controller = new Talika_Services_REST_Controller( TALIKA_SERVICES_POST_TYPE );
	$controller->register_routes();
	$controller->register_talika_service_meta_rest();
}

add_action( 'rest_api_init', 'talika_register_services_rest_routes' );
