<?php
/**
 * Plugin Name: PressSEARCH
 * Plugin URI:  #
 * Description: A started plugin.
 * Version:     0.0.1
 * Author:      PressMaximum
 * Author URI:  #
 * Text Domain: press-search
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

/**
 * Copyright (c) 2018 PressMaximum (email : PressMaximum@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
// Useful global constants.
define( 'PRESS_SEARCH_VERSION', '0.0.1' );
define( 'PRESS_SEARCH_URL', plugin_dir_url( __FILE__ ) );
define( 'PRESS_SEARCH_DIR', plugin_dir_path( __FILE__ ) );

class Press_Search_Start {
	/**
	 * Plugin url
	 *
	 * @var [string]
	 */
	protected $plugin_url;
	/**
	 * Plugin dir
	 *
	 * @var [string]
	 */
	protected $plugin_dir;
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_url = PRESS_SEARCH_URL;
		$this->plugin_dir = PRESS_SEARCH_DIR;
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$this->load_files();
	}

	/**
	 * Load text domain
	 *
	 * @return void
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'press-search', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Load neccessary files
	 *
	 * @return void
	 */
	function load_files() {
		// Load custom cm2 fields.
		if ( file_exists( $this->plugin_dir . 'inc/helpers/init.php' ) ) {
			require_once $this->plugin_dir . 'inc/helpers/init.php';
		}
		if ( ! class_exists( 'CMB2' ) ) {
			if ( file_exists( $this->plugin_dir . 'inc/3rd/CMB2/init.php' ) ) {
				require_once $this->plugin_dir . 'inc/3rd/CMB2/init.php';
			}
		}
		// Include files.
		require_once $this->plugin_dir . 'inc/admin/class-setting.php';
		require_once $this->plugin_dir . 'inc/class-plugin.php';
	}
}
new Press_Search_Start();
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

add_action( 'plugins_loaded', 'press_search_init', 2 );
function press_search_init() {
	press_search();
}
