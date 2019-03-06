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


}

new Press_Search_Searching();
