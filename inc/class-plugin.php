<?php
class Press_Search {
	/**
	 * The single instance of the class
	 *
	 * @var Started_Instance
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * The plugin dir
	 *
	 * @var Press_Search_Dir
	 * @since 0.1.0
	 */
	protected $plugin_dir;
	/**
	 * The plugin url
	 *
	 * @var Press_Search_Url
	 * @since 0.1.0
	 */
	protected $plugin_url;
	/**
	 * The plugin version
	 *
	 * @var Press_Search_Version
	 * @since 0.1.0
	 */
	protected $plugin_version;
	/**
	 * Admin var
	 *
	 * @var Started_Admin
	 * @since 0.1.0
	 */
	protected static $admin = null;
	/**
	 * Instance
	 *
	 * @return Press_Search
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Press_Search Constructor.
	 */
	public function __construct() {
		$this->setup();
		if ( is_admin() ) {
			$this->setup_admin();
		}
	}

	/**
	 * Method setup.
	 */
	public function setup() {
		$this->plugin_dir = PRESS_SEARCH_DIR;
		$this->plugin_url = PRESS_SEARCH_URL;
		$this->plugin_version = PRESS_SEARCH_VERSION;
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		do_action( 'press_search_loaded' );
	}

	/**
	 * Method enqueue_scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'press-search', $this->plugin_url . 'assets/js/frontend.js', array(), $this->plugin_version, true );
		wp_enqueue_style( 'press-search', $this->plugin_url . 'assets/css/frontend.css', array(), $this->plugin_version );
	}

	/**
	 * Method setup_admin.
	 */
	public function setup_admin() {
		require_once $this->plugin_dir . 'inc/admin/class-admin.php';
		self::$admin = new Press_Search_Admin();
	}

}

