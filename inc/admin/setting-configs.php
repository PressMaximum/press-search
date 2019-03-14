<?php
$press_search_setting = press_search_settings();

$press_search_setting->add_settings_page(
	array(
		'menu_slug' => 'press-search-settings',
		'parent_slug' => 'press-search-settings',
		'page_title' => esc_html__( 'Settings', 'press-search' ),
		'menu_title' => esc_html__( 'Settings', 'press-search' ),
	)
);

$press_search_setting->add_settings_page(
	array(
		'menu_slug' => 'press-search-report',
		'parent_slug' => 'press-search-settings',
		'page_title' => esc_html__( 'Reports', 'press-search' ),
		'menu_title' => esc_html__( 'Reports', 'press-search' ),
	)
);

$press_search_setting->set_setting_fields(
	'press-search-settings',
	array(
		array(
			'name' => esc_html__( 'Enable login', 'press-search' ),
			'desc' => esc_html__( 'Enable', 'press-search' ),
			'id'   => 'loging_enable_login',
			'type' => 'checkbox',
		),
	)
);

// Register tabs for settings.
$press_search_setting->register_tab( 'press-search-settings', 'engines', esc_html__( 'Engines', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'searching', esc_html__( 'Searching', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'settings-loging', esc_html__( 'Loging', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'stopwords', esc_html__( 'Stopwords', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'synonyms', esc_html__( 'Synonyms', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'redirects', esc_html__( 'Redirects', 'press-search' ) );

// Register tabs for report.
$press_search_setting->register_tab( 'press-search-report', 'overview', esc_html__( 'Engines', 'press-search' ), array( press_search_reports(), 'engines_tab_content' ) );
$press_search_setting->register_tab( 'press-search-report', 'popular-searches', esc_html__( 'Popular Searches', 'press-search' ), array( press_search_reports(), 'engines_popular_search_content' ) );
$press_search_setting->register_tab( 'press-search-report', 'no-results', esc_html__( 'No Results', 'press-search' ), array( press_search_reports(), 'engines_no_results_content' ) );

$press_search_setting->set_tab_file_configs( 'engines', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/engines.php' );
$press_search_setting->register_sub_tab(
	'settings-loging',
	'loging',
	esc_html__( 'Loging', 'press-search' )
);
$press_search_setting->register_sub_tab(
	'settings-loging',
	'settings_loging_reports',
	esc_html__( 'View Reports', 'press-search' ),
	array(),
	array( press_search_reports(), 'logging_subtab_report_content' )
);
$press_search_setting->register_sub_tab(
	'settings-loging',
	'settings_loging_empty_logs',
	esc_html__( 'Empty Logs', 'press-search' ),
	array(
		'link' => add_query_arg(
			array(
				'action' => 'press_search_empty_logs',
			),
			admin_url( 'admin-ajax.php' )
		),
		'target' => '_self',
		'onclick' => sprintf( 'return confirm("%s");', esc_html__( 'Are you sure to empty log?', 'press-search' ) ),
	)
);

$press_search_setting->set_sub_tab_file_configs( 'settings-loging', 'loging', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/loging/loging.php' );
$press_search_setting->set_tab_file_configs( 'searching', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/searching.php' );
$press_search_setting->set_tab_file_configs( 'stopwords', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/stopwords.php' );
$press_search_setting->set_tab_file_configs( 'synonyms', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/synonyms.php' );
$press_search_setting->set_tab_file_configs( 'redirects', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/redirects.php' );
