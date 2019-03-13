<?php
class Press_Search_Query {
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

	}

	/**
	 * Search post title and redirect if has single template
	 *
	 * @return void
	 */
	public function search_title_sql() {
		global $wpdb;
		$keywords = preg_quote( get_query_var( 's' ) );
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_type FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_title LIKE '%s' AND {$wpdb->posts}.post_status='%s'", array( $keywords, 'publish' ) ), ARRAY_A ); // @codingStandardsIgnoreLine .
		if ( isset( $result[0] ) && isset( $result[0]['ID'] ) && isset( $result[0]['post_type'] ) ) {
			$post_id = $result[0]['ID'];
			$post_type = $result[0]['post_type'];
			if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
				wp_safe_redirect( get_the_permalink( $post_id ) );
			} else {
				$singular_template = locate_template( array( "single-$post_type.php" ) );
				if ( ! empty( $singular_template ) ) {
					wp_safe_redirect( get_the_permalink( $post_id ) );
				}
			}
		}
	}

	/**
	 * Auto redirect if has setting redirect
	 *
	 * @param array $custom_redirect_settings
	 * @return void
	 */
	public function search_auto_redirect( $custom_redirect_settings = array() ) {
		if ( empty( $custom_redirect_settings ) ) {
			$custom_redirect_settings = press_search_get_setting( 'redirects_automatic_custom', array() );
		}
		$keywords = get_query_var( 's' );
		$search_keywords = press_search_string()->explode_keywords( $keywords );

		if ( ! empty( $custom_redirect_settings ) ) {
			foreach ( $custom_redirect_settings as $val ) {
				if ( isset( $val['keyword'] ) && ! empty( $val['keyword'] ) && isset( $val['url_redirect'] ) && ! empty( $val['url_redirect'] ) ) {
					$condition_keyword = mb_strtolower( $val['keyword'] );
					if ( in_array( $condition_keyword, $search_keywords ) ) {
						wp_safe_redirect( esc_url( $val['url_redirect'] ) );
					}
				}
			}
		}
	}

	public function maybe_add_synonyms_keywords( $origin_keywords = '' ) {
		if ( ! is_array( $origin_keywords ) ) {
			$origin_keywords = press_search_string()->explode_keywords( $origin_keywords );
		}
		$synonyms_settings = press_search_get_setting( 'synonymns', '' );
		$synonyms_settings = explode( PHP_EOL, $synonyms_settings );
		$synonyms_settings = array_map( 'trim', $synonyms_settings );
		$synonyms = array();
		foreach ( $synonyms_settings as $synonym ) {
			$split_words = explode( '=', $synonym );
			if ( isset( $split_words[0] ) && isset( $split_words[1] ) ) {
				$synonyms[ $split_words[0] ][] = $split_words[1];
				$synonyms[ $split_words[1] ][] = $split_words[0];
			}
		}
		foreach ( $origin_keywords as $keyword ) {
			if ( array_key_exists( $keyword, $synonyms ) ) {
				$origin_keywords = array_merge( $origin_keywords, $synonyms[ $keyword ] );
			}
		}
		return $origin_keywords;
	}

	public function search_index_sql( $keywords = '', $engine_slug = 'engine_default' ) {
		global $press_search_db_name;

		$redirect_auto_post_page = press_search_get_setting( 'redirects_automatic_post_page', '' );
		if ( 'on' == $redirect_auto_post_page ) {
			$this->search_title_sql();
		}

		$custom_redirect_settings = press_search_get_setting( 'redirects_automatic_custom', array() );
		if ( ! empty( $custom_redirect_settings ) ) {
			$this->search_auto_redirect( $custom_redirect_settings );
		}

		$engine_settings = array();
		$db_engine_settings = press_search_engines()->get_engine_settings();
		$default_operator = press_search_get_setting( 'searching_default_operator', 'or' );
		$searching_weight = press_search_get_setting(
			'searching_weights',
			array(
				'title' => 8,
				'content' => 5,
				'excerpt' => 8,
				'category' => 8,
				'tag' => 8,
				'custom_field' => 1,
			)
		);

		$table_index_name = $press_search_db_name['tbl_index'];
		if ( array_key_exists( $engine_slug, $db_engine_settings ) ) {
			$engine_settings = $db_engine_settings[ $engine_slug ];
		}
		$search_keywords = $keywords;
		if ( ! is_array( $keywords ) ) {
			$search_keywords = press_search_string()->explode_keywords( $keywords );
		}
		$where_object_in = '';
		if ( isset( $engine_settings['post_type'] ) && ! empty( $engine_settings['post_type'] ) ) {
			foreach ( $engine_settings['post_type'] as $k => $post_type ) {
				$engine_settings['post_type'][ $k ] = "post_{$post_type}";
			}
			$post_type_in = implode( "', '", $engine_settings['post_type'] );
			$where_object_in = " AND i1.object_type IN ( '{$post_type_in}' )";
		}
		$c_weight = array();
		$sql = 'SELECT';
		$keyword_like = array();
		$keyword_reverse_like = array();
		foreach ( $search_keywords as $keyword ) {
			$keyword_like[] = "`term` LIKE '${keyword}%'";
			$keyword_reverse = strrev( $keyword );
			$keyword_reverse_like[] = "`term_reverse` LIKE CONCAT(REVERSE('{$keyword}'), '%')";
		}

		if ( 'or' == $default_operator ) {
			foreach ( $searching_weight as $column => $weight ) {
				$c_weight[] = "{$weight} * i1.{$column}";
			}
			$sql .= ' i1.object_id AS c_object_id, i1.title AS c_title, i1.content AS c_content';
			$sql .= ', ' . implode( ' + ', $c_weight ) . ' AS c_weight';
			$sql .= " FROM {$table_index_name} AS i1 ";
			$sql .= ' WHERE ';
			$sql .= implode( ' OR ', $keyword_like );
			$sql .= ' OR ' . implode( ' OR ', $keyword_reverse_like );
			if ( '' !== $where_object_in ) {
				$sql .= $where_object_in;
			}
			$sql .= ' GROUP BY i1.object_id';
			$sql .= ' ORDER BY c_weight DESC';
		} else {
			$select_title = array();
			$select_content = array();
			$left_join = array();
			$select_weight = array();
			$where = array();
			$number_keywords = count( $search_keywords );
			foreach ( $search_keywords as $k => $keyword ) {
				$key = $k + 1;
				$next_key = $key + 1;
				$select_title[]                   = "i{$key}.title";
				$select_content[]                 = "i{$key}.content";
				$select_weight['title']           = $select_title;
				$select_weight['content']         = $select_content;
				$select_weight['excerpt'][]       = "i{$key}.excerpt";
				$select_weight['category'][]      = "i{$key}.category";
				$select_weight['tag'][]           = "i{$key}.tag";
				$select_weight['custom_field'][]  = "i{$key}.custom_field";
				if ( $key < $number_keywords ) {
					$left_join[] = "LEFT JOIN {$table_index_name} as i{$next_key} ON i{$key}.object_id = i{$next_key}.object_id";
				}
				$where[] = "( i{$key}.`term` = '{$keyword}' OR i{$key}.`term_reverse` LIKE CONCAT(REVERSE('{$keyword}'), '%') )";
			}
			$sql .= ' i1.`object_id` AS c_object_id, ';
			$sql .= ' ' . implode( ' + ', $select_title ) . ' AS c_title,';
			$sql .= ' ' . implode( ' + ', $select_content ) . ' AS c_content';
			foreach ( $select_weight as $k => $val ) {
				$weight = $searching_weight[ $k ];
				$c_weight[] = " {$weight} * ( " . implode( ' + ', $val ) . ' )';
			}
			$sql .= ', ' . implode( ' + ', $c_weight ) . ' AS c_weight';
			$sql .= " FROM {$table_index_name} AS i1 ";
			$sql .= ' ' . implode( ' ', $left_join );
			$sql .= ' WHERE ' . implode( ' AND ', $where );
			if ( '' !== $where_object_in ) {
				$sql .= $where_object_in;
			}
			$sql .= ' GROUP BY i1.object_id';
			$sql .= ' ORDER BY c_weight DESC';
		}
		return $sql;
	}

	function remove_post_exclusion( $post_ids = array() ) {
		$searching_post_exclusion = press_search_get_setting( 'searching_post_exclusion', '' );
		$post_exclusion = press_search_string()->explode_comma_str( $searching_post_exclusion );
		$return = array();
		if ( ! empty( $post_ids ) ) {
			$return = array_diff( $post_ids, $post_exclusion );
		}
		return $return;
	}

	function get_object_ids( $keywords = '', $engine_slug = 'engine_default' ) {
		global $wpdb;
		$query = $this->search_index_sql( $keywords, $engine_slug );
		$return = array();
		$result = $wpdb->get_results( $query ); // WPCS: unprepared SQL OK.
		if ( is_array( $result ) && ! empty( $result ) ) {
			foreach ( $result as $object ) {
				if ( isset( $object->c_object_id ) && ! empty( $object->c_object_id ) ) {
					$return[] = $object->c_object_id;
				}
			}
		}
		$return = $this->remove_post_exclusion( $return );
		return $return;
	}
}
