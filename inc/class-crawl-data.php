<?php

class Press_Search_Crawl_Data {
	public function __construct() {
		if ( is_admin() ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
	}

	public function get_post_data( $post_id ) {
		$return_data = array();
		$get_post = get_post( $post_id, ARRAY_A );

		$return_data = array(
			'ID' => $get_post['ID'],
			'title' => $get_post['post_title'],
			'content' => $get_post['post_content'],
			'excerpt' => $get_post['post_excerpt'],
			'author_display_name' => get_the_author_meta( 'display_name', $get_post['post_author'] ),
			'tag_name'  => $get_post['tags_input'],
			'cat_names' => array(),
		);
		if ( ! empty( $get_post['post_category'] ) ) {
			foreach ( $get_post['post_category'] as $cat ) {
				if ( '' !== get_cat_name( $cat ) ) {
					$return_data['cat_names'][] = get_cat_name( $cat );
				}
			}
		}
		$custom_fields = get_post_custom();
		$return_data['custom_fields'] = $custom_fields;

		return $return_data;
	}
	public function get_term_data( $term_id ) {

	}
}

new Press_Search_Crawl_Data();

