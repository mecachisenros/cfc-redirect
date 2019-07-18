<?php
/**
 * Plugin Name: Caldera Forms CiviCRM Redirect
 * Description: Redirect CiviCRM Contribution and Event pages to Caldera Forms.
 * Version: 0.3
 * Author: Andrei Mondoc
 * Author URI: https://github.com/mecachisenros
 * Plugin URI: https://github.com/mecachisenros/cfc-redirect
 * GitHub Plugin URI: mecachisenros/cfc-redirect
 */

// bail if called directly
if ( ! defined( 'WPINC' ) ) die( 'Cheating huh!?' );

// version
define( 'CFC_REDIRECT_VERSION', '0.3' );
// plugin basename
define( 'CFC_REDIRECT_BASE', plugin_basename( __FILE__ ) );
// plugin path
define( 'CFC_REDIRECT_PATH', plugin_dir_path( __FILE__ ) );
// source path
define( 'CFC_REDIRECT_SRC', CFC_REDIRECT_PATH . 'src' );
// plugin url
define( 'CFC_REDIRECT_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {

	// bail if Caldera Forms is not available
	if ( ! defined( 'CFCORE_VER' ) ) return;
	// bail if CiviCRM is not available
	if ( ! function_exists( 'civi_wp' ) ) return;

	// autoloader
	require_once( trailingslashit( CFC_REDIRECT_SRC ) . 'Autoloader.php' );
	new CFCR\Autoloader( $namespace = 'CFCR', $source_path = CFC_REDIRECT_SRC );

	// initialize plugin
	new CFCR\Plugin( __FILE__ );

} );
