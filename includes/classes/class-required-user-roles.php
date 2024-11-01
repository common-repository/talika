<?php

namespace Talika;

/**
 * Class User Roles
 *
 */
class TALIKA_USER_ROLES
{
    /**
     * @param $roles
     */
    public static function activate()
    {
        self::create_roles();
        self::add_caps();
    }

    /**
	 * Create roles and capabilities.
	 */
	public static function create_roles() {
        global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

        if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		// Appointment Manager role.
        $talika_manager_caps = apply_filters( 'talika_manager_caps', array(
            'talika_view_menu'                => true,
            'talika_view_appointments'        => true,
            'talika_view_all_appointments'    => true,
            'talika_edit_appointments'        => true,
            'talika_view_dashboard'           => true,
            'talika_view_calendar'            => true,
            'talika_edit_calendar'            => true,
            'talika_view_staffs'              => true,
            'talika_edit_staffs'              => true,
            'talika_view_services'            => true,
            'talika_edit_services'            => true,
            'talika_view_locations'           => true,
            'talika_edit_locations'           => true,
            'talika_view_customers'           => true,
            'talika_edit_customers'           => true,
            'talika_edit_appointment_status'  => true,
            'talika_edit_appointment_time'    => true,
	    ));
        add_role( 'talika_manager', __( 'Talika Manager', 'talika' ), $talika_manager_caps );

        // Appointment Staff role.
        $talika_staff_caps = apply_filters( 'talika_staff_caps', array(
            'talika_view_menu'                => true,
            'talika_view_appointments'        => true,
            'talika_view_all_appointments'    => false,
            'talika_edit_appointments'        => true,
            'talika_view_dashboard'           => true,
            'talika_view_calendar'            => true,
            'talika_edit_calendar'            => true,
            'talika_view_staffs'              => true,
            'talika_edit_staffs'              => true,
            'talika_view_services'            => true,
            'talika_edit_services'            => true,
            'talika_view_locations'           => true,
            'talika_edit_locations'           => true,
            'talika_view_customers'           => true,
            'talika_edit_customers'           => true,
            'talika_edit_appointment_status'  => true,
            'talika_edit_appointment_time'    => true,
        ));
        add_role( 'talika_staff', __( 'Talika Staff', 'talika' ), $talika_staff_caps );

        // Appointment Customer role.
        $talika_customer_caps = apply_filters( 'talika_customer_caps', array(
            'talika_view_menu'                => true,
            'talika_view_calendar'            => true,
            'talika_view_appointments'        => true,
            'talika_edit_appointment_status'  => true,
            'talika_edit_appointment_time'    => true,
            'talika_edit_appointments'        => true,
        ));
		add_role( 'talika_customer', __( 'Talika Customer', 'talika' ), $talika_customer_caps );
    }

    /**
     * Add Caps
     *
     * @return void
     */
    public static function add_caps() {
        global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {

			$wp_roles->add_cap( 'talika_manager', 'manage_booked_appointments' );
            $wp_roles->add_cap( 'talika_staff', 'edit_booked_appointments' );
            $wp_roles->add_cap( 'talika_customer', 'view_talika_appointment' );
            $wp_roles->add_cap( 'administrator', 'edit_talika_settings' );
            $wp_roles->add_cap( 'administrator', 'talika_edit_appointments' );
            $wp_roles->add_cap( 'talika_customer', 'talika_edit_appointments' );
		}
	}
}
