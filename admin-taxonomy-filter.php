<?php
/**
 * Plugin Name: WC Order Categories
 * Plugin URI:  https://elightup.com
 * Description: Filter posts or custom post types by taxonomy in the admin area.
 * Version:     1.0.2
 * Author:      eLightUp
 * Author URI:  https://elightup.com
 * License:     GPL2+
 * Text Domain: admin-taxonomy-filter
 * Domain Path: /languages/
 */

defined( 'ABSPATH' ) || die;

if ( is_admin() ) {
	require __DIR__ . '/inc/controller.php';


	new ATF_Controller;

}

add_action( 'init', function () {
	load_plugin_textdomain( 'admin-taxonomy-filter', false, plugin_basename( __DIR__ ) . '/languages/' );
} );