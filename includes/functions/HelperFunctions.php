<?php
/**
 * Helper functions for our plugin.
 * 
 * @package Talika
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper for _doing_it_wrong().
 *
 * @since  1.0.0
 * @param string $function Function used.
 * @param string $message Message to log.
 * @param string $version Version the message was added in.
 */
function talika_doing_it_wrong( $function, $message, $version ) {
	// @codingStandardsIgnoreStart
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();
	
	_doing_it_wrong( $function, $message, $version );
	// @codingStandardsIgnoreEnd
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @since 1.0.0
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path Default path. (default: '').
 *
 * @return string Template path.
 */
function talika_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = TALIKA_APP()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = TALIKA_APP()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit(  $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template.
	if ( ! $template || TALIKA_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'talika_locate_template', $template, $template_name, $template_path );
}

/**
 * Get other templates (e.g. article attributes) passing attributes and including the file.
 *
 * @since 1.0.0
 *
 * @param string $template_name   Template name.
 * @param array  $args            Arguments. (default: array).
 * @param string $template_path   Template path. (default: '').
 * @param string $default_path    Default path. (default: '').
 */
function talika_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path, TALIKA_VERSION ) ) );
	$template = (string) wp_cache_get( $cache_key, 'talika' );

	if ( ! $template ) {
		$template = talika_locate_template( $template_name, $template_path, $default_path );
		wp_cache_set( $cache_key, $template, 'talika' );
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'talika_get_template', $template, $template_name, $args, $template_path, $default_path );

	if( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			/* translators: %s template */
			talika_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'talika' ), '<code>' . $template . '</code>' ), '1.0.0' );
			return;
		}
		$template = $filter_template;
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		if ( isset( $args['action_args'] ) ) {
			talika_doing_it_wrong(
				__FUNCTION__,
				__( 'action_args should not be overwritten when calling talika_get_template.', 'talika' ),
				'1.0.0'
			);
			unset( $args['action_args'] );
		}
		extract( $args );
	}

	do_action( 'talika_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'talika_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

/**
 * Get template part.
 *
 * TALIKA_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed $slug Template slug.
 * @param string $name Template name (default: '').
 *
 */
function talika_get_template_part( $slug, $name = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template-part', $slug, $name, TALIKA_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'talika' );

	if ( ! $template ) {
		if ( $name ) {
			$template = TALIKA_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}-{$name}.php",
					TALIKA_APP()->template_path() . "{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = TALIKA_APP()->plugin_path() . "/templates/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/talika/slug.php.
			$template = TALIKA_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}.php",
					TALIKA_APP()->template_path() . "{$slug}.php",
				)
			);
		}

		wp_cache_set( $cache_key, $template, 'talika' );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'talika_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Like talika_get_template, but return the HTML instaed of outputting.
 *
 * @see talika_get_template
 * @since 1.0.0
 *
 * @param string $template_name Template name.
 * @param array $args           Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string.
 */
function talika_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
    talika_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}

/**
 * Get permalink settings for Talika.
 *
 * @since  1.0.0
 * @return array
 */
function talika_get_permalink_structure() {

    // Get global settings.
    $global_settings = get_option('talika_permalink_settings');

    $permalinks = array();

	$permalinks['appointment_base']       = isset( $global_settings['appointment_base'] ) && ! empty( $global_settings['appointment_base'] ) ? trim( $global_settings['appointment_base'], '/\\' ) : 'talika';
	$permalinks['service_base']           = isset( $global_settings['service_base'] ) && ! empty( $global_settings['service_base'] ) ? trim( $global_settings['service_base'], '/\\' ) : 'services';
    $permalinks['service_category_base']  = isset( $global_settings['service_category_base'] ) && ! empty( $global_settings['service_category_base'] ) ? trim( $global_settings['service_category_base'], '/\\' ) : 'services-category';
    $permalinks['location_base']          = isset( $global_settings['service_category_base'] ) && ! empty( $global_settings['locations_base'] ) ? trim( $global_settings['service_category_base'], '/\\' ) : 'locations';
    return $permalinks;
}

/**
 * Gets value of provided index.
 *
 * @param array  $array Array to pick value from.
 * @param string $index Index.
 * @param any    $default Default Values.
 * @return mixed
 */
function talika_get_array_values_by_index( $array, $index = null, $default = null ) {
	if ( ! is_array( $array ) ) {
		return $default;
	}

	if ( is_null( $index ) ) {
		return $array;
	}

	$multi_label_indices = explode( '.', $index );
	$value               = $array;

	foreach ( $multi_label_indices as $key ) {
		if ( ! isset( $value[ $key ] ) ) {
			$value = $default;
			break;
		}
		$value = $value[ $key ];
	}

	return $value;
}

/**
 * Get Email Templates content.
 */
function talika_get_template_content( $email_template_type = 'appointment_approved', $template = '', $sendto = 'customer', $default_content = false ) {
    $settings = get_option( 'talika_notifications', array() );
    $templates = array(
        'appointment_approved' => array(
            'customer' => talika_get_array_values_by_index( $settings, 'customerNotification.approved.message', '' ),
			'employee' => talika_get_array_values_by_index( $settings, 'staffNotification.approved.message', '' ),
		),
		'appointment_pending' => array(
            'customer' => talika_get_array_values_by_index( $settings, 'customerNotification.pending.message', '' ),
			'employee' => talika_get_array_values_by_index( $settings, 'staffNotification.pending.message', '' ),
		),
		'appointment_rejected' => array(
            'customer' => talika_get_array_values_by_index( $settings, 'customerNotification.rejected.message', '' ),
			'employee' => talika_get_array_values_by_index( $settings, 'staffNotification.rejected.message', '' ),
		),
		'appointment_canceled' => array(
            'customer' => talika_get_array_values_by_index( $settings, 'customerNotification.canceled.message', '' ),
			'employee' => talika_get_array_values_by_index( $settings, 'staffNotification.canceled.message', '' ),
		),
    );

    $content = empty( $templates[ $email_template_type ][ $sendto ] ) || ( $default_content ) 
                ? '' : $templates[ $email_template_type ][ $sendto ];

    if ( ! empty( $content ) ) {
        return $content;
    }

    if ( empty( $template ) ) {
        switch ( $email_template_type ) {
            case 'appointment_approved':
                $template = $sendto === "customer" ? 'emails/customer/customer-appointment-approved.php' : 'emails/employee/employee-appointment-approved.php';
                break;
			case 'appointment_pending':
				$template = $sendto === "customer" ? 'emails/customer/customer-appointment-pending.php' : 'emails/employee/employee-appointment-pending.php';
				break;
			case 'appointment_rejected':
				$template = $sendto === "customer" ? 'emails/customer/customer-appointment-rejected.php' : 'emails/employee/employee-appointment-rejected.php';
				break;
			case 'appointment_canceled':
				$template = $sendto === "customer" ? 'emails/customer/customer-appointment-canceled.php' : 'emails/employee/employee-appointment-canceled.php';
				break;
            default:
                $template = 'emails/customer/customer-appointment-approved.php';
                break;
        }
    }

    return talika_get_template_html( $template );
}

function get_appointment_details( $appointment_id = false ) {
	if ( ! $appointment_id ) {
		return false;
	}

	$customers           = get_post_meta( $appointment_id, 'customers', true );
	$customer_full_name  = isset( $customers[0]['label'] ) && $customers[0]['label'] ? $customers[0]['label'] : '';
	$customer_id         = isset( $customers[0]['value'] ) && $customers[0]['value'] ? absint( $customers[0]['value'] ) : '';
	$customer_first_name = get_the_author_meta( 'first_name', $customer_id, true );
	$customer_last_name  = get_the_author_meta( 'last_name', $customer_id, true );
	$customer_email      = get_the_author_meta( 'user_email', $customer_id, true );
	$customer_phone      = get_user_meta(  $customer_id, 'customerPhone', true );
	$customer_notes      = get_user_meta(  $customer_id, 'noteToCustomer', true );
	
	$service         = get_post_meta( $appointment_id, 'service', true );
	$service_name    = isset( $service['label'] ) && $service['label'] ? $service['label'] : '';
	$service_id      = isset( $service['value'] ) && $service['value'] ? absint( $service['value'] ) : '';
	$start_date      = get_post_meta( $appointment_id, 'appointmentDate', true );
	$start_date      = isset( $start_date ) && $start_date ? date( 'Y-m-d', strtotime( $start_date ) ) : 'N/A';
	$start_time      = get_post_meta( $appointment_id, 'appointmentTime', true );
	$start_time      = isset( $start_time['label'] ) && $start_time['label'] ? $start_time['label'] : 'N/A';
	$duration        = get_post_meta( $service_id, 'serviceDuration', true );
	$duration        = isset( $duration['label'] ) && $duration['label'] ? $duration['label'] : 'N/A';
	$status          = get_post_meta( $appointment_id, 'appointmentStatus', true );
	$status          = isset( $status['label'] ) && $status['label'] ? $status['label'] : 'N/A';
	$payment_details = get_post_meta( $appointment_id, 'appointmentPayment', true );
	$payment_method  = isset( $payment_details['paymentMethod'] ) && $payment_details['paymentMethod'] ? $payment_details['paymentMethod'] : 'N/A';
	$payment_amount  = isset( $payment_details['amount'] ) && $payment_details['amount'] ? $payment_details['amount'] : 'N/A';
	$price           = isset( $payment_details['bookingPrice'] ) && $payment_details['bookingPrice'] ? $payment_details['bookingPrice'] : 'N/A';

	$staff            = get_post_meta( $appointment_id, 'staff', true );
	$staff_full_name  = isset( $staff['label'] ) && $staff['label'] ? $staff['label'] : '';
	$staff_id         = isset( $staff['value'] ) && $staff['value'] ? absint( $staff['value'] ) : '';
	$staff_first_name = get_the_author_meta( 'first_name', $staff_id, true );
	$staff_last_name  = get_the_author_meta( 'last_name', $staff_id, true );
	$staff_email      = get_the_author_meta( 'user_email', $staff_id, true );
	$staff_info       = get_user_meta( $staff_id, 'additionalInformations', true );
	$staff_phone      = isset( $staff_info['staffTelephoneNumber'] ) && $staff_info['staffTelephoneNumber'] ? $staff_info['staffTelephoneNumber'] : 'N/A';

	$location             = get_post_meta( $appointment_id, 'location', true );
	$location_name        = isset( $location['label'] ) && $location['label'] ? $location['label'] : '';
	$location_id          = isset( $location['value'] ) && $location['value'] ? absint( $location['value'] ) : '';
	$location_address     = get_post_meta( $location_id, 'locationAddress', true );
	$location_address     = isset( $location_address ) && $location_address ? $location_address : 'N/A';
	$location_details     = get_post_meta( $location_id, 'locationAdditionalDetails', true );
	$location_description = isset( $location_details['locationDescription'] ) && $location_details['locationDescription'] ? $location_details['locationDescription'] : 'N/A';
	$location_phone       = isset( $location_details['locationTelephone'] ) && $location_details['locationTelephone'] ? $location_details['locationTelephone'] : 'N/A';

	$appointment_data = array(
		'appointment_id'       => $appointment_id,
		'service_name'         => $service_name,
		'start_date'           => $start_date,
		'start_time'           => $start_time,
		'duration'             => $duration,
		'status'               => $status,
		'payment_method'       => $payment_method,
		'payment_amount'       => $payment_amount,
		'price'                => $price,
		'customer_full_name'   => $customer_full_name,
		'customer_first_name'  => $customer_first_name,
		'customer_last_name'   => $customer_last_name,
		'customer_email'       => $customer_email,
		'customer_phone'       => $customer_phone,
		'customer_notes'       => $customer_notes,
		'staff_full_name'      => $staff_full_name,
		'staff_first_name'     => $staff_first_name,
		'staff_last_name'      => $staff_last_name,
		'staff_email'          => $staff_email,
		'staff_phone'          => $staff_phone,
		'location_name'        => $location_name,
		'location_address'     => $location_address,
		'location_description' => $location_description,
		'location_phone'       => $location_phone,
	);

	$appointment_data_object = (object) $appointment_data;
	return $appointment_data_object;
}

/**
 * Sets up the dates used to filter graph data
 *
 * Date is read first and then modified (if needed) to match the
 * selected date-range (if any)
 *
 * @return array
*/
function talika_get_range_dates( $range, $start_date = false, $end_date = false ) {
	$dates = array();

	$current_time = current_time( 'timestamp' );

	$dates['range'] = isset( $range ) ? $range : 'this_year';

	if ( $dates['range'] ) {
		if( $start_date && $end_date ) {
			$start_date = strtotime( $start_date );
			$end_date   = strtotime( $end_date );
		}

		$dates['year']     = $start_date ? date( 'Y', $start_date ) : date( 'Y' );
		$dates['year_end'] = $end_date ? date( 'Y', $end_date ) : date( 'Y' );
		$dates['m_start']  = $start_date ? date( 'n', $start_date ) : 1;
		$dates['m_end']    = $end_date ? date( 'n', $end_date ) : 12;
		$dates['day']      = $start_date ? date( 'd', $start_date ) : 1;
		$dates['day_end']  = $end_date ? date( 'd', $end_date ) : cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
	}

	// Modify dates based on predefined ranges
	switch ( $dates['range'] ) :

		case 'this_month' :
			$dates['m_start']  = date( 'n', $current_time );
			$dates['m_end']    = date( 'n', $current_time );
			$dates['day']      = 1;
			$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			$dates['year']     = date( 'Y' );
			$dates['year_end'] = date( 'Y' );
		break;

		case 'last_month' :
			if ( date( 'n' ) == 1 ) {
				$dates['m_start']  = 12;
				$dates['m_end']    = 12;
				$dates['year']     = date( 'Y', $current_time ) - 1;
				$dates['year_end'] = date( 'Y', $current_time ) - 1;
			} else {
				$dates['m_start']  = date( 'n' ) - 1;
				$dates['m_end']    = date( 'n' ) - 1;
				$dates['year_end'] = $dates['year'];
			}
			$dates['day']     = 1;
			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
		break;

		case 'today' :
			$dates['day']      = date( 'd', $current_time );
			$dates['m_start']  = date( 'n', $current_time );
			$dates['m_end']    = date( 'n', $current_time );
			$dates['year']     = date( 'Y', $current_time );
			$dates['year_end'] = date( 'Y', $current_time );
			$dates['day_end']  = date( 'd', $current_time );
		break;

		case 'yesterday' :
			$year  = date( 'Y', $current_time );
			$month = date( 'n', $current_time );
			$day   = date( 'd', $current_time );

			if ( $month == 1 && $day == 1 ) {
				$year  -= 1;
				$month = 12;
				$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );
			} elseif ( $month > 1 && $day == 1 ) {
				$month -= 1;
				$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );
			} else {
				$day -= 1;
			}

			$dates['day']       = $day;
			$dates['m_start']   = $month;
			$dates['m_end']     = $month;
			$dates['year']      = $year;
			$dates['year_end']  = $year;
			$dates['day_end']   = $day;
		break;

		case 'this_week' :
		case 'last_week' :
			$base_time = $dates['range'] === 'this_week' ? current_time( 'mysql' ) : date( 'Y-m-d h:i:s', current_time( 'timestamp' ) - WEEK_IN_SECONDS );
			$start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );

			$dates['day']      = date( 'd', $start_end['start'] );
			$dates['m_start']  = date( 'n', $start_end['start'] );
			$dates['year']     = date( 'Y', $start_end['start'] );

			$dates['day_end']  = date( 'd', $start_end['end'] );
			$dates['m_end']    = date( 'n', $start_end['end'] );
			$dates['year_end'] = date( 'Y', $start_end['end'] );
		break;

		case 'this_year' :
			$dates['day']      = 1;
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = date( 'Y', $current_time );
			$dates['year_end'] = $dates['year'];
		break;

		case 'last_year' :
			$dates['day']      = 1;
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = date( 'Y', $current_time ) - 1;
			$dates['year_end'] = date( 'Y', $current_time ) - 1;
		break;

	endswitch;

	return apply_filters( 'talika_get_range_dates', $dates );
}

/**
 * Converts a date to a timestamp
 *
 * @since 1.0
 * @return array|WP_Error If the date is invalid, a WP_Error object will be returned
 */
function talika_convert_date( $date, $end_date = false ) {

	$timestamp = false;
	$second    = $end_date ? 59 : 0;
	$minute    = $end_date ? 59 : 0;
	$hour      = $end_date ? 23 : 0;
	$day       = 1;
	$month     = date( 'n', current_time( 'timestamp' ) );
	$year      = date( 'Y', current_time( 'timestamp' ) );

	if( is_numeric( $date ) ) {

		// return $date unchanged since it is a timestamp
		$timestamp = true;

	} else if( false !== strtotime( $date ) ) {

		$date  = strtotime( $date, current_time( 'timestamp' ) );
		$year  = date( 'Y', $date );
		$month = date( 'm', $date );
		$day   = date( 'd', $date );

	} else {

		return new WP_Error( 'invalid_date', __( 'Improper date provided.', 'talika' ) );

	}

	if( false === $timestamp ) {
		// Create an exact timestamp
		$date = mktime( $hour, $minute, $second, $month, $day, $year );
	}

	return apply_filters( 'talika_stats_date', $date, $end_date );

}

/**
 * Is the date range cachable
 *
 * @param  string $range Date range of the report
 * @return boolean Whether the date range is allowed to be cached or not
 */
function talika_is_range_cacheable( $date_range = "" ) {
	if ( empty( $date_range ) ) {
		return false;
	}

	$cacheable_ranges = array(
		'today',
		'this_week',
		'last_week',
		'this_month',
		'last_month',
	);

	return in_array( $date_range, $cacheable_ranges );
}

/**
 * Retrieve appointment stats based on range provided (used for Overview Analytics)
 *
 * @param string|bool  $start_date The starting date for which we'd like to filter our sale stats. If false, we'll use the default start date of `this_week`
 * @param string|bool  $end_date The end date for which we'd like to filter our sale stats. If false, we'll use the default end date of `this_week`
 *
 * @return array Total amount of appointments based on the passed arguments.
 */
function talika_get_appointments_by_range( $range = 'today', $day_by_day = false, $start_date = false, $end_date = false, $count_only = false ) {
	global $wpdb;

	$start_date = talika_convert_date( $start_date );
	$end_date   = talika_convert_date( $end_date, true );
	$end_date   = strtotime( 'midnight', $end_date );

	// Make sure start date is valid
	if ( is_wp_error( $start_date ) ) {
		return $start_date;
	}

	// Make sure end date is valid
	if ( is_wp_error( $end_date ) ) {
		return $end_date;
	}

	$cached      = get_transient( 'talika_stats' );
	$key         = md5( $range . '_' . date( 'Y-m-d', $start_date ) . '_' . date( 'Y-m-d', strtotime( '+1 DAY', $end_date ) ) );
	$appointments = isset( $cached[ $key ] ) ? $cached[ $key ] : false;

	if ( false === $appointments || ! talika_is_range_cacheable( $range ) ) {
		if( $count_only ) {
			$select = "COUNT(DISTINCT posts.ID) as count";
			$grouping = "YEAR(meta_value), MONTH(meta_value), DAY(meta_value)";
		} elseif ( ! $day_by_day ) {
			$select = "DATE_FORMAT(meta_value, '%%m') AS m, YEAR(meta_value) AS y, COUNT(DISTINCT posts.ID) as count";
			$grouping = "YEAR(meta_value), MONTH(meta_value)";
		} else {
			if ( $range == 'today' ) {
				$select = "DATE_FORMAT(meta_value, '%%d') AS d, DATE_FORMAT(meta_value, '%%m') AS m, YEAR(meta_value) AS y, HOUR(meta_value) AS h, COUNT(DISTINCT posts.ID) as count";
				$grouping = "YEAR(meta_value), MONTH(meta_value), DAY(meta_value), HOUR(meta_value)";
			} else {
				$select = "DATE_FORMAT(meta_value, '%%d') AS d, DATE_FORMAT(meta_value, '%%m') AS m, YEAR(meta_value) AS y, COUNT(DISTINCT posts.ID) as count";
				$grouping = "YEAR(meta_value), MONTH(meta_value), DAY(meta_value)";
			}
		}

		if ( $range == 'today' ) {
			$grouping = "YEAR(meta_value), MONTH(meta_value), DAY(meta_value), HOUR(meta_value)";
		}

		$appointments = $wpdb->get_results( 
			$wpdb->prepare(
				"SELECT $select
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
				WHERE posts.post_type IN ('wpa-appointments')
				AND posts.post_status IN ('publish')
				AND postmeta.meta_key = 'appointmentDate'
				AND meta_value >= %s
				AND meta_value < %s
				GROUP BY $grouping
				ORDER by meta_value ASC",
				date( 'Y-m-d', $start_date ), 
				date( 'Y-m-d', strtotime( '+1 day', $end_date ) ) 
			), ARRAY_A );

		if ( talika_is_range_cacheable( $range ) ) {
			$cached[ $key ] = $appointments;
			set_transient( 'talika_stats', $cached, HOUR_IN_SECONDS );
		}
	}

	return $appointments;
}


function talika_get_appointments_analytics( $range, $dates, $day_by_day ) {
	if( $range == "custom" ) {
		if ( $dates['m_start'] == 12 && $dates['m_end'] == 1 ) {
			$day_by_day = true;
		} elseif ( $dates['m_end'] - $dates['m_start'] >= 3 || ( $dates['year_end'] > $dates['year'] && ( $dates['m_start'] - $dates['m_end'] ) != 10 ) ) {
			$day_by_day = false;
		} else {
			$day_by_day = true;
		}
	}

	$appointments_data = array();

	if( $dates['range'] == 'today' || $dates['range'] == 'yesterday' ) {
		// Hour by hour
		$hour  = 0;
		$month = $dates['m_start'];

		$start_date = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
		$end_date   = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];

		$i = 0;
		$appointments = talika_get_appointments_by_range( $dates['range'], $day_by_day, $start_date, $end_date );
		while ( $hour <= 23 ) {
			$date = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;

			if ( isset( $appointments[ $i ] ) && $appointments[ $i ]['h'] == $hour ) {
				$appointments_data[] = array( $date, $appointments[ $i ]['count'] );
				$i++;
			} else {
				$appointments_data[] = array( $date, 0 );
			}

			$hour++;
		}
	} elseif ( $dates['range'] == 'this_week' || $dates['range'] == 'last_week' ) {
		$report_dates = array();
		$i = 0;

		while ( $i <= 6 ) {
			if ( ( $dates['day'] + $i ) <= $dates['day_end'] ) {
				$report_dates[ $i ] = array(
					'day'   => (string) $dates['day'] + $i,
					'month' => $dates['m_start'],
					'year'  => $dates['year'],
				);
			} else {
				$report_dates[ $i ] = array(
					'day'   => (string) $i,
					'month' => $dates['m_end'],
					'year'  => $dates['year_end'],
				);
			}

			$i++;
		}

		$start_date = $report_dates[0];
		$end_date = end( $report_dates );

		$appointments = talika_get_appointments_by_range( $dates['range'], $day_by_day, $start_date['year'] . '-' . $start_date['month'] . '-' . $start_date['day'], $end_date['year'] . '-' . $end_date['month'] . '-' . $end_date['day'] );

		$i = 0;
		foreach ( $report_dates as $report_date ) {
			$date = mktime( 0, 0, 0,  $report_date['month'], $report_date['day'], $report_date['year']  ) * 1000;

			if ( array_key_exists( $i, $appointments ) && $report_date['day'] == $appointments[ $i ]['d'] && $report_date['month'] == $appointments[ $i ]['m'] && $report_date['year'] == $appointments[ $i ]['y'] ) {
				$appointments_data[] = array( $date, $appointments[ $i ]['count'] );
				$i++;
			} else {
				$appointments_data[] = array( $date, 0 );
			}
		}

	} else {
		if ( cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] ) < $dates['day'] ) {
			$next_day = mktime( 0, 0, 0, $dates['m_start'] + 1, 1, $dates['year'] );
			$day = date( 'd', $next_day );
			$month = date( 'm', $next_day );
			$year = date( 'Y', $next_day );
			$start_date = $year . '-' . $month . '-' . $day;
		} else {
			$start_date = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
		}

		if ( cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] ) < $dates['day_end'] ) {
			$end_date = $dates['year_end'] . '-' . $dates['m_end'] . '-' . cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
		} else {
			$end_date = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];
		}

		$appointments = talika_get_appointments_by_range( $dates['range'], $day_by_day, $start_date, $end_date );

		$y = $dates['year'];
		$temp_data = array();

		foreach ( $appointments as $appointment ) {
			if( $day_by_day ) {
				$temp_data[ $appointment['y'] ][ $appointment['m']][ $appointment['d'] ] = $appointment['count'];
			} else {
				$temp_data[ $appointment['y'] ][ $appointment['m'] ] = $appointment['count'];
			}
		}

		while ( $day_by_day && ( strtotime( $start_date ) <= strtotime( $end_date ) ) ) {
			$d = date( 'd', strtotime( $start_date ) );
			$m = date( 'm', strtotime( $start_date ) );
			$y = date( 'Y', strtotime( $start_date ) );

			if ( ! isset( $temp_data[ $y ][ $m ][ $d ] ) ) {
				$temp_data[ $y ][ $m ][ $d ] = 0;
			}

			$start_date = date( 'Y-m-d', strtotime( '+1 day', strtotime( $start_date ) ) );
		}
		
		while ( ! $day_by_day && ( strtotime( $start_date ) <= strtotime( $end_date ) ) ) {
			$m = date( 'm', strtotime( $start_date ) );
			$y = date( 'Y', strtotime( $start_date ) );

			if ( ! isset( $temp_data[ $y ][ $m ] ) ) {
				$temp_data[ $y ][ $m ] = 0;
			}

			$start_date = date( 'Y-m', strtotime( '+1 month', strtotime( $start_date ) ) );
		}

		// When using 3 months or smaller as the custom range, show each day individually on the graph
		if ( $day_by_day ) {
			foreach ( $temp_data as $year => $months ) {
				foreach ( $months as $month => $days ) {
					foreach ( $days as $day => $count ) {
						$date         = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
						$appointments_data[] = array( $date, $count );
					}
				}
			}

			// Sort dates in ascending order
			foreach ( $appointments_data as $key => $value ) {
				$timestamps[ $key ] = $value[0];
			}
			if ( ! empty( $timestamps ) ) {
				array_multisort( $timestamps, SORT_ASC, $appointments_data );
			}

		// When showing more than 3 months of results, group them by month, by the first (except for the last month, group on the last day of the month selected)
		} else {

			foreach ( $temp_data as $year => $months ) {
				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				if ( $day_by_day ) {
					foreach ( $months as $month => $days ) {
						$day_keys = array_keys( $days );
						$last_day = end( $day_keys );

						$month_keys = array_keys( $months );

						$consolidated_date = $month === end( $month_keys ) ? cal_days_in_month( CAL_GREGORIAN, $month, $year ) : 1;

						$appointments = array_sum( $days );
						$date        = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
						$appointments_data[] = array( $date, $appointments );
					}
				} else {
					foreach ( $months as $month => $count ) {
						$month_keys = array_keys( $months );
						$consolidated_date = $month === end( $month_keys ) ? cal_days_in_month( CAL_GREGORIAN, $month, $year ) : 1;
	
						$date = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
						$appointments_data[] = array( $date, $count );
					}
				}
			}

			// Sort dates in ascending order
			foreach ( $appointments_data as $key => $value ) {
				$timestamps[ $key ] = $value[0];
			}
			if ( ! empty( $timestamps ) ) {
				array_multisort( $timestamps, SORT_ASC, $appointments_data );
			}
		}
	}

	return apply_filters( 'talika_analytics', $appointments_data );
}

// get appointments growth percentage
function talika_get_appointments_growth_percentage( $totalValue, $pastTotalValue ) {
	if ($totalValue === 0 && $pastTotalValue === 0) {
		return 0;
	}

	if ($totalValue === 0 && $pastTotalValue !== 0) {
		return '-∞';
	}

	if ($totalValue !== 0 && $pastTotalValue === 0) {
		return '+∞';
	}

	return $totalValue - $pastTotalValue === 0 ? 0 : round( (($totalValue - $pastTotalValue) / $pastTotalValue * 100), 1 );
}

// get appointments count by status
function talika_get_appointments_count( $status = '', $start_date = '', $end_date = '' ) {
	global $wpdb;

	$appointments_count = 0;

	if ( $status ) {
		$args = array(
			'post_type'      => 'wpa-appointments',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'appointmentStatus',
					'value'   => $status,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'appointmentDate',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);
		$appointments = get_posts( $args );
		$appointments_count = count( $appointments );
	}

	return $appointments_count;
}

// get customer statictics by appointment date
function talika_get_customers_analytics( $start_date = '', $end_date = '' ) {
	$customer_statistics = array();
	$customers = array();

	if ( $start_date && $end_date ) {
		$args = array(
			'post_type'      => 'wpa-appointments',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'appointmentDate',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);
		$appointments = get_posts( $args );

		if ( $appointments ) {
			foreach ( $appointments as $appointment ) {
				$customer_id = get_post_meta( $appointment->ID, 'customers', true );
				if ( isset( $customer_id[0]['value'] ) ) {
					$customers[] = $customer_id[0]['value'];
				}
			}
			$customers_count     = count( array_unique( $customers ) );
		}
	}

	
	$customer_statistics = array(
		'customers' => array_unique( $customers ),
		'count'     => $customers_count,
	);

	return $customer_statistics;
}

// get staffs statictics by appointment date
function talika_get_staffs_analytics( $start_date = '', $end_date = '' ) {
	$staffs_statistics = array();
	$staffs            = array();
	$staffs_count      = 0;

	if ( $start_date && $end_date ) {
		$args = array(
			'post_type'      => 'wpa-appointments',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'appointmentDate',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);
		$appointments = get_posts( $args );

		if ( $appointments ) {
			foreach ( $appointments as $appointment ) {
				$staff_id = get_post_meta( $appointment->ID, 'staff', true );
				if ( isset( $staff_id['value'] ) ) {
					$staff_meta        = get_user_meta( $staff_id['value'], 'additionalInformations', true );
					$staff_image       = isset( $staff_meta['staffProfileImage']['0']['thumb'] ) ? $staff_meta['staffProfileImage']['0']['thumb'] : '';

					$assigned_services = get_user_meta( $staff_id['value'], 'assignedServices', true );
					if( $assigned_services ) {
						$assigned_services = array_filter( $assigned_services );
						$services_count    = count( $assigned_services );
						$assigned_services = end( $assigned_services );
						$title             = get_the_title( $assigned_services['serviceID'] );
						$service_info      = $services_count > 1 ? sprintf( _n( '%s and %d Other', '%s and %d Others', $services_count - 1, 'talika' ), $title, $services_count - 1 ) : $title;
						
					} else {
						$service_info = "";
					}
					
					if ( array_key_exists( $staff_id['value'], $staffs ) ) {
						$staffs[ $staff_id['value'] ]['tasks'] ++;
					} else {
						$staffs[ $staff_id['value'] ] = array(
							'id'          => $staff_id['value'],
							'name'        => $staff_id['label'],
							'image'       => $staff_image,
							'tasks'       => 1,
							'services'    => $service_info,
						);
					}
				}
			}
			$staffs       = array_values( $staffs );
			$staffs_count = count( $staffs );
		}
	}

	$staffs_statistics = array(
		'staffs' => $staffs,
		'count'  => $staffs_count,
	);

	return $staffs_statistics;
}

// get staff statictics by appointment date
function talika_get_services_analytics( $start_date = '', $end_date = '' ) {
	$services_statistics = array();
	$services            = array();
	$services_count      = 0;

	if ( $start_date && $end_date ) {
		$args = array(
			'post_type'      => 'wpa-appointments',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'appointmentDate',
					'value'   => array( $start_date, $end_date ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		);
		$appointments = get_posts( $args );

		if ( $appointments ) {
			foreach ( $appointments as $appointment ) {
				$service_id = get_post_meta( $appointment->ID, 'service', true );
				if ( isset( $service_id['value'] ) ) {
					$service_category = get_post_meta( $appointment->ID, 'category', true );

					$service_staffs = get_post_meta( $service_id['value'], 'serviceStaff', true );
					if( $service_staffs ) {
						$service_staffs = array_filter( $service_staffs );
						$staffs_count   = count( $service_staffs );
						$service_staffs = end( $service_staffs );
						$user           = get_user_by( 'id', $service_staffs['staffId'] );
						$name           = $user->display_name;
						$staff_info     = $staffs_count > 1 ? sprintf( _n( '%s and %d Other', '%s and %d Others', $staffs_count - 1, 'talika' ), $name, $staffs_count - 1 ) : $name;
						
					} else {
						$staff_info = "";
					}

					if ( array_key_exists( $service_id['value'], $services ) ) {
						$services[ $service_id['value'] ]['tasks'] ++;
					} else {
						$services[ $service_id['value'] ] = array(
							'id'     => $service_id['value'],
							'name'   => $service_id['label'],
							'cat'    => isset( $service_category['label'] ) ? $service_category['label'] : '',
							'tasks'  => 1,
							'staffs' => $staff_info,
						);
					}
				}
				
			}
			$services       = array_values( $services );
			$services_count = count( $services );
		}
	}

	$services_statistics = array(
		'services' => $services,
		'count'    => $services_count,
	);

	return $services_statistics;
}

/**
 * is_talika_block - Returns true when viewing a block page.
 *
 * @return bool
 */
function is_talika_block() {
	$wpa_block =
		has_block( 'talika/add-appointment-button' );

	return apply_filters( 'is_talika_block', $wpa_block );
}

/**
 * get global label settings
 */
function talika_get_global_label_settings() {
	$settings = get_option( 'talika_label_settings' ) ? get_option( 'talika_label_settings' ) : array();

	$label_defaults = apply_filters( 'talika_label_settings_default', array(
		'enableLabels'     => false,
		'staffLabel'       => "Staff",
		'serviceLabel'     => "Service",
		'appointmentLabel' => "Appointment",
		'locationLabel'    => "Location",
	) );

	$settings = wp_parse_args( $settings, $label_defaults );

	return $settings;
}

/**
 * get global payment settings
 */
function talika_get_global_payment_settings() {
	$settings = get_option( 'talika_payment_settings' ) ? get_option( 'talika_payment_settings' ) : array();

	$payment_defaults = apply_filters( 'talika_payment_settings_default', array(
		'currency'       => [ 'value' => 'USD', 'label' => 'United States dollar' ],
		'priceSeparator' => [ 'value' => 'comma-dot', 'label' => 'Comma-Dot' ],
		'position'       => [ 'value' => 'before', 'label' => 'Before' ],
		'priceDecimal'   => "2",
		'paymentMethod'  => [ 'value' => 'paypal', 'label' => 'PayPal Standard' ],
		'debugMode'      => true,
		'paypalId'       => "",
	) );
	
	$settings = wp_parse_args( $settings, $payment_defaults );

	return $settings;
}

/**
 * get global general settings 
 */
function talika_get_global_general_settings() {
	$settings = get_option( 'talika_general_settings' ) ? get_option( 'talika_general_settings' ) : array();

	$general_defaults = apply_filters( 'talika_general_settings_default', array(
		'timeSlotStep'           => ['value' => '60', 'label' => '1 hour'],
		'appointmentStatus'      => ['value' => 'approved', 'label' => 'Approved'],
		'minTimeBeforeBooking'   => ['value' => 'disabled', 'label' => 'Disabled'],
		'minTimeBeforeCancel'    => ['value' => 'disabled', 'label' => 'Disabled'],
		'advanceBookingDuration' => "365",
		'bufferTimeBefore'       => ['value' => 'disabled', 'label' => 'Disabled'],
		'bufferTimeAfter'        => ['value' => 'disabled', 'label' => 'Disabled'],
		'phoneCountryCode'       => ['value' => '+977', 'label' => '+977 Nepal'],
	) );
	
	$settings = wp_parse_args( $settings, $general_defaults );

	return $settings;
}

/*
 * get global work hours settings
 */
function talika_get_global_work_hrs_settings() {
	$settings = get_option( 'talika_work_hours_settings' ) ? get_option( 'talika_work_hours_settings' ) : array();

	$workhr_defaults = apply_filters( 'talika_work_hrs_settings_default', array(
		'workingHours' => array(
			'monday' => array(
				'key'         => "monday",
				'label'       => __("Monday", "talika"),
				'avvr'        => __("Mon", "talika"),
				'isActiveDay' => true,
				'workHour'    => [
					[ 'startingHour' => "09:00", 'endingHour' => "17:00" ]
				],
				'breakHour'        => [],
				'isFirstDayOfWeek' => true,
			),
			'tuesday' => array(
				'key'         => "tuesday",
				'label'       => __("Tuesday", "talika"),
				'avvr'        => __("Tues", "talika"),
				'isActiveDay' => true,
				'workHour'    => [
					[ 'startingHour' => "09:00", 'endingHour' => "17:00" ]
				],
				'breakHour'        => [],
				'isFirstDayOfWeek' => false,
			),
			'wednesday' => array(
				'key'         => "wednesday",
				'label'       => __("Wednesday", "talika"),
				'avvr'        => __("Wed", "talika"),
				'isActiveDay' => true,
				'workHour'    => [
					[ 'startingHour' => "09:00", 'endingHour' => "17:00" ]
				],
				'breakHour'        => [],
				'isFirstDayOfWeek' => false,
			),
			'thursday' => array(
				'key'         => "thursday",
				'label'       => __("Thursday", "talika"),
				'avvr'        => __("Thu", "talika"),
				'isActiveDay' => true,
				'workHour'    => [
					[ 'startingHour' => "09:00", 'endingHour' => "17:00" ]
				],
				'breakHour'        => [],
				'isFirstDayOfWeek' => false,
			),
			'friday' => array(
				'key'         => "friday",
				'label'       => __("Friday", "talika"),
				'avvr'        => __("Fri", "talika"),
				'isActiveDay' => true,
				'workHour'    => [
					[ 'startingHour' => "09:00", 'endingHour' => "17:00" ]
				],
				'breakHour'        => [],
				'isFirstDayOfWeek' => false,
			),
			'saturday' => array(
				'key'              => "saturday",
				'label'            => __("Saturday", "talika"),
				'avvr'             => __("Sat", "talika"),
				'isActiveDay'      => false,
				'workHour'         => [],
				'breakHour'        => [],
				'isFirstDayOfWeek' => false,
			),
			'sunday' => array(
				'key'              => "sunday",
				'label'            => __("Sunday", "talika"),
				'avvr'             => __("Sun", "talika"),
				'isActiveDay'      => false,
				'workHour'         => [],
				'breakHour'        => [],
				'isFirstDayOfWeek' => false,
			),
		),
	));
	
	$settings = wp_parse_args( $settings, $workhr_defaults );

	return $settings;
}

/**
 * Sanitize Array
 *
 * @param [type] $array
 * @return void
 */
function talika_sanitize_values( $array ) {
	if ( is_array( $array ) ) {
		foreach ( $array as $key => $value ) {
			$array[ $key ] = talika_sanitize_values( $value );
		}
	} else {
		$array = sanitize_text_field( $array );
	}

	return $array;
}

/**
 * Sanitize Editor values.
 *
 * @param [type] $array
 * @return void
 */
function talika_sanitize_editor_values ( $array ) {
	if ( is_array( $array ) ) {
		foreach ( $array as $key => $value ) {
			$array[ $key ] = talika_sanitize_editor_values( $value );
		}
	} else {
		$array = wp_kses_post( $array );
	}

	return $array;
}
