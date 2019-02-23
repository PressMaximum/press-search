<?php

class Press_Search_Reports {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Reports
	 * @since 0.1.0
	 */
	protected static $_instance = null;

	/**
	 * Instance
	 *
	 * @return Press_Search_Reports
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

	}

	public function engines_tab_content() {
		esc_html_e( 'Engines tab content', 'press-search' );
	}

	public function engines_popular_search_content() {
		esc_html_e( 'Popular searches tab content', 'press-search' );
	}

	public function engines_no_results_content() {
		esc_html_e( 'No result tab content', 'press-search' );
	}

	public function logging_subtab_report_content() {
		esc_html_e( 'Logging subtab reports content', 'press-search' );
	}

}
