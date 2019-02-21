<?php
return array(
	array(
		'name' => esc_html__( 'Synonyms', 'press-search' ),
		'id'   => 'synonymns_title',
		'type' => 'custom_title',
	),
	array(
		'id'   => 'synonymns',
		'type' => 'textarea',
		'before'       => sprintf( '<p>%1$s<br/>%2$s</br>%3$s</p>', esc_html__( 'Add synonyms here to make the searches find better results.', 'press-search' ), esc_html__( 'I you notice your user frequently misspelling a product name, or for other reasons use many names for one thing.', 'press-search' ), esc_html__( 'Adding synonyms will make the results betters.', 'press-search' ) ),
		'after' => sprintf( '<p>%1$s<br/>%2$s<br/>%3$s</p>', esc_html__( 'Each item per line, example:', 'press-search' ), esc_html__( 'amazing=incredible', 'press-search' ), esc_html__( 'angry=mad', 'press-search' ) ),
		'after_row' => sprintf( '<p>%s</p>', esc_html__( 'So, If you search the "amazing" it will return both "amazing" and "incredible"', 'press-search' ) ),

	),
);
