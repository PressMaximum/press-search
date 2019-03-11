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

	public function build_sql( $keywords = '', $engine_slug = 'engine_default' ) {
		global $press_search_db_name;
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
		$search_keywords = explode( ' ', strtolower( $keywords ) );
		$search_keywords = array_map( 'trim', $search_keywords );

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

	function get_object_ids( $keywords = '', $engine_slug = 'engine_default' ) {
		global $wpdb;
		$query = $this->build_sql( $keywords, $engine_slug );
		echo 'Query: ' . $query;
		$return = array();
		$result = $wpdb->get_results( $query ); // WPCS: unprepared SQL OK.
		if ( is_array( $result ) && ! empty( $result ) ) {
			foreach ( $result as $object ) {
				if ( isset( $object->object_id ) && ! empty( $object->object_id ) ) {
					$return[] = $object->object_id;
				}
			}
		}
		return $return;
	}
}
