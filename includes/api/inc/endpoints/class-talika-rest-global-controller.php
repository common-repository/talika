<?php
/**
 * REST API: Talika_REST_Global_Settings_Controller class
 *
 * @package Talika API Core
 * @subpackage API Core
 * @since 1.0.0
 */

/**
 * Core base controller for managing and interacting with Global Appointment Settings.
 *
 * @since 1.0.0
 */
class Talika_REST_Global_Settings_Controller extends Talika_API_Controller {

	/**
	 * Constructor
	 */
	public function __construct( $post_type ) {
		$this->base_name = TALIKA_GLOBAL_SETTINGS;
	}

	// Register our routes.
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/payments',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_payment_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_payment_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
				// Register our schema callback.
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/general',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_general_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_general_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
				// Register our schema callback.
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/company',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_company_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_company_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
				// Register our schema callback.
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/work-hours',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_workhrs_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_workhrs_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
				// Register our schema callback.
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/days-off',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_daysoff_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_daysoff_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
				// Register our schema callback.
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base_name . '/labels',
			array(
				// Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_label_settings' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_label_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
				),
				// Register our schema callback.
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Grabs Global Appointment Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_payment_settings( $request ) {

		$settings = talika_get_global_payment_settings();

		if ( empty( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Payment Settings not found.' , 'talika' ),
					'data'    => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'Payment Settings found.', 'talika' ),
		);

		// $response = $this->prepare_item_for_response( $settings, $request );
		$response = rest_ensure_response( $settings );
		$data['data'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Saves Talika Global Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_payment_settings( $request ) {

		$formdata = $request->get_json_params();
		$formdata = stripslashes_deep( $formdata );

		// Sanitize and save settings.
		$sanitized_settings = $this->sanitize_settings( $formdata );
		update_option( 'talika_payment_settings', $sanitized_settings );

		$data = array(
			'success'  => true,
			'message'  => __( 'Payment Settings Saved Successfully.', 'talika' )
		);

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs General Appointment Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_general_settings( $request ) {

		$settings = talika_get_global_general_settings();

		if ( empty( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'General Settings not found.' , 'talika' ),
					'data'    => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'General Settings found.', 'talika' ),
		);

		$response     = rest_ensure_response( $settings );
		$data['data'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Saves Talika General Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_general_settings( $request ) {

		$formdata = $request->get_json_params();
		$formdata = stripslashes_deep( $formdata );

		// Sanitize and save settings.
		$sanitized_settings = $this->sanitize_settings( $formdata );
		update_option( 'talika_general_settings', $sanitized_settings );

		$data = array(
			'success'  => true,
			'message'  => __( 'General Settings Saved Successfully.', 'talika' )
		);

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs Company Appointment Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_company_settings( $request ) {

		$settings = get_option( 'talika_company_settings' ) ? get_option( 'talika_company_settings' ) : array();

		if ( empty( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Company Settings not found.' , 'talika' ),
					'data'    => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'Company Settings found.', 'talika' ),
		);

		$response     = rest_ensure_response( $settings );
		$data['data'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Saves Talika Company Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_company_settings( $request ) {

		$formdata = $request->get_json_params();
		$formdata = stripslashes_deep( $formdata );

		// Sanitize and save settings.
		$sanitized_settings = $this->sanitize_settings( $formdata );
		update_option( 'talika_company_settings', $sanitized_settings );

		$data = array(
			'success'  => true,
			'message'  => __( 'Company Settings Saved Successfully.', 'talika' )
		);

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs Company Appointment Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_workhrs_settings( $request ) {

		$settings = talika_get_global_work_hrs_settings();

		if ( empty( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Work Hours Settings not found.' , 'talika' ),
					'data'    => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'Work Hours Settings found.', 'talika' ),
		);

		$response     = rest_ensure_response( $settings );
		$data['data'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Saves Talika Company Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_workhrs_settings( $request ) {

		$formdata = $request->get_json_params();
		$formdata = stripslashes_deep( $formdata );

		// Sanitize and save settings.
		$sanitized_settings = $this->sanitize_settings( $formdata );
		update_option( 'talika_work_hours_settings', $sanitized_settings );

		$data = array(
			'success'  => true,
			'message'  => __( 'Work Hours Settings Saved Successfully.', 'talika' )
		);

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs Company Appointment Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_daysoff_settings( $request ) {

		$settings = get_option( 'talika_days_off_settings' ) ? get_option( 'talika_days_off_settings' ) : array();

		if ( empty( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Days Off Settings not found.' , 'talika' ),
					'data'    => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'Days Off Settings found.', 'talika' ),
		);

		$response     = rest_ensure_response( $settings );
		$data['data'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Saves Talika Company Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_daysoff_settings( $request ) {

		$formdata = $request->get_json_params();
		$formdata = stripslashes_deep( $formdata );

		// Sanitize and save settings.
		$sanitized_settings = $this->sanitize_settings( $formdata );
		update_option( 'talika_days_off_settings', $sanitized_settings );

		$data = array(
			'success'  => true,
			'message'  => __( 'Days Off Settings Saved Successfully.', 'talika' )
		);

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Grabs Labels Appointment Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function get_label_settings( $request ) {

		$settings = talika_get_global_label_settings();

		if ( empty( $settings ) ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => __( 'Label Settings not found.' , 'talika' ),
					'data'    => array(),
				)
			);
		}

		$data = array(
			'success' => true,
			'message' => __( 'Label Settings found.', 'talika' ),
		);

		$response     = rest_ensure_response( $settings );
		$data['data'] = $this->prepare_response_for_collection( $response );

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Saves Talika Label Settings.
	 *
	 * @param $request WP_REST_Request Current request.
	 */
	public function post_label_settings( $request ) {

		$formdata = $request->get_json_params();
		$formdata = stripslashes_deep( $formdata );

		// Sanitize and save settings.
		$sanitized_settings = $this->sanitize_settings( $formdata );
		update_option( 'talika_label_settings', $sanitized_settings );

		$data = array(
			'success'  => true,
			'message'  => __( 'Label Settings Saved Successfully.', 'talika' )
		);

		// Return all of our post response data.
		return $data;
	}

	/**
	 * Settings data sanitization.
	 *
	 * @param [type] $settings_data
	 * @return void
	 */
	public function sanitize_settings( $settings_data ) {

		if ( ! empty( $settings_data ) ) {
			foreach( $settings_data as $key => $setting ) {
				if( ! is_array( $setting ) ) {
					if( is_bool( $setting ) ) {
						$settings_data[ $key ] = rest_sanitize_boolean( $setting );
					} elseif( 'companyMap' === $key ) {
						$settings_data[ $key ] = $setting;
					} else {
						$settings_data[$key] = sanitize_text_field( $setting );
					}
				} else {
					foreach( $setting as $sub_key => $sub_setting ) {
						if ( ! is_array( $sub_setting ) ) {
							if( is_bool( $sub_setting ) ) {
								$settings_data[$key][$sub_key] = rest_sanitize_boolean( $sub_setting );
							} else {
								$settings_data[$key][$sub_key] = sanitize_text_field( $sub_setting );
							}
						} else {
							foreach( $sub_setting as $sub_sub_key => $sub_sub_setting ) {
								if ( ! is_array( $sub_sub_setting ) ) {
									if ( 'content' === $sub_sub_key ) {
										$settings_data[$key][$sub_key][$sub_sub_key] = wp_kses_post( $sub_sub_setting );
									} else {
										$settings_data[$key][$sub_key][$sub_sub_key] = sanitize_text_field( $sub_sub_setting );
									}
								} else {
									foreach( $sub_sub_setting as $sub_sub_sub_key => $sub_sub_sub_setting ) {
										if ( ! is_array( $sub_sub_sub_setting ) ) {
											$settings_data[$key][$sub_key][$sub_sub_key][$sub_sub_sub_key] = sanitize_text_field( $sub_sub_sub_setting );
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return $settings_data;
	}

	/**
	 * Check if a given request has access to get items
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Matches the post data to the schema we want.
	 *
	 * @param WP_Post $post The comment object whose response is being prepared.
	 */
	public function prepare_item_for_response( $settings, $request ) {

		$schema = $this->get_item_schema( $request );

		foreach ( $schema['properties'] as $schema_properties_k => $schema_properties_v ) {
			$data[ $schema_properties_k ] = $settings->$schema_properties_k;
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
	 * Get our sample schema for a post.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_item_schema( $request = null ) {
		$schema = array(
			// This tells the spec of JSON Schema we are using which is draft 4.
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			// The title property marks the identity of the resource.
			'title' => TALIKA_GLOBAL_SETTINGS,
			'type'  => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'currency' => array(
					'description' => __( 'Currency', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'priceSeparator' => array(
					'description' => __( 'Price Separator', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'position' => array(
					'description' => __( 'Position', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'priceDecimal' => array(
					'description' => __( 'Price Decimal', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'paymentMethod' => array(
					'description' => __( 'Payment Method', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'debugMode' => array(
					'description' => __( 'Debug Mode', 'talika' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'paypalID' => array(
					'description' => __( 'Paypal ID', 'talika' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
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
function talika_register_global_settings_rest_routes() {
	$controller = new Talika_REST_Global_Settings_Controller( TALIKA_GLOBAL_SETTINGS );
	$controller->register_routes();
}

add_action( 'rest_api_init', 'talika_register_global_settings_rest_routes' );
