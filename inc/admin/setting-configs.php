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

$press_search_setting->register_tab( 'settings_engines', esc_html__( 'Engines', 'press-search' ), 'press-search-settings' );
$press_search_setting->register_tab( 'settings_searching', esc_html__( 'Searching', 'press-search' ), 'press-search-settings' );
$press_search_setting->register_tab( 'settings_loging', esc_html__( 'Loging', 'press-search' ), 'press-search-settings' );
$press_search_setting->register_tab( 'settings_stopwords', esc_html__( 'Stopwords', 'press-search' ), 'press-search-settings' );
$press_search_setting->register_tab( 'settings_synonyms', esc_html__( 'Synonyms', 'press-search' ), 'press-search-settings' );
$press_search_setting->register_tab( 'settings_redirects', esc_html__( 'Redirects', 'press-search' ), 'press-search-settings' );


$press_search_setting->register_sub_tab( 'settings_loging_loging', 'settings_loging', esc_html__( 'Loging', 'press-search' ) );
$press_search_setting->register_sub_tab( 'settings_loging_reports', 'settings_loging', esc_html__( 'View Reports', 'press-search' ) );
$press_search_setting->register_sub_tab( 'settings_loging_empty_logs', 'settings_loging', esc_html__( 'Empty Logs', 'press-search' ) );


$press_search_setting->set_tab_fields(
	'settings_engines',
	array(
		array(
			'name' => esc_html__( 'Synonyms', 'press-search' ),
			'desc' => esc_html__( 'field description (optional)', 'press-search' ),
			'id'   => 'synonymns',
			'type' => 'textarea',
			'before'       => esc_html__( 'Add synonyms here to make the searches find better results. I you notice your user frequently misspelling a product name, or for other reasons use many names for one thing. Adding synonyms will make the results betters.', 'press-search' ),
			'after' => esc_html__( 'Each item per line, example: amazing=incredible angry=mad' ),
		),
	)
);

$press_search_setting->set_sub_tab_file_configs( 'settings_loging_loging', 'settings_loging', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/loging/loging.php' );

$press_search_setting->set_tab_file_configs( 'settings_searching', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/searching.php' );
$press_search_setting->set_tab_file_configs( 'settings_stopwords', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/stopwords.php' );
$press_search_setting->set_tab_file_configs( 'settings_synonyms', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/synonyms.php' );
$press_search_setting->set_tab_file_configs( 'settings_redirects', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/redirects.php' );
