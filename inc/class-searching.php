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
	protected $excerpt_contain_keywords = false;

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
	protected $enable_cache_result = true;

	public function __construct() {
		$this->excerpt_contain_keywords = apply_filters( 'press_search_is_excerpt_contain_keywords', true );
		$this->enable_custom_ajax_url = apply_filters( 'press_search_is_enable_custom_ajax_url', true );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );

		add_filter( 'get_the_excerpt', array( $this, 'hightlight_excerpt_keywords' ), PHP_INT_MAX );
		add_action( 'press_search_auto_delete_logs', array( $this, 'auto_delete_logs' ) );
		add_action( 'excerpt_more', array( $this, 'modify_excerpt_more' ), PHP_INT_MAX );
		add_action( 'excerpt_length', array( $this, 'modify_excerpt_length' ), PHP_INT_MAX );

		if ( $this->enable_custom_ajax_url ) {
			add_action( 'ps_ajax_press_seach_do_live_search', array( $this, 'do_live_search' ) );
			add_action( 'ps_ajax_press_seach_do_live_search', array( $this, 'do_live_search' ) );
		} else {
			add_action( 'wp_ajax_nopriv_press_seach_do_live_search', array( $this, 'do_live_search' ) );
			add_action( 'wp_ajax_press_seach_do_live_search', array( $this, 'do_live_search' ) );
		}
		add_action( 'wp_ajax_press_search_empty_logs', array( $this, 'ajax_empty_logs' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
		add_filter( 'body_class', array( $this, 'body_classes' ) );

		add_action( 'admin_notices', array( $this, 'admin_notice_clear_logs' ) );
		add_filter( 'get_search_query', array( $this, 'modify_input_search_query' ) );
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

	public function body_classes( $classes ) {
		$enable_ajax = press_search_get_setting( 'searching_enable_ajax_live_search', 'yes' );
		if ( 'yes' == $enable_ajax ) {
			$classes[] = 'ps_enable_live_search';
		}

		if ( $this->enable_custom_ajax_url ) {
			$classes[] = 'ps_using_custom_ajaxurl';
		}
		return $classes;
	}

	/**
	 * Hook to pre_get_posts
	 *
	 * @param array $query
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		global $wpdb, $press_search_query;
		$table_index_name = press_search_get_var( 'tbl_index' );
		if ( ! $query->is_admin && $query->is_main_query() && $query->is_search ) {
			$search_keywords = get_query_var( 's' );
			$engine_slug = ( isset( $_REQUEST['ps_engine'] ) && '' !== $_REQUEST['ps_engine'] ) ? trim( $_REQUEST['ps_engine'] ) : 'engine_default';
			$origin_search_keywords = $search_keywords;
			if ( '' !== $search_keywords ) {
				$search_keywords = $press_search_query->maybe_add_synonyms_keywords( $search_keywords );
				$object_ids = $press_search_query->get_object_ids( $search_keywords, $engine_slug );
				if ( is_array( $object_ids ) && ! empty( $object_ids ) ) {
					$query->set( 'post__in', $object_ids );
					$query->set( 'orderby', 'post__in' );
				} else {
					$query->set( 'p', PHP_INT_MIN ); // Set post id to the min int -> not found any posts.
				}
				$query->set( 's', '' );
				$query->set( 'seach_keyword', $origin_search_keywords );
				$this->keywords = $search_keywords;
				$this->maybe_insert_logs( $origin_search_keywords, $object_ids );
			}
		}
	}

	public function modify_input_search_query( $origin_query ) {
		$origin_query = get_query_var( 'seach_keyword' );
		return $origin_query;
	}

	/**
	 * Maybe insert search log
	 *
	 * @param string $search_keywords
	 * @param mixed  $results array or numeric.
	 * @return void
	 */
	public function maybe_insert_logs( $search_keywords = '', $results = array(), $logging_when_ajax = false ) {
		$is_enable_logs = press_search_get_setting( 'loging_enable_log', '' );
		if ( 'on' == $is_enable_logs ) {
			if ( is_array( $results ) ) {
				$results = count( array_filter( $results ) );
			}
			$insert_log = $this->insert_log( $search_keywords, $results, $logging_when_ajax );
		}
	}
	/**
	 * Insert user search logs to db logs
	 *
	 * @param string  $keywords
	 * @param integer $result_number
	 * @return boolean
	 */
	public function insert_log( $keywords = '', $result_number = 0, $logging_when_ajax = false ) {
		if ( ! $logging_when_ajax ) {
			if ( ! is_search() || is_paged() ) {
				return false;
			}
		}
		global $wpdb;

		$log_user_target = press_search_get_setting( 'loging_enable_user_target', 'both' );
		$is_log_user_ip = press_search_get_setting( 'loging_enable_log_user_ip', '' );
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
		);
		$value_format = array( '%s', '%d', '%s', '%s', '%d' );
		$result = $wpdb->insert( $table_logs_name, $values, $value_format );
		return $result;
	}

	/**
	 * Auto delete logs by cronjob
	 *
	 * @return void
	 */
	public function auto_delete_logs() {
		global $wpdb, $press_search_db_name;
		$table_logs_name = $press_search_db_name['tbl_logs'];
		$loging_save_time = press_search_get_setting( 'loging_save_log_time', 0 );
		$loging_save_time = absint( $loging_save_time );
		if ( $loging_save_time > 0 ) {
			$result = $wpdb->get_results( "DELETE FROM {$table_logs_name} WHERE {$table_logs_name}.date_time < SUBDATE( CURDATE(), 1 )" ); // WPCS: unprepared SQL OK.
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
		global $wpdb, $press_search_db_name;
		$table_logs_name = $press_search_db_name['tbl_logs'];
		$result = $wpdb->get_results( "DELETE FROM {$table_logs_name}" ); // WPCS: unprepared SQL OK.
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
			if ( $this->excerpt_contain_keywords ) {
				$excerpt = press_search_string()->get_excerpt_contain_keyword( $this->keywords, $excerpt, $post->post_content );
			}
			$excerpt = press_search_string()->highlight_keywords( $excerpt, $this->keywords );
			$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
			$excerpt .= $excerpt_more;
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
		$cache_key = sanitize_title( $search_keywords ) . '|ps_engine_' . $engine_slug;
		return set_transient( $cache_key, $result, $expire );
	}

	public function get_ajax_result_cache( $search_keywords = '', $engine_slug = 'engine_default' ) {
		$cache_key = sanitize_title( $search_keywords ) . '|ps_engine_' . $engine_slug;
		return get_transient( $cache_key );
	}

	public function ajax_get_post_by_keywords( $search_keywords = '', $engine_slug = 'engine_default' ) {
		global $press_search_query;
		$return = array();
		$list_posttype = array();
		$html = array();
		if ( '' !== $search_keywords ) {
			$search_keywords = $press_search_query->maybe_add_synonyms_keywords( $search_keywords );
			$object_ids = $press_search_query->get_object_ids_group_by_posttype( $search_keywords, $engine_slug );
			$ajax_limit_items = press_search_get_setting( 'searching_ajax_limit_items', 10 );
			$this->keywords = $search_keywords;
			if ( is_array( $object_ids ) && ! empty( $object_ids ) ) {
				$count_object_types = count( array_keys( $object_ids ) );
				if ( $count_object_types > 0 ) {
					$ajax_limit_items = round( $ajax_limit_items / $count_object_types );
				}
				$args = array(
					'orderby' => 'post__in',
					'posts_per_page' => $ajax_limit_items,
				);
				$result_found_count = 0;
				foreach ( $object_ids as $object_type => $ids ) {
					if ( is_array( $ids ) && ! empty( $ids ) ) {
						$result_found_count += count( $ids );
						$args['post__in'] = $ids;
						$query = new WP_Query( apply_filters( 'press_search_ajax_get_post_by_keywords', $args ) );
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
								ob_start();
								?>
								<div class="live-search-item" data-posttype="<?php echo esc_attr( $posttype ); ?>" data-posttype_label="<?php echo esc_attr( $posttype_label ); ?>">
									<?php if ( has_post_thumbnail() ) { ?>
										<div class="item-thumb">
											<a href="<?php the_permalink(); ?>" class="item-thumb-link">
												<?php the_post_thumbnail(); ?>
											</a>
										</div>
									<?php } ?>
									<div class="item-wrap">
										<h3 class="item-title">
											<a href="<?php the_permalink(); ?>" class="item-title-link">
												<?php the_title(); ?>
											</a>
										</h3>
										<div class="item-excerpt"><?php the_excerpt(); ?></div>
									</div>
								</div>
								<?php
								$output = ob_get_contents();
								ob_end_clean();
								$list_posttype[ $object_type ]['posts'][] = $output;
							}
						}
					}
				}
				$this->maybe_insert_logs( $search_keywords, $result_found_count, true );
			} else {
				$this->maybe_insert_logs( $search_keywords, 0, true );
				$result = '<div class="ajax-no-result align-center">' . esc_html__( 'No post found', 'press-search' ) . '</div>';
				return $result;
			}
			$remove_group_if_one_posttype = true;
			if ( is_array( $list_posttype ) && ! empty( $list_posttype ) ) {
				$posttype_keys = array_keys( $list_posttype );
				foreach ( $list_posttype as $key => $data ) {
					if ( count( $posttype_keys ) < 2 && $remove_group_if_one_posttype ) {
						$html[] = implode( '', $data['posts'] );
					} else {
						$html[] = '<div class="group-posttype group-posttype-' . esc_attr( $key ) . '">';
						$html[]     = '<div class="group-posttype-label group-posttype-label-' . esc_attr( $key ) . '">';
						$html[]         = '<span class="group-label">' . esc_attr( $data['label'] ) . '</span>';
						$html[]     = '</div>';
						$html[]     = '<div class="group-posttype-items group-posttype-' . esc_attr( $key ) . '-items">';
						$html[]         = implode( '', $data['posts'] );
						$html[]     = '</div>';
						$html[] = '</div>';
					}
				}
			}
		}
		$return = implode( '', $html );
		flush();
		return $return;
	}

	public function do_live_search() {
		$security = ( isset( $_REQUEST['security'] ) && '' !== $_REQUEST['security'] ) ? trim( $_REQUEST['security'] ) : '';
		$keywords = ( isset( $_REQUEST['s'] ) && '' !== $_REQUEST['s'] ) ? trim( $_REQUEST['s'] ) : '';
		$engine_slug = ( isset( $_REQUEST['ps_engine'] ) && '' !== $_REQUEST['ps_engine'] ) ? trim( $_REQUEST['ps_engine'] ) : 'engine_default';
		if ( '' == $keywords ) {
			wp_send_json_success( array( 'content' => sprintf( '<p>%s</p>', esc_html__( 'Sorry, but nothing matched your search terms.', 'press-search' ) ) ) );
		}
		if ( $this->enable_cache_result && false !== $this->get_ajax_result_cache( $keywords, $engine_slug ) ) {
			$post_by_keywords = $this->get_ajax_result_cache( $keywords, $engine_slug );
			$result_type = 'cached_result';
		} else {
			$post_by_keywords = $this->ajax_get_post_by_keywords( $keywords, $engine_slug );
			$this->set_ajax_result_cache( $keywords, $engine_slug, $post_by_keywords );
			$result_type = 'no_cache_result';
		}
		$json_args = array(
			'content'        => $post_by_keywords,
			'result_type'    => $result_type,
		);
		wp_send_json_success( $json_args );
	}


	public function get_suggest_keyword() {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$return = array();
		$results = $wpdb->get_results( "SELECT DISTINCT query FROM {$table_logs_name} WHERE `hits` > 0 ORDER BY `hits` DESC LIMIT 0,5" ); // WPCS: unprepared SQL OK.
		if ( is_array( $results ) && ! empty( $results ) ) {
			foreach ( $results as $result ) {
				if ( isset( $result->query ) && '' !== $result->query ) {
					$return[] = $result->query;
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
				$keywords[] = sprintf( '<p class="suggest-keyword">%s</p>', $keyword );
			}
		}

		$localize_args = array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'security' => wp_create_nonce( 'frontend-ajax-security' ),
			'suggest_keywords' => implode( '', $keywords ),
		);
		if ( $this->enable_custom_ajax_url ) {
			$localize_args['ps_ajax_url'] = press_search_get_var( 'plugin_url' ) . 'inc/ps-ajax.php';
		}
		wp_localize_script( 'press-search', 'PRESS_SEARCH_FRONTEND_JS', $localize_args );
	}

}

$searching = new Press_Search_Searching();
