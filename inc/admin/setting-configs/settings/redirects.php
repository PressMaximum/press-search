<?php
return array(
	array(
		//'name' => esc_html__( 'Test Checkbox', 'cmb2' ),
		'desc' => esc_html__( 'Redirect automatically to post, page if keywords like exactly post title', 'cmb2' ),
		'id'   => 'redirects_automatic_post_page',
		'type' => 'checkbox',
	),
	array(
		'id'          => 'redirects_automatic_custom',
		'type'        => 'group',
		'options'     => array(
			'group_title'    => esc_html__( 'Entry {#}', 'press-search' ), // {#} gets replaced by row number
			'add_button'     => esc_html__( 'Add', 'press-search' ),
			'remove_button'  => esc_html__( 'Remove Entry', 'press-search' ),
			'sortable'       => true,
			// 'closed'      => true, // true to have the groups closed by default
			'remove_confirm' => esc_html__( 'Are you sure you want to remove?', 'press-search' ), // Performs confirmation before removing group.
		),
		'fields' => array(
			array(
				'name'       => esc_html__( 'Keyword', 'press-search' ),
				'id'         => 'redirects_automatic_custom_keyword',
				'type'       => 'text',
			),
			array(
				'name'       => esc_html__( 'Url to redirect', 'press-search' ),
				'id'         => 'redirects_automatic_custom_url',
				'type'       => 'text',
				// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
			),
		),
	)
);
