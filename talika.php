<?php
/**
 * Plugin Name:       Talika
 * Plugin URI:        https://kraftplugins.com/talika/
 * Description:       Talika is a free online appointment and scheduling plugin to manage your services and staff.
 * Version:           1.0.0
 * Author:            Kraft Plugins
 * Author URI:        https://kraftplugins.com
 * License:           GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       talika
 * Domain Path:       /languages
 */

use Talika\TalikaAPP;

defined( 'ABSPATH' ) || exit;

// Include the autoloader.
require_once __DIR__ . '/vendor/autoload.php';

if ( ! defined( 'TALIKA_PLUGIN_FILE' ) ) {
	define( 'TALIKA_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'TALIKA_AUTH_SECRET_KEY' ) ) {
	define( 'TALIKA_AUTH_SECRET_KEY', SECURE_AUTH_KEY );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/classes/class-talika-activator.php
 */
function talika_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-talika-activator.php';
	Talika\Talika_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/classes/class-talika-deactivator.php
 */
function talika_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/classes/class-talika-deactivator.php';
	Talika\Talika_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'talika_activate' );
register_deactivation_hook( __FILE__, 'talika_deactivate' );

/**
 * Function
 * Return the main instance of Talika
 *
 * @since 1.0.0
 * @return TalikaAPP
 */
function TALIKA_APP() {
	return TalikaAPP::instance();
}

$GLOBALS['Appointment'] = TALIKA_APP();
