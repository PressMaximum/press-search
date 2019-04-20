<?php

class Press_Search_Table_Report_Base extends WP_List_Table {
	protected static $_instance = null;
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function delete_log_item( $log_id ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$result = $wpdb->delete( $table_logs_name, array( 'ID' => 1 ), array( '%d' ) );
		if ( false === $result ) {
			return false;
		}
		return true;
	}
}
