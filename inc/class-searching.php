<?php
class Press_Search_Searching {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Indexing
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	protected $keywords;

	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_filter( 'get_the_excerpt', array( $this, 'hightlight_excerpt_keywords' ), PHP_INT_MAX );
		add_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
		add_filter( 'excerpt_more', array( $this, 'custom_excerpt_more' ), PHP_INT_MAX );
		add_action( 'template_redirect', array( $this, 'single_result' ) );
	}
	/**
	 * Instance
	 *
	 * @return Press_Search_Indexing
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function single_result() {
		if ( is_search() ) {
			global $wp_query;
			$search_keywords = get_query_var( 's' );
			if ( 1 == $wp_query->post_count && $search_keywords == $this->keywords ) {
				wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
			}
		}
	}

	public function pre_get_posts( $query ) {
		global $wpdb, $press_search_db_name, $press_search_query;
		$table_index_name = $press_search_db_name['tbl_index'];
		if ( ! $query->is_admin && $query->is_main_query() && $query->is_search ) {
			$search_keywords = get_query_var( 's' );
			if ( '' !== $search_keywords ) {
				$result = $this->insert_log( $search_keywords );
				$this->keywords = $search_keywords;
				$object_ids = $press_search_query->get_object_ids( $search_keywords );
				$query->set( 'post__in', $object_ids );
				$query->set( 'orderby', 'post__in' );
				$query->set( 's', '' );
			}
		}
	}

	public function insert_log( $keywords = '', $result_number = 0 ) {
		global $wpdb;
		global $press_search_db_name;
		$table_logs_name = $press_search_db_name['tbl_logs'];

		$user_id = 0;
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
		}
		$values = array(
			'query'     => $keywords,
			'hits'      => $result_number,
			'date_time' => current_time( 'mysql', 1 ),
			'ip'        => $this->get_the_user_ip(),
			'user_id'   => $user_id,
		);
		$value_format = array( '%s', '%d', '%s', '%s', '%d' );
		$result = $wpdb->insert( $table_logs_name, $values, $value_format );
		return $result;
	}

	function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	public function template_include( $template ) {
		// return locate_template( 'search.php' ); .
		return $template;
	}

	public function hightlight_excerpt_keywords( $excerpt ) {
		if ( ! empty( $this->keywords ) ) {
			$excerpt = press_search_string()->highlight_keywords( $excerpt, $this->keywords );
		}
		$excerpt_length = press_search_get_setting(
			'searching_excerpt_length',
			array(
				'length' => 30,
				'type' => 'text',
			)
		);
		if ( 'character' == $excerpt_length['type'] ) {
			return mb_substr( $excerpt, 0, 10 );
		}
		return $excerpt;
	}

	public function custom_excerpt_length( $length ) {
		$excerpt_length = press_search_get_setting(
			'searching_excerpt_length',
			array(
				'length' => 30,
				'type' => 'text',
			)
		);
		$return = $length;
		if ( 'text' == $excerpt_length['type'] ) {
			$return = $excerpt_length['length'];
		}

		return $return;
	}

	public function custom_excerpt_more( $more ) {
		$excerpt_more = press_search_get_setting( 'searching_excerpt_more', $more );
		return sprintf( '&nbsp; %s', $excerpt_more );
	}
}

new Press_Search_Searching();


