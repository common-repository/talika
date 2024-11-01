<?php
/**
 * REST API: Talika_Analytics_REST_Controller class
 *
 * @package Talika API Core
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with Appointment Analytics.
 *
 * @since 1.0.0
 */
class Talika_Analytics_REST_Controller extends Talika_API_Controller {
	/**
	 * Constructor
	 */
	public function __construct( $post_type ) {
		$this->base_name = TALIKA_ANALYTICS;
	}

	// Register our routes.
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/appointments'
			. '/action=(?P<action>[a-zA-Z0-9-]+)(?:&start=(?P<start>[\d/-]+))?(?:&end=(?P<end>[\d/-]+))?',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_appointments' ),
					'permission_callback' => '__return_true',
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/appointment-stats'
			. '&range=(?P<range>[a-zA-Z0-9-]+)(?:&start=(?P<start>[\d/-]+))?(?:&end=(?P<end>[\d/-]+))?',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_appointment_statistics' ),
					'permission_callback' => '__return_true',
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/appointments/overview',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_appointment_statistics_overview' ),
					'permission_callback' => '__return_true',
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/customer-stats'
			. '&range=(?P<range>[a-zA-Z0-9-]+)(?:&start=(?P<start>[\d/-]+))?(?:&end=(?P<end>[\d/-]+))?',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_customer_statistics' ),
					'permission_callback' => '__return_true',
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/staff-stats'
			. '&range=(?P<range>[a-zA-Z0-9-]+)(?:&start=(?P<start>[\d/-]+))?(?:&end=(?P<end>[\d/-]+))?',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_staff_statistics' ),
					'permission_callback' => '__return_true',
				),
				// Register our schema callback.
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/service-stats'
			. '&range=(?P<range>[a-zA-Z0-9-]+)(?:&start=(?P<start>[\d/-]+))?(?:&end=(?P<end>[\d/-]+))?',
			array(
				// Here we register the readable endpoint for collections.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_service_statistics' ),
					'permission_callback' => '__return_true',
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
	public function get_appointments( $request ) {
		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$mappings   = array(
			'per_page' => 'posts_per_page',
			'page'     => 'paged',
		);
		$args  = array(
			'posts_per_page' => 10,
			'post_type'      => TALIKA_POST_TYPE,
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		);
		
		$today  = date('Y-m-d');
		$action = $request->get_param( 'action' );
		$start  = $request->get_param( 'start' ) ? $request->get_param( 'start' ) : false;
		$end    = $request->get_param( 'end' ) ? $request->get_param( 'end' ) : false;

		switch( $action ) {
			case 'upcoming':
				$args['meta_query'] = array(
					array(
						'key'     => 'appointmentDate',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				);
				break;

			case 'today':
				$args['meta_query'] = array(
					array(
						'key'     => 'appointmentDate',
						'value'   => $today,
						'compare' => '=',
						'type'    => 'DATE',
					),
				);
				break;

			case 'weekly':
				$base_time = current_time( 'mysql' );
				$start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );
				$start     = date( 'Y', $start_end['start'] ) . '-' . date( 'n', $start_end['start'] ) . '-' . date( 'd', $start_end['start'] );
				$end       = date( 'Y', $start_end['end'] ) . '-' . date( 'n', $start_end['end'] ) . '-' . date( 'd', $start_end['end'] );

				$args['meta_query'] = array(
					array(
						'key'     => 'appointmentDate',
						'value'   => array( $start, $end ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				);
				break;

			case 'monthly':
				$current_time = current_time( 'timestamp' );
				$start        = date( 'Y' ) . '-' . date( 'n', $current_time ). '-' . 1;
				$end          = date( 'Y' ) . '-' . date( 'n', $current_time ) . '-' . cal_days_in_month( CAL_GREGORIAN, date( 'n', $current_time ), date( 'Y' ) );

				$args['meta_query'] = array(
					array(
						'key'     => 'appointmentDate',
						'value'   => array( $start, $end ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				);
				break;

			case 'custom':
				$args['meta_query'] = array(
					array(
						'key'     => 'appointmentDate',
						'value'   => array( $start, $end ),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
				);
				break;

			default:
				$args['meta_query'] = array(
					array(
						'key'     => 'appointmentDate',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				);
				break;
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
		}

		$response = rest_ensure_response( $data );

		$response->header( 'X-WP-Total', (int) $posts_query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $posts_query->max_num_pages );

		return $response;
	}

	/**
	 * Grabs the five most recent posts and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_appointment_statistics( $request ) {
		$range          = $request->get_param( 'range' );
		$start          = $request->get_param( 'start' ) ? $request->get_param( 'start' ) : false;
		$end            = $request->get_param( 'end' ) ? $request->get_param( 'end' ) : false;
		$previous_stats = $start && $end ? false : true;

		// Determine range
		switch ( $range ) {
			case 'day': 
				$range          = "today";
				$previous_range = "yesterday";
				$day_by_day     = true;
				break;
			case 'week': 
				$range          = "this_week";
				$previous_range = "last_week";
				$day_by_day     = true;
				break;
			case 'month': 
				$range          = "this_month";
				$previous_range = "last_month";
				$day_by_day     = true;
				break;
			case 'year': 
				$range          = "this_year";
				$previous_range = "last_year";
				$day_by_day     = false;
				break;
			case 'custom': 
				$range          = "custom";
				$previous_range = false;
				$day_by_day     = true;
				break;
			default: 
				$range          = "this_year";
				$previous_range = "last_year";
				$day_by_day     = false;
				break;
		}
		
		// Retrieve the queried dates
		$dates = talika_get_range_dates( $range, $start, $end );
		if( $previous_stats && $previous_range) {
			$previous_dates = talika_get_range_dates( $previous_range, false, false );
		}

		$appointments_stats = talika_get_appointments_analytics( $range, $dates, $day_by_day );

		$previous_count    = 0;
		$growth_percentage = 0;
		$current_count     = array_sum( array_column( $appointments_stats, 1 ) );

		if( $previous_dates ) {
			$prev_start_date       = $previous_dates['year'] . '-' . $previous_dates['m_start'] . '-' . $previous_dates['day'];
			$prev_end_date         = $previous_dates['year_end'] . '-' . $previous_dates['m_end'] . '-' . $previous_dates['day_end'];
			$previous_appointments = talika_get_appointments_by_range( $previous_range, false, $prev_start_date, $prev_end_date, true );
			$previous_count        = array_sum( array_column( $previous_appointments, 'count' ) );
			$growth_percentage     = talika_get_appointments_growth_percentage( $current_count, $previous_count );
		}

		$appointments = array(
			'appointments'              => $appointments_stats,
			'previousCount'             => $previous_count,
			'currentCount'              => $current_count,
			'growthPercentage'          => $growth_percentage,
			'growthPercentageCharacter' => $growth_percentage === '+∞' || $growth_percentage === '-∞' ? '' : '%',
		);

		$data = array(
			'success'      => true,
			'message'      => __( 'Appointments Found.', 'talika' ),
			'appointments' => array(),
		);

		if ( empty( $appointments ) ) {
			return rest_ensure_response(
				array(
					'success'      => false,
					'message'      => __( 'No Appointments Found.', 'talika' ),
					'appointments' => array(),
				)
			);
		}

		$data['appointments'] = $appointments;
		
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Grabs the five most recent posts and outputs them as a rest response.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_appointment_statistics_overview( $request ) {
		$current_user = wp_get_current_user();
		$start_date   = $end_date = date('Y-m-d');

		if( is_user_logged_in() ) {
			$appointments = array(
				'currentUser' => esc_html( $current_user->display_name ),
				'count'       => array(
					'approved' => talika_get_appointments_count( 'approved', $start_date, $end_date ),
					'pending'  => talika_get_appointments_count( 'pending', $start_date, $end_date ),
				)
			);
		}

		$data = array(
			'success'      => true,
			'message'      => __( 'Appointments Found.', 'talika' ),
			'appointments' => array(),
		);

		if ( empty( $appointments ) ) {
			return rest_ensure_response(
				array(
					'success'      => false,
					'message'      => __( 'No Appointments Found.', 'talika' ),
					'appointments' => array(),
				)
			);
		}

		$data['appointments'] = $appointments;
		
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get customer statistics for appointments.
	 */
	public function get_customer_statistics( $request ) {
		$range          = $request->get_param( 'range' );
		$start          = $request->get_param( 'start' ) ? $request->get_param( 'start' ) : false;
		$end            = $request->get_param( 'end' ) ? $request->get_param( 'end' ) : false;
		$previous_stats = $start && $end ? false : true;

		switch ( $range ) {
			case 'day': 
				$range          = "today";
				$previous_range = "yesterday";
				break;
			case 'week': 
				$range          = "this_week";
				$previous_range = "last_week";
				break;
			case 'month': 
				$range          = "this_month";
				$previous_range = "last_month";
				break;
			case 'year': 
				$range          = "this_year";
				$previous_range = "last_year";
				break;
			case 'custom': 
				$range          = "custom";
				$previous_range = false;
				break;
			default: 
				$range          = "this_year";
				$previous_range = "last_year";
				break;
		}

		// Retrieve the queried dates
		$dates = talika_get_range_dates( $range, $start, $end );
		if( $previous_stats && $previous_range) {
			$previous_dates = talika_get_range_dates( $previous_range, false, false );
		}

		$start_date      = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
		$end_date        = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];
		$customers_stats = talika_get_customers_analytics( $start_date, $end_date );

		$previous_count      = 0;
		$growth_percentage   = 0;
		$returning_customers = 0;
		$current_count       = isset( $customers_stats['count'] ) ? $customers_stats['count'] : 0;

		if( $previous_dates ) {
			$prev_start_date     = $previous_dates['year'] . '-' . $previous_dates['m_start'] . '-' . $previous_dates['day'];
			$prev_end_date       = $previous_dates['year_end'] . '-' . $previous_dates['m_end'] . '-' . $previous_dates['day_end'];
			$prev_customer_stats = talika_get_customers_analytics( $prev_start_date, $prev_end_date );
			$previous_count      = isset( $prev_customer_stats['count'] ) ? $prev_customer_stats['count'] : 0;
			$growth_percentage   = talika_get_appointments_growth_percentage( $current_count, $previous_count );
			$returning_customers = ( $previous_count > 0 && $current_count > 0 ) ? 
					count( array_intersect( $customers_stats['customers'], $prev_customer_stats['customers'] ) ) : 0;
		}

		$customers = array(
			'total'                     => $current_count,
			'new'                       => $current_count - $returning_customers,
			'returning'                 => $returning_customers,
			'growthPercentage'          => $growth_percentage,
			'growthPercentageCharacter' => $growth_percentage === '+∞' || $growth_percentage === '-∞' ? '' : '%'
		);

		$data = array(
			'success'      => true,
			'message'      => __( 'Customers Found.', 'talika' ),
			'customers'    => array(),
		);

		if ( empty( $customers ) ) {
			return rest_ensure_response(
				array(
					'success'      => false,
					'message'      => __( 'No Customers Found.', 'talika' ),
					'customers'    => array(),
				)
			);
		}

		$data['customers'] = $customers;
		
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get staff statistics for appointments.
	 */
	public function get_staff_statistics( $request ) {
		$range          = $request->get_param( 'range' );
		$start          = $request->get_param( 'start' ) ? $request->get_param( 'start' ) : false;
		$end            = $request->get_param( 'end' ) ? $request->get_param( 'end' ) : false;
		$previous_stats = $start && $end ? false : true;

		switch ( $range ) {
			case 'day': 
				$range          = "today";
				$previous_range = "yesterday";
				break;
			case 'week': 
				$range          = "this_week";
				$previous_range = "last_week";
				break;
			case 'month': 
				$range          = "this_month";
				$previous_range = "last_month";
				break;
			case 'year': 
				$range          = "this_year";
				$previous_range = "last_year";
				break;
			case 'custom': 
				$range          = "custom";
				$previous_range = false;
				break;
			default: 
				$range          = "this_year";
				$previous_range = "last_year";
				break;
		}

		// Retrieve the queried dates
		$dates = talika_get_range_dates( $range, $start, $end );
		if( $previous_stats && $previous_range) {
			$previous_dates = talika_get_range_dates( $previous_range, false, false );
		}

		$start_date   = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
		$end_date     = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];
		$staffs_stats = talika_get_staffs_analytics( $start_date, $end_date );

		$previous_count    = 0;
		$growth_percentage = 0;
		$current_count     = isset( $staffs_stats['count'] ) ? $staffs_stats['count'] : 0;

		if( $previous_dates ) {
			$prev_start_date   = $previous_dates['year'] . '-' . $previous_dates['m_start'] . '-' . $previous_dates['day'];
			$prev_end_date     = $previous_dates['year_end'] . '-' . $previous_dates['m_end'] . '-' . $previous_dates['day_end'];
			$prev_staff_stats  = talika_get_staffs_analytics( $prev_start_date, $prev_end_date );
			$previous_count    = isset( $prev_staff_stats['count'] ) ? $prev_staff_stats['count'] : 0;
			$growth_percentage = talika_get_appointments_growth_percentage( $current_count, $previous_count );
		}

		$staffs = array(
			'staffs'                    => isset( $staffs_stats['staffs'] ) ? $staffs_stats['staffs'] : array(),
			'total'                     => $current_count,
			'growthPercentage'          => $growth_percentage,
			'growthPercentageCharacter' => $growth_percentage === '+∞' || $growth_percentage === '-∞' ? '' : '%'
		);

		$data = array(
			'success' => true,
			'message' => __( 'Staffs Found.', 'talika' ),
			'staffs'  => array(),
		);

		if ( empty( $staffs ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'No Staffs Found.', 'talika' ),
					'staffs'  => array(),
				)
			);
		}

		$data['staffs'] = $staffs;
		
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get services statistics for appointments.
	 */
	public function get_service_statistics( $request ) {
		$range          = $request->get_param( 'range' );
		$start          = $request->get_param( 'start' ) ? $request->get_param( 'start' ) : false;
		$end            = $request->get_param( 'end' ) ? $request->get_param( 'end' ) : false;
		$previous_stats = $start && $end ? false : true;

		switch ( $range ) {
			case 'day': 
				$range          = "today";
				$previous_range = "yesterday";
				break;
			case 'week': 
				$range          = "this_week";
				$previous_range = "last_week";
				break;
			case 'month': 
				$range          = "this_month";
				$previous_range = "last_month";
				break;
			case 'year': 
				$range          = "this_year";
				$previous_range = "last_year";
				break;
			case 'custom': 
				$range          = "custom";
				$previous_range = false;
				break;
			default: 
				$range          = "this_year";
				$previous_range = "last_year";
				break;
		}

		// Retrieve the queried dates
		$dates = talika_get_range_dates( $range, $start, $end );
		if( $previous_stats && $previous_range) {
			$previous_dates = talika_get_range_dates( $previous_range, false, false );
		}

		$start_date     = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
		$end_date       = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];
		$services_stats = talika_get_services_analytics( $start_date, $end_date );

		$previous_count    = 0;
		$growth_percentage = 0;
		$current_count     = isset( $services_stats['count'] ) ? $services_stats['count'] : 0;

		if( $previous_dates ) {
			$prev_start_date   = $previous_dates['year'] . '-' . $previous_dates['m_start'] . '-' . $previous_dates['day'];
			$prev_end_date     = $previous_dates['year_end'] . '-' . $previous_dates['m_end'] . '-' . $previous_dates['day_end'];
			$prev_staff_stats  = talika_get_services_analytics( $prev_start_date, $prev_end_date );
			$previous_count    = isset( $prev_staff_stats['count'] ) ? $prev_staff_stats['count'] : 0;
			$growth_percentage = talika_get_appointments_growth_percentage( $current_count, $previous_count );
		}

		$services = array(
			'services'                  => isset( $services_stats['services'] ) ? $services_stats['services'] : array(),
			'total'                     => $current_count,
			'growthPercentage'          => $growth_percentage,
			'growthPercentageCharacter' => $growth_percentage === '+∞' || $growth_percentage === '-∞' ? '' : '%'
		);

		$data = array(
			'success'  => true,
			'message'  => __( 'Services Found.', 'talika' ),
			'services' => array(),
		);

		if ( empty( $services ) ) {
			return rest_ensure_response(
				array(
					'success'  => false,
					'message'  => __( 'No Services Found.', 'talika' ),
					'services' => array(),
				)
			);
		}

		$data['services'] = $services;
		
		$response = rest_ensure_response( $data );

		return $response;
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
function talika_register_analytics_rest_routes() {
	$controller = new Talika_Analytics_REST_Controller( TALIKA_ANALYTICS );
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_analytics_rest_routes' );
