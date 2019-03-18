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
	 * User setting: false - don't index user, true - index user
	 *
	 * @var mixed boolean or array
	 */
	protected $user;

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
	 * Attachment content setting: false - ignore attachment, true - index attachment file content
	 *
	 * @var boolean
	 */
	protected $attachment_content;

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

	protected static $_instance = null;

	public static function instance( $args ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $args );
		}
		return self::$_instance;
	}
	/**
	 * Constructor method
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		global $wpdb;
		$this->table_indexing_name = press_search_get_var( 'tbl_index' );
		$this->table_logging_name  = press_search_get_var( 'tbl_logs' );

		$this->init_settings( $args );

		if ( ! function_exists( 'get_userdata' ) ) {
			require_once ABSPATH . 'wp-includes/pluggable.php';
		}

		add_action( 'init', array( $this, 'init_action' ) );
	}

	public function init_action() {
		$this->clear_object_indexing();
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
			'category'              => true,
			'tag'                   => true,
			'custom_tax'            => false,
			'comment'               => false,
			'post_type'             => false,
			'post_author'           => true,
			'post_excerpt'          => true,
			'expand_shortcodes'     => false,
			'user'                  => true,
			'user_meta'             => true,
			'attachment_content'    => false,
		);
		if ( isset( $args['settings'] ) && is_array( $args['settings'] ) ) {
			$settings = wp_parse_args( $args['settings'], $settings );
		}
		foreach ( $settings as $key => $value ) {
			$value = apply_filters( 'press_search_init_crawl_data_settings', $value, $key, $settings );
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
				$cat_name = array();
				foreach ( $get_post['post_category'] as $cat ) {
					if ( '' !== get_cat_name( $cat ) ) {
						$cat_name[] = get_cat_name( $cat );
					}
				}
				$return_data['category'] = implode( ' ', $cat_name );
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
				$return_data['tag'] = implode( ' ', $get_post['tags_input'] );
			}

			$custom_field_data = $this->get_post_custom( $post_id );
			if ( ! empty( $custom_field_data ) ) {
				$return_data['custom_field'] = $this->array_el_to_str( $custom_field_data );
			}

			$custom_tax = $this->get_post_tax( $post_id );
			if ( ! empty( $custom_tax ) ) {
				$return_data['taxonomy'] = $this->array_el_to_str( $custom_tax );
			}

			$comments = $this->get_post_comment( $post_id );
			if ( ! empty( $comments ) ) {
				$return_data['comment'] = $this->array_el_to_str( $comments );
			}
		}
		return $return_data;
	}

	/**
	 * Loop throught array to make an string
	 *
	 * @param array $array
	 * @return string
	 */
	public function array_el_to_str( $array ) {
		$arr = $this->recursive_array( $array );
		return implode( ' ', $arr );
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
			$custom_fields = get_post_custom( $post_id );
			if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
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
				$return[ $key ] = press_search_string()->count_words_from_str( $data );
			}
		}
		return $return;
	}

	/**
	 * Get object: post, term, user... key
	 *
	 * @param array $object_count
	 * @return array
	 */
	function get_object_data_count_key( $object_count ) {
		$return = array();
		foreach ( $object_count as $val ) {
			if ( is_array( $val ) && ! empty( $val ) ) {
				$words = array_keys( $val );
				if ( is_array( $words ) && ! empty( $words ) ) {
					$return = array_merge( $return, $words );
				}
			}
		}
		return $return;
	}

	/**
	 * Count a keyword appear time in each column
	 *
	 * @param string  $object_type
	 * @param integer $object_id
	 * @return array
	 */
	public function object_data_count_by_keyword( $object_type = 'post', $object_id = 0 ) {
		$return = array();
		switch ( $object_type ) {
			case 'post':
				$object_data_count = $this->get_post_data_count( $object_id );
				break;
			case 'user':
				$object_data_count = $this->get_user_data_count( $object_id );
				break;
			case 'term':
				$object_data_count = $this->get_term_data_count( $object_id );
				break;
			case 'attachment':
				$object_data_count = $this->get_attachment_content_count( $object_id );
				break;
		}
		$keywords = $this->get_object_data_count_key( $object_data_count );
		foreach ( $keywords as $keyword ) {
			$keys = array();
			foreach ( $object_data_count as $key => $values ) {
				if ( is_array( $values ) && ! empty( $values ) ) {
					$word_count = 0;
					if ( array_key_exists( $keyword, $values ) ) {
						$word_count += $values[ $keyword ];
					}
					$keys[ $key ] = $word_count;
				}
			}
			$return[ $keyword ] = $keys;
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
			$return_data['title'] = $user_info->display_name;
			$user_metas = get_user_meta( $user_id );
			if ( isset( $user_metas['description'] ) && isset( $user_metas['description'][0] ) && '' !== $user_metas['description'][0] ) {
				$return_data['content'] = $user_metas['description'][0];
			}
			$author_meta = $this->get_all_user_meta( $user_id );
			if ( ! empty( $author_meta ) ) {
				$return_data['custom_field'] = implode( ' ', $author_meta );
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
				$return[ $key ] = press_search_string()->count_words_from_str( $data );
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
				$return = $this->recursive_array( $metas );
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
				'title' => $term_info->name,
			);

			if ( isset( $term_info->description ) && ! empty( $term_info->description ) ) {
				$return_data['content'] = $term_info->description;
			}

			// Term meta.
			$term_meta = get_term_meta( $term_id );
			$term_meta_data = $this->get_meta_data( $term_meta );
			if ( ! empty( $term_meta_data ) ) {
				$return_data['custom_field'] = $this->array_el_to_str( $term_meta_data );
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
				$return[ $key ] = press_search_string()->count_words_from_str( $data );
			}
		}
		return $return;
	}

	/**
	 * Get term info
	 *
	 * @param integer $term_id
	 * @return mixed boolean or array
	 */
	public function get_term_info( $term_id ) {
		global $wpdb;
		$return = false;
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.term_id = %d", $term_id ) );
		if ( isset( $terms[0] ) && is_object( $terms[0] ) ) {
			$return = $terms[0];
		}
		return $return;
	}

	/**
	 * Delete indexed object
	 *
	 * @param string  $object_type
	 * @param integer $object_id
	 * @param string  $args
	 * @return boolean
	 */
	public function delete_indexed_object( $object_type = 'post', $object_id = 0, $args = '' ) {
		global $wpdb;
		$where = array(
			'object_id' => $object_id,
		);
		$where_format = array( '%d', '%s' );
		$return = false;
		switch ( $object_type ) {
			case 'post':
				$post_type = get_post_type( $object_id );
				$where['object_type'] = 'post_' . $post_type;
				$return = $wpdb->delete( $this->table_indexing_name, $where, $where_format );
				break;
			case 'term':
				$taxonomy = '';
				if ( isset( $args['taxonomy'] ) && '' !== $args['taxonomy'] ) {
					$taxonomy = $args['taxonomy'];
				} else {
					$term_info = $this->get_term_info( $object_id );
					if ( isset( $term_info ) && isset( $term_info->taxonomy ) ) {
						$taxonomy = $term_info->taxonomy;
					}
				}
				$where['object_type'] = 'tax_' . $taxonomy;
				$return = $wpdb->delete( $this->table_indexing_name, $where, $where_format );
				break;
			case 'user':
				$where['object_type'] = 'user';
				$return = $wpdb->delete( $this->table_indexing_name, $where, $where_format );
				break;
			case 'attachment':
				$where['object_type'] = 'post_attachment';
				$return = $wpdb->delete( $this->table_indexing_name, $where, $where_format );
				break;
		}
		return $return;
	}

	/**
	 * Get indexing columns value format and default value
	 *
	 * @return array
	 */
	public function indexing_colum_val_format() {
		$table_structure = $this->get_indexing_table_structure();
		$return = array();
		$default_args = array();
		$args_format = array();
		if ( is_array( $table_structure ) && ! empty( $table_structure ) ) {
			foreach ( $table_structure as $struct ) {
				if ( isset( $struct->Field ) ) { // @codingStandardsIgnoreLine
					$field_name = $struct->Field; // @codingStandardsIgnoreLine
					$field_type = $struct->Type; // @codingStandardsIgnoreLine
					if ( ( strpos( $field_type, 'text' ) !== false ) || ( strpos( $field_type, 'varchar' ) !== false ) ) {
						$default_args[ $field_name ] = '';
						$args_format[ $field_name ] = '%s';
					} else {
						$default_args[ $field_name ] = 0;
						$args_format[ $field_name ] = '%d';
					}
				}
			}
			if ( isset( $args_format['term_reverse'] ) ) {
				$args_format['term_reverse'] = '%mysql_function';
			}
			$return = array(
				'default_args' => $default_args,
				'args_format' => $args_format,
			);
		}
		return $return;
	}

	/**
	 * Get table index structure
	 *
	 * @return mixed
	 */
	public function get_indexing_table_structure() {
		global $wpdb;
		$query = $wpdb->get_results( 'DESCRIBE ' . $this->table_indexing_name ); // WPCS: unprepared SQL OK.
		return $query;
	}

	/**
	 * Insert post data to indexing table
	 *
	 * @param string  $object_type
	 * @param integer $object_id
	 * @return boolean if all data inserted return true else return false
	 */
	public function insert_indexing_object( $object_type = 'post', $object_id = 0 ) {
		global $wpdb;
		$origin_object_type = $object_type;
		$return = false;
		$data_by_keys = $this->object_data_count_by_keyword( $object_type, $object_id );
		$columns_values = array();
		switch ( $object_type ) {
			case 'post':
				$object_type = 'post_' . get_post_type( $object_id );
				break;
			case 'user':
				$object_type = 'user';
				break;
			case 'term':
				$taxonomy = '';
				$term_info = $this->get_term_info( $object_id );
				if ( isset( $term_info->taxonomy ) ) {
					$taxonomy = $term_info->taxonomy;
				}
				$object_type = 'tax_' . $taxonomy;
				break;
			case 'attachment':
				$object_type = 'post_attachment';
				break;
		}
		if ( ! empty( $data_by_keys ) ) {
			$colmns_fomat = $this->indexing_colum_val_format();
			$default_args = array();
			if ( isset( $colmns_fomat['default_args'] ) && ! empty( $colmns_fomat['default_args'] ) ) {
				$default_args = $colmns_fomat['default_args'];
			}
			foreach ( $data_by_keys as $word => $count ) {
				$args = array();
				if ( array_key_exists( 'object_id', $default_args ) ) {
					$args['object_id'] = $object_id;
				}
				if ( array_key_exists( 'object_type', $default_args ) ) {
					$args['object_type'] = $object_type;
				}
				if ( array_key_exists( 'term', $default_args ) ) {
					$args['term'] = $word;
				}
				if ( array_key_exists( 'term_reverse', $default_args ) ) {
					$args['term_reverse'] = "REVERSE('{$word}')";
				}
				$columns_values[] = wp_parse_args( $args, wp_parse_args( $count, $default_args ) );
			}
			$this->delete_indexed_object( $origin_object_type, $object_id );
			$return = $this->do_insert_indexing( $columns_values );
		}

		return $return;
	}

	/**
	 * Check is term exists
	 *
	 * @param integer $term_id
	 * @return bool
	 */
	public function term_exists( $term_id ) {
		global $wpdb;

		$select = "SELECT term_id FROM $wpdb->terms as t WHERE ";
		$where  = 't.term_id = %d';
		return $wpdb->get_var( $wpdb->prepare( $select . $where, $term_id ) ); // WPCS: unprepared SQL OK.
	}

	/**
	 * Insert data to indexing table
	 *
	 * @param array $columns_values
	 * @return boolean true if all data inserted, false if have lease one data did not insert
	 */
	public function do_insert_indexing( $columns_values ) {
		$return = false;
		$colmns_fomat = $this->indexing_colum_val_format();
		$args_format = array();
		if ( isset( $colmns_fomat['args_format'] ) && ! empty( $colmns_fomat['args_format'] ) ) {
			$args_format = $colmns_fomat['args_format'];
		}

		if ( ! empty( $columns_values ) ) {
			foreach ( $columns_values as $val ) {
				$is_duplicate = $this->is_indexing_row_exists( $val );
				if ( ! $is_duplicate ) {
					$return = $this->insert( $this->table_indexing_name, $val, $args_format );
				}
			}
		}
		return $return;
	}

	/**
	 * Check is a row exist in table indexing
	 *
	 * @param array $val
	 * @return boolean
	 */
	public function is_indexing_row_exists( $val ) {
		global $wpdb;
		$return = false;
		$args = array();
		$where = array();
		$where_condition = '';
		if ( array_key_exists( 'object_id', $val ) ) {
			$args['object_id'] = $val['object_id'];
			$where[] = 'object_id = %d';
		}
		if ( array_key_exists( 'object_type', $val ) ) {
			$args['object_type'] = $val['object_type'];
			$where[] = 'object_type = %s';
		}
		if ( array_key_exists( 'term', $val ) ) {
			$args['term'] = $val['term'];
			$where[] = 'term = %s';
		}
		if ( ! empty( $where ) ) {
			$where_condition = 'WHERE ' . implode( ' AND ', $where );
		}
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_indexing_name} {$where_condition}", $args ) ); // @codingStandardsIgnoreLine
		if ( is_object( $result ) && ! is_wp_error( $result ) ) {
			$return = true;
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
	 * @param string  $type
	 * @param boolean $sort
	 * @param integer $limit_number
	 * @return array
	 */
	public function get_can_index_user_ids( $type = 'un_indexed', $sort = true, $limit_number = 15 ) {
		global $wpdb;
		$return = array();
		$user_author_ids = array();
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_status = %s", array( 'publish' ) ), ARRAY_N );
		if ( $this->user && is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result[0] ) ) {
					if ( get_userdata( $result[0] ) ) {
						$user_author_ids[] = $result[0];
					}
				}
			}
		}

		$user_args = array(
			'fields'    => 'ids',
			'number'    => $limit_number,
			'who' => 'authors',
		);

		if ( 'un_indexed' == $type ) {
			$user_args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'ps_indexed',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'ps_indexed',
					'value'   => 'yes',
					'compare' => '!=',
				),
			);
		} else {
			$user_args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => 'ps_indexed',
					'value'   => 'yes',
					'compare' => '=',
				),
			);
		}

		if ( ! empty( $user_author_ids ) ) {
			$user_args['include'] = $user_author_ids;
		}
		$get_user = get_users( $user_args );
		if ( ! empty( $get_user ) ) {
			$return = $get_user;
		}
		if ( $sort ) {
			sort( $return );
		}
		return $return;
	}

	/**
	 * Get all publish post ids support exclude ids from user settings.
	 *
	 * @param string  $type
	 * @param bool    $sort
	 * @param integer $limit_number
	 * @return array
	 */
	public function get_can_index_post_ids( $type = 'un_indexed', $sort = true, $limit_number = 15 ) {
		global $wpdb;
		$return = array();
		$allow_post_type = $this->post_type;
		if ( is_array( $allow_post_type ) && ! empty( $allow_post_type ) ) {
			$args = array(
				'posts_per_page'    => $limit_number,
				'post_status'       => 'publish',
				'post_type'         => $allow_post_type,
				'fields'            => 'ids',
				'orderby'           => 'date',
				'order'             => 'ASC',
			);
			if ( 'un_indexed' == $type ) {
				$args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'ps_indexed',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'ps_indexed',
						'value'   => 'yes',
						'compare' => '!=',
					),
				);
			} else {
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'ps_indexed',
						'value'   => 'yes',
						'compare' => '=',
					),
				);
			}
			$query = new WP_Query( apply_filters( 'press_search_query_args_get_can_index_post_ids', $args ) );
			if ( isset( $query->posts ) && is_array( $query->posts ) && ! empty( $query->posts ) ) {
				$return = $query->posts;
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
	 * @param string  $type
	 * @param bool    $sort
	 * @param integer $limit_number
	 * @return array
	 */
	public function get_can_index_term_ids( $type = 'un_indexed', $sort = true, $limit_number = 15 ) {
		$return = array();
		$custom_tax = ( is_array( $this->custom_tax ) ) ? $this->custom_tax : array();
		if ( $this->category ) {
			$custom_tax[] = 'category';
		}
		if ( $this->tag ) {
			$custom_tax[] = 'post_tag';
		}
		if ( is_array( $custom_tax ) && ! empty( $custom_tax ) ) {
			$term_args = array(
				'taxonomy'   => $custom_tax,
				'hide_empty' => false,
				'number'     => $limit_number,
				'fields' => 'ids',
			);
			if ( 'un_indexed' == $type ) {
				$term_args['meta_query'] = array(
					'relation' => 'OR',
					array(
						'key'     => 'ps_indexed',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'ps_indexed',
						'value'   => 'yes',
						'compare' => '!=',
					),
				);
			} else {
				$term_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'ps_indexed',
						'value'   => 'yes',
						'compare' => '=',
					),
				);
			}
			$taxonomies = get_terms( $term_args );
			if ( is_array( $taxonomies ) && ! empty( $taxonomies ) ) {
				$return = $taxonomies;
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
	 * @param string  $type
	 * @param boolean $sort
	 * @param integer $limit_number
	 * @return array
	 */
	public function get_can_index_attachment_ids( $type = 'un_indexed', $sort = true, $limit_number = 15 ) {
		$return = array();
		$args = array(
			'post_type'         => 'attachment',
			'posts_per_page'    => $limit_number,
			'fields'            => 'ids',
			'orderby'           => 'date',
			'order'             => 'ASC',
		);
		if ( 'un_indexed' == $type ) {
			$args['meta_query'] = array(
				'relation'      => 'OR',
				array(
					'key'     => 'ps_indexed',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'ps_indexed',
					'value'   => 'yes',
					'compare' => '!=',
				),
			);
		} else {
			$args['meta_query'] = array(
				'relation'      => 'AND',
				array(
					'key'     => 'ps_indexed',
					'value'   => 'yes',
					'compare' => '=',
				),
			);
		}
		$readable_mime_type = apply_filters(
			'press_search_get_readble_mime_type',
			array(
				'text/xml',
				'text/plain',
				'application/plain',
				'text/csv',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/xml',
				'application/pdf',
				'application/mspowerpoint',
				'application/powerpoint',
				'application/x-mspowerpoint',
				'application/vnd.ms-powerpoint',
				'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'application/vnd.ms-excel',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			)
		);
		$attachments = get_posts( $args );
		if ( $attachments && $this->attachment_content ) {
			foreach ( $attachments as $attachment ) {
				if ( in_array( $attachment->post_mime_type, $readable_mime_type ) ) {
					$return[] = $attachment->ID;
				}
			}
		}
		if ( $sort ) {
			sort( $return );
		}
		return $return;
	}

	/**
	 * Get attachment content count
	 *
	 * @param integer $attachment_id
	 * @return array
	 */
	public function get_attachment_content_count( $attachment_id ) {
		$content_count = array();
		$file_path = get_attached_file( $attachment_id );

		$readable_files = $this->get_readable_attachments();
		if ( '' !== $file_path ) {
			$file_title = get_the_title( $attachment_id );
			$content_count['title'] = press_search_string()->count_words_from_str( $file_title );

			$file_content = strip_tags( file_get_contents( $file_path ) );
			$content_no_url = press_search_string()->remove_urls( $file_content );
			if ( ! empty( $content_no_url ) ) {
				$content_count['content'] = press_search_string()->count_words_from_str( $content_no_url );
			}
		}
		return $content_count;
	}

	public function get_object_index_count() {
		$return = array(
			'post' => array(
				'indexed'       => count( $this->get_can_index_post_ids( 'indexed', true, -1 ) ),
				'un_indexed'    => count( $this->get_can_index_post_ids( 'un_indexed', true, -1 ) ),
			),
			'term' => array(
				'indexed'       => count( $this->get_can_index_term_ids( 'indexed', true, PHP_INT_MAX ) ),
				'un_indexed'    => count( $this->get_can_index_term_ids( 'un_indexed', true, PHP_INT_MAX ) ),
			),
			'user' => array(
				'indexed'       => count( $this->get_can_index_user_ids( 'indexed', true, PHP_INT_MAX ) ),
				'un_indexed'    => count( $this->get_can_index_user_ids( 'un_indexed', true, PHP_INT_MAX ) ),
			),
			'attachment' => array(
				'indexed'       => count( $this->get_can_index_attachment_ids( 'indexed', true, -1 ) ),
				'un_indexed'    => count( $this->get_can_index_attachment_ids( 'un_indexed', true, -1 ) ),
			),
		);
		return $return;
	}

	public function clear_object_indexing() {
		if ( isset( $_REQUEST['object_indexing_action'] ) && wp_unslash( $_REQUEST['object_indexing_action'] ) == 'clear_indexing' ) {
			global $wpdb;
			delete_metadata( 'post', null, 'ps_indexed', '', true );
			delete_metadata( 'user', null, 'ps_indexed', '', true );
			delete_metadata( 'term', null, 'ps_indexed', '', true );

			$option_prefix = press_search_get_var( 'db_option_key' );
			delete_option( $option_prefix . 'index_count' );
			delete_option( $option_prefix . 'last_time_index' );

			$table_indexing = press_search_get_var( 'tbl_index' );

			$wpdb->query( "DELETE FROM {$table_indexing}" ); // @codingStandardsIgnoreLine .
		}
	}
}

function press_search_crawl_data() {
	$index_settings = press_search_engines()->__get( 'index_settings' );
	return Press_Search_Crawl_Data::instance( array( 'settings' => $index_settings ) );
}

