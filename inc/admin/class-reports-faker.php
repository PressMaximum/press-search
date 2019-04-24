<?php
class Press_Search_Reports_Faker {
	protected static $_instance = null;
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function get_site_post_count() {
		$count_posts = wp_count_posts();
		$count_pages = wp_count_posts( 'page' );
	}

	public function get_fake_keywords() {
		$fake = array(
			'world cup',
			'easter ' . date( 'Y' ),
			'super bowl',
			'fifa world cup',
			'walmart black friday',
			'world series',
			'labor day',
			'memorial day',
			'calendar ' . date( 'Y' ),
			'nba playoffs',
			'movies',
			'thanksgiving',
			'olympics',
			'mundial',
			'when is easter',
			'pga championship',
			'presidents day ',
			'oscar nominations',
			'british open',
			'first day of spring',
			'short hair',
			'warped tour',
			'good friday',
			'wrestlemania',
			'mothers day',
			'when is thanksgivin',
			'ford bronco',
			'horoscope',
			'fathers day',
			'super bowl commercials',
			'kia sportage',
		);
		return $fake;
	}

	public function get_random_fake_data( $limit, $type = 'logs' ) {
		$return = array();
		$fake_keywords = $this->get_fake_keywords();
		shuffle( $fake_keywords );
		if ( -1 === $limit ) {
			$limit = 30;
		}
		for ( $i = 1; $i <= $limit; $i++ ) {
			$fake_date = strtotime( "-{$i} day" );
			$fake_date = date( 'F d, Y', $fake_date );
			$return[] = array(
				'ID' => $i,
				'query' => $fake_keywords[ $i - 1 ],
				'hits' => rand( 10, 500 ),
				'query_count' => rand( 10, 200 ),
				'date_time' => $fake_date,
				'ip' => '192.168.1.1',
				'user_id' => '1',
			);
		}
		return $return;
	}

	public function get_search_logs( $limit = 20, $args = array() ) {
		return $this->get_random_fake_data( $limit );
	}

	public function get_no_results_search( $limit = 20, $orderby = 'date_time', $order = 'desc' ) {
		return $this->get_random_fake_data( $limit );
	}

	public function get_popular_search( $limit = 20, $orderby = 'query_count', $order = 'desc' ) {
		return $this->get_random_fake_data( $limit );
	}

	public function search_logs_for_chart() {
		$labels = array();
		$searches = array();
		$hits = array();
		$no_results = array();
		for ( $i = 1; $i <= 20; $i++ ) {
			$fake_date = strtotime( "-{$i} days" );
			$fake_date = date( 'M d, Y', $fake_date );
			$labels[] = $fake_date;
			$rand_searches = rand( 50, 200 );
			$searches[] = $rand_searches;
			$no_results[] = rand( 0, $rand_searches - 5 );
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
