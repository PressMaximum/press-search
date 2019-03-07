<?php
class Press_Search_Searching {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Indexing
	 * @since 0.1.0
	 */
	protected static $_instance = null;

	public function __construct() {
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 1 );
		add_filter( 'template_include', array( $this, 'template_include' ) );
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

	function get_result( $sql ) {
		global $wpdb;
		$return = array();
		$result = $wpdb->get_results( $sql ); // WPCS: unprepared SQL OK.
		if ( is_array( $result ) && ! empty( $result ) ) {
			foreach ( $result as $object ) {
				if ( isset( $object->object_id ) && ! empty( $object->object_id ) ) {
					$return[] = $object->object_id;
				}
			}
		}
		return $return;
	}

	function pre_get_posts( $query ) {
		global $wpdb, $press_search_db_name;
		$table_index_name = $press_search_db_name['tbl_index'];
		if ( $query->is_search() && $query->is_main_query() ) {
			$search_query = get_query_var( 's' );
			$search_query_arr = explode( ' ', strtolower( $search_query ) );
			$search_query_arr = array_map( 'trim', $search_query_arr );

			$search_query = "
				SELECT DISTINCT ( i1.`object_id` ),  i1.title + i2.title + i3.title + i4.title AS c_title, i1.content + i2.content + i3.content +  i4.content AS c_content   
				FROM `$table_index_name` as i1
				LEFT JOIN `$table_index_name` as i2 ON i1.object_id =  i2.object_id
				LEFT JOIN `$table_index_name` as i3 ON i2.object_id =  i3.object_id
				LEFT JOIN `$table_index_name` as i4 ON i2.object_id =  i4.object_id
				WHERE i1.`term` = 'black' and i2.`term` = 'friday' and i3.`term` = 'cyber' and i4.`term` = 'monday'
				GROUP BY i1.object_id
			";
			$object_ids = $this->get_result( $search_query );
			$query->set( 's', '' );
			$query->set( 'post__in', $object_ids );
			$query->set( 'orderby', 'post__in' );
		}
	}

	function template_include( $template ) {
		// return locate_template( 'search.php' ); .
		return $template;
	}
}

new Press_Search_Searching();
