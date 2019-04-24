<?php
class Press_Search_Admin {
	/**
	 * Plugin dir
	 *
	 * @var string
	 */
	protected $plugin_dir;
	/**
	 * Plugin url
	 *
	 * @var string
	 */
	protected $plugin_url;
	/**
	 * Plugin version
	 *
	 * @var string
	 */
	protected $plugin_version;
	/**
	 * Method __construct
	 */
	public function __construct() {
		$this->plugin_dir = press_search_get_var( 'plugin_dir' );
		$this->plugin_url = press_search_get_var( 'plugin_url' );
		$this->plugin_version = press_search_get_var( 'plugin_version' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Method enqueue_scripts
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'presssearch_page_press-search-report' == $hook ) {
			wp_enqueue_style( 'jquery-ui-datepicker', esc_url( 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css' ), false, '1.9.0', false );
			wp_enqueue_script( 'jquery-chart-js', esc_url( 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.js' ), false, '2.7.2', true );
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}
		if ( 'toplevel_page_press-search-settings' == $hook ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
		wp_enqueue_script( 'press-search-admin', $this->plugin_url . 'assets/js/admin.js', array( 'jquery' ), $this->plugin_version, true );
		wp_enqueue_style( 'press-search-admin', $this->plugin_url . 'assets/css/admin.css', array(), $this->plugin_version );

		$chart_reports = press_search_reports()->search_logs_for_chart();

		wp_localize_script(
			'press-search-admin',
			'Press_Search_Js',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'admin-ajax-security' ),
				'chart_reports' => $chart_reports,
				'chart_title' => esc_html__( 'Searches Chart', 'press-search' ),
			)
		);
	}
}
