<?php
class Press_Search {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * The plugin dir
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $plugin_dir;
	/**
	 * The plugin url
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $plugin_url;
	/**
	 * The plugin version
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $plugin_version;
	/**
	 * Admin var
	 *
	 * @var Press_Search_Admin
	 * @since 0.1.0
	 */
	protected static $admin = null;
	/**
	 * Instance
	 *
	 * @return Press_Search
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Press_Search Constructor.
	 */
	public function __construct() {
		$this->setup();
		if ( is_admin() ) {
			$this->setup_admin();
		}
		$this->load_files();
	}

	/**
	 * Method setup.
	 */
	public function setup() {
		$this->plugin_dir = press_search_get_var( 'plugin_dir' );
		$this->plugin_url = press_search_get_var( 'plugin_url' );
		$this->plugin_version = press_search_get_var( 'plugin_version' );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		do_action( 'press_search_loaded' );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'activated_plugin', array( $this, 'plugin_activation_redirect' ), PHP_INT_MAX );
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		add_action( 'init', array( $this, 'search_log_cronjob' ), 1 );

		if ( ps_is__pro() ) {
			require_once $this->plugin_dir . 'updater/class-updater.php';
			if ( is_admin() ) {
				new Press_Search_Pro_Updater( PRESS_SEARCH_ITEM_ID, $this->plugin_dir, admin_url( 'admin.php?page=press-search-settings&tab=license' ) );
			}
		}
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
		if ( ps_is__pro() ) {
			require_once $this->plugin_dir . 'inc/admin/class-reports-pro.php';
		} else {
			require_once $this->plugin_dir . 'inc/admin/class-reports-placeholder.php';
		}
		require_once $this->plugin_dir . 'inc/admin/reports/class-table-no-results.php';
		require_once $this->plugin_dir . 'inc/admin/reports/class-table-popular-searches.php';
		require_once $this->plugin_dir . 'inc/admin/reports/class-table-search-logs.php';
		require_once $this->plugin_dir . 'inc/admin/class-setting.php';

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

	/**
	 * Load text domain
	 *
	 * @return void
	 */
	function load_textdomain() {
		load_plugin_textdomain( 'press-search', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	public function plugin_activation_redirect( $plugin ) {
		if ( plugin_basename( __FILE__ ) === $plugin ) {
			$redirect_args = array(
				'page' => 'press-search-settings',
				'tab' => 'engines',
			);
			$redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );
			exit( wp_redirect( $redirect_url ) );
		}
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

	/**
	 * Method enqueue_scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'press-search', $this->plugin_url . 'assets/css/frontend.css', array(), $this->plugin_version );

		wp_enqueue_script( 'press-search', $this->plugin_url . 'assets/js/frontend.js', array( 'jquery' ), $this->plugin_version, true );
	}

	/**
	 * Method setup_admin.
	 */
	public function setup_admin() {
		require_once $this->plugin_dir . 'inc/admin/class-admin.php';
		self::$admin = new Press_Search_Admin();
	}

	public function cronjob_deactivation() {
		$timestamp = wp_next_scheduled( 'press_search_indexing_cronjob' );
		wp_unschedule_event( $timestamp, 'press_search_indexing_cronjob' );
	}

	public function register_activation_hook() {
		$this->create_db_tables();
		$this->cronjob_activation();
	}

	public function cronjob_activation() {
		// Schedule event for indexing.
		if ( ! wp_next_scheduled( 'press_search_indexing_cronjob' ) ) {
			wp_schedule_event( time(), 'press_search_everyminute', 'press_search_indexing_cronjob' );
		}
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

