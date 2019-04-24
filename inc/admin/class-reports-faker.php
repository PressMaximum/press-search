<?php
class Press_Search_Reports_Faker {
	protected static $_instance = null;
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function get_search_logs( $limit = 20, $args = array() ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$return = array();
		$default_args = array(
			'orderby' => 'date_time',
			'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $default_args );
		$sql = "SELECT DISTINCT query, id, hits, date_time, ip, user_id, COUNT(query) as query_count FROM {$table_logs_name} GROUP BY query ORDER BY {$args['orderby']} {$args['order']}";
		if ( -1 !== $limit ) {
			$sql .= " LIMIT 0,{$limit}";
		}
		$results = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result->query ) && '' !== $result->query ) {
					$date_time = date( 'F d, Y H:m:i', strtotime( $result->date_time ) );
					$return[] = array(
						'ID' => $result->id,
						'query' => $result->query,
						'hits' => $result->hits,
						'query_count' => $result->query_count,
						'date_time' => $date_time,
						'ip' => $result->ip,
						'user_id' => $result->user_id,
					);
				}
			}
		}
		return $return;
	}

	public function get_no_results_search( $limit = 20, $orderby = 'date_time', $order = 'desc' ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$return = array();
		$order = strtoupper( $order );
		$sql = "SELECT DISTINCT query, id, hits, date_time, ip, user_id, COUNT(query) as query_count FROM {$table_logs_name} WHERE `hits` = 0 GROUP BY query ORDER BY {$orderby} {$order}";
		if ( -1 !== $limit ) {
			$sql .= " LIMIT 0,{$limit}";
		}
		$results = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result->query ) && '' !== $result->query ) {
					$date_time = date( 'F d, Y H:m:i', strtotime( $result->date_time ) );
					$return[] = array(
						'ID' => $result->id,
						'query' => $result->query,
						'hits' => $result->hits,
						'query_count' => $result->query_count,
						'date_time' => $date_time,
						'ip' => $result->ip,
						'user_id' => $result->user_id,
					);
				}
			}
		}
		return $return;
	}

	public function get_popular_search( $limit = 20, $orderby = 'query_count', $order = 'desc' ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$return = array();
		$order = strtoupper( $order );
		$sql = "SELECT DISTINCT query, id, hits, date_time, ip, user_id, COUNT(query) as query_count FROM {$table_logs_name} WHERE `hits` > 0 GROUP BY query ORDER BY {$orderby} {$order}";
		if ( -1 !== $limit ) {
			$sql .= " LIMIT 0,{$limit}";
		}
		$results = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result->query ) && '' !== $result->query ) {
					$date_time = date( 'F d, Y H:m:i', strtotime( $result->date_time ) );
					$return[] = array(
						'ID' => $result->id,
						'query' => $result->query,
						'hits' => $result->hits,
						'query_count' => $result->query_count,
						'date_time' => $date_time,
						'ip' => $result->ip,
						'user_id' => $result->user_id,
					);
				}
			}
		}
		return $return;
	}

	public function search_logs_for_chart() {
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

		$search_logs = $this->get_search_logs_for_chart( -1, $report_args );
		$labels = array();
		$searches = array();
		$hits = array();
		$no_results = array();
		foreach ( $search_logs as $log ) {
			$labels[] = $log['date_time'];
			$searches[] = $log['query_count'];
			$no_results[] = $log['no_result'];
		}
		$return = array(
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => esc_html__( 'Total Searches', 'press-search' ),
					'data' => $searches,
					'fill' => false,
					'backgroundColor' => '#0073aa',
					'borderColor' => '#0073aa',
				),
				array(
					'label' => esc_html__( 'No Result Searches', 'press-search' ),
					'data' => $no_results,
					'fill' => false,
					'backgroundColor' => '#ca4a1f',
					'borderColor' => '#ca4a1f',
					'type' => 'line',
				),
			),
		);
		return $return;
	}
}

function press_search_report_faker() {
	return Press_Search_Reports_Faker::get_instance();
}
