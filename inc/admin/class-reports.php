<?php

class Press_Search_Reports {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Reports
	 * @since 0.1.0
	 */
	protected static $_instance = null;

	protected $db_option_key = 'press_search_';

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

	public function get_indexing_progress() {
		$db_data = array(
			'post_unindex' => get_option( $this->db_option_key . 'post_to_index', array() ),
			'post_indexed' => get_option( $this->db_option_key . 'post_indexed', array() ),
			'term_unindex' => get_option( $this->db_option_key . 'term_to_index', array() ),
			'term_indexed' => get_option( $this->db_option_key . 'term_indexed', array() ),
		);
		foreach ( $db_data as $k => $v ) {
			$db_data[ $k ] = count( $v );
		}
		$total_posts = $db_data['post_unindex'] + $db_data['post_indexed'];
		$total_terms = $db_data['term_unindex'] + $db_data['term_indexed'];
		$total_items = $total_posts + $total_terms;
		$total_items_indexed = $db_data['post_indexed'] + $db_data['term_indexed'];
		$percent_progress = ( $total_items_indexed / $total_items ) * 100;
		$return = array(
			'percent_progress'  => ( is_float( $percent_progress ) ) ? number_format( $percent_progress, 2 ) : $percent_progress,
			'post_indexed'      => $db_data['post_indexed'],
			'post_unindex'      => $db_data['post_unindex'],
			'term_indexed'      => $db_data['term_indexed'],
			'term_unindex'      => $db_data['term_unindex'],
			'last_activity'     => get_option( $this->db_option_key . 'last_time_index', esc_html__( 'No data', 'press-search' ) ),
		);
		return $return;
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
