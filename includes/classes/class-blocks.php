<?php
/**
 * Init Gutenberg Blocks
 * 
 * @package Talika
 */
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
add_action( 'enqueue_block_assets', 'talika_gutenberg_block_assets' );
/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function talika_gutenberg_block_assets() { // phpcs:ignore
	
	if( ! is_admin() ) {

		$should_enqueue = has_block( 'talika/add-appointment-button' );
		if ( $should_enqueue ) {
			wp_enqueue_style(
				'talika-gutenberg-block-frontend', // Handle.
				plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/app/build/blocksPublic.css'
			);
		}
	}

	// Styles.
	wp_enqueue_style(
		'all',
		plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/assets/dist/fontawesome/css/all.min.css',
		array(),
		'5.2.0'
	);
}


add_action( 'enqueue_block_editor_assets', 'talika_gb_editor_assets' );
/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function talika_gb_editor_assets() { // phpcs:ignore
	
	$blocks_deps = include_once plugin_dir_path( TALIKA_PLUGIN_FILE ) . '/app/build/blocks.asset.php';
	wp_enqueue_script( 
		'talika-block-js', 
		plugin_dir_url( TALIKA_PLUGIN_FILE ) . 'app/build/blocks.js',
		$blocks_deps['dependencies'],
		$blocks_deps['version'],
		true 
	);

	wp_localize_script( 'talika-block-js', 'wpapp', array( 
		'setting_options'     => "",
		'ajaxURL'             => admin_url( 'admin-ajax.php' ),
		'pluginURL'           => TALIKA_PLUGIN_URL,
	) );

	wp_set_script_translations( 'talika-block-js', 'talika' );
	
	// Styles.
	wp_enqueue_style(
		'talika-block-style-css', // Handle.
		plugin_dir_url( TALIKA_PLUGIN_FILE ) . '/app/build/blockscss.css', // Block style CSS.
		array( 'wp-editor' ), // Dependency to include the CSS after it.
		TALIKA_VERSION // Version: File modification time.
	);

}
add_filter( 'block_categories_all', 'talika_block_categories', 10, 2 );
/**
 * Register new Block Category
 */
function talika_block_categories( $categories, $post ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'talika',
				'title' => __( 'Talika', 'talika' ),
				// 'icon'  => '',
			),
		)
	);
}
