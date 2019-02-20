<?php
class Press_Search_Admin {
	/**
	 * Plugin dir
	 *
	 * @var [string]
	 */
	protected $plugin_dir;
	/**
	 * Plugin url
	 *
	 * @var [string]
	 */
	protected $plugin_url;
	/**
	 * Plugin version
	 *
	 * @var [string]
	 */
	protected $plugin_version;
	/**
	 * Method __construct
	 */
	public function __construct() {
		$this->plugin_dir = PRESS_SEARCH_DIR;
		$this->plugin_url = PRESS_SEARCH_URL;
		$this->plugin_version = PRESS_SEARCH_VERSION;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Method enqueue_scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'press-search-admin', $this->plugin_url . 'assets/js/admin.js', array(), $this->plugin_version, true );
		wp_enqueue_style( 'press-search-admin', $this->plugin_url . 'assets/css/admin.css', array(), $this->plugin_version );
	}
}
