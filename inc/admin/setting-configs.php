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
		'link' => '#empty-logs',
		'target' => '_blank',
	)
);

$press_search_setting->set_sub_tab_file_configs( 'settings-loging', 'loging', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/loging/loging.php' );
$press_search_setting->set_tab_file_configs( 'searching', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/searching.php' );
$press_search_setting->set_tab_file_configs( 'stopwords', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/stopwords.php' );
$press_search_setting->set_tab_file_configs( 'synonyms', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/synonyms.php' );
$press_search_setting->set_tab_file_configs( 'redirects', PRESS_SEARCH_DIR . '/inc/admin/setting-configs/settings/redirects.php' );

// Add meta box to crawl post meta.
$metabox_args = array(
	'id'            => 'metabox',
	'title'         => esc_html__( 'Metabox', 'press-search' ),
	'object_types'  => array( 'post', 'page' ),
);
$metabox_fields = array(
	array(
		'name'       => esc_html__( 'Test Text', 'cmb2' ),
		'desc'       => esc_html__( 'field description (optional)', 'cmb2' ),
		'id'         => '_text',
		'type'       => 'text',
	),
	array(
		'name' => esc_html__( 'Test Text Medium', 'cmb2' ),
		'desc' => esc_html__( 'field description (optional)', 'cmb2' ),
		'id'   => '_textmedium',
		'type' => 'text_medium',
	),
	array(
		'id'          => '_group_fields',
		'type'        => 'group',
		'description' => esc_html__( 'Generates reusable form entries', 'cmb2' ),
		'options'     => array(
			'group_title'    => esc_html__( 'Entry {#}', 'cmb2' ), // {#} gets replaced by row number
			'add_button'     => esc_html__( 'Add Another Entry', 'cmb2' ),
			'remove_button'  => esc_html__( 'Remove Entry', 'cmb2' ),
			'sortable'       => true,
			// 'closed'      => true, // true to have the groups closed by default
			// 'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'cmb2' ), // Performs confirmation before removing group.
		),
		'fields' => array(
			array(
				'name'       => esc_html__( 'Entry Title', 'cmb2' ),
				'id'         => '_title',
				'type'       => 'text',
				// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
			),
			array(
				'name'        => esc_html__( 'Description', 'cmb2' ),
				'description' => esc_html__( 'Write a short description for this entry', 'cmb2' ),
				'id'          => '_description',
				'type'        => 'textarea_small',
			),
			array(
				'name' => esc_html__( 'Entry Image', 'cmb2' ),
				'id'   => '_image',
				'type' => 'file',
			),
			array(
				'name' => esc_html__( 'Image Caption', 'cmb2' ),
				'id'   => '_image_caption',
				'type' => 'text',
			),
		),
	),
);
$press_search_setting->add_meta_box( $metabox_args, $metabox_fields );


$term_metabox_args = array(
	'id'            => 'term_metabox',
	'title'         => esc_html__( 'Metabox', 'press-search' ),
	'object_types'  => array( 'term' ),
	'taxonomies'       => array( 'category', 'post_tag', 'custom-taxonomy' ),
);

$press_search_setting->add_meta_box( $term_metabox_args, $metabox_fields );

$user_metabox_args = array(
	'id'            => 'user_metabox',
	'title'         => esc_html__( 'User Metabox', 'press-search' ),
	'object_types'  => array( 'user' ),
);

$press_search_setting->add_meta_box( $user_metabox_args, $metabox_fields );

/**
 * Create custom taxonomy
 *
 * @return void
 */
function press_search_custom_taxonomy() {
		$labels = array(
			'name' => esc_html__( 'Custom taxonomy', 'press-search' ),
			'singular' => esc_html__( 'Custom taxonomy', 'press-search' ),
			'menu_name' => esc_html__( 'Custom taxonomy', 'press-search' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_in_rest'               => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
		);
		register_taxonomy( 'custom-taxonomy', array( 'post' ), $args );

		$labels = array(
			'name' => esc_html__( 'Custom taxonomy 2', 'press-search' ),
			'singular' => esc_html__( 'Custom taxonomy 2', 'press-search' ),
			'menu_name' => esc_html__( 'Custom taxonomy 2', 'press-search' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_in_rest'               => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => false,
		);
		register_taxonomy( 'custom-taxonomy2', array( 'post' ), $args );
}
add_action( 'init', 'press_search_custom_taxonomy', 0 );


function press_search_custom_post_type() {
	$labels = array(
		'name'               => _x( 'Books', 'post type general name', 'press-search' ),
		'singular_name'      => _x( 'Book', 'post type singular name', 'press-search' ),
		'menu_name'          => _x( 'Books', 'admin menu', 'press-search' ),
		'name_admin_bar'     => _x( 'Book', 'add new on admin bar', 'press-search' ),
		'add_new'            => _x( 'Add New', 'book', 'press-search' ),
		'add_new_item'       => __( 'Add New Book', 'press-search' ),
		'new_item'           => __( 'New Book', 'press-search' ),
		'edit_item'          => __( 'Edit Book', 'press-search' ),
		'view_item'          => __( 'View Book', 'press-search' ),
		'all_items'          => __( 'All Books', 'press-search' ),
		'search_items'       => __( 'Search Books', 'press-search' ),
		'parent_item_colon'  => __( 'Parent Books:', 'press-search' ),
		'not_found'          => __( 'No books found.', 'press-search' ),
		'not_found_in_trash' => __( 'No books found in Trash.', 'press-search' ),
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'Description.', 'press-search' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'book' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
	);
	register_post_type( 'book', $args );

	$labels2 = array(
		'name'               => _x( 'Portfolios', 'post type general name', 'press-search' ),
		'singular_name'      => _x( 'Portfolio', 'post type singular name', 'press-search' ),
		'menu_name'          => _x( 'Portfolios', 'admin menu', 'press-search' ),
		'name_admin_bar'     => _x( 'Portfolio', 'add new on admin bar', 'press-search' ),
		'add_new'            => _x( 'Add New', 'book', 'press-search' ),
		'add_new_item'       => __( 'Add New Portfolio', 'press-search' ),
		'new_item'           => __( 'New Portfolio', 'press-search' ),
		'edit_item'          => __( 'Edit Portfolio', 'press-search' ),
		'view_item'          => __( 'View Portfolio', 'press-search' ),
		'all_items'          => __( 'All Portfolios', 'press-search' ),
		'search_items'       => __( 'Search Portfolios', 'press-search' ),
		'parent_item_colon'  => __( 'Parent Portfolios:', 'press-search' ),
		'not_found'          => __( 'No portfolios found.', 'press-search' ),
		'not_found_in_trash' => __( 'No portfolios found in Trash.', 'press-search' ),
	);

	$args2 = array(
		'labels'             => $labels2,
		'description'        => __( 'Description.', 'press-search' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'portfolio' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
	);
	register_post_type( 'portfolio', $args2 );

	flush_rewrite_rules();
}
add_action( 'init', 'press_search_custom_post_type' );
