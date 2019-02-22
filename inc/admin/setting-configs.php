<?php
$press_search_setting = press_search_settings();
$press_search_setting->add_settings_page(
	array(
		'menu_slug' => 'press-search-settings',
		'parent_slug' => null,
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

$press_search_setting->register_tab( 'press-search-settings', 'settings_engines', esc_html__( 'Engines', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'settings_searching', esc_html__( 'Searching', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'settings_loging', esc_html__( 'Loging', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'settings_stopwords', esc_html__( 'Stopwords', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'settings_synonyms', esc_html__( 'Synonyms', 'press-search' ) );
$press_search_setting->register_tab( 'press-search-settings', 'settings_redirects', esc_html__( 'Redirects', 'press-search' ) );

$press_search_setting->set_tab_file_configs( 'settings_engines', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/engines.php' );
$press_search_setting->register_sub_tab( 'settings_loging', 'settings_loging_loging', esc_html__( 'Loging', 'press-search' ) );
$press_search_setting->register_sub_tab(
	'settings_loging',
	'settings_loging_reports',
	esc_html__( 'View Reports', 'press-search' ),
	array(
		'link' => '#report',
		'target' => '_blank',
	)
);
$press_search_setting->register_sub_tab(
	'settings_loging',
	'settings_loging_empty_logs',
	esc_html__( 'Empty Logs', 'press-search' ),
	array(
		'link' => '#empty-logs',
		'target' => '_blank',
	)
);

$press_search_setting->set_sub_tab_file_configs( 'settings_loging', 'settings_loging_loging', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/loging/loging.php' );
$press_search_setting->set_tab_file_configs( 'settings_searching', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/searching.php' );
$press_search_setting->set_tab_file_configs( 'settings_stopwords', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/stopwords.php' );
$press_search_setting->set_tab_file_configs( 'settings_synonyms', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/synonyms.php' );
$press_search_setting->set_tab_file_configs( 'settings_redirects', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/redirects.php' );


$press_search_setting->set_sub_tab_fields(
	'settings_loging_loging',
	'settings_loging',
	array(
		array(
			'name' => esc_html__( 'Enable login', 'press-search' ),
			'desc' => esc_html__( 'Enable', 'press-search' ),
			'id'   => 'loging_enable_login',
			'type' => 'checkbox',
		),
	)
);



