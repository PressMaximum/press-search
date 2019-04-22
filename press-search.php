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

function ps_is__pro() {
	return false;
}

function press_search_get_var( $key = '' ) {
	global $wpdb;
	$configs = array(
		'plugin_url'        => plugin_dir_url( __FILE__ ),
		'plugin_dir'        => plugin_dir_path( __FILE__ ),
		'plugin_version'    => '0.0.1',
		'db_version'        => '0.0.1',
		'tbl_index'         => $wpdb->prefix . 'ps_index',
		'tbl_logs'          => $wpdb->prefix . 'ps_logs',
		'db_option_key'     => 'press_search_',
	);
	if ( isset( $configs[ $key ] ) ) {
		return $configs[ $key ];
	}
	return false;
}

class Press_Search_Start {
	/**
	 * Plugin url
	 *
	 * @var string
	 */
	protected $plugin_url;
	/**
	 * Plugin dir
	 *
	 * @var string
	 */
	protected $plugin_dir;

	protected static $_instance = null;
	public static function get_instance() {
		if ( null == self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_url = press_search_get_var( 'plugin_url' );
		$this->plugin_dir = press_search_get_var( 'plugin_dir' );
		$this->db_version = press_search_get_var( 'db_version' );
		add_action( 'init', array( $this, 'load_textdomain' ) );
		$this->load_files();
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		add_action( 'init', array( $this, 'search_log_cronjob' ), 1 );
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
	 * Load necessary files
	 *
	 * @return void
	 */
	function load_files() {
		if ( file_exists( $this->plugin_dir . 'inc/helpers/init.php' ) ) {
			require_once $this->plugin_dir . 'inc/helpers/init.php';
		}
		// Load custom cm2 fields.
		if ( ! class_exists( 'CMB2' ) ) {
			if ( file_exists( $this->plugin_dir . 'inc/3rd/CMB2/init.php' ) ) {
				require_once $this->plugin_dir . 'inc/3rd/CMB2/init.php';
			}
		}
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		// Include files.
		require_once $this->plugin_dir . 'inc/admin/class-reports.php';
		require_once $this->plugin_dir . 'inc/admin/reports/class-table-no-results.php';
		require_once $this->plugin_dir . 'inc/admin/reports/class-table-popular-searches.php';
		require_once $this->plugin_dir . 'inc/admin/reports/class-table-search-logs.php';
		require_once $this->plugin_dir . 'inc/admin/class-setting.php';
		require_once $this->plugin_dir . 'inc/class-plugin.php';
		require_once $this->plugin_dir . 'inc/class-string-process.php';
		require_once $this->plugin_dir . 'inc/class-crawl-data.php';
		require_once $this->plugin_dir . 'inc/class-indexing.php';
		require_once $this->plugin_dir . 'inc/class-search-engines.php';
		if ( file_exists( $this->plugin_dir . 'inc/admin/class-setting-hooks.php' ) ) {
			require_once $this->plugin_dir . 'inc/admin/class-setting-hooks.php';
		}
		require_once $this->plugin_dir . 'inc/class-search-query.php';
		require_once $this->plugin_dir . 'inc/class-searching.php';
	}

	public function add_custom_schedules( $schedules ) {
		// Register schedules for auto indexing.
		$schedules['press_search_everyminute'] = array(
			'interval' => 60,
			'display' => esc_html__( 'Press Search Every Minute', 'press-search' ),
		);
		// Register schedules for auto deleting logs.
		$loging_save_time = press_search_get_setting( 'loging_save_log_time', 0 );
		$loging_save_time = absint( $loging_save_time );
		if ( $loging_save_time > 0 ) {
			$schedules_key = "press_search_every_{$loging_save_time}_days";
			$schedules[ $schedules_key ] = array(
				'interval' => 60 * 60 * 24 * $loging_save_time,
				'display' => sprintf( '%s %d %s', esc_html__( 'Press Search Every', 'press-search' ), $loging_save_time, esc_html__( 'Days', 'press-search' ) ),
			);
		}

		return $schedules;
	}

	public function register_activation_hook() {
		$this->create_db_tables();
		$this->cronjob_activation();
	}

	public function search_log_cronjob() {
		// Schedule event for auto deleting logs.
		$loging_save_time = press_search_get_setting( 'loging_save_log_time', 0 );
		$loging_save_time = absint( $loging_save_time );
		if ( $loging_save_time > 0 ) {
			$schedules_key = "press_search_every_{$loging_save_time}_days";
			if ( ! wp_next_scheduled( 'press_search_auto_delete_logs' ) ) {
				wp_schedule_event( time(), $schedules_key, 'press_search_auto_delete_logs' );
			}
		}
	}

	public function cronjob_activation() {
		// Schedule event for indexing.
		if ( ! wp_next_scheduled( 'press_search_indexing_cronjob' ) ) {
			wp_schedule_event( time(), 'press_search_everyminute', 'press_search_indexing_cronjob' );
		}
	}

	public function cronjob_deactivation() {
		$timestamp = wp_next_scheduled( 'press_search_indexing_cronjob' );
		wp_unschedule_event( $timestamp, 'press_search_indexing_cronjob' );
	}

	public function create_db_tables() {
		global $wpdb;
		$table_indexing = press_search_get_var( 'tbl_index' );
		$table_search_logs = press_search_get_var( 'tbl_logs' );
		$charset_collate = $wpdb->get_charset_collate();

		$indexing_sql = "
			CREATE TABLE IF NOT EXISTS `$table_indexing` (
				`object_id` bigint(20) NOT NULL,
				`object_type` varchar(50) NOT NULL,
				`term` varchar(50) NOT NULL,
				`term_reverse` varchar(50) NOT NULL,
				`title` mediumint(9) NOT NULL,
				`content` mediumint(9) NOT NULL,
				`excerpt` mediumint(9) NOT NULL,
				`author` mediumint(9) NOT NULL,
				`comment` mediumint(9) NOT NULL,
				`category` mediumint(9) NOT NULL,
				`tag` mediumint(9) NOT NULL,
				`taxonomy` mediumint(9) NOT NULL,
				`custom_field` mediumint(9) NOT NULL,
				`column_name` varchar(255) NOT NULL,
				`lat` double NOT NULL,
				`lng` double NOT NULL,
				`object_title` text NOT NULL,
				INDEX ps_object_type (`object_type`),
				INDEX ps_term (`term`),
				INDEX ps_term_reverse (`term_reverse`),
				UNIQUE KEY ps_index_key (`object_id`,`object_type`,`term`)
			) $charset_collate;
		";

		$search_log_sql = "
			CREATE TABLE IF NOT EXISTS `$table_search_logs` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`query` varchar(255) NOT NULL,
				`hits` mediumint NOT NULL,
				`date_time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				`ip` varchar(30) NOT NULL,
				`user_id` bigint(20) NOT NULL,
				`search_engine` varchar(255) NOT NULL,
				INDEX ps_query (`query`),
				PRIMARY KEY (id)
			) $charset_collate;
		";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $indexing_sql );
		dbDelta( $search_log_sql );
	}
}

function press_search_start() {
	return Press_Search_Start::get_instance();
}
press_search_start();

register_activation_hook( __FILE__, array( press_search_start(), 'register_activation_hook' ) );
register_deactivation_hook( __FILE__, array( press_search_start(), 'cronjob_deactivation' ) );

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

/**
 * Main instance of Press_Search_String_Process.
 *
 * Returns the main instance of Press_Search_String_Process to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Press_Search_String_Process
 */
function press_search_string() {
	return Press_Search_String_Process::instance();
}

/**
 * Main instance of Press_Search_Setting.
 *
 * Returns the main instance of Press_Search_Setting to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Press_Search_Setting
 */
function press_search_settings() {
	return Press_Search_Setting::instance();
}

/**
 * Main instance of Press_Search_Reports.
 *
 * Returns the main instance of Press_Search_Reports to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Press_Search_Reports
 */
function press_search_reports() {
	return Press_Search_Reports::instance();
}

function press_search_indexing() {
	return Press_Search_Indexing::instance();
}

function press_search_query() {
	return Press_Search_Query::instance();
}



add_action( 'plugins_loaded', 'press_search_init', 2 );
function press_search_init() {
	press_search();
	$GLOBALS['press_search_indexing'] = press_search_indexing();
	$GLOBALS['press_search_query'] = press_search_query();
}


