<?php
/**
 * Add Appointment Block Styles
 *
 * @package Talika
 */

class Talika_Add_Appointment_Styles {

	public static function block_fonts( $attr ) {
		return array( 'buttonTypography' => isset( $attr['buttonTypography'] ) ? $attr['buttonTypography'] : array() );
	}

	public static function block_css( $attr, $id ) {
		$selectors = self::get_selectors( $attr );

		$m_selectors = self::get_mobileselectors( $attr );

		$t_selectors = self::get_tabletselectors( $attr );

		$desktop = Talika_Helpers::generate_css( $selectors, '#wpa-appointment-blocks-style-' . $id );

		$tablet = Talika_Helpers::generate_css( $t_selectors, '#wpa-appointment-blocks-style-' . $id );

		$mobile = Talika_Helpers::generate_css( $m_selectors, '#wpa-appointment-blocks-style-' . $id );

		$generated_css = array(
			'desktop' => $desktop,
			'tablet'  => $tablet,
			'mobile'  => $mobile,
		);

		return $generated_css;
	}

	public static function get_selectors( $attr ) {

		$bgType           = isset( $attr['buttonBGType'] ) ? $attr['buttonBGType'] : 'solid';
		$buttonBgGradient = isset( $attr['buttonBgGradient']['gradient'] ) ? $attr['buttonBgGradient']['gradient'] : 'linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%)';
		$buttonBGColor    = isset( $attr['buttonBGColor'] ) ? $attr['buttonBGColor'] : '#2670FF';
		$variation        = isset( $attr['buttonTypography']['variation'] ) ? $attr['buttonTypography']['variation'] : 'n4';

		$selectors = array(
			' .wpa-appointment-button'                    => array(
				'font-family'      => isset( $attr['buttonTypography']['family'] ) ? $attr['buttonTypography']['family'] : 'Default',
				'font-size'        => isset( $attr['buttonTypography']['size']['desktop'] ) ? $attr['buttonTypography']['size']['desktop'] : '18px',
				'line-height'      => isset( $attr['buttonTypography']['line-height']['desktop'] ) ? $attr['buttonTypography']['line-height']['desktop'] : '1.65',
				'text-transform'   => isset( $attr['buttonTypography']['text-transform'] ) ? $attr['buttonTypography']['text-transform'] : 'none',
				'text-decoration'  => isset( $attr['buttonTypography']['text-decoration'] ) ? $attr['buttonTypography']['text-decoration'] : 'none',
				'letter-spacing'   => isset( $attr['buttonTypography']['letter-spacing']['desktop'] ) ? $attr['buttonTypography']['letter-spacing']['desktop'] : '0em',
				'margin-top'       => isset( $attr['buttonMargin']['desktop']['top'] ) ? $attr['buttonMargin']['desktop']['top'] : '0px',
				'margin-left'      => isset( $attr['buttonMargin']['desktop']['left'] ) ? $attr['buttonMargin']['desktop']['left'] : '0px',
				'margin-right'     => isset( $attr['buttonMargin']['desktop']['right'] ) ? $attr['buttonMargin']['desktop']['right'] : '0px',
				'margin-bottom'    => isset( $attr['buttonMargin']['desktop']['bottom'] ) ? $attr['buttonMargin']['desktop']['bottom'] : '30px',
				'padding-top'      => isset( $attr['buttonPadding']['desktop']['top'] ) ? $attr['buttonPadding']['desktop']['top'] : '',
				'padding-left'     => isset( $attr['buttonPadding']['desktop']['left'] ) ? $attr['buttonPadding']['desktop']['left'] : '',
				'padding-right'    => isset( $attr['buttonPadding']['desktop']['right'] ) ? $attr['buttonPadding']['desktop']['right'] : '',
				'padding-bottom'   => isset( $attr['buttonPadding']['desktop']['bottom'] ) ? $attr['buttonPadding']['desktop']['bottom'] : '',
				'border-style'     => isset( $attr['buttonBorder']['style'] ) ? $attr['buttonBorder']['style'] : 'none',
				'border-width'     => isset( $attr['buttonBorder']['width'] ) ? $attr['buttonBorder']['width'] . 'px' : '1px',
				'border-color'     => isset( $attr['buttonBorder']['color']['color'] ) ? $attr['buttonBorder']['color']['color'] : '#dddddd',
				'color'            => isset( $attr['buttonTextColor'] ) ? $attr['buttonTextColor'] : '#ffffff',
				'background-color' => $buttonBGColor,
				'background'       => $bgType && $bgType === 'solid' ? $buttonBGColor : $buttonBgGradient,
				'box-shadow'       => isset( $attr['buttonShadow'] ) && $attr['buttonShadow']['enable'] ? Talika_Helpers::get_css_boxshadow( $attr['buttonShadow'] ) : 'none',
				'font-weight'      => Talika_Helpers::get_fontweight_variation( $variation ),
				'font-style'       => Talika_Helpers::get_font_style( $variation ),
				'border-radius'    => isset( $attr['buttonRadius']['desktop']['top'] ) && isset( $attr['buttonRadius']['desktop']['right'] ) && isset( $attr['buttonRadius']['desktop']['bottom'] ) && isset( $attr['buttonRadius']['desktop']['left'] ) ? $attr['buttonRadius']['desktop']['top'] . ' ' . $attr['buttonRadius']['desktop']['right'] . ' ' . $attr['buttonRadius']['desktop']['bottom'] . ' ' . $attr['buttonRadius']['desktop']['left'] . ' ' : '0 0 0 0',

			),
			' .btn-is-fixed'                         => array(
				'max-width' => isset( $attr['buttonFixWidth'] ) ? $attr['buttonFixWidth'] : '100px',
				'width'     => '100%',
			),
			' .wpa-appointment-btn-inner'     => array(
				'justify-content' => isset( $attr['buttonAlignment'] ) ? $attr['buttonAlignment'] : 'flex-start',
			),
			' .wpa-appointment-button:hover'              => array(
				'color'        => isset( $attr['buttonTextHoverColor'] ) ? $attr['buttonTextHoverColor'] : '#ffffff',
				'background'   => isset( $attr['buttonBGHoverColor'] ) ? $attr['buttonBGHoverColor'] : '#084ACA',
				'border-color' => isset( $attr['buttonborderHoverColor'] ) ? $attr['buttonborderHoverColor'] : '#ffffff',
			),
			' .button-icon'                          => array(
				'font-size' => isset( $attr['buttonIconSize'] ) ? $attr['buttonIconSize'] : '18px',
				'color'     => isset( $attr['buttonIconColor'] ) ? $attr['buttonIconColor'] : '#ffffff',
			),
			' .wpa-appointment-button:hover .button-icon' => array(
				'color' => isset( $attr['buttonIconHoverColor'] ) ? $attr['buttonIconHoverColor'] : '#ffffff',
			),
		);

		return $selectors;
	}

	public static function get_mobileselectors( $attr ) {

		$mobile_selectors = array(
			' .wpa-appointment-button'            => array(
				'font-size'      => isset( $attr['buttonTypography']['size']['mobile'] ) ? $attr['buttonTypography']['size']['mobile'] : '18px',
				'line-height'    => isset( $attr['buttonTypography']['line-height']['mobile'] ) ? $attr['buttonTypography']['line-height']['mobile'] : '1.65',
				'letter-spacing' => isset( $attr['buttonTypography']['letter-spacing']['mobile'] ) ? $attr['buttonTypography']['letter-spacing']['mobile'] : '0em',
				'margin-top'     => isset( $attr['buttonMargin']['mobile']['top'] ) ? $attr['buttonMargin']['mobile']['top'] : '0px',
				'margin-left'    => isset( $attr['buttonMargin']['mobile']['left'] ) ? $attr['buttonMargin']['mobile']['left'] : '0px',
				'margin-right'   => isset( $attr['buttonMargin']['mobile']['right'] ) ? $attr['buttonMargin']['mobile']['right'] : '0px',
				'margin-bottom'  => isset( $attr['buttonMargin']['mobile']['bottom'] ) ? $attr['buttonMargin']['mobile']['bottom'] : '30px',
				'padding-top'    => isset( $attr['buttonPadding']['mobile']['top'] ) ? $attr['buttonPadding']['mobile']['top'] : '',
				'padding-left'   => isset( $attr['buttonPadding']['mobile']['left'] ) ? $attr['buttonPadding']['mobile']['left'] : '',
				'padding-right'  => isset( $attr['buttonPadding']['mobile']['right'] ) ? $attr['buttonPadding']['mobile']['right'] : '',
				'padding-bottom' => isset( $attr['buttonPadding']['mobile']['bottom'] ) ? $attr['buttonPadding']['mobile']['bottom'] : '',
				'border-radius'  => isset( $attr['buttonRadius']['mobile']['top'] ) && isset( $attr['buttonRadius']['mobile']['right'] ) && isset( $attr['buttonRadius']['mobile']['bottom'] ) && isset( $attr['buttonRadius']['mobile']['left'] ) ? $attr['buttonRadius']['mobile']['top'] . ' ' . $attr['buttonRadius']['mobile']['right'] . ' ' . $attr['buttonRadius']['mobile']['bottom'] . ' ' . $attr['buttonRadius']['mobile']['left'] . ' ' : '0 0 0 0',

			),
		);

		return $mobile_selectors;
	}

	public static function get_tabletselectors( $attr ) {

		$tablet_selectors = array(
			' .wpa-appointment-button'            => array(
				'font-size'      => isset( $attr['buttonTypography']['size']['tablet'] ) ? $attr['buttonTypography']['size']['tablet'] : '18px',
				'line-height'    => isset( $attr['buttonTypography']['line-height']['tablet'] ) ? $attr['buttonTypography']['line-height']['tablet'] : '1.65',
				'letter-spacing' => isset( $attr['buttonTypography']['letter-spacing']['tablet'] ) ? $attr['buttonTypography']['letter-spacing']['tablet'] : '0em',
				'margin-top'     => isset( $attr['buttonMargin']['tablet']['top'] ) ? $attr['buttonMargin']['tablet']['top'] : '0px',
				'margin-left'    => isset( $attr['buttonMargin']['tablet']['left'] ) ? $attr['buttonMargin']['tablet']['left'] : '0px',
				'margin-right'   => isset( $attr['buttonMargin']['tablet']['right'] ) ? $attr['buttonMargin']['tablet']['right'] : '0px',
				'margin-bottom'  => isset( $attr['buttonMargin']['tablet']['bottom'] ) ? $attr['buttonMargin']['tablet']['bottom'] : '30px',
				'padding-top'    => isset( $attr['buttonPadding']['tablet']['top'] ) ? $attr['buttonPadding']['tablet']['top'] : '',
				'padding-left'   => isset( $attr['buttonPadding']['tablet']['left'] ) ? $attr['buttonPadding']['tablet']['left'] : '',
				'padding-right'  => isset( $attr['buttonPadding']['tablet']['right'] ) ? $attr['buttonPadding']['tablet']['right'] : '',
				'padding-bottom' => isset( $attr['buttonPadding']['tablet']['bottom'] ) ? $attr['buttonPadding']['tablet']['bottom'] : '',
				'border-radius'  => isset( $attr['buttonRadius']['tablet']['top'] ) && isset( $attr['buttonRadius']['tablet']['right'] ) && isset( $attr['buttonRadius']['tablet']['bottom'] ) && isset( $attr['buttonRadius']['tablet']['left'] ) ? $attr['buttonRadius']['tablet']['top'] . ' ' . $attr['buttonRadius']['tablet']['right'] . ' ' . $attr['buttonRadius']['tablet']['bottom'] . ' ' . $attr['buttonRadius']['tablet']['left'] . ' ' : '0 0 0 0',

			),
		);

		return $tablet_selectors;
	}

}
