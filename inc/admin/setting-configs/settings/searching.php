<?php
press_search_get_all_categories();

return array(
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
		'default'    => 'or',
	),
	array(
		'name'       => esc_html__( 'Weights', 'press-search' ),
		'id'         => 'searching_weights',
		'type'       => 'element_weight',
		'before'     => sprintf( '<p>%1$s<br/>%2$s</p>', esc_html__( 'All the weights in the table are multipliers. To increase the weight of an element, use a higher number.', 'press-search' ), esc_html__( 'To make an element less significant, use a number lower than 1', 'press-search' ) ),
	),
	array(
		'name'    => esc_html__( 'Terms exclusion', 'press-search' ),
		'id'      => 'searching_category_exclusion',
		'type'    => 'text',
	),
	array(
		'name'    => esc_html__( 'Post exclusion', 'press-search' ),
		'id'      => 'searching_post_exclusion',
		'type'    => 'text',
		'after'   => sprintf( '<p>%s</p>', esc_html__( 'Post in these terms are not included in search results. To exclude the posts completely from the index.', 'press-search' ) ),
	),
	array(
		'name'       => esc_html__( 'Excerpts and highlights', 'press-search' ),
		'id'         => 'searching_excerpt_hightlight_title',
		'type'       => 'custom_title',
	),
	array(
		'name'       => esc_html__( 'Excerpt length', 'press-search' ),
		'id'         => 'searching_excerpt_length',
		'type'       => 'content_length',
		'default'    => array(
			'length' => 30,
			'type'   => 'words',
		),
	),
	array(
		'name'       => esc_html__( 'Excerpt more', 'press-search' ),
		'id'         => 'searching_excerpt_more',
		'type'       => 'text',
		'attributes' => array(
			'placeholder' => esc_html__( '...', 'press-search' ),
		),
	),
	array(
		'name'             => esc_html__( 'Hightlight terms', 'press-search' ),
		'id'               => 'searching_hightlight_terms',
		'type'             => 'select',
		'options'          => array(
			'default'      => esc_html__( 'Default', 'press-search' ),
			'bold'         => esc_html__( 'Bold', 'press-search' ),
		),
		'default'          => 'bold',
	),
	array(
		'name'       => esc_html__( 'Enable ajax live search', 'press-search' ),
		'id'         => 'searching_enable_ajax_live_search',
		'type'       => 'select',
		'options'    => array(
			'yes'    => esc_html__( 'Yes', 'press-search' ),
			'no'     => esc_html__( 'No', 'press-search' ),
		),
		'default'    => 'yes',
	),
	array(
		'name'       => esc_html__( 'Ajax limit items', 'press-search' ),
		'id'         => 'searching_ajax_limit_items',
		'type'       => 'text',
		'attributes' => array(
			'type'      => 'number',
			'min'       => '1',
			'max'       => '100',
			'step'      => '1',
		),
		'default'    => 10,
		'attributes' => array(
			'data-conditional-id'    => 'searching_enable_ajax_live_search',
			'data-conditional-value' => 'yes',
		),
	),
);
