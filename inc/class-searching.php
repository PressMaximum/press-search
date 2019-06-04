<?php
class Press_Search_Searching {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Indexing
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * Keyword enter by user
	 *
	 * @var mixed string or array
	 */
	protected $keywords;
	/**
	 * Does support excerpt contain keyword. Useful for the excerpt does not contain keywords
	 *
	 * @var boolean
	 */
	protected $excerpt_contain_keywords;

	/**
	 * Using plugin ps-ajax for process ajax with more faster
	 *
	 * @var boolean
	 */
	protected $enable_custom_ajax_url;
	/**
	 * Enable cache search result
	 *
	 * @var boolean
	 */
	protected $enable_cache_result = false;

	public function __construct() {
		$excerpt_contain_keywords = press_search_get_setting( 'searching_excerpt_contain_keywords', 'yes' );

		$this->excerpt_contain_keywords = apply_filters( 'press_search_is_excerpt_contain_keywords', $excerpt_contain_keywords );
		if ( ps_is__pro() ) {
			$this->enable_custom_ajax_url = apply_filters( 'press_search_is_enable_custom_ajax_url', true );
		}

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );

		add_filter( 'get_the_excerpt', array( $this, 'hightlight_excerpt_keywords' ), PHP_INT_MAX );
		add_action( 'press_search_auto_delete_logs', array( $this, 'auto_delete_logs' ) );
		add_action( 'excerpt_more', array( $this, 'modify_excerpt_more' ), PHP_INT_MAX );
		add_action( 'excerpt_length', array( $this, 'modify_excerpt_length' ), PHP_INT_MAX );

		$ajax_action_prefix = 'wp_ajax_';
		$ajax_nopriv_action_prefix = 'wp_ajax_nopriv_';
		if ( $this->enable_custom_ajax_url ) {
			$ajax_action_prefix = 'ps_ajax_';
			$ajax_nopriv_action_prefix = 'ps_ajax_nopriv_';
		}
		add_action( $ajax_action_prefix . 'press_seach_do_live_search', array( $this, 'do_live_search' ) );
		add_action( $ajax_nopriv_action_prefix . 'press_seach_do_live_search', array( $this, 'do_live_search' ) );
		add_action( $ajax_action_prefix . 'press_search_ajax_insert_log', array( $this, 'ajax_insert_log' ) );
		add_action( $ajax_nopriv_action_prefix . 'press_search_ajax_insert_log', array( $this, 'ajax_insert_log' ) );

		add_action( 'wp_ajax_press_search_empty_logs', array( $this, 'ajax_empty_logs' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
		add_filter( 'body_class', array( $this, 'body_classes' ) );

		add_action( 'admin_notices', array( $this, 'admin_notice_clear_logs' ) );
		add_filter( 'get_search_query', array( $this, 'modify_input_search_query' ) );
		add_filter( 'press_search_query_search_extra_params', array( $this, 'add_extra_params' ), 100, 2 );
		add_filter( 'press_search_sql_query_where_clause', array( $this, 'modify_search_where_clause' ), 100, 6 );
		add_filter( 'press_search_live_search_item_is_has_thumb', array( $this, 'product_search_item_thumbnail' ), 10, 4 );
		add_action( 'press_search_live_search_item_after_title_link', array( $this, 'product_search_item_price' ), 10, 2 );
	}
	/**
	 * Instance
	 *
	 * @return Press_Search_Indexing
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function product_search_item_price( $posttype, $product_id ) {
		if ( 'product' == $posttype ) {
			$get_product = wc_get_product( $product_id );
			echo sprintf( '<span class="el-in-right">%s</span>', $get_product->get_price_html() );
		}
	}

	public function product_search_item_thumbnail( $is_has_thumbnail, $posttype, $product_id, $has_post_thumbnail ) {
		if ( 'product' == $posttype && $has_post_thumbnail ) {
			$is_has_thumbnail = true;
		}
		return $is_has_thumbnail;
	}

	public function body_classes( $classes ) {
		$enable_ajax = press_search_get_setting( 'searching_enable_ajax_live_search', 'yes' );
		if ( 'yes' == $enable_ajax ) {
			$classes[] = 'ps_enable_live_search';
		}

		if ( $this->enable_custom_ajax_url ) {
			$classes[] = 'ps_using_custom_ajaxurl';
		}
		$stylesheet = get_option( 'stylesheet' );
		$classes[] = 'theme-' . $stylesheet;
		return $classes;
	}

	/**
	 * Modify extra param
	 *
	 * @param array $extra_param
	 * @param array $request
	 * @return array
	 */
	public function add_extra_params( $extra_param, $request ) {
		if ( isset( $request['parent_id'] ) && is_numeric( $request['parent_id'] ) && $request['parent_id'] > 0 ) {
			$extra_param['parent_id'] = absint( $request['parent_id'] );
		}

		if ( isset( $request['ps_tax'] ) && ! empty( $request['ps_tax'] ) ) {
			$request_term = $request['ps_tax'];
			$term_ids = array();
			if ( ! is_array( $request_term ) ) {
				$term_ids = array_unique( explode( ',', $request_term ) );
			} else {
				foreach ( $request_term as $_key => $_term ) {
					if ( is_string( $_term ) ) {
						$term_ids[ $_key ] = array_unique( explode( ',', $_term ) );
					} else {
						$term_ids[ $_key ] = array_unique( $_term );
					}
				}
			}
			$extra_param['posts_in_terms'] = $term_ids;
		}
		return $extra_param;
	}

	/**
	 * Modify sql where clause
	 *
	 * @param string $where
	 * @param array  $args
	 * @param array  $extra_args
	 * @param mix    $keywords
	 * @param string $engine_slug
	 * @param bool   $has_post_query
	 * @return string
	 */
	public function modify_search_where_clause( $where, $args, $extra_args, $keywords, $engine_slug, $has_post_query ) {
		$post_parent_keys = array(
			'parent_id',
			'parent_not_id',
		);

		$where = '';
		foreach ( $post_parent_keys as $parent_key ) {
			if ( isset( $extra_args[ $parent_key ] ) && ! empty( $extra_args[ $parent_key ] ) ) {
				$operator = 'IN';
				if ( is_array( $extra_args[ $parent_key ] ) ) {
					$parent_ids = array_unique( $extra_args[ $parent_key ] );
				} else {
					$parent_ids = array_unique( explode( ',', $extra_args[ $parent_key ] ) );
				}
				$parent_ids = array_filter( $parent_ids, 'is_numeric' );
				if ( 'parent_not_id' == $parent_key ) {
					$operator = 'NOT IN';
				}
				if ( $has_post_query && is_array( $parent_ids ) && ! empty( $parent_ids ) ) {
					$where .= ' AND post.post_parent ' . $operator . ' ( ' . implode( ', ', $parent_ids ) . ' ) ';
				}
			}
		}

		return $where;
	}

	/**
	 * Hook to pre_get_posts
	 *
	 * @param array $query
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		global $wpdb;
		$table_index_name = press_search_get_var( 'tbl_index' );
		if ( ! $query->is_admin && $query->is_main_query() && $query->is_search ) {
			$search_keywords = get_query_var( 's' );
			$engine_slug = ( isset( $_REQUEST['ps_engine'] ) && '' !== $_REQUEST['ps_engine'] ) ? trim( $_REQUEST['ps_engine'] ) : 'engine_default';
			$extra_params = apply_filters( 'press_search_query_search_extra_params', array(), $_REQUEST );
			$origin_search_keywords = $search_keywords;
			$query->set( 'seach_keyword', $origin_search_keywords );
			if ( '' !== $search_keywords ) {
				$search_keywords = press_search_query()->maybe_add_synonyms_keywords( $search_keywords );

				$limit_args = array();
				if ( ! is_null( get_query_var( 'paged' ) ) && is_numeric( get_query_var( 'paged' ) ) ) {
					$limit_args['page'] = absint( get_query_var( 'paged' ) );
				}
				if ( ! is_null( $query->get( 'posts_per_page' ) ) && is_numeric( $query->get( 'posts_per_page' ) ) ) {
					$limit_args['posts_per_page'] = $query->get( 'posts_per_page' );
				}
				if ( ! is_null( $query->get( 'offset' ) ) && is_numeric( $query->get( 'offset' ) ) ) {
					$limit_args['offset'] = $query->get( 'offset' );
				}

				$get_object_ids = press_search_query()->get_object_ids( $search_keywords, $engine_slug, $limit_args, $extra_params );
				$object_ids = $get_object_ids['object_ids'];
				$found_rows = $get_object_ids['found_rows'];
				$max_num_pages = $get_object_ids['max_num_pages'];

				if ( is_array( $object_ids ) && ! empty( $object_ids ) ) {
					$query->set( 'post__in', $object_ids );
					$query->set( 'orderby', 'post__in' );
				} else {
					$query->set( 'p', -1000 ); // Set post id to the min int -> not found any posts.
				}
				$query->set( 's', '' );
				$query->set( 'posts_per_page', -1 );
				$query->set( 'ignore_sticky_posts', true );
				$this->keywords = $search_keywords;
				$query->max_num_pages = $max_num_pages;
				do_action( 'press_search_do_pre_get_posts', $query, $extra_params, $search_keywords, $origin_search_keywords, $engine_slug );

				$this->maybe_insert_logs( $origin_search_keywords, $found_rows, false, $engine_slug );
			}
		}
	}

	public function modify_input_search_query( $origin_query ) {
		$origin_query = get_query_var( 'seach_keyword' );
		return $origin_query;
	}

	public function ajax_insert_log() {
		if ( isset( $_REQUEST['logging_args'] ) ) {
			$logging_args = wp_unslash( $_REQUEST['logging_args'] ); // WPCS: Input var ok.
			if ( isset( $logging_args['search_keywords'] ) && isset( $logging_args['log_result_count'] ) && isset( $logging_args['logging_when_ajax'] ) && isset( $logging_args['engine'] ) ) {
				$this->maybe_insert_logs( $logging_args['search_keywords'], $logging_args['log_result_count'], $logging_args['logging_when_ajax'], $logging_args['engine'] );
			}
		}
		wp_die();
	}

	/**
	 * Maybe insert search log
	 *
	 * @param string $search_keywords
	 * @param mixed  $results array or numeric.
	 * @param bool   $logging_when_ajax array or numeric.
	 * @param string $engine_slug array or numeric.
	 * @return void
	 */
	public function maybe_insert_logs( $search_keywords = '', $results = array(), $logging_when_ajax = false, $engine_slug = '' ) {
		$is_enable_logs = press_search_get_setting( 'loging_enable_log', 'on' );
		if ( 'on' == $is_enable_logs ) {
			if ( is_array( $results ) ) {
				$results = count( array_filter( $results ) );
			}
			$insert_log = $this->insert_log( $search_keywords, $results, $logging_when_ajax, $engine_slug );
		}
	}
	/**
	 * Insert user search logs to db logs
	 *
	 * @param string  $keywords
	 * @param integer $result_number
	 * @param bool    $logging_when_ajax
	 * @param string  $engine_slug
	 * @return boolean
	 */
	public function insert_log( $keywords = '', $result_number = 0, $logging_when_ajax = false, $engine_slug = '' ) {
		if ( ! $logging_when_ajax ) {
			if ( ! is_search() || is_paged() ) {
				return false;
			}
		}
		global $wpdb;

		$log_user_target = press_search_get_setting( 'loging_enable_user_target', 'both' );
		$is_log_user_ip = press_search_get_setting( 'loging_enable_log_user_ip', 'on' );
		$maybe_exclude_users = press_search_get_setting( 'loging_exclude_users', '' );
		$exclude_users = press_search_string()->explode_comma_str( $maybe_exclude_users );

		$user_id = 0;
		$user_name = '';
		$is_user_loggedin = false;
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$user_id = $user->ID;
			$user_name = $user->user_login;
			$is_user_loggedin = true;
		}

		if ( is_array( $exclude_users ) && ! empty( $exclude_users ) && $is_user_loggedin ) {
			foreach ( $exclude_users as $exclude_user ) {
				if ( ! empty( $exclude_user ) && ( $exclude_user == $user_id || $exclude_user == $user_name ) ) {
					return;
				}
			}
		}

		if ( 'logged_in' == $log_user_target ) {
			if ( ! $is_user_loggedin ) {
				return;
			}
		} elseif ( 'not_logged_in' == $log_user_target ) {
			if ( $is_user_loggedin ) {
				return;
			}
		}

		$table_logs_name = press_search_get_var( 'tbl_logs' );
		if ( is_array( $keywords ) ) {
			$keywords = implode( ' ', $keywords );
		}
		$user_ip = '';
		if ( 'on' == $is_log_user_ip ) {
			$user_ip = $this->get_the_user_ip();
		}
		$values = array(
			'query'     => $keywords,
			'hits'      => $result_number,
			'date_time' => current_time( 'mysql', 1 ),
			'ip'        => $user_ip,
			'user_id'   => $user_id,
			'search_engine'   => $engine_slug,
		);
		$value_format = array( '%s', '%d', '%s', '%s', '%d', '%s' );
		$result = $wpdb->insert( $table_logs_name, $values, $value_format );
		return $result;
	}

	public function delete_log_item( $log_id = 0 ) {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$result = $wpdb->delete( $table_logs_name, array( 'ID' => 1 ), array( '%d' ) );
		if ( false === $result ) {
			return false;
		}
		return true;
	}

	/**
	 * Auto delete logs by cronjob
	 *
	 * @return void
	 */
	public function auto_delete_logs() {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$loging_save_time = press_search_get_setting( 'loging_save_log_time', 0 );
		$loging_save_time = absint( $loging_save_time );
		if ( $loging_save_time > 0 ) {
			$result = $wpdb->query( "DELETE FROM {$table_logs_name} WHERE DATE({$table_logs_name}.date_time) <= DATE_SUB( CURDATE(), INTERVAL {$loging_save_time} DAY )" ); // WPCS: unprepared SQL OK.
		}
	}

	public function admin_notice_clear_logs() {
		if ( isset( $_GET['clear_logs'] ) && wp_unslash( $_GET['clear_logs'] ) == 'done' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Clear logs done!', 'press-search' ); ?></p>
			</div>
			<?php
		}
	}

	public function ajax_empty_logs() {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$result = $wpdb->query( "DELETE FROM {$table_logs_name}" ); // WPCS: unprepared SQL OK.
		wp_redirect( add_query_arg( array( 'clear_logs' => 'done' ), admin_url() ) );
	}

	/**
	 * Get user ip
	 *
	 * @return string
	 */
	function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	/**
	 * Hightlight keywords in string
	 *
	 * @param string $excerpt
	 * @return string
	 */
	public function hightlight_excerpt_keywords( $excerpt = '' ) {
		global $post;
		if ( ! empty( $this->keywords ) ) {
			if ( 'yes' == $this->excerpt_contain_keywords ) {
				$excerpt = press_search_string()->get_excerpt_contain_keyword( $this->keywords, $excerpt, $post->post_content );
			}
			$excerpt = press_search_string()->highlight_keywords( $excerpt, $this->keywords );
			if ( 'yes' == $this->excerpt_contain_keywords ) {
				$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
				$excerpt .= $excerpt_more;
			}
		}
		return $excerpt;
	}

	function modify_excerpt_more( $more_string ) {
		$excerpt_text = press_search_get_setting( 'searching_excerpt_more', '' );
		if ( '' == $excerpt_text ) {
			return $more_string;
		}
		$link = sprintf( '<a href="%1$s" class="wp-embed-more" target="_top">%2$s</a>', esc_url( get_permalink() ), esc_html( $excerpt_text ) );
		return '&nbsp;' . $link;
	}

	function modify_excerpt_length( $length ) {
		$excerpt_length = press_search_get_setting(
			'searching_excerpt_length',
			array(
				'length' => 30,
				'type' => 'words',
			)
		);
		if ( 'words' == $excerpt_length['type'] ) {
			$length = $excerpt_length['length'];
		}
		return $length;
	}


	/**
	 * Hook to modify origin excerpt more
	 *
	 * @param string $more
	 * @return string
	 */
	public function custom_excerpt_more( $more ) {
		$excerpt_more = press_search_get_setting( 'searching_excerpt_more', $more );
		return sprintf( '&nbsp; %s', $excerpt_more );
	}

	public function set_ajax_result_cache( $search_keywords = '', $engine_slug = 'engine_default', $result = '', $expire = 24 * HOUR_IN_SECONDS ) {
		$cache_key = 'ps_search_cache_result|' . sanitize_title( $search_keywords ) . '|ps_engine_' . $engine_slug;
		return set_transient( $cache_key, $result, $expire );
	}

	public function get_ajax_result_cache( $search_keywords = '', $engine_slug = 'engine_default' ) {
		$cache_key = 'ps_search_cache_result|' . sanitize_title( $search_keywords ) . '|ps_engine_' . $engine_slug;
		return get_transient( $cache_key );
	}

	/**
	 * Ajax get posts by search keywords.
	 *
	 * @param string $search_keywords
	 * @param string $engine_slug
	 * @param array  $extra_args
	 * @return string
	 */
	public function ajax_get_post_by_keywords( $search_keywords = '', $engine_slug = 'engine_default', $extra_args = array() ) {
		$return = array();
		$list_posttype = array();
		$html = array();
		$db_engine_settings = press_search_engines()->get_engine_settings();
		$_search_post_type = array();
		if ( array_key_exists( $engine_slug, $db_engine_settings ) ) {
			$engine_settings = $db_engine_settings[ $engine_slug ];
			if ( isset( $engine_settings['post_type'] ) && is_array( $engine_settings['post_type'] ) && ! empty( $engine_settings['post_type'] ) ) {
				$_search_post_type = $engine_settings['post_type'];
			}
		}
		$log_result_count = 0;
		if ( '' !== $search_keywords ) {
			$search_keywords = press_search_query()->maybe_add_synonyms_keywords( $search_keywords );
			$object_ids = press_search_query()->get_object_ids_group_by_posttype( $search_keywords, $engine_slug, $extra_args );

			$this->keywords = $search_keywords;
			$result_found_count = 0;
			if ( is_array( $object_ids ) && ! empty( $object_ids ) ) {
				$args = array(
					'orderby' => 'post__in',
					'ignore_sticky_posts' => true,
					'posts_per_page' => press_search_get_setting( 'searching_ajax_limit_items', 10 ),
				);
				if ( ! empty( $_search_post_type ) ) {
					$args['post_type'] = $_search_post_type;
				}
				$ajax_item_display = press_search_get_setting( 'searching_ajax_items_display', array() );
				foreach ( $object_ids as $object_type => $object_data ) {
					$ids = $object_data['object_ids'];
					$row_founds = $object_data['found_rows'];
					if ( is_array( $ids ) && ! empty( $ids ) ) {
						$result_found_count += $row_founds;
						$args['post__in'] = $ids;
						$query = new WP_Query( apply_filters( 'press_search_ajax_get_post_by_keywords', $args, $extra_args ) );

						if ( $query->have_posts() ) {
							while ( $query->have_posts() ) {
								$query->the_post();
								$posttype = get_post_type();
								$posttype_object = get_post_type_object( $posttype );
								$posttype_label = $posttype_object->labels->singular_name;
								if ( ! isset( $list_posttype[ $object_type ] ) ) {
									$list_posttype[ $object_type ] = array(
										'label' => $posttype_label,
										'posts' => array(),
									);
								}
								$pass_args = array(
									'posttype' => $posttype,
									'posttype_label' => $posttype_label,
									'ajax_item_display' => $ajax_item_display,
								);
								ob_start();
								press_search_get_template( 'search-items.php', $pass_args );
								$output = ob_get_contents();
								ob_end_clean();
								$list_posttype[ $object_type ]['posts'][] = apply_filters( 'press_search_loop_item_output', $output, get_the_ID(), $pass_args );
							}
						}
					}
				}
				$log_result_count = $result_found_count;
			} else {
				$log_result_count = 0;
				ob_start();
				press_search_get_template( 'no-result.php', array() );
				$result = ob_get_contents();
				ob_end_clean();
				$return = array(
					'html' => apply_filters( 'press_search_no_result_content_html', $result ),
				);
				$return = $this->maybe_add_logging_args( $return, $search_keywords, $log_result_count, $engine_slug );
				return $return;
			}
			if ( is_array( $list_posttype ) && ! empty( $list_posttype ) ) {
				$posttype_keys = array_keys( $list_posttype );
				$_count_result_posts = 0;
				$see_all_result = press_search_get_setting( 'searching_enable_ajax_see_all_result_link', 'yes' );
				$group_posttype_all_result = press_search_get_setting( 'searching_enable_ajax_see_all_post_group_result_link', 'no' );
				$search_link_args = array(
					's' => ( is_array( $search_keywords ) ) ? urlencode( implode( ' ', $search_keywords ) ) : urlencode( $search_keywords ),
				);

				foreach ( $list_posttype as $key => $data ) {
					$_count_result_posts += count( $data['posts'] );
					$group_result = '';
					$group_result .= '<div class="group-posttype group-posttype-' . esc_attr( $key ) . '">';
					$group_result     .= '<div class="group-posttype-label group-posttype-label-' . esc_attr( $key ) . '">';
					$group_result         .= '<span class="group-label">' . esc_html( $data['label'] ) . '</span>';
					if ( 'yes' == $group_posttype_all_result && count( $list_posttype ) > 1 && count( $data['posts'] ) > 1 ) {
						$posttype_link_args = $search_link_args;
						$posttype_link_args['post_type'] = str_replace( 'post_', '', $key );
						if ( isset( $engine_slug ) && '' !== $engine_slug && 'engine_default' !== $engine_slug ) {
							$posttype_link_args['ps_engine'] = $engine_slug;
						}
						$posttype_results_link = add_query_arg( $posttype_link_args, site_url() );
						$group_result .= '<a class="posttype-results-link" target="_blank" href="' . esc_url( $posttype_results_link ) . '">' . esc_html__( 'View all', 'press-search' ) . '</a>';
					}
					$group_result     .= '</div>';
					$group_result     .= '<div class="group-posttype-items group-posttype-' . esc_attr( $key ) . '-items">';
					$group_result         .= implode( '', $data['posts'] );
					$group_result     .= '</div>';
					$group_result .= '</div>';
					$html[] = apply_filters( 'press_search_group_result', $group_result, $data, $posttype_keys, $list_posttype );
				}
				if ( 'yes' == $see_all_result && $_count_result_posts < $result_found_count ) {
					$all_results_link = add_query_arg( $search_link_args, site_url() );
					$see_all_link = '<div class="see-all-results"><a href="' . esc_url( $all_results_link ) . '" class="all-results-link">' . sprintf( '%s(%s) %s', esc_html__( 'See all', 'press-search' ), esc_html( $result_found_count ), esc_html__( 'results', 'press-search' ) ) . '</a></div>';
					$html[] = apply_filters( 'press_search_see_all_results_link', $see_all_link );
				}
			}
		}

		$return = array(
			'html' => implode( '', apply_filters( 'press_search_ajax_result_html', $html ) ),
		);
		$return = $this->maybe_add_logging_args( $return, $search_keywords, $log_result_count, $engine_slug );
		flush();
		return $return;
	}

	public function maybe_add_logging_args( $args = array(), $search_keywords = '', $log_result_count = 0, $engine_slug = '' ) {
		$is_enable_logs = press_search_get_setting( 'loging_enable_log', 'on' );
		if ( 'on' == $is_enable_logs ) {
			$search_log_args = array(
				'search_keywords' => $search_keywords,
				'log_result_count' => $log_result_count,
				'logging_when_ajax' => true,
				'engine' => $engine_slug,
			);
			$args['logging_args'] = $search_log_args;
		}
		return $args;
	}

	public function do_live_search() {
		$security = ( isset( $_REQUEST['security'] ) && '' !== $_REQUEST['security'] ) ? sanitize_text_field( $_REQUEST['security'] ) : '';
		$keywords = ( isset( $_REQUEST['s'] ) && '' !== $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
		set_query_var( 's', $keywords );
		$engine_slug = ( isset( $_REQUEST['ps_engine'] ) && '' !== $_REQUEST['ps_engine'] ) ? sanitize_text_field( $_REQUEST['ps_engine'] ) : 'engine_default';
		$extra_params = apply_filters( 'press_search_query_search_extra_params', array(), $_REQUEST );

		if ( '' == $keywords ) {
			ob_start();
			press_search_get_template( 'no-search-terms.php' );
			$nothing_match_terms = ob_get_contents();
			ob_end_clean();
			wp_send_json_success( array( 'content' => apply_filters( 'press_search_nothing_match_search', $nothing_match_terms ) ) );
		}
		if ( $this->enable_cache_result && false !== $this->get_ajax_result_cache( $keywords, $engine_slug ) ) {
			$post_by_keywords = $this->get_ajax_result_cache( $keywords, $engine_slug );
			$result_type = 'cached_result';
		} else {
			$ajax_get_post = $this->ajax_get_post_by_keywords( $keywords, $engine_slug, $extra_params );
			$post_by_keywords = $ajax_get_post['html'];
			if ( isset( $ajax_get_post['logging_args'] ) ) {
				$logging_args = $ajax_get_post['logging_args'];
			}
			$this->set_ajax_result_cache( $keywords, $engine_slug, $post_by_keywords );
			$result_type = 'no_cache_result';
		}
		$json_args = array(
			'keywords'    => $keywords,
			'content'        => $post_by_keywords,
			'result_type'    => $result_type,
		);
		if ( isset( $logging_args ) && ! empty( $logging_args ) ) {
			$json_args['logging_args'] = $logging_args;
		}
		wp_send_json_success( $json_args );
	}


	public function get_suggest_keyword() {
		$top_keywords = press_search_get_setting( 'searching_ajax_top_search_keywords', '' );
		$top_keywords = explode( PHP_EOL, $top_keywords );
		$return = array();
		if ( is_array( $top_keywords ) && ! empty( $top_keywords ) ) {
			foreach ( $top_keywords as $result ) {
				if ( ! empty( $result ) ) {
					$return[] = $result;
				}
			}
		}
		return $return;
	}

	public function enqueue_scripts() {
		$suggest_keyword = $this->get_suggest_keyword();
		$keywords = array();
		if ( is_array( $suggest_keyword ) && ! empty( $suggest_keyword ) ) {
			foreach ( $suggest_keyword as $keyword ) {
				$keywords[] = apply_filters( 'press_search_suggest_keyword', $keyword );
			}
			ob_start();
			press_search_get_template( 'suggest-keywords.php', array( 'keywords' => $keywords ) );
			$keyword_html = ob_get_contents();
			ob_end_clean();
		} else {
			$keyword_html = '';
		}

		$default_search_engine = 'engine_default';
		if ( ps_is__pro() ) {
			$default_search_engine = press_search_get_setting( 'searching_form_engine', 'engine_default' );
		}
		$box_result_flexible = press_search_get_setting( 'searching_enable_ajax_box_result_flex_position', 'no' );

		$localize_args = array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'security' => wp_create_nonce( 'frontend-ajax-security' ),
			'ajax_delay_time' => press_search_get_setting( 'searching_ajax_delay_time', 500 ),
			'ajax_min_char' => press_search_get_setting( 'searching_ajax_min_char', 3 ),
			'suggest_keywords' => apply_filters( 'press_search_suggest_keywords_html', $keyword_html ),
			'form_search_engine' => apply_filters( 'press_search_form_search_engine', $default_search_engine ),
			'box_result_flexible_position' => apply_filters( 'press_search_box_result_flexible_position', $box_result_flexible ),
		);
		if ( ps_is__pro() ) {
			if ( $this->enable_custom_ajax_url ) {
				$localize_args['ps_ajax_url'] = press_search_get_var( 'plugin_url' ) . 'inc/ps-ajax.php';
			}
		}
		wp_localize_script( 'press-search', 'Press_Search_Frontend_Js', $localize_args );
	}

}

$searching = new Press_Search_Searching();
