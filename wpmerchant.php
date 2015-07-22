<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              wpmerchant.com/team
 * @since             1.0.0
 * @package           Wpmerchant
 *
 * @wordpress-plugin
 * Plugin Name:       WPMerchant
 * Plugin URI:        wpmerchant.com
 * Description:       Start making money by easily and professionally selling anything on your website.
 * Version:           1.0.2
 * Author:            WPMerchant
 * Author URI:        wpmerchant.com/team
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpmerchant
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpmerchant-activator.php
 */
function activate_wpmerchant() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpmerchant-activator.php';
	Wpmerchant_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpmerchant-deactivator.php
 */
function deactivate_wpmerchant() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpmerchant-deactivator.php';
	Wpmerchant_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpmerchant' );
register_deactivation_hook( __FILE__, 'deactivate_wpmerchant' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpmerchant.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpmerchant() {

	$plugin = new Wpmerchant();
	$plugin->run();

}
run_wpmerchant();