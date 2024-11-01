<?php

/**
 * Register Post Type.
 *
 * @package Talika
 * @subpackage  Talika
 */

namespace Talika;

defined( 'ABSPATH' ) || exit;

class TalikaAPPRegisterRequiredPostType {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialization.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function init() {
		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function init_hooks() {
		 // Register Custom Post Type : Appointment
		add_action( 'init', array( $this, 'register_post_type_appointment' ) );

		// Register Custom Post Type : Services
		add_action( 'init', array( $this, 'register_post_type_services' ) );
		add_action( 'init', array( $this, 'register_post_type_services_taxonomies' ) );

		// Register Custom Post Type : Location
		add_action( 'init', array( $this, 'register_post_type_locations' ) );
		add_action( 'rest_api_init', array( $this, 'wpa_register_locations_custom_fields' ) );
		add_action( 'rest_api_init', array( $this, 'wpa_register_services_custom_fields' ) );
		add_action( 'rest_api_init', array( $this, 'wpa_register_services_category_custom_meta' ) );
		add_action( 'rest_api_init', array( $this, 'wpa_register_staff_category_custom_meta' ) );
	}

	/**
	 * Register Custom post type
	 * Appointment
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_post_type_appointment() {
		$permalink             = talika_get_permalink_structure();
		$talika_labels = array(
			'name'               => _x( 'Appointments', 'post type general name', 'talika' ),
			'singular_name'      => _x( 'Appointment', 'post type singular name', 'talika' ),
			'menu_name'          => _x( 'Appointments', 'admin menu', 'talika' ),
			'name_admin_bar'     => _x( 'Appointment', 'add new on admin bar', 'talika' ),
			'add_new'            => _x( 'Add New', 'Appointment', 'talika' ),
			'add_new_item'       => __( 'Add New Appointment', 'talika' ),
			'new_item'           => __( 'New Appointment', 'talika' ),
			'edit_item'          => __( 'Edit Appointment', 'talika' ),
			'view_item'          => __( 'View Appointment', 'talika' ),
			'all_items'          => __( 'All Appointments', 'talika' ),
			'search_items'       => __( 'Search Appointments', 'talika' ),
			'parent_item_colon'  => __( 'Parent Appointments:', 'talika' ),
			'not_found'          => __( 'No Appointments found.', 'talika' ),
			'not_found_in_trash' => __( 'No Appointment found in Trash.', 'talika' ),
		);

		$talika_args = array(
			'labels'             => $talika_labels,
			'description'        => __( 'Description.', 'talika' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => false,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $permalink['location_base'],
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 30,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		);
		register_post_type( TALIKA_POST_TYPE, $talika_args );
	}

	/**
	 * Register Custom post type
	 * Services
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_post_type_services() {
		$permalink                     = talika_get_permalink_structure();
		$talika_service_labels = array(
			'name'               => _x( 'Services', 'post type general name', 'talika' ),
			'singular_name'      => _x( 'Service', 'post type singular name', 'talika' ),
			'menu_name'          => _x( 'Services', 'admin menu', 'talika' ),
			'name_admin_bar'     => _x( 'Service', 'add new on admin bar', 'talika' ),
			'add_new'            => _x( 'Add New', 'Service', 'talika' ),
			'add_new_item'       => __( 'Add New Service', 'talika' ),
			'new_item'           => __( 'New Service', 'talika' ),
			'edit_item'          => __( 'Edit Service', 'talika' ),
			'view_item'          => __( 'View Service', 'talika' ),
			'all_items'          => __( 'All Services', 'talika' ),
			'search_items'       => __( 'Search Services', 'talika' ),
			'parent_item_colon'  => __( 'Parent Services:', 'talika' ),
			'not_found'          => __( 'No Services found.', 'talika' ),
			'not_found_in_trash' => __( 'No Services found in Trash.', 'talika' ),
		);

		$talika_service_args        = array(
			'labels'             => $talika_service_labels,
			'description'        => __( 'Description.', 'talika' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $permalink['service_base'],
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 30,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		);
		register_post_type( TALIKA_SERVICES_POST_TYPE, $talika_service_args );
	}

	function register_post_type_services_taxonomies() {
		$permalink = talika_get_permalink_structure();
		$labels    = array(
			'name'              => _x( 'Service Categories', 'taxonomy general name', 'talika' ),
			'singular_name'     => _x( 'Service Category', 'taxonomy singular name', 'talika' ),
			'search_items'      => __( 'Service Categories', 'talika' ),
			'all_items'         => __( 'All Service Categories', 'talika' ),
			'parent_item'       => __( 'Parent Category', 'talika' ),
			'parent_item_colon' => __( 'Parent Category', 'talika' ),
			'edit_item'         => __( 'Edit Category', 'talika' ),
			'update_item'       => __( 'Update Category', 'talika' ),
			'add_new_item'      => __( 'Add New Category', 'talika' ),
			'new_item_name'     => __( 'New Category Name', 'talika' ),
			'menu_name'         => __( 'Service Categories', 'talika' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_in_menu'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'         => $permalink['service_category_base'],
				'hierarchical' => true,
			),
		);

		register_taxonomy( 'wpa-service-category', array( TALIKA_SERVICES_POST_TYPE ), $args );
	}

	/**
	 * Register Custom post type
	 * Locations
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_post_type_locations() {
		$permalink             = talika_get_permalink_structure();
		$talika_labels = array(
			'name'               => _x( 'Locations', 'post type general name', 'talika' ),
			'singular_name'      => _x( 'Location', 'post type singular name', 'talika' ),
			'menu_name'          => _x( 'Locations', 'admin menu', 'talika' ),
			'name_admin_bar'     => _x( 'Location', 'add new on admin bar', 'talika' ),
			'add_new'            => _x( 'Add New', 'Location', 'talika' ),
			'add_new_item'       => __( 'Add New Location', 'talika' ),
			'new_item'           => __( 'New Location', 'talika' ),
			'edit_item'          => __( 'Edit Location', 'talika' ),
			'view_item'          => __( 'View Location', 'talika' ),
			'all_items'          => __( 'All Locations', 'talika' ),
			'search_items'       => __( 'Search Locations', 'talika' ),
			'parent_item_colon'  => __( 'Parent Locations:', 'talika' ),
			'not_found'          => __( 'No Locations found.', 'talika' ),
			'not_found_in_trash' => __( 'No Location found in Trash.', 'talika' ),
		);

		$talika_args       = array(
			'labels'             => $talika_labels,
			'description'        => __( 'Description.', 'talika' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $permalink['appointment_base'],
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 30,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		);

		register_post_type( TALIKA_LOCATIONS_POST_TYPE, $talika_args );
	}

	/**
	 * Register Service Category Custom Meta.
	 *
	 * @since 1.0.0
	 */
	function wpa_register_services_category_custom_meta() {
		register_rest_field(
			'term',
			'catColor',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'      => array( $this, 'get_talika_service_category_color' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_rest_field(
			'term',
			'catImage',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'      => array( $this, 'get_talika_service_category_image' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_rest_field(
			'term',
			'catStatus',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'      => array( $this, 'get_talika_service_category_image' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Register Location Custom Meta Fields
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	function wpa_register_locations_custom_fields() {
		// Address
		register_rest_field(
			TALIKA_LOCATIONS_POST_TYPE,
			'locationAddress',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'      => array( $this, 'get_talika_location_address' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		// Gallery
		register_rest_field(
			TALIKA_LOCATIONS_POST_TYPE,
			'locationGallery',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_gallery' ),
			)
		);
		// Featured image
		register_rest_field(
			TALIKA_LOCATIONS_POST_TYPE,
			'locationFeaturedImage',
			array(
				'show_in_rest'  => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_featured_image' ),
			)
		);

		// Location Status toggle to visible / hidden
		register_rest_field(
			TALIKA_LOCATIONS_POST_TYPE,
			'locationStatus',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_status' ),
			)
		);

		// All other additional info
		register_rest_field(
			TALIKA_LOCATIONS_POST_TYPE,
			'locationAdditionalDetails',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_additional_details' ),
			)
		);
	}
	/**
	 * Register SErvice Custom Meta Fields
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	function wpa_register_services_custom_fields() {
		// Address
		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceCategory',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'array',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'      => array( $this, 'get_talika_service_category' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		// Gallery
		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceGallery',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_service_gallery' ),
			)
		);
		// Featured image
		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceFeaturedImage',
			array(
				'show_in_rest'  => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_service_featured_image' ),
			)
		);

		// Location Status toggle to visible / hidden
		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceStatus',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'array',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_service_status' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceDuration',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_duration' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'servicePrice',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_price' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceDepositeMode',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_deposite_mode' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceDepositeType',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_deposite_type' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceDepositeAmount',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_deposite_amount' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceBufferTimeBefore',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_bf_time_before' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceBufferTimeAfter',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_bf_time_after' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceMaxCapacity',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_max_capacity' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceMinCapacity',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_min_capacity' ),
			)
		);

		register_rest_field(
			TALIKA_SERVICES_POST_TYPE,
			'serviceStaff',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_location_service_staff' ),
			)
		);
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Category Color
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_category_color
	 */
	function get_talika_service_category_color( $object, $field_name, $request ) {
		$service_category_color = get_term_meta( $object['id'], 'catColor', true );
		return $service_category_color;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Category Image
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_category_image
	 */
	function get_talika_service_category_image( $object, $field_name, $request ) {
		$service_category_image = get_term_meta( $object['id'], 'catImage', true );
		// if ( $service_category_image ) {
		// 	wp_get_attac
		// }
		return $service_category_image;
	}
	/**
	 * Function:
	 * Metadata for Talika Location
	 * Address
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $location_meta_address
	 */
	function get_talika_location_address( $object, $field_name, $request ) {
		$location_meta_address = get_post_meta( $object['id'], 'locationAddress' );
		return $location_meta_address;
	}
	/**
	 * Function:
	 * Metadata for Talika Location
	 * Location Gallery
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $talika_gallery
	 */
	function get_talika_location_gallery( $object, $field_name, $request ) {
		$talika_gallery = get_post_meta( $object['id'], 'locationGallery' );
		return $talika_gallery;
	}
	/**
	 * Function:
	 * Metadata for Talika Location
	 * Feature Image
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $location_featured_image
	 */
	function get_talika_location_featured_image( $object, $field_name, $request ) {
		$location_featured_image = get_post_meta( $object['id'], 'locationFeaturedImage' );
		return $location_featured_image;
	}
	/**
	 * Function:
	 * Metadata for Talika Location
	 * locationAdditionalDetails
	 * To store all other remaining data
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $location_meta_additional
	 */
	function get_talika_location_additional_details( $object, $field_name, $request ) {
		$location_meta_additional = get_post_meta( $object['id'], 'locationAdditionalDetails' );
		return $location_meta_additional;
	}
	/**
	 * Function:
	 * Metadata for Talika Location
	 * Location Status
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $location_meta_status
	 */
	function get_talika_location_status( $object, $field_name, $request ) {
		$location_meta_status = get_post_meta( $object['id'], 'locationStatus' );
		return $location_meta_status;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service Category
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_category
	 */
	function get_talika_service_category( $object, $field_name, $request ) {
		$service_meta_category = get_post_meta( $object['id'], 'serviceCategory' );
		return $service_meta_category;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * To store all service gallery
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_gallery
	 */
	function get_talika_service_gallery( $object, $field_name, $request ) {
		$service_meta_gallery = get_post_meta( $object['id'], 'serviceGallery' );
		return $service_meta_gallery;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service Feature Image
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_feature_image
	 */
	function get_talika_service_featured_image( $object, $field_name, $request ) {
		$service_meta_feature_image = get_post_meta( $object['id'], 'serviceFeaturedImage' );
		return $service_meta_feature_image;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service status
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_status
	 */
	function get_talika_service_status( $object, $field_name, $request ) {
		$service_meta_status = get_post_meta( $object['id'], 'serviceStatus' );
		return $service_meta_status;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service Duration
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_duration
	 */
	function get_talika_location_service_duration( $object, $field_name, $request ) {
		$service_meta_duration = get_post_meta( $object['id'], 'serviceDuration' );
		return $service_meta_duration;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service Price
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_price
	 */
	function get_talika_location_service_price( $object, $field_name, $request ) {
		$service_meta_price = get_post_meta( $object['id'], 'servicePrice' );
		return $service_meta_price;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service Deposite Mode
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_deposite_mode
	 */
	function get_talika_location_service_deposite_mode( $object, $field_name, $request ) {
		$service_meta_deposite_mode = get_post_meta( $object['id'], 'serviceDepositeMode' );
		return $service_meta_deposite_mode;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service deposite type
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_deposite_type
	 */
	function get_talika_location_service_deposite_type( $object, $field_name, $request ) {
		$service_meta_deposite_type = get_post_meta( $object['id'], 'serviceDepositeType' );
		return $service_meta_deposite_type;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service Deposite Amount
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_deposite_amount
	 */
	function get_talika_location_service_deposite_amount( $object, $field_name, $request ) {
		$service_meta_deposite_amount = get_post_meta( $object['id'], 'serviceDepositeAmount' );
		return $service_meta_deposite_amount;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service buffer time before
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_bf_time_before
	 */
	function get_talika_location_service_bf_time_before( $object, $field_name, $request ) {
		$service_meta_bf_time_before = get_post_meta( $object['id'], 'serviceBufferTimeBefore' );
		return $service_meta_bf_time_before;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service buffer time after
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_bf_time_after
	 */
	function get_talika_location_service_bf_time_after( $object, $field_name, $request ) {
		$service_meta_bf_time_after = get_post_meta( $object['id'], 'serviceBufferTimeAfter' );
		return $service_meta_bf_time_after;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service max capacity
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_max_capacity
	 */
	function get_talika_location_service_max_capacity( $object, $field_name, $request ) {
		$service_meta_max_capacity = get_post_meta( $object['id'], 'serviceMaxCapacity' );
		return $service_meta_max_capacity;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Service min capacity
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_min_capacity
	 */
	function get_talika_location_service_min_capacity( $object, $field_name, $request ) {
		$service_meta_min_capacity = get_post_meta( $object['id'], 'serviceMinCapacity' );
		return $service_meta_min_capacity;
	}
	/**
	 * Function:
	 * Metadata for Talika Service
	 * Staff(s) that are assigned to this particular service
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $service_meta_staff
	 */
	function get_talika_location_service_staff( $object, $field_name, $request ) {
		$service_meta_staff = get_post_meta( $object['id'], 'serviceStaff' );
		return $service_meta_staff;
	}
	/**
	 * Register Custom Meta for
	 * Talika Staff
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function wpa_register_staff_category_custom_meta() {
		register_rest_field(
			'user',
			'staffStatus',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'      => array( $this, 'get_talika_staffStatus' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_rest_field(
			'user',
			'additionalInformations',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_additionalInformations' ),
			)
		);
		register_rest_field(
			'user',
			'assignedServices',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_assignedServices' ),

			)
		);
		register_rest_field(
			'user',
			'workingHours',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_workingHours' ),
			)
		);
		register_rest_field(
			'user',
			'daysOff',
			array(
				'show_in_rest'  => true,
				'single'        => true,
				'type'          => 'string',
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'get_callback'  => array( $this, 'get_talika_daysOff' ),
			)
		);
	}
	/**
	 * Function:
	 * Metadata for Staff
	 * All other information of staff beside default.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $telephoneNumber
	 */
	function get_talika_additionalInformations( $user, $field_name, $request ) {
		return get_user_meta( $user['id'], $field_name, true );
	}
	/**
	 * Function:
	 * Metadata for Staff
	 * All assigned services for the staff
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $telephoneNumber
	 */
	function get_talika_assignedServices( $user, $field_name, $request ) {
		return get_user_meta( $user['id'], $field_name, true );
	}
	/**
	 * Function:
	 * Metadata for Staff
	 * Working hour for the staff
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $telephoneNumber
	 */
	function get_talika_workingHours( $user, $field_name, $request ) {
		return get_user_meta( $user['id'], $field_name, true );
	}
	/**
	 * Function:
	 * Day off or holiday for the staff
	 * Staff Title
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $telephoneNumber
	 */
	function get_talika_daysOff( $user, $field_name, $request ) {
		return get_user_meta( $user['id'], $field_name, true );
	}
	/**
	 * Function:
	 * Metadata for staff
	 * Staff Status
	 *
	 * @since 1.0.0
	 * @access public
	 * @return $telephoneNumber
	 */
	function get_talika_staffStatus( $user, $field_name, $request ) {
		return get_user_meta( $user['id'], $field_name, true );
	}
}
