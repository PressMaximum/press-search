<?php
class Press_Search_Report_Search_Logs extends WP_List_Table {
	protected static $_instance = null;
	protected $per_page = 20;
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::$_instance->init();
		}
		return self::$_instance;
	}

	public function init() {
		$items_per_page = press_search_reports()->get_screen_items_per_page();
		$this->per_page = $items_per_page;
	}

	protected function get_table_data() {
		$search_engine = 'all';
		$filter_date = '';
		if ( isset( $_GET['search_engine'] ) ) {
			$search_engine = sanitize_text_field( wp_unslash( $_GET['search_engine'] ) );
		}
		if ( isset( $_GET['date'] ) ) {
			$filter_date = sanitize_text_field( wp_unslash( $_GET['date'] ) );
			if ( false !== strpos( $filter_date, 'to' ) ) {
				$filter_date = explode( 'to', $filter_date );
			}
		}
		$report_args = array(
			'search_engine' => $search_engine,
			'date' => $filter_date,
		);
		$result = press_search_reports()->get_search_logs( -1, $report_args );
		return $result;
	}

	public function get_list_table_data() {
		$results = $this->get_table_data();
		$per_page = $this->per_page;
		$current_page = $this->get_pagenum();
		$data = array_slice( $results, ( ( $current_page - 1 ) * $per_page ), $per_page );
		return $data;
	}

	public function __construct() {
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => 'press_seach_report',
				'plural'   => 'press_seach_reports',
				'ajax'     => false,
			)
		);
	}
	public function get_columns() {
		$columns = array(
			'query'    => _x( 'Keywords', 'Column label', 'press-search' ),
			'query_count'    => _x( 'Total searches', 'Column label', 'press-search' ),
			'hits' => _x( 'Hits', 'Column label', 'press-search' ),
			'date_time' => _x( 'Latest time', 'Column label', 'press-search' ),
		);
		return $columns;
	}
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'query_count'    => array( 'query_count', false ),
			'query'    => array( 'query', false ),
			'hits'    => array( 'hits', false ),
		);
		return $sortable_columns;
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'query':
			case 'query_count':
			case 'hits':
			case 'date_time':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	protected function column_cb( $item ) {
		return sprintf(
			'',
			$this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$item['ID']                // The value of the checkbox should be the record's ID.
		);
	}

	protected function get_bulk_actions() {
		$actions = array();
		return $actions;
	}
	protected function process_bulk_action() {
		if ( 'delete' === $this->current_action() ) {
			wp_die( 'Items deleted (or they would be if we had items to delete)!' );
		}
	}
	function prepare_items() {
		global $wpdb;
		$per_page = $this->per_page;
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$data = $this->get_table_data();
		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->items = $data;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

function press_search_report_search_logs() {
	return Press_Search_Report_Search_Logs::get_instance();
}
