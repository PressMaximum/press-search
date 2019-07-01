<?php
/**
 * Plugin Name: WordPress & WooCommerce Ajax Live Search SUFFIX
 * Plugin URI: https://pressmaximum.com/press-search/
 * Description: A better search engine for WordPress and WooCommerce. Quickly and accurately.
 * Version: 0.0.2
 * Author: PressMaximum
 * Author URI: https://pressmaximum.com/
 * Text Domain: press-search
 * Domain Path: /languages
 * License: GPL-2.0+
 */
function ps_is__pro() {
	return true;
}

if ( ps_is__pro() ) {
	define( 'PRESS_SEARCH_ITEM_ID', 11070 );
}

global $wpdb;
global $press_search_configs;
$press_search_plugin_data = get_file_data(
	__FILE__,
	array(
		'Version' => 'Version',
		'Name' => 'Plugin Name',
	),
	'plugin'
);
$press_search_configs = array(
	'plugin_url'        => plugin_dir_url( __FILE__ ),
	'plugin_dir'        => plugin_dir_path( __FILE__ ),
	'plugin_version'    => $press_search_plugin_data['Version'],
	'plugin_name'       => $press_search_plugin_data['Name'],
	'db_version'        => '0.0.1',
	'tbl_index'         => $wpdb->prefix . 'ps_index',
	'tbl_logs'          => $wpdb->prefix . 'ps_logs',
	'db_option_key'     => 'press_search_',
	'upgrade_pro_url'   => '#',
	'default_operator'  => 'or',
	'default_searching_weights' => array(
		'title' => 1000,
		'content' => 0.01,
		'excerpt' => 0.1,
		'category' => 3,
		'tag' => 2,
		'custom_field' => 0.005,
	),
);

if ( ! function_exists( 'press_search_get_var' ) ) {
	function press_search_get_var( $key = '' ) {
		global $press_search_configs;
		if ( isset( $press_search_configs[ $key ] ) ) {
			return $press_search_configs[ $key ];
		}
		return false;
	}
}
if ( ! class_exists( 'Press_Search' ) ) {
	require_once press_search_get_var( 'plugin_dir' ) . 'inc/class-press-search.php';
}

if ( ! function_exists( 'press_search' ) ) {
	/**
	 * Main instance of Press_Search.
	 *
	 * Returns the main instance of Press_Search to prevent the need to use globals.
	 *
	 * @since  0.1.0
	 * @return Press_Search
	 */
	function press_search() {
		return Press_Search::instance();
	}
}

if ( ps_is__pro() ) {
	function press_search_deactivate_free_version() {
		$pro_plugin = 'press-search/press-search.php';
		if ( file_exists( WP_PLUGIN_DIR . '/' . $pro_plugin ) ) { // Check if both plugin exists.
			$free_plugin = 'press-search-pro/press-search.php';
			$plugin_basename = plugin_basename( __FILE__ );
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			if ( $free_plugin != $plugin_basename ) {
				deactivate_plugins( $free_plugin );
			}
		}
	}

	press_search_deactivate_free_version();
}

function press_search_SUFFIX_activation() {
	press_search()->register_activation_hook();
	if ( ps_is__pro() ) {
		press_search_deactivate_free_version();
	}
}

register_activation_hook( __FILE__, 'press_search_SUFFIX_activation' );
register_deactivation_hook( __FILE__, array( press_search(), 'cronjob_deactivation' ) );


function press_search__SUFFIX_init() {
	press_search();
	press_search_indexing();
	press_search_query();
}
add_action( 'plugins_loaded', 'press_search__SUFFIX_init', 2 );


