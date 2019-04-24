<?php
class Press_Search_Reports_Pro {
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

	public function get_search_logs_for_chart( $limit = 20, $args = array() ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$return = array();
		$where = 'WHERE 1=1';
		if ( isset( $args['search_engine'] ) && 'all' !== $args['search_engine'] ) {
			$where .= " AND search_engine='{$args['search_engine']}'";
		}
		$allow_fixed_date = array(
			'current_year',
			'current_month',
			'last_7_days',
		);
		$select_by_year = false;
		if ( isset( $args['date'] ) ) {
			if ( is_string( $args['date'] ) && in_array( $args['date'], $allow_fixed_date ) ) {
				switch ( $args['date'] ) {
					case 'current_year':
						$select_by_year = true;
						$where .= ' AND YEAR(date_time) = YEAR(CURDATE())';
						break;
					case 'current_month':
						$where .= ' AND MONTH(date_time) = MONTH(CURRENT_DATE())';
						break;
					case 'last_7_days':
						$where .= ' AND date_time >= (SELECT SUBDATE(CURDATE(), INTERVAL 7 DAY))';
						break;
				}
			} elseif ( is_array( $args['date'] ) ) {
				$date_from = '';
				$date_to = '';
				if ( isset( $args['date'][0] ) && strtotime( $args['date'][0] ) ) {
					$date_from = $args['date'][0];
				}
				if ( isset( $args['date'][1] ) && strtotime( $args['date'][1] ) ) {
					$date_to = $args['date'][1];
				}
				if ( '' !== $date_from && '' !== $date_to ) {
					$where .= " AND (date_time BETWEEN '{$date_from}' AND '{$date_to}')";
				} elseif ( '' !== $date_from && '' == $date_to ) {
					$where .= " AND date_time >= '{$date_from}'";
				} elseif ( '' == $date_from && '' !== $date_to ) {
					$where .= " AND date_time <= '{$date_to}'";
				}
			}
		}
		if ( $select_by_year ) {
			$sql = "SELECT id, query, hits, date_time, ip, user_id, COUNT(*) AS query_count FROM {$table_logs_name} {$where} GROUP BY MONTH(date_time) ORDER BY date_time DESC";
		} else {
			$sql = "SELECT *, COUNT(*) AS query_count FROM {$table_logs_name} {$where} GROUP BY DATE(date_time) ORDER BY date_time DESC";
		}

		if ( -1 !== $limit ) {
			$sql .= " LIMIT 0,{$limit}";
		}
		if ( $select_by_year ) {
			$no_result_type = 'by_month';
			$date_time_format = 'F';
		} else {
			$no_result_type = 'by_date';
			$date_time_format = 'M d, Y';
		}
		$results = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result->query ) && '' !== $result->query ) {
					$date_time = date( $date_time_format, strtotime( $result->date_time ) );
					$return[] = array(
						'ID' => $result->id,
						'query' => $result->query,
						'hits' => $result->hits,
						'query_count' => $result->query_count,
						'date_time' => $date_time,
						'ip' => $result->ip,
						'user_id' => $result->user_id,
						'no_result' => $this->get_search_no_result_by_date_or_month( $result->date_time, $no_result_type ),
					);
				}
			}
		}
		return $return;
	}

	public function get_search_no_result_by_date_or_month( $date_time, $type = 'by_date' ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		if ( 'by_date' == $type ) {
			$sql = "SELECT COUNT(*) FROM {$table_logs_name} WHERE hits=0 AND DATE(date_time) = DATE('{$date_time}')";
		} else {
			$sql = "SELECT COUNT(*) FROM {$table_logs_name} WHERE hits=0 AND MONTH(date_time) = MONTH('{$date_time}')";
		}
		$results = $wpdb->get_var( $sql ); // WPCS: unprepared SQL OK.
		return $results;
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

function press_search_report_pro() {
	return Press_Search_Reports_Pro::get_instance();
}


