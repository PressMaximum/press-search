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

	protected $table_indexing_name;
	protected $table_logging_name;
	protected $index_columns_values = array();

	public function __construct( $args = array() ) {
		global $wpdb;
		$this->table_indexing_name = $wpdb->prefix . 'indexing';
		$this->table_logging_name = $wpdb->prefix . 'search_logs';
		$this->init_settings( $args );

		if ( is_admin() ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}
		add_action(
			'before_render_content',
			function() {
				echo '<pre>Term data: ';
				print_r( get_term( 23 ) );
				echo '</pre>';
			}
		);

		add_action( 'wp_ajax_index_data_ajax', array( $this, 'index_data_ajax' ) );
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


	public function detect_post_custom_tax_slug( $post_id = 0 ) {
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
				'author' => get_the_author_meta( 'display_name', $get_post['post_author'] ),
			);
			if ( ! empty( $get_post['post_category'] ) && $this->category ) {
				foreach ( $get_post['post_category'] as $cat ) {
					if ( '' !== get_cat_name( $cat ) ) {
						$return_data['category'][ $cat ] = get_cat_name( $cat );
					}
				}
			}

			if ( ! empty( $get_post['tags_input'] ) && $this->tag ) {
				$return_data['tag'] = $get_post['tags_input'];
			}

			$custom_field_data = $this->get_post_custom( $post_id );
			if ( ! empty( $custom_field_data ) ) {
				$return_data['custom_field'] = $custom_field_data;
			}

			$custom_tax = $this->get_post_tax( $post_id );
			if ( ! empty( $custom_tax ) ) {
				$return_data['taxonomy'] = $custom_tax;
			}

			$comments = $this->get_post_comment( $post_id );
			if ( ! empty( $comments ) ) {
				$return_data['comment'] = $comments;
			}

			$author_meta = $this->get_all_user_meta( $get_post['post_author'] );
			if ( ! empty( $author_meta ) ) {
				$return_data['user_meta'] = $author_meta;
			}
		}
		return $return_data;
	}

	public function get_post_custom( $post_id = 0 ) {
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

	public function get_post_comment( $post_id = 0, $comment_status = 'all' ) {
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

	public function get_post_tax( $post_id = 0 ) {
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

	public function recursive_array( $array = array() ) {
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
				if ( 'tag' == $key || 'category' == $key ) {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $term_data ) {
							$arr_key = press_search_string()->replace_str_spaces( $term_data, '_' );
							$return[ $key ][ $arr_key ] = press_search_string()->count_words_from_str( $term_data );
						}
					}
				} elseif ( 'custom_field' == $key || 'user_meta' == $key ) {
					$count_custom_field = $this->get_meta_data_count( $data );
					if ( ! empty( $count_custom_field ) ) {
						$return[ $key ] = $count_custom_field;
					}
				} elseif ( 'taxonomy' == $key ) {
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

	public function get_all_user_meta( $user_id = 0 ) {
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

	public function is_valid_url( $string = '' ) {
		if ( is_string( $string ) && filter_var( $string, FILTER_VALIDATE_URL ) ) {
			return true;
		}
		return false;
	}

	public function get_meta_data( $data = array() ) {
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

	public function get_meta_data_count( $data = array() ) {
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

	public function index_data_ajax() {
		// http://startedplugin.local/wp-admin/admin-ajax.php?action=index_data_ajax&data_type=post|taxonomy&ids=1,2,3,4 .
		// http://startedplugin.local/wp-admin/admin-ajax.php?action=index_data_ajax&data_type=post&ids=1,29 .
		$data_type = ( isset( $_REQUEST['data_type'] ) && in_array( $_REQUEST['data_type'], array( 'post', 'taxonomy' ), true ) ) ? $_REQUEST['data_type'] : 'post';
		$data_ids = ( isset( $_REQUEST['ids'] ) && '' !== $_REQUEST['ids'] ) ? $_REQUEST['ids'] : '';
		if ( '' == $data_ids ) {
			wp_send_json_error();
			wp_die();
		}
		$ids = array_unique( array_filter( explode( ',', $data_ids ), 'absint' ) );
		if ( empty( $ids ) ) {
			wp_send_json_error();
			wp_die();
		}

		if ( 'post' == $data_type ) {
			$return = false;
			foreach ( $ids as $post_id ) {
				if ( is_string( get_post_status( $post_id ) ) ) {
					$return = $this->insert_indexing_post( $post_id );
				}
			}
			if ( $return ) {
				wp_send_json_success(
					array(
						'message' => esc_html__( 'Index post success', 'press-search' ),
					)
				);
				wp_die();
			}
		}
	}

	public function insert_indexing_post( $post_id = 0 ) {
		global $wpdb;
		$post_data_count = $this->get_post_data_count( $post_id );
		$columns_values = array();
		$return = false;

		foreach ( $post_data_count as $key => $values ) {
			$args = array(
				'object_id' => $post_id,
				'object_type' => $key,
			);
			if ( in_array( $key, array( 'title', 'content', 'excerpt' ) ) ) {
				$args['object_type'] = 'post';
			} elseif ( 'author' == $key ) {
				$args['object_type'] = 'user';
			} elseif ( in_array( $key, array( 'category', 'tag', 'taxonomy' ) ) ) {
				$args['object_type'] = 'post_' . $key;
			}
			if ( ! empty( $values ) ) {
				switch ( $key ) {
					case 'title':
					case 'content':
					case 'excerpt':
					case 'author':
						$columns_values = array_merge( $this->get_index_column_value( $args, $key, $values ), $columns_values );
						break;
					case 'category':
					case 'tag':
					case 'comment':
						foreach ( $values as $column_key => $arr_data ) {
							$columns_values = array_merge( $this->get_index_column_value( $args, $key, $arr_data ), $columns_values );
						}
						break;
					case 'custom_field':
					case 'taxonomy':
					case 'user_meta':
						if ( 'user_meta' == $key ) {
							$key = 'author';
						}
						foreach ( $values as $column_key => $arr_data ) {
							$args['column_name'] = $column_key;
							if ( count( $arr_data ) == count( $arr_data, COUNT_RECURSIVE ) ) {
								$columns_values = array_merge( $this->get_index_column_value( $args, $key, $arr_data ), $columns_values );
							} else {
								foreach ( $arr_data as $k => $child_data ) {
									$columns_values = array_merge( $this->get_index_column_value( $args, $key, $child_data ), $columns_values );
								}
							}
						}
						break;
				}
			}
		}
		if ( ! empty( $columns_values ) ) {
			foreach ( $columns_values as $val ) {
				$return = $this->insert(
					$this->table_indexing_name,
					$val,
					array(
						'%d', // object_id.
						'%s', // object_type.
						'%s', // term.
						'%mysql_function', // term_reverse.
						'%d', // title.
						'%d', // content.
						'%d', // excerpt.
						'%d', // author.
						'%d', // comment.
						'%d', // category.
						'%d', // tag.
						'%d', // taxonomy.
						'%d', // custom_field.
						'%s', // column_name.
						'%s', // object_title.
						'%d', // lat.
						'%d', // lng.
					)
				);
			}
		}
		return $return;
	}

	public function get_index_column_value( $args = array(), $key = '', $data = array() ) {
		$return = array();
		$object_name = implode( ' ', array_keys( $data ) );
		$args['object_title'] = $object_name;
		$return = $this->set_index_column_value( $args, $key, $data );
		return $return;
	}

	public function set_index_column_value( $args = array(), $key = '', $values = array() ) {
		$columns_values = array(
			'object_id' => $args['object_id'],
			'object_type' => $args['object_type'],
			'term' => '',
			'term_reverse' => '',
			'title' => 0,
			'content' => 0,
			'excerpt' => 0,
			'author' => 0,
			'comment' => 0,
			'category' => 0,
			'tag' => 0,
			'taxonomy' => 0,
			'custom_field' => 0,
			'column_name' => ( isset( $args['column_name'] ) && '' !== $args['column_name'] ) ? $args['column_name'] : '',
			'object_title' => $args['object_title'],
			'lat' => 0,
			'lng' => 0,
		);
		$return = array();
		foreach ( $values as $word => $word_count ) {
			foreach ( array( 'title', 'content', 'excerpt', 'author', 'comment', 'category', 'tag', 'taxonomy', 'custom_field' ) as $k ) {
				$columns_values[ $k ] = 0;
			}
			if ( array_key_exists( $key, $columns_values ) ) {
				$columns_values[ $key ] = $word_count;
			}
			$columns_values['term'] = $word;
			$columns_values['term_reverse'] = "REVERSE('{$word}')";
			$this->index_columns_values[ $args['object_id'] ][] = $columns_values;
			$return[] = $columns_values;
		}
		return $return;
	}

	public function insert( $table, $data, $format = null ) {
		return $this->insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

	public function insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		global $wpdb;
		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) ) {
			return false;
		}
		$data = $this->process_fields( $table, $data, $format );
		if ( false === $data ) {
			return false;
		}

		$formats = array();
		$values = array();
		$remove_key = array();
		foreach ( $data as $key => $value ) {
			if ( is_null( $value['value'] ) ) {
				$formats[ $key ] = 'NULL';
				continue;
			}
			if ( '%mysql_function' == $value['format'] ) {
				$formats[ $key ] = $value['value'];
				$remove_key[]  = $key;
			} else {
				$formats[ $key ] = $value['format'];
			}
			$values[ $key ]  = $value['value'];
		}
		if ( ! empty( $remove_key ) ) {
			foreach ( $remove_key as $rm_key ) {
				if ( array_key_exists( $rm_key, $values ) ) {
					unset( $values[ $rm_key ] );
				}
			}
		}
		$fields  = '`' . implode( '`, `', array_keys( $data ) ) . '`';
		$formats = implode( ', ', $formats );
		$sql = "$type INTO `$table` ($fields) VALUES ($formats)";
		return $wpdb->query( $wpdb->prepare( $sql, $values ) );
	}

	protected function process_fields( $table, $data, $format ) {
		$data = $this->process_field_formats( $data, $format );
		if ( false === $data ) {
			return false;
		}
		$data = $this->process_field_charsets( $data, $table );
		if ( false === $data ) {
			return false;
		}
		$data = $this->process_field_lengths( $data, $table );
		if ( false === $data ) {
			return false;
		}
		return $data;
	}

	protected function process_field_formats( $data, $format ) {
		$formats = $original_formats = (array) $format;
		foreach ( $data as $field => $value ) {
			$value = array(
				'value'  => $value,
				'format' => '%s',
			);
			if ( ! empty( $format ) ) {
				$value['format'] = array_shift( $formats );
				if ( ! $value['format'] ) {
					$value['format'] = reset( $original_formats );
				}
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$value['format'] = $this->field_types[ $field ];
			}
			$data[ $field ] = $value;
		}
		return $data;
	}

	protected function process_field_charsets( $data, $table ) {
		global $wpdb;
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				$value['charset'] = false;
			} else {
				$value['charset'] = $wpdb->get_col_charset( $table, $field );
				if ( is_wp_error( $value['charset'] ) ) {
					return false;
				}
			}
			$data[ $field ] = $value;
		}
		return $data;
	}

	protected function process_field_lengths( $data, $table ) {
		global $wpdb;
		foreach ( $data as $field => $value ) {
			if ( '%d' === $value['format'] || '%f' === $value['format'] ) {
				/*
				 * We can skip this field if we know it isn't a string.
				 * This checks %d/%f versus ! %s because its sprintf() could take more.
				 */
				$value['length'] = false;
			} else {
				$value['length'] = $wpdb->get_col_length( $table, $field );
				if ( is_wp_error( $value['length'] ) ) {
					return false;
				}
			}
			$data[ $field ] = $value;
		}
		return $data;
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

