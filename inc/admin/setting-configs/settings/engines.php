<?php
$link_args = array(
	'page' => 'press-search-report',
	'search_engine' => 'ps_engine_name',
);
$view_detail = add_query_arg( $link_args, admin_url( 'admin.php' ) );
$repeatable = false;
if ( ps_is__pro() ) {
	$repeatable = true;
}

return array(
	array(
		'id'          => 'engines',
		'type'        => 'group',
		'options'     => array(
			'group_title'    => esc_html__( 'Engine Name {#}', 'press-search' ),
			'add_button'     => esc_html__( 'Add engine', 'press-search' ),
			'remove_button'  => esc_html__( 'Delete engine', 'press-search' ),
			'sortable'       => false,
			'remove_confirm' => esc_html__( 'Are you sure to remove this engine?', 'press-search' ),
		),
		'repeatable'  => $repeatable,
		'attributes' => array(
			'data-target' => 'group_setting_engine',
		),
		'fields' => array(
			array(
				'default' => 'engine_default',
				'id'      => 'engine_slug',
				'type'    => 'text',
				'attributes' => array(
					'type' => 'hidden',
					'class' => 'unique_engine_slug',
				),
				'row_classes' => 'custom-row-class',
			),
			array(
				'id'               => 'engines_name',
				'type'             => 'editable_input',
				'default'          => 'Default Engine',
				'after'            => esc_html__( 'These post types will be included in your search results, all other post types will be excluded.', 'press-search' ),
				'extra_text'       => array(
					'text'         => esc_html__( 'Search Statistics', 'press-search' ),
					'link'         => esc_url( $view_detail ),
					'target'       => '_blank',
				),
			),
			array(
				'name'             => esc_html__( 'Post type', 'press-search' ),
				'id'               => 'engines_post_type',
				'type'             => 'animate_select',
				'options_cb'       => 'press_search_engines_post_type_options_cb',
				'text'        => array(
					'select_placeholder' => esc_html__( 'Select post type', 'press-search' ),
					'add_value' => esc_html__( 'Add', 'press-search' ),
				),
				'default'          => array( 'post', 'page' ),
			),
			array(
				'name'             => esc_html__( 'Taxonomy', 'press-search' ),
				'id'               => 'engines_taxonomy',
				'type'             => 'animate_select',
				'options_cb'  => 'press_search_engines_taxonomy_options_cb',
				'text'        => array(
					'select_placeholder' => esc_html__( 'Select taxonomy', 'press-search' ),
					'add_value' => esc_html__( 'Add', 'press-search' ),
				),
			),
			array(
				'desc' => esc_html__( 'Include comments', 'press-search' ),
				'id'   => 'engines_include_comments',
				'type' => 'checkbox',
			),
			array(
				'desc' => esc_html__( 'Index the post author display name', 'press-search' ),
				'id'   => 'engines_index_post_author',
				'type' => 'checkbox',
			),
			array(
				'desc' => esc_html__( 'Index the post excerpt', 'press-search' ),
				'id'   => 'engines_index_post_excerpt',
				'type' => 'checkbox',
			),
			array(
				'desc' => esc_html__( 'Expand shortcodes when indexing', 'press-search' ),
				'id'   => 'engines_expand_shortcodes',
				'type' => 'checkbox',
			),
			array(
				'name'             => esc_html__( 'Custom fields', 'press-search' ),
				'id'               => 'engines_custom_fields',
				'type'             => 'select',
				'options'          => array(
					'none'         => esc_html__( 'None', 'press-search' ),
					'all'          => esc_html__( 'All', 'press-search' ),
					'let-me-choice' => esc_html__( 'Let me choice', 'press-search' ),
				),
			),
			array(
				'id'               => 'engines_choice_custom_fields',
				'type'             => 'animate_select',
				'options'          => press_search_get_custom_field_keys(),
				'attributes' => array(
					'data-conditional-id'    => 'engines_custom_fields',
					'data-conditional-value' => 'let-me-choice',
				),
				'text'        => array(
					'select_placeholder' => esc_html__( 'Select custom field', 'press-search' ),
					'add_value' => esc_html__( 'Add', 'press-search' ),
				),
			),
			array(
				'name'       => esc_html__( 'Searching', 'press-search' ),
				'id'         => 'searching_title',
				'type'       => 'custom_title',
			),
			array(
				'name'       => esc_html__( 'Default operator', 'press-search' ),
				'id'         => 'searching_default_operator',
				'type'       => 'select',
				'options'    => array(
					'and'    => esc_html__( 'And', 'press-search' ),
					'or'     => esc_html__( 'Or', 'press-search' ),
				),
				'default'    => press_search_get_var( 'default_operator' ),
			),
			array(
				'name'       => esc_html__( 'Weights', 'press-search' ),
				'id'         => 'searching_weights',
				'type'       => 'element_weight',
				'before'     => sprintf( '<p>%1$s<br/>%2$s</p>', esc_html__( 'All the weights in the table are multipliers. To increase the weight of an element, use a higher number.', 'press-search' ), esc_html__( 'To make an element less significant, use a number lower than 1.', 'press-search' ) ),
			),
		),
	),
);
