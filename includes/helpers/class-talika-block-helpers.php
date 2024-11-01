<?php
/**
 * Talika Block Helper.
 *
 * @package Talika
 */
if ( ! class_exists( 'Talika_Block_Helpers' ) ) {

	/**
	 * Class Talika_Block_Helpers.
	 */
	class Talika_Block_Helpers {

		public static function get_block_css( $blockname, $attr, $id ) {
			$block_css     = '';
			$blockfilename = sanitize_title( $blockname );
			if ( file_exists(  plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/helpers/block-helpers/class-' . $blockfilename . '-styles.php') ) {
				include_once plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/helpers/block-helpers/class-' . $blockfilename . '-styles.php';
				$blockname = str_replace( ' ', '_', $blockname );
				$class     = "Talika_{$blockname}_Styles";
				if ( class_exists( $class ) ) {
					$block_class = new $class();
					$block_css   = $block_class::block_css( $attr, $id);
				}
			}
			return $block_css;
		}

		public static function get_block_fonts( $blockname, $attr ) {
			$block_fonts   = array();
			$blockfilename = sanitize_title( $blockname );
			if ( file_exists(  plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/helpers/block-helpers/class-' . $blockfilename . '-styles.php') ) {
				include_once plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/includes/helpers/block-helpers/class-' . $blockfilename . '-styles.php';
				$blockname = str_replace( ' ', '_', $blockname );
				$class     = "Talika_{$blockname}_Styles";
				if ( class_exists( $class ) ) {
					$block_class = new $class();
					$block_fonts = $block_class::block_fonts( $attr);
				}
			}
			return $block_fonts;
		}

	}
}
