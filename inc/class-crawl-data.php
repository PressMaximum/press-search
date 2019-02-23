<?php

class Press_Search_Crawl_Data {
	public function __construct() {
		if ( is_admin() ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		add_action(
			'before_render_content',
			function() {
				echo '<pre>Term data:';
				print_r( $this->get_term_data( 22 ) );
				echo '</pre>';

				echo '<pre>Term data count:';
				print_r( $this->get_term_data_count( 22 ) );
				echo '</pre>';
			}
		);
	}

	/**
	 * Get post data by post ID
	 *
	 * @param integer $post_id
	 * @return array
	 */
	public function get_post_data( $post_id = 0 ) {
		$return_data = array();
		$get_post = get_post( $post_id, ARRAY_A );
		if ( ! empty( $get_post ) ) {
			$return_data = array(
				'ID' => $get_post['ID'],
				'title' => $get_post['post_title'],
				'content' => $get_post['post_content'],
				'excerpt' => $get_post['post_excerpt'],
				'author_display_name' => get_the_author_meta( 'display_name', $get_post['post_author'] ),
				'tag_names'  => $get_post['tags_input'],
				'cat_names' => array(),
			);
			if ( ! empty( $get_post['post_category'] ) ) {
				foreach ( $get_post['post_category'] as $cat ) {
					if ( '' !== get_cat_name( $cat ) ) {
						$return_data['cat_names'][ $cat ] = get_cat_name( $cat );
					}
				}
			}
			$custom_fields = get_post_custom();
			$custom_field_data = $this->get_meta_data( $custom_fields );
			if ( ! empty( $custom_field_data ) ) {
				$return_data['custom_fields'] = $custom_field_data;
			}
		}
		return $return_data;
	}

	public function maybe_get_text_from_recursive_array( $array ) {
		$flat = array();

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, $this->maybe_get_text_from_recursive_array( $value ) );
			} else {
				$flat[] = $value;
			}
		}
		return $flat;
	}

	public function get_post_data_count( $post_id = 0 ) {
		$return = array();
		$post_data = $this->get_post_data( $post_id );
		if ( is_array( $post_data ) && ! empty( $post_data ) ) {
			foreach ( $post_data as $key => $data ) {
				if ( 'ID' == $key ) {
					continue;
				}
				if ( 'tag_names' == $key || 'cat_names' == $key ) {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $term_data ) {
							$arr_key = press_search_string()->replace_str_spaces( $term_data, '_' );
							$return[ $key ][ $arr_key ] = press_search_string()->count_words_from_str( $term_data );
						}
					}
				} elseif ( 'custom_fields' == $key ) {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $k => $v ) {
							if ( is_array( $v ) ) {
								$arr = array();
								foreach ( $v as $loop_arr ) {
									$arr[] = press_search_string()->count_words_from_str( $loop_arr );
								}
								$return[ $key ] = $arr;
							} else {
								$return[ $key ][ $k ] = press_search_string()->count_words_from_str( $v );
							}
						}
					}
				} else {
					$return[ $key ] = press_search_string()->count_words_from_str( $data );
				}
			}
		}
		return $return;
	}

	public function get_meta_data( $data ) {
		$return_data = array();
		if ( is_array( $data ) && ! empty( $data ) ) {
			foreach ( $data as $key => $field ) {
				if ( isset( $field[0] ) && ! empty( $field[0] ) ) {
					$field_data = maybe_unserialize( $field[0] );
					if ( is_array( $field_data ) ) {
						$field_data = $this->maybe_get_text_from_recursive_array( $field_data );
					}
					$return_data[ $key ] = $field_data;
				}
			}
		}
		return $return_data;
	}

	public function get_meta_data_count( $data ) {
		$return = array();
		if ( is_array( $data ) && ! empty( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$arr = array();
					foreach ( $v as $loop_arr ) {
						$arr[] = press_search_string()->count_words_from_str( $loop_arr );
					}
					$return[] = $arr;
				} else {
					$return[][ $k ] = press_search_string()->count_words_from_str( $v );
				}
			}
		}
		return $return;
	}
	/**
	 * Get term data by term id
	 *
	 * @param integer $term_id
	 * @return array
	 */
	public function get_term_data( $term_id = 0 ) {
		$term_info = get_term( $term_id );
		$return_data = array();
		if ( ! is_wp_error( $term_info ) ) {
			$return_data = array(
				'name' => $term_info->name,
			);

			// Term meta.
			$term_meta = get_term_meta( $term_id );
			$term_meta_data = $this->get_meta_data( $term_meta );
			if ( ! empty( $term_meta_data ) ) {
				$return_data['meta_data'] = $term_meta_data;
			}
		}
		return $return_data;
	}

	public function get_term_data_count( $term_id = 0 ) {
		$term_data = $this->get_term_data( $term_id );
		$return = array();

		if ( is_array( $term_data ) && ! empty( $term_data ) ) {
			foreach ( $term_data as $key => $data ) {
				if ( 'meta_data' !== $key ) {
					$return[ $key ] = press_search_string()->count_words_from_str( $data );
				} else {

				}
			}
		}
		return $return;
	}
}

new Press_Search_Crawl_Data();

