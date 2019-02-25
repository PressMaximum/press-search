<?php

class Press_Search_Crawl_Data {
	/**
	 * Custom fields setting: fase - don't index custom field, true - index custom field, array - list custom field keys will be index
	 *
	 * @var mixed boolean or array
	 */
	protected $custom_field;
	/**
	 * Category setting: fase - don't index category, true - index category
	 *
	 * @var boolean
	 */
	protected $category;
	/**
	 * Tag setting: fase - don't index tag, true - index tag
	 *
	 * @var boolean
	 */
	protected $tag;
	/**
	 * Custom taxonomy setting: fase - don't index custom tax, true - index custom tax, array - list custom tax with meta keys will be index
	 *
	 * @var mixed boolean or array
	 */
	protected $custom_tax;
	/**
	 * Comment setting: fase - don't index comment, true - index comment
	 *
	 * @var boolean
	 */
	protected $comment;
	/**
	 * User meta setting: fase - don't index user meta, true - index user meta, array - list user meta keys will be index
	 *
	 * @var mixed boolean or array
	 */
	protected $user_meta;

	public function __construct( $args = array() ) {
		$this->init_settings( $args );

		if ( is_admin() ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		add_action(
			'before_render_content',
			function() {
				/*
				echo '<pre>Post data:';
				print_r( $this->get_post_data( 1 ) );
				echo '</pre>';
				echo '<pre>Post data count:';
				print_r( $this->get_post_data_count( 1 ) );
				echo '</pre>'; 
				*/
				echo '<pre>Term data:';
				print_r( $this->get_term_data( 23 ) );
				echo '</pre>';
				echo '<pre>Term data count:';
				print_r( $this->get_term_data_count( 23 ) );
				echo '</pre>';
			}
		);
	}

	public function init_settings( $args = null ) {
		$settings = array(
			'custom_field' => true,
			'category' => true,
			'tag' => true,
			'custom_tax' => true,
			'comment' => true,
			'user_meta' => true,
		);
		if ( isset( $args['settings'] ) && is_array( $args['settings'] ) ) {
			$settings = wp_parse_args( $args['settings'], $settings );
		}
		foreach ( $settings as $key => $value ) {
			$this->$key = $value;
		}
	}


	public function detect_post_custom_tax_slug( $post_id ) {
		$custom_tax = array();
		$post = get_post( $post_id );
		$all_tax = get_object_taxonomies( $post );
		if ( is_array( $all_tax ) && ! empty( $all_tax ) ) {
			foreach ( $all_tax as $tax ) {
				if ( ! in_array( $tax, array( 'category', 'post_tag', 'post_format' ), true ) ) {
					$custom_tax[] = $tax;
				}
			}
		}
		return $custom_tax;
	}

	public function get_post_term_name( $post_id = 0, $term_slug = '' ) {
		$term_name = array();
		$term_list = wp_get_post_terms( $post_id, $term_slug, array( 'fields' => 'all' ) );
		if ( ! is_wp_error( $term_list ) && is_array( $term_list ) && ! empty( $term_list ) ) {
			foreach ( $term_list as $term ) {
				$term_name[] = $term->name;
			}
		}
		return $term_name;
	}

	public function get_post_term_info( $post_id = 0, $term_slug = '' ) {
		$term_info = array();
		$term_list = wp_get_post_terms( $post_id, $term_slug, array( 'fields' => 'all' ) );
		if ( ! is_wp_error( $term_list ) && is_array( $term_list ) && ! empty( $term_list ) ) {
			foreach ( $term_list as $term ) {
				$term_info[] = array(
					'id' => $term->id,
					'name' => $term->name,
				);
			}
		}
		return $term_info;
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
			);
			if ( ! empty( $get_post['post_category'] ) && $this->category ) {
				foreach ( $get_post['post_category'] as $cat ) {
					if ( '' !== get_cat_name( $cat ) ) {
						$return_data['cat_names'][ $cat ] = get_cat_name( $cat );
					}
				}
			}

			if ( ! empty( $get_post['tags_input'] ) && $this->tag ) {
				$return_data['tag_names'] = $get_post['tags_input'];
			}

			$custom_field_data = $this->get_post_custom( $post_id );
			if ( ! empty( $custom_field_data ) ) {
				$return_data['custom_fields'] = $custom_field_data;
			}

			$custom_tax = $this->get_post_tax( $post_id );
			if ( ! empty( $custom_tax ) ) {
				$return_data['custom_tax_names'] = $custom_tax;
			}

			$comments = $this->get_post_comment( $post_id );
			if ( ! empty( $comments ) ) {
				$return_data['comments'] = $comments;
			}

			$author_meta = $this->get_all_user_meta( $get_post['post_author'] );
			if ( ! empty( $author_meta ) ) {
				$return_data['user_meta'] = $author_meta;
			}
		}
		return $return_data;
	}

	public function get_post_custom( $post_id ) {
		$return = array();
		if ( $this->custom_field ) {
			$custom_fields = get_post_custom();
			$custom_field_data = $this->get_meta_data( $custom_fields );
			if ( ! empty( $custom_field_data ) ) {
				if ( is_array( $this->custom_field ) && ! empty( $this->custom_field ) ) {
					foreach ( $custom_field_data as $key => $value ) {
						if ( in_array( $key, $this->custom_field, true ) ) {
							$return[ $key ] = $value;
						}
					}
				} else {
					$return = $custom_field_data;
				}
			}
		}
		// Remove fields are an url or boolean.
		if ( ! empty( $return ) ) {
			foreach ( $return as $k => $v ) {
				if ( ( is_string( $v ) && $this->is_valid_url( $v ) ) || is_bool( $v ) ) {
					unset( $return[ $k ] );
				}
			}
		}
		return $return;
	}

	public function get_post_comment( $post_id, $comment_status = 'all' ) {
		$return = array();
		if ( $this->comment ) {
			$comments = get_comments(
				array(
					'post_id' => $post_id,
					'status' => $comment_status,
				)
			);
			if ( is_array( $comments ) && ! empty( $comments ) ) {
				foreach ( $comments as $comment ) {
					if ( isset( $comment->comment_content ) && '' !== $comment->comment_content ) {
						$return[ $comment->comment_ID ] = $comment->comment_content;
					}
				}
			}
		}
		return $return;
	}

	public function get_post_tax( $post_id ) {
		$return = array();
		if ( $this->custom_tax ) {
			$custom_tax_slug = $this->detect_post_custom_tax_slug( $post_id );
			if ( is_array( $custom_tax_slug ) && ! empty( $custom_tax_slug ) ) {
				foreach ( $custom_tax_slug as $tax_slug ) {
					if ( is_array( $this->custom_tax ) && ! empty( $this->custom_tax ) ) {
						if ( ! in_array( $tax_slug, $this->custom_tax, true ) ) {
							continue;
						}
					}
					$get_term_name = $this->get_post_term_name( $post_id, $tax_slug );
					if ( ! empty( $get_term_name ) ) {
						$return[ $tax_slug ] = $get_term_name;
					}
				}
			}
		}
		return $return;
	}

	public function recursive_array( $array ) {
		$flat = array();
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, $this->recursive_array( $value ) );
			} else {
				if ( ! $this->is_valid_url( $value ) && ! is_bool( $value ) ) {
					$flat[] = $value;
				}
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
				} elseif ( 'custom_fields' == $key || 'user_meta' == $key ) {
					$count_custom_field = $this->get_meta_data_count( $data );
					if ( ! empty( $count_custom_field ) ) {
						$return[ $key ] = $count_custom_field;
					}
				} elseif ( 'custom_tax_names' == $key ) {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $term_slug => $term_datas ) {
							if ( is_array( $term_datas ) && ! empty( $term_datas ) ) {
								foreach ( $term_datas as $term_name ) {
									$term_item_key = press_search_string()->replace_str_spaces( $term_name, '_' );
									$return[ $key ][ $term_slug ][ $term_item_key ] = press_search_string()->count_words_from_str( $term_name );
								}
							}
						}
					}
				} else {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $k => $v ) {
							if ( '' !== $v ) {
								$return[ $key ][ $k ] = press_search_string()->count_words_from_str( $v );
							}
						}
					} else {
						$return[ $key ] = press_search_string()->count_words_from_str( $data );
					}
				}
			}
		}
		return $return;
	}

	public function get_all_user_meta( $user_id ) {
		$return = array();
		if ( $this->user_meta ) {
			$user_metas = get_user_meta( $user_id );
			if ( is_array( $user_metas ) && ! empty( $user_metas ) ) {
				$metas = $this->get_meta_data( $user_metas );
				if ( ! empty( $metas ) ) {
					if ( is_array( $this->user_meta ) ) {
						foreach ( $metas as $key => $meta ) {
							if ( in_array( $key, $this->user_meta, true ) ) {
								$return[ $key ] = $meta;
							}
						}
					} else {
						$return = $metas;
					}
				}
			}
		}
		return $return;
	}

	public function is_valid_url( $string ) {
		if ( is_string( $string ) && filter_var( $string, FILTER_VALIDATE_URL ) ) {
			return true;
		}
		return false;
	}

	public function get_meta_data( $data ) {
		$return_data = array();
		if ( is_array( $data ) && ! empty( $data ) ) {
			foreach ( $data as $key => $field ) {
				if ( isset( $field[0] ) && ! empty( $field[0] ) ) {
					$field_data = maybe_unserialize( $field[0] );
					if ( is_array( $field_data ) ) {
						$field_data = $this->recursive_array( $field_data );
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
					$return[ $k ] = $arr;
				} else {
					$return[ $k ] = press_search_string()->count_words_from_str( $v );
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

			if ( isset( $term_info->description ) && ! empty( $term_info->description ) ) {
				$return_data['description'] = $term_info->description;
			}

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
					$count_custom_field = $this->get_meta_data_count( $data );
					if ( ! empty( $count_custom_field ) ) {
						$return[ $key ] = $count_custom_field;
					}
				}
			}
		}
		return $return;
	}
}

new Press_Search_Crawl_Data(
	array(
		'settings' => array(
			'custom_field' => array(
				'_press_search__group_fields',
				'_test_post_meta',
				'_press_search__text',
				'_press_search__textmedium',
			),
			'custom_tax' => array(
				'custom-taxonomy',
				'custom-taxonomy2',
			),
			'category' => true,
			'tag'   => true,
			'user_meta' => array(
				'_press_search__text',
				'_press_search__textmedium',
				'_press_search__group_fields',
			),
			'comment' => true,
		),
	)
);

