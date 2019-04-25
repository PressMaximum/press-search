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
		add_action(
			'init',
			function() {
				$this->get_post_exclusion_from_setting();
			}
		);
	}

	/**
	 * Search post title and redirect if has single template
	 *
	 * @return void
	 */
	public function search_title_sql() {
		if ( ps_is__pro() ) {
			global $wpdb;
			$keywords = preg_quote( get_query_var( 's' ) );
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_type FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_title LIKE '%s' AND {$wpdb->posts}.post_status='%s'", array( $keywords, 'publish' ) ), ARRAY_A ); // @codingStandardsIgnoreLine .
			if ( isset( $result[0] ) && isset( $result[0]['ID'] ) && isset( $result[0]['post_type'] ) ) {
				$post_id = $result[0]['ID'];
				$post_type = $result[0]['post_type'];
				if ( in_array( $post_type, array( 'post', 'page' ) ) ) {
					wp_safe_redirect( get_the_permalink( $post_id ) );
				} else {
					$post_type_obj = get_post_type_object( $post_type );
					if ( is_object( $post_type_obj ) && isset( $post_type_obj->public ) && $post_type_obj->public ) {
						wp_safe_redirect( get_the_permalink( $post_id ) );
					}
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
		if ( ps_is__pro() ) {
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

	public function search_index_sql_group_by_posttype( $keywords = '', $engine_slug = 'engine_default' ) {
		$query = array();
		$db_engine_settings = press_search_engines()->get_engine_settings();
		if ( isset( $db_engine_settings[ $engine_slug ] ) ) {
			$engine_settings = $db_engine_settings[ $engine_slug ];
		}

		if ( isset( $engine_settings['post_type'] ) && ! empty( $engine_settings['post_type'] ) ) {
			foreach ( $engine_settings['post_type'] as $k => $post_type ) {
				$post_type = "post_{$post_type}";
				$args = array(
					'post_type_condition' => array(
						'compare'   => '=',
						'value'     => $post_type,
					),
				);
				$query[ $post_type ] = $this->search_index_sql( $keywords, $engine_slug, $args );
			}
		}
		return $query;
	}

	public function search_index_sql( $keywords = '', $engine_slug = 'engine_default', $args = array() ) {
		if ( ps_is__pro() ) {
			$redirect_auto_post_page = press_search_get_setting( 'redirects_automatic_post_page', '' );
			if ( 'on' == $redirect_auto_post_page ) {
				$this->search_title_sql();
			}

			$custom_redirect_settings = press_search_get_setting( 'redirects_automatic_custom', array() );
			if ( ! empty( $custom_redirect_settings ) ) {
				$this->search_auto_redirect( $custom_redirect_settings );
			}
		}

		$engine_settings = array();
		$db_engine_settings = press_search_engines()->get_engine_settings();
		$default_operator = press_search_get_setting( 'searching_default_operator', 'or' );
		$searching_weight = press_search_get_setting(
			'searching_weights',
			array(
				'title' => 1000,
				'content' => 0.01,
				'excerpt' => 0.1,
				'category' => 3,
				'tag' => 2,
				'custom_field' => 0.005,
			)
		);

		$table_index_name = press_search_get_var( 'tbl_index' );
		if ( array_key_exists( $engine_slug, $db_engine_settings ) ) {
			$engine_settings = $db_engine_settings[ $engine_slug ];
		}
		$search_keywords = $keywords;
		if ( ! is_array( $keywords ) ) {
			$search_keywords = press_search_string()->explode_keywords( $keywords );
		}
		$where_object_type_in = '';
		if ( isset( $engine_settings['post_type'] ) && ! empty( $engine_settings['post_type'] ) && isset( $args['post_type_condition'] ) ) {
			$compare = ( isset( $args['post_type_condition']['compare'] ) ) ? $args['post_type_condition']['compare'] : '=';
			$value = ( isset( $args['post_type_condition']['value'] ) ) ? $args['post_type_condition']['value'] : '';
			$where_object_type_in = " AND i1.object_type {$compare} '{$value}' ";
		} elseif ( isset( $engine_settings['post_type'] ) && ! empty( $engine_settings['post_type'] ) ) {
			foreach ( $engine_settings['post_type'] as $k => $post_type ) {
				$engine_settings['post_type'][ $k ] = "post_{$post_type}";
			}
			$post_type_in = implode( "', '", $engine_settings['post_type'] );
			$where_object_type_in = " AND i1.object_type IN ( '{$post_type_in}' )";
		}
		$c_weight = array();
		$sql = 'SELECT';
		$keyword_like = array();
		$keyword_reverse_like = array();
		$sql_order_by = array();
		$sql_or_order_by = array();
		foreach ( $search_keywords as $keyword ) {
			if ( press_search_string()->is_cjk( $keyword ) ) { // If is cjk, we no need search term reverse.
				$keyword_like[] = "`term` = '${keyword}'";
			} else {
				$keyword_like[] = "`term` LIKE '${keyword}%'";
				$keyword_reverse_like[] = "`term_reverse` LIKE CONCAT(REVERSE('{$keyword}'), '%')";
			}
			$sql_or_order_by[] = "
				WHEN ( i1.term LIKE '{$keyword}' AND i1.title > 0 ) THEN 1
				WHEN ( i1.term LIKE '{$keyword}%' AND i1.title > 0 ) THEN 2
				WHEN i1.term LIKE '{$keyword}' THEN 3
				WHEN i1.term_reverse LIKE CONCAT(REVERSE('{$keyword}'), '%') and i1.title > 0 THEN 4
				WHEN i1.term_reverse LIKE CONCAT(REVERSE('{$keyword}'), '%') THEN 5
			";
		}
		if ( count( $search_keywords ) > 1 ) {
			$sql_group_by = ' GROUP BY i1.object_id';
		} else {
			$sql_group_by = ' GROUP BY i1.term, i1.object_id';
		}

		$post_in_terms = array();
		if ( isset( $args['posts_in_terms'] ) && is_array( $args['posts_in_terms'] ) && ! empty( $args['posts_in_terms'] ) ) {
			$post_in_terms = $args['posts_in_terms'];
		}

		$post_in_terms_leftjoin = '';
		$post_in_terms_where = '';
		if ( is_array( $post_in_terms ) && ! empty( $post_in_terms ) ) {
			global $wpdb;
			$post_in_terms_leftjoin = " LEFT JOIN {$wpdb->term_relationships} ON (i1.`object_id` = {$wpdb->term_relationships}.object_id)";
			$post_in_terms_where = " AND ( {$wpdb->term_relationships}.term_taxonomy_id IN (" . implode( ', ', $post_in_terms ) . ') )';
		}

		if ( 'or' == $default_operator ) {
			foreach ( $searching_weight as $column => $weight ) {
				$c_weight[] = "{$weight} * i1.{$column}";
			}
			$sql .= ' i1.term AS c_term, i1.object_id AS c_object_id, i1.title AS c_title, i1.content AS c_content';
			$sql .= ', ' . implode( ' + ', $c_weight ) . ' AS c_weight';
			$sql .= " FROM {$table_index_name} AS i1 ";
			if ( '' !== $post_in_terms_leftjoin ) {
				$sql .= $post_in_terms_leftjoin;
			}
			$sql .= ' WHERE (';
			$sql .= implode( ' OR ', $keyword_like );
			if ( ! empty( $keyword_reverse_like ) ) {
				$sql .= ' OR ' . implode( ' OR ', $keyword_reverse_like );
			}
			$sql .= ')';
			$sql_order_by = $sql_or_order_by;
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
				if ( press_search_string()->is_cjk( $keyword ) ) { // If is cjk, we no need search term reverse.
					$where[] = "i{$key}.`term` = '{$keyword}'";
				} else {
					$where[] = "( i{$key}.`term` = '{$keyword}' OR i{$key}.`term` LIKE '{$keyword}%' OR i{$key}.`term_reverse` LIKE CONCAT(REVERSE('{$keyword}'), '%') )";
				}

				$sql_order_by[] = "
				WHEN ( i{$key}.term LIKE '{$keyword}' AND i{$key}.title > 0 ) THEN 1
				WHEN ( i{$key}.term LIKE '{$keyword}%' AND i{$key}.title > 0 ) THEN 2
				WHEN i{$key}.term LIKE '{$keyword}' THEN 3
				WHEN i{$key}.term_reverse LIKE CONCAT(REVERSE('{$keyword}'), '%') and i{$key}.title > 0 THEN 4
				WHEN i{$key}.term_reverse LIKE CONCAT(REVERSE('{$keyword}'), '%') THEN 5
				";
			}
			$sql .= ' i1.term AS c_term, i1.`object_id` AS c_object_id, ';
			$sql .= ' ' . implode( ' + ', $select_title ) . ' AS c_title,';
			$sql .= ' ' . implode( ' + ', $select_content ) . ' AS c_content';
			foreach ( $select_weight as $k => $val ) {
				$weight = $searching_weight[ $k ];
				$c_weight[] = " {$weight} * ( " . implode( ' + ', $val ) . ' )';
			}
			$sql .= ', ' . implode( ' + ', $c_weight ) . ' AS c_weight';
			$sql .= " FROM {$table_index_name} AS i1 ";
			$sql .= ' ' . implode( ' ', $left_join );
			if ( '' !== $post_in_terms_leftjoin ) {
				$sql .= $post_in_terms_leftjoin;
			}
			$sql .= ' WHERE (' . implode( ' AND ', $where ) . ')';
		}
		if ( '' !== $where_object_type_in ) {
			$sql .= $where_object_type_in;
		}
		if ( '' !== $post_in_terms_where ) {
			$sql .= $post_in_terms_where;
		}
		$post_exclusion = $this->get_post_exclusion_from_setting();
		if ( is_array( $post_exclusion ) && ! empty( $post_exclusion ) ) {
			$object_id_not_in = implode( ', ', $post_exclusion );
			$object_id_not_in = " AND i1.object_id NOT IN ( {$object_id_not_in} )";
			$sql .= $object_id_not_in;
		}

		$sql .= $sql_group_by;
		$order_by = ' ORDER BY ( CASE ' . implode( ' ', $sql_order_by ) . ' ELSE 6 END ), c_weight DESC';
		$sql .= $order_by;
		if ( isset( $_GET['dev'] ) && $_GET['dev'] ) {
			echo 'SQL: ' . $sql;
		}
		return $sql;
	}

	function get_post_exclusion_from_setting() {
		global $wpdb;
		$searching_post_exclusion = press_search_get_setting( 'searching_post_exclusion', '' );
		$searching_terms_exclusion = press_search_get_setting( 'searching_category_exclusion', '' );
		$post_exclusion = press_search_string()->explode_comma_str( $searching_post_exclusion );
		$term_exclusion = press_search_string()->explode_comma_str( $searching_terms_exclusion );
		if ( ! empty( $term_exclusion ) ) {
			$post_in_terms = $wpdb->get_results( "SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN (" . implode( ', ', $term_exclusion ) . ')', ARRAY_A ); // WPCS: unprepared SQL OK.
			if ( is_array( $post_in_terms ) && ! empty( $post_in_terms ) ) {
				foreach ( $post_in_terms as $term ) {
					if ( isset( $term['object_id'] ) && is_numeric( $term['object_id'] ) ) {
						$post_exclusion[] = $term['object_id'];
					}
				}
			}
		}
		return $post_exclusion;
	}

	function get_post_ids_from_term( $term_ids = array() ) {
		global $wpdb;
		$return = array();
		$table_term_relationships = $wpdb->term_relationships;
		if ( is_array( $term_ids ) && ! empty( $term_ids ) ) {
			$results = $wpdb->get_results( "SELECT DISTINCT ( t_r.object_id ) FROM {$table_term_relationships} AS t_r WHERE term_taxonomy_id IN (" . implode( ',', $term_ids ) . ')' ); // WPCS: unprepared SQL OK.
			if ( is_array( $results ) && ! empty( $results ) ) {
				foreach ( $results as $result ) {
					if ( isset( $result->object_id ) && is_numeric( $result->object_id ) ) {
						$return[] = $result->object_id;
					}
				}
			}
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
		return $return;
	}

	function get_object_ids_group_by_posttype( $keywords = '', $engine_slug = 'engine_default' ) {
		global $wpdb;
		$object_ids = array();
		$queries = $this->search_index_sql_group_by_posttype( $keywords, $engine_slug );
		foreach ( $queries as $key => $query ) {
			$result = $wpdb->get_results( $query ); // WPCS: unprepared SQL OK.
			$_object_ids = array();
			if ( is_array( $result ) && ! empty( $result ) ) {
				foreach ( $result as $object ) {
					if ( isset( $object->c_object_id ) && ! empty( $object->c_object_id ) ) {
						$_object_ids[] = $object->c_object_id;
					}
				}
			}

			$_object_ids = array_unique( $_object_ids );
			if ( ! empty( $_object_ids ) ) {
				$object_ids[ $key ] = $_object_ids;
			}
		}
		return $object_ids;
	}

	public function get_post_exclusion() {
		$exclude_post_ids = press_search_get_setting( 'searching_post_exclusion', '' );
		$exclude_ids = array();
		if ( '' !== $exclude_post_ids ) {
			$exclude_ids = array_unique( array_filter( explode( ',', $exclude_post_ids ), 'absint' ) );
		}
	}

	public function get_tax_exclusion() {
		$exclude_term_ids = press_search_get_setting( 'searching_category_exclusion', '' );
		$exclude_ids = array();
		if ( '' !== $exclude_term_ids ) {
			$exclude_ids = array_unique( array_filter( explode( ',', $exclude_term_ids ), 'absint' ) );
		}
	}
}
