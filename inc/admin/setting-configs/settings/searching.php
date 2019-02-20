<?php
press_search_get_all_categories();

return array(
	array(
		'name'       => esc_html__( 'Default operator', 'press-search' ),
		'id'         => 'searching_default_operator',
		'type'       => 'text',
	),
	array(
		'name'       => esc_html__( 'Weights', 'press-search' ),
		'id'         => 'searching_weights',
		'type'       => 'element_weight',
	),
	array(
		'name'    => esc_html__( 'Category exclusion', 'press-search' ),
		'id'      => 'searching_category_exclusion',
		'desc'    => esc_html__( 'Post in these categories are not included in search results. To exclude the posts completely from the index.', 'press-search' ),
		'type'    => 'text',
		// 'options' => press_search_get_all_categories(),
	),
	array(
		'name'    => esc_html__( 'Post exclusion', 'press-search' ),
		'id'      => 'searching_post_exclusion',
		'type'    => 'text',
		// 'options' => press_search_get_all_posts(),
	),
	array(
		'name'       => esc_html__( 'Excerpt length', 'press-search' ),
		'id'         => 'searching_excerpt_length',
		'type'       => 'content_length',
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
);
