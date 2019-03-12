<?php
class Press_Search_Searching {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Indexing
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * Keyword enter by user
	 *
	 * @var mixed string or array
	 */
	protected $keywords;
	/**
	 * Does support excerpt contain keyword. Useful for the excerpt does not contain keywords
	 *
	 * @var boolean
	 */
	protected $excerpt_contain_keywords = false;

	public function __construct() {
		$this->excerpt_contain_keywords = true;
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		add_action( 'template_redirect', array( $this, 'single_result' ) );

		add_filter( 'get_the_excerpt', array( $this, 'hightlight_excerpt_keywords' ), PHP_INT_MAX );
		add_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
		add_filter( 'excerpt_more', array( $this, 'custom_excerpt_more' ), PHP_INT_MAX );
		add_action( 'press_search_auto_delete_logs', array( $this, 'auto_delete_logs' ) );
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

	/**
	 * Redirect to single post if only return one result
	 *
	 * @return void
	 */
	public function single_result() {
		if ( is_search() ) {
			global $wp_query;
			$search_keywords = get_query_var( 's' );
			if ( 1 == $wp_query->post_count && $search_keywords == $this->keywords ) {
				wp_redirect( get_permalink( $wp_query->posts['0']->ID ) );
			}
		}
	}

	/**
	 * Hook to pre_get_posts
	 *
	 * @param array $query
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		global $wpdb, $press_search_db_name, $press_search_query;
		$table_index_name = $press_search_db_name['tbl_index'];
		if ( ! $query->is_admin && $query->is_main_query() && $query->is_search ) {
			$search_keywords = get_query_var( 's' );
			if ( '' !== $search_keywords ) {
				$search_keywords = $press_search_query->maybe_add_synonyms_keywords( $search_keywords );
				$object_ids = $press_search_query->get_object_ids( $search_keywords );
				$this->keywords = $search_keywords;
				$query->set( 'post__in', $object_ids );
				$query->set( 'orderby', 'post__in' );
				$query->set( 's', '' );

				$is_enable_logs = press_search_get_setting( 'loging_enable_log', '' );
				if ( 'on' == $is_enable_logs ) {
					$insert_log = $this->insert_log( $search_keywords, count( $object_ids ) );
				}
			}
		}
	}
	/**
	 * Insert user search logs to db logs
	 *
	 * @param string  $keywords
	 * @param integer $result_number
	 * @return boolean
	 */
	public function insert_log( $keywords = '', $result_number = 0 ) {
		if ( ! is_search() || is_paged() ) {
			return false;
		}
		global $wpdb, $press_search_db_name;

		$log_user_target = press_search_get_setting( 'loging_enable_user_target', 'both' );
		$is_log_user_ip = press_search_get_setting( 'loging_enable_log_user_ip', '' );
		$maybe_exclude_users = press_search_get_setting( 'loging_exclude_users', '' );
		$exclude_users = press_search_string()->explode_comma_str( $maybe_exclude_users );

		$user_id = 0;
		$user_name = '';
		$is_user_loggedin = false;
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
			$user_name = $user->user_login;
			$is_user_loggedin = true;
		}

		if ( is_array( $exclude_users ) && ! empty( $exclude_users ) && $is_user_loggedin ) {
			foreach ( $exclude_users as $exclude_user ) {
				if ( ! empty( $exclude_user ) && ( $exclude_user == $user_id || $exclude_user == $user_name ) ) {
					return;
				}
			}
		}

		if ( 'logged_in' == $log_user_target ) {
			if ( ! $is_user_loggedin ) {
				return;
			}
		} elseif ( 'not_logged_in' == $log_user_target ) {
			if ( $is_user_loggedin ) {
				return;
			}
		}

		$table_logs_name = $press_search_db_name['tbl_logs'];
		if ( is_array( $keywords ) ) {
			$keywords = implode( ' ', $keywords );
		}
		$user_ip = '';
		if ( 'on' == $is_log_user_ip ) {
			$user_ip = $this->get_the_user_ip();
		}

		$values = array(
			'query'     => $keywords,
			'hits'      => $result_number,
			'date_time' => current_time( 'mysql', 1 ),
			'ip'        => $user_ip,
			'user_id'   => $user_id,
		);
		$value_format = array( '%s', '%d', '%s', '%s', '%d' );
		$result = $wpdb->insert( $table_logs_name, $values, $value_format );
		return $result;
	}

	/**
	 * Auto delete logs by cronjob
	 *
	 * @return void
	 */
	public function auto_delete_logs() {
		global $wpdb, $press_search_db_name;
		$table_logs_name = $press_search_db_name['tbl_logs'];
		$loging_save_time = press_search_get_setting( 'loging_save_log_time', 0 );
		$loging_save_time = absint( $loging_save_time );
		if ( $loging_save_time > 0 ) {
			$result = $wpdb->get_results( "DELETE FROM {$table_logs_name} WHERE {$table_logs_name}.date_time < SUBDATE( CURDATE(), 1 )" ); // WPCS: unprepared SQL OK.
		}
	}

	/**
	 * Get user ip
	 *
	 * @return string
	 */
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

	/**
	 * Hightlight keywords in string
	 *
	 * @param string $excerpt
	 * @return string
	 */
	public function hightlight_excerpt_keywords( $excerpt = '' ) {
		global $post;
		if ( ! empty( $this->keywords ) ) {
			if ( $this->excerpt_contain_keywords ) {
				$excerpt = press_search_string()->get_excerpt_contain_keyword( $this->keywords, $excerpt, $post->post_content );
			}
			$excerpt = press_search_string()->highlight_keywords( $excerpt, $this->keywords );
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
		}
		return $excerpt;
	}

	/**
	 * Hook to custom origin excerpt length
	 *
	 * @param integer $length
	 * @return integer
	 */
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

	/**
	 * Hook to modify origin excerpt more
	 *
	 * @param string $more
	 * @return string
	 */
	public function custom_excerpt_more( $more ) {
		$excerpt_more = press_search_get_setting( 'searching_excerpt_more', $more );
		return sprintf( '&nbsp; %s', $excerpt_more );
	}

}

// new Press_Search_Searching(); .
$searching = new Press_Search_Searching();

