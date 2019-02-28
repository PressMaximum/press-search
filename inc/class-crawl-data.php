<?php

class Press_Search_Crawl_Data {
	/**
	 * Custom fields setting: fasle - don't index custom field, true - index custom field, array - list custom field keys will be index
	 *
	 * @var mixed boolean or array
	 */
	protected $custom_field;
	/**
	 * Category setting: false - don't index category, true - index category
	 *
	 * @var boolean
	 */
	protected $category;
	/**
	 * Tag setting: false - don't index tag, true - index tag
	 *
	 * @var boolean
	 */
	protected $tag;
	/**
	 * Custom taxonomy setting: false - don't index custom tax, true - index custom tax, array - list custom tax with meta keys will be index
	 *
	 * @var mixed boolean or array
	 */
	protected $custom_tax;
	/**
	 * Comment setting: fasle - don't index comment, true - index comment
	 *
	 * @var boolean
	 */
	protected $comment;
	/**
	 * User meta setting: false - don't index user meta, true - index user meta, array - list user meta keys will be index
	 *
	 * @var mixed boolean or array
	 */
	protected $user_meta;

	/**
	 * Post type setting: false - don't index any post, array - index post in list post types
	 *
	 * @var mixed boolean or array
	 */
	protected $post_type;
	/**
	 * Post author setting: false - don't index, true - index
	 *
	 * @var boolean
	 */
	protected $post_author;
	/**
	 * Post excerpt setting: false - don't index, true - index
	 *
	 * @var boolean
	 */
	protected $post_excerpt;
	/**
	 * Post shortcode setting: false - ignore shortcode, true - do this shortcode to get content for indexing
	 *
	 * @var boolean
	 */
	protected $expand_shortcodes;

	/**
	 * Database table indexing name
	 *
	 * @var string
	 */
	protected $table_indexing_name;
	/**
	 * Database table search log name
	 *
	 * @var string
	 */
	protected $table_logging_name;
	/**
	 * Store array value can insert to table
	 *
	 * @var string
	 */
	protected $index_columns_values = array();

	/**
	 * Constructor method
	 *
	 * @param array $args
	 */
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
				// code here.
			}
		);

		add_action( 'wp_ajax_index_data_ajax', array( $this, 'index_data_ajax' ) );
	}

	/**
	 * Init settings
	 *
	 * @param mixed $args
	 * @return void
	 */
	public function init_settings( $args = null ) {
		$settings = array(
			'custom_field'          => false,
			'category'              => false,
			'tag'                   => false,
			'custom_tax'            => false,
			'comment'               => false,
			'post_type'             => false,
			'post_author'           => false,
			'post_excerpt'          => false,
			'expand_shortcodes'     => false,
			'user_meta'             => false,
		);
		if ( isset( $args['settings'] ) && is_array( $args['settings'] ) ) {
			$settings = wp_parse_args( $args['settings'], $settings );
		}
		foreach ( $settings as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Get all custom taxonomy of a post
	 *
	 * @param integer $post_id
	 * @return array
	 */
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

	/**
	 * Get post term name
	 *
	 * @param integer $post_id
	 * @param string  $term_slug
	 * @return array
	 */
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
	/**
	 * Get post term info
	 *
	 * @param integer $post_id
	 * @param string  $term_slug
	 * @return array
	 */
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
			);
			if ( ! empty( $get_post['post_category'] ) && $this->category ) {
				foreach ( $get_post['post_category'] as $cat ) {
					if ( '' !== get_cat_name( $cat ) ) {
						$return_data['category'][ $cat ] = get_cat_name( $cat );
					}
				}
			}

			if ( $this->post_author ) {
				$return_data['author'] = get_the_author_meta( 'display_name', $get_post['post_author'] );
			}

			if ( $this->post_excerpt ) {
				$return_data['excerpt'] = $get_post['post_excerpt'];
			}

			if ( $this->expand_shortcodes ) {
				// Expand shortcodes for indexing.
				$return_data['content'] = apply_filters( 'the_content', $get_post['post_content'] );
			} else {
				$return_data['content'] = strip_shortcodes( $get_post['post_content'] );
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
		}
		return $return_data;
	}

	/**
	 * Get post custom fields
	 *
	 * @param integer $post_id
	 * @return array
	 */
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

	/**
	 * Get all post comments
	 *
	 * @param integer $post_id
	 * @param string  $comment_status
	 * @return array
	 */
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

	/**
	 * Get all post taxonomy
	 *
	 * @param integer $post_id
	 * @return array
	 */
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

	/**
	 * Recursive array to get all nested value.
	 *
	 * @param array $array
	 * @return array
	 */
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

	/**
	 * Get post data count
	 *
	 * @param integer $post_id
	 * @param array   $post_data
	 * @return array
	 */
	public function get_post_data_count( $post_id = 0, $post_data = array() ) {
		$return = array();
		if ( empty( $post_data ) ) {
			$post_data = $this->get_post_data( $post_id );
		}
		if ( is_array( $post_data ) && ! empty( $post_data ) ) {
			foreach ( $post_data as $key => $data ) {
				if ( 'ID' == $key ) {
					continue;
				}
				if ( 'tag' == $key || 'category' == $key ) {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $term_data ) {
							$arr_key = press_search_string()->replace_str_spaces( $term_data, '||', false );
							$return[ $key ][ $arr_key ] = press_search_string()->count_words_from_str( $term_data );
						}
					}
				} elseif ( 'custom_field' == $key ) {
					$count_custom_field = $this->get_meta_data_count( $data );
					if ( ! empty( $count_custom_field ) ) {
						$return[ $key ] = $count_custom_field;
					}
				} elseif ( 'taxonomy' == $key ) {
					if ( is_array( $data ) && ! empty( $data ) ) {
						foreach ( $data as $term_slug => $term_datas ) {
							if ( is_array( $term_datas ) && ! empty( $term_datas ) ) {
								foreach ( $term_datas as $term_name ) {
									$term_item_key = press_search_string()->replace_str_spaces( $term_name, '||', false );
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
	/**
	 * Get user data
	 *
	 * @param integer $user_id
	 * @return array
	 */
	public function get_user_data( $user_id = 0 ) {
		$return_data = array();
		$user_info = get_userdata( $user_id );
		if ( $user_info ) {
			$return_data['display_name'] = $user_info->display_name;
			$user_metas = get_user_meta( $user_id );
			if ( isset( $user_metas['description'] ) && isset( $user_metas['description'][0] ) && '' !== $user_metas['description'][0] ) {
				$return_data['description'] = $user_metas['description'][0];
			}
			$author_meta = $this->get_all_user_meta( $user_id );
			if ( ! empty( $author_meta ) ) {
				$return_data['user_meta'] = $author_meta;
			}
		}
		return $return_data;
	}

	/**
	 * Get user data count
	 *
	 * @param integer $user_id
	 * @param array   $user_data
	 * @return array
	 */
	public function get_user_data_count( $user_id = 0, $user_data = array() ) {
		if ( empty( $user_data ) ) {
			$user_data = $this->get_user_data( $user_id );
		}
		$return = array();

		if ( is_array( $user_data ) && ! empty( $user_data ) ) {
			foreach ( $user_data as $key => $data ) {
				if ( 'user_meta' !== $key ) {
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

	/**
	 * Get all user meta
	 *
	 * @param integer $user_id
	 * @param array   $user_metas
	 * @return array
	 */
	public function get_all_user_meta( $user_id = 0, $user_metas = array() ) {
		$return = array();
		if ( $this->user_meta ) {
			if ( empty( $user_metas ) ) {
				$user_metas = get_user_meta( $user_id );
			}
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

	/**
	 * Check if a string is a valid url
	 *
	 * @param string $string
	 * @return boolean
	 */
	public function is_valid_url( $string = '' ) {
		if ( is_string( $string ) && filter_var( $string, FILTER_VALIDATE_URL ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Loop to meta data and recursive serialized data
	 *
	 * @param array $data
	 * @return array
	 */
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

	/**
	 * Get meta data count
	 *
	 * @param array $data
	 * @return array
	 */
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

	/**
	 * Get term data count
	 *
	 * @param integer $term_id
	 * @param array   $term_data
	 * @return array
	 */
	public function get_term_data_count( $term_id = 0, $term_data = array() ) {
		if ( empty( $term_data ) ) {
			$term_data = $this->get_term_data( $term_id );
		}
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

	/**
	 * Index data to db via ajax request
	 *
	 * @return void
	 */
	public function index_data_ajax() {
		// http://startedplugin.local/wp-admin/admin-ajax.php?action=index_data_ajax&data_type=post|term|user&ids=1,2,3,4 .
		// http://startedplugin.local/wp-admin/admin-ajax.php?action=index_data_ajax&data_type=post&ids=1,29 .
		// http://startedplugin.local/wp-admin/admin-ajax.php?action=index_data_ajax&data_type=term&ids=22,23 .
		// http://startedplugin.local/wp-admin/admin-ajax.php?action=index_data_ajax&data_type=user&ids=1,2,3 .
		$data_type = ( isset( $_REQUEST['data_type'] ) && in_array( $_REQUEST['data_type'], array( 'post', 'term', 'user' ), true ) ) ? $_REQUEST['data_type'] : 'post';
		$data_ids = ( isset( $_REQUEST['ids'] ) && '' !== $_REQUEST['ids'] ) ? $_REQUEST['ids'] : '';
		if ( '' == $data_ids ) {
			$this->send_json_error();
		}
		$ids = array_unique( array_filter( explode( ',', $data_ids ), 'absint' ) );
		if ( empty( $ids ) ) {
			$this->send_json_error();
		}

		$result = false;
		if ( 'post' == $data_type ) { // Index for posts.
			foreach ( $ids as $post_id ) {
				if ( is_string( get_post_status( $post_id ) ) ) {
					$result = $this->insert_indexing_post( $post_id );
				}
			}
		} elseif ( 'term' == $data_type ) { // Index for terms.
			foreach ( $ids as $term_id ) {
				if ( $this->term_exists( $term_id ) ) {
					$result = $this->insert_indexing_term( $term_id );
				}
			}
		} elseif ( 'user' == $data_type ) { // Index for users.
			foreach ( $ids as $user_id ) {
				if ( get_userdata( $user_id ) ) {
					$result = $this->insert_indexing_user( $user_id );
				}
			}
		}
		if ( $result ) {
			$this->send_json_success( array( 'message' => esc_html__( 'Index success', 'press-search' ) ) );
		} else {
			$this->send_json_error();
		}
	}

	/**
	 * Send json error
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function send_json_error( $data = null ) {
		wp_send_json_error( $data );
		wp_die();
	}

	/**
	 * Send json success with data
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function send_json_success( $data = null ) {
		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Insert post data to indexing table
	 *
	 * @param integer $post_id
	 * @return boolean if all data inserted return true else return false
	 */
	public function insert_indexing_post( $post_id = 0 ) {
		global $wpdb;
		$post_data = $this->get_post_data( $post_id );
		$post_data_count = $this->get_post_data_count( $post_id, $post_data );
		$columns_values = array();
		$return = false;
		$post_type = get_post_type( $post_id );
		foreach ( $post_data_count as $key => $values ) {
			$args = array(
				'object_id' => $post_id,
				'object_type' => $key,
			);
			if ( in_array( $key, array( 'title', 'content', 'excerpt' ) ) ) {
				if ( 'post' == $post_type || 'page' == $post_type ) {
					$args['object_type'] = $post_type;
				} else {
					$args['object_type'] = 'post_type|' . $post_type;
				}
			} elseif ( in_array( $key, array( 'category', 'taxonomy' ) ) ) {
				$args['object_type'] = 'post_' . $key;
			} elseif ( 'tag' == $key ) {
				$args['object_type'] = 'post_post_tag';
			}

			if ( ! empty( $values ) ) {
				switch ( $key ) {
					case 'title':
					case 'content':
					case 'excerpt':
					case 'author':
						if ( 'title' == $key ) {
							$args['object_title'] = $post_data['title'];
						} elseif ( 'author' == $key ) {
							$args['object_title'] = $post_data['author'];
						}
						$columns_values = array_merge( $this->get_index_column_value( $args, $key, $values ), $columns_values );
						break;
					case 'category':
					case 'tag':
					case 'comment':
						foreach ( $values as $column_key => $arr_data ) {
							if ( in_array( $key, array( 'category', 'tag' ), true ) ) {
								$args['object_title'] = str_replace( '||', ' ', $column_key );
							}
							$columns_values = array_merge( $this->get_index_column_value( $args, $key, $arr_data ), $columns_values );
						}
						break;
					case 'custom_field':
					case 'taxonomy':
						foreach ( $values as $column_key => $arr_data ) {
							$args['column_name'] = $column_key;
							if ( count( $arr_data ) == count( $arr_data, COUNT_RECURSIVE ) ) {
								$columns_values = array_merge( $this->get_index_column_value( $args, $key, $arr_data ), $columns_values );
							} else {
								foreach ( $arr_data as $k => $child_data ) {
									$args['column_name'] = $column_key . '|' . $k;
									if ( 'taxonomy' == $key ) {
										$args['object_title'] = str_replace( '||', ' ', $k );
									}
									$columns_values = array_merge( $this->get_index_column_value( $args, $key, $child_data ), $columns_values );
								}
							}
						}
						break;
				}
			}
		}
		$return = $this->do_insert_indexing( $columns_values );
		return $return;
	}

	/**
	 * Check is term exists
	 *
	 * @param integer $term_id
	 * @return bool
	 */
	protected function term_exists( $term_id ) {
		global $wpdb;

		$select = "SELECT term_id FROM $wpdb->terms as t WHERE ";
		$where  = 't.term_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $select . $where, $term_id ) ); // WPCS: unprepared SQL OK.
	}

	/**
	 * Insert term data to table indexing
	 *
	 * @param integer $term_id
	 * @return boolean if all data inserted return true else return false
	 */
	public function insert_indexing_term( $term_id = 0 ) {
		if ( ! $this->term_exists( $term_id ) ) {
			return false;
		}
		$term_data = $this->get_term_data( $term_id );
		$term_data_count = $this->get_term_data_count( $term_id, $term_data );

		if ( empty( $term_data_count ) ) {
			return false;
		}
		$term_info = get_term( $term_id );
		$taxonomy = $term_info->taxonomy;
		$columns_values = array();
		$return = false;

		// Save term title to colum title, term description to column content.
		foreach ( $term_data_count as $key => $values ) {
			$args = array(
				'object_id' => $term_id,
				'object_type' => $taxonomy,
			);
			if ( ! in_array( $taxonomy, array( 'post_tag', 'category' ) ) ) {
				$args['object_type'] = "tax|{$taxonomy}";
			}

			if ( ! empty( $values ) ) {
				switch ( $key ) {
					case 'name':
					case 'description':
						if ( 'name' == $key ) {
							$main_key = 'title';
							$args['object_title'] = $term_data['name'];
						} else {
							$main_key = 'content';
						}
						$columns_values = array_merge( $this->get_index_column_value( $args, $main_key, $values ), $columns_values );
						break;
					case 'meta_data':
						$main_key = 'custom_field';
						foreach ( $values as $column_key => $arr_data ) {
							$args['column_name'] = $column_key;
							if ( count( $arr_data ) == count( $arr_data, COUNT_RECURSIVE ) ) {
								$columns_values = array_merge( $this->get_index_column_value( $args, $main_key, $arr_data ), $columns_values );
							} else {
								foreach ( $arr_data as $k => $child_data ) {
									$args['column_name'] = $column_key . '|' . $k;
									$columns_values = array_merge( $this->get_index_column_value( $args, $main_key, $child_data ), $columns_values );
								}
							}
						}
						break;
				}
			}
		}
		$return = $this->do_insert_indexing( $columns_values );
		return $return;
	}

	/**
	 * Insert user to indexing table
	 *
	 * @param integer $user_id
	 * @return bool
	 */
	public function insert_indexing_user( $user_id = 0 ) {
		if ( ! get_userdata( $user_id ) ) {
			return false;
		}
		$user_data = $this->get_user_data( $user_id );
		$user_data_count = $this->get_user_data_count( $user_id, $user_data );
		if ( empty( $user_data_count ) ) {
			return false;
		}
		$columns_values = array();
		$return = false;

		// Save term title to colum title, term description to column content.
		foreach ( $user_data_count as $key => $values ) {
			$args = array(
				'object_id' => $user_id,
				'object_type' => 'user',
			);
			if ( ! empty( $values ) ) {
				switch ( $key ) {
					case 'display_name':
					case 'description':
						if ( 'display_name' == $key ) {
							$main_key = 'title';
							$args['object_title'] = $user_data['display_name'];
						} else {
							$main_key = 'content';
						}
						$columns_values = array_merge( $this->get_index_column_value( $args, $main_key, $values ), $columns_values );
						break;
					case 'user_meta':
						$main_key = 'custom_field';
						foreach ( $values as $column_key => $arr_data ) {
							$args['column_name'] = $column_key;
							if ( count( $arr_data ) == count( $arr_data, COUNT_RECURSIVE ) ) {
								$columns_values = array_merge( $this->get_index_column_value( $args, $main_key, $arr_data ), $columns_values );
							} else {
								foreach ( $arr_data as $k => $child_data ) {
									$args['column_name'] = $column_key . '|' . $k;
									$columns_values = array_merge( $this->get_index_column_value( $args, $main_key, $child_data ), $columns_values );
								}
							}
						}
						break;
				}
			}
		}
		$return = $this->do_insert_indexing( $columns_values );
		return $return;
	}

	/**
	 * Insert data to indexing table
	 *
	 * @param [type] $columns_values
	 * @return boolean true if all data inserted, false if have lease one data did not insert
	 */
	public function do_insert_indexing( $columns_values ) {
		$return = false;
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

	/**
	 * Get index table columm value
	 *
	 * @param array  $args
	 * @param string $key
	 * @param array  $data
	 * @return array
	 */
	public function get_index_column_value( $args = array(), $key = '', $data = array() ) {
		$return = array();
		$return = $this->set_index_column_value( $args, $key, $data );
		return $return;
	}

	/**
	 * Prepare data for index table
	 *
	 * @param array  $args
	 * @param string $key
	 * @param array  $values
	 * @return array
	 */
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
			'object_title' => ( isset( $args['object_title'] ) && '' !== $args['object_title'] ) ? $args['object_title'] : '',
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

	/**
	 * DB insert.
	 *
	 * @param string $table
	 * @param array  $data
	 * @param mixed  $format
	 * @return boolean
	 */
	public function insert( $table, $data, $format = null ) {
		return $this->insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

	/**
	 * Create insert sql command
	 *
	 * @param string $table
	 * @param array  $data
	 * @param mixed  $format
	 * @param string $type
	 * @return boolean
	 */
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
		return $wpdb->query( $wpdb->prepare( $sql, $values ) ); // WPCS: unprepared SQL OK.
	}

	/**
	 * Process data field
	 *
	 * @param string $table
	 * @param array  $data
	 * @param mixed  $format
	 * @return array
	 */
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

	/**
	 * Process field formats
	 *
	 * @param array $data
	 * @param mixed $format
	 * @return array
	 */
	protected function process_field_formats( $data, $format ) {
		$formats = (array) $format;
		$original_formats = $formats;
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

	/**
	 * Process field charsets
	 *
	 * @param array  $data
	 * @param string $table
	 * @return array
	 */
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

	/**
	 * Process field lengths
	 *
	 * @param array  $data
	 * @param string $table
	 * @return array
	 */
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

	/**
	 * Get all user ids have publish post
	 *
	 * @return array
	 */
	public function get_user_has_posts() {
		global $wpdb;
		$user_ids = array();
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_status = %s", array( 'publish' ) ), ARRAY_N );
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result[0] ) ) {
					if ( get_userdata( $result[0] ) ) {
						$user_ids[] = $result[0];
					}
				}
			}
		}
		return $user_ids;
	}

	/**
	 * Get all publish post ids support exclude ids from user settings.
	 *
	 * @param string $post_status
	 * @param bool   $sort
	 * @return array
	 */
	public function get_all_post_ids( $post_status = 'publish', $sort = true ) {
		$return = array();
		$allow_post_type = $this->post_type;
		if ( is_array( $allow_post_type ) && ! empty( $allow_post_type ) ) {
			$args = array(
				'posts_per_page'    => -1,
				'post_status'       => 'publish',
				'post_type'         => $allow_post_type,
			);
			$query = new WP_Query( apply_filters( 'press_search_get_all_post_id_query_args', $args ) );
			if ( $query->have_posts() ) {
				$exclude_post_ids = press_search_get_setting( 'searching_post_exclusion', '' );
				$exclude_ids = array();
				if ( '' !== $exclude_post_ids ) {
					$exclude_ids = array_unique( array_filter( explode( ',', $exclude_post_ids ), 'absint' ) );
				}
				while ( $query->have_posts() ) {
					$query->the_post();
					if ( ! empty( $exclude_ids ) ) {
						if ( ! in_array( get_the_ID(), $exclude_ids ) ) {
							$return[] = get_the_ID();
						}
					} else {
						$return[] = get_the_ID();
					}
				}
			}
			wp_reset_postdata();
		}
		if ( $sort ) {
			sort( $return );
		}
		return $return;
	}
	/**
	 * Get all term ids support exclude ids from user settings.
	 *
	 * @param bool $sort
	 * @return array
	 */
	public function get_all_terms_id( $sort = true ) {
		$return = array();
		$exclude_term_ids = press_search_get_setting( 'searching_category_exclusion', '' );
		$exclude_ids = array();
		if ( '' !== $exclude_term_ids ) {
			$exclude_ids = array_unique( array_filter( explode( ',', $exclude_term_ids ), 'absint' ) );
		}
		$custom_tax = $this->custom_tax;
		if ( is_array( $custom_tax ) && ! empty( $custom_tax ) ) {
			if ( $this->category ) {
				$custom_tax[] = 'category';
			}
			if ( $this->tag ) {
				$custom_tax[] = 'post_tag';
			}
			$taxonomies = get_terms(
				array(
					'taxonomy'   => $custom_tax,
					'hide_empty' => false,
				)
			);
			foreach ( $taxonomies as $tax ) {
				if ( isset( $tax->term_id ) ) {
					if ( ! empty( $exclude_ids ) ) {
						if ( ! in_array( $tax->term_id, $exclude_ids ) ) {
							$return[] = $tax->term_id;
						}
					} else {
						$return[] = $tax->term_id;
					}
				}
			}
		}
		if ( $sort ) {
			sort( $return );
		}
		return $return;
	}

	/**
	 * Get list readable attachments files
	 *
	 * @return void
	 */
	public function get_content_readable_attachments() {
		$return = array();
		$args = array(
			'post_type' => 'attachment',
			'posts_per_page' => -1,
		);
		$readable_mime_type = apply_filters(
			'press_search_content_readble_mime_type',
			array(
				'text/xml',
			)
		);
		$attachments = get_posts( $args );
		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				if ( in_array( $attachment->post_mime_type, $readable_mime_type ) ) {
					$return[ $attachment->ID ] = array(
						'ID' => $attachment->ID,
						'url' => wp_get_attachment_url( $attachment->ID ),
						'path' => get_attached_file( $attachment->ID ),
					);
				}
			}
		}
	}
}

$press_search_index_settings = press_search_engines()->__get( 'index_settings' );

new Press_Search_Crawl_Data(
	array(
		'settings' => $press_search_index_settings,
	)
);

