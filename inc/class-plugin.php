<?php
class Press_Search {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * The plugin dir
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $plugin_dir;
	/**
	 * The plugin url
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $plugin_url;
	/**
	 * The plugin version
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $plugin_version;
	/**
	 * Admin var
	 *
	 * @var Press_Search_Admin
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
		$this->plugin_dir = press_search_get_var( 'plugin_dir' );
		$this->plugin_url = press_search_get_var( 'plugin_url' );
		$this->plugin_version = press_search_get_var( 'plugin_version' );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		do_action( 'press_search_loaded' );
	}

	/**
	 * Method enqueue_scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'press-search', $this->plugin_url . 'assets/css/frontend.css', array(), $this->plugin_version );

		wp_enqueue_script( 'press-search', $this->plugin_url . 'assets/js/frontend.js', array(), $this->plugin_version, true );
	}

	/**
	 * Method setup_admin.
	 */
	public function setup_admin() {
		require_once $this->plugin_dir . 'inc/admin/class-admin.php';
		self::$admin = new Press_Search_Admin();
	}

}

