<?php
$default_stop_words = '';
if ( file_exists( PRESS_SEARCH_DIR . 'inc/default-stop-words.php' ) ) {
	$stop_words = include PRESS_SEARCH_DIR . 'inc/default-stop-words.php';
	if ( ! empty( $stop_words ) ) {
		$default_stop_words = $stop_words;
	}
}
return array(
	array(
		'name' => esc_html__( 'Stopwords', 'press-search' ),
		'id'   => 'stopwords_title',
		'type' => 'custom_title',
	),
	array(
		'id'   => 'stopwords',
		'type' => 'textarea',
		'default' => $default_stop_words,
		'before'       => sprintf( '<p>%1$s<br/>%2$s</p>', esc_html__( 'The words will automatically be removed from the index, so re-indexing is not necessary', 'press-search' ), esc_html__( 'You can enter many words at the same time, separate words with commas', 'press-search' ) ),
	),
);
