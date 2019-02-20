<?php
return array(
	array(
		'name' => esc_html__( 'Stopwords', 'press-search' ),
		'id'   => 'stopwords',
		'type' => 'textarea',
		'before'       => sprintf( '<p>%1$s<br/>%2$s</p>', esc_html__( 'The words will automatically be removed from the index, so re-indexing is not necessary', 'press-search' ), esc_html__( 'You can enter many words at the same time, separate words with commas', 'press-search' ) ),
	),
);
