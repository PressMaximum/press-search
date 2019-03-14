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

	public function __construct() {
		$this->excerpt_contain_keywords = true;
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );

		add_filter( 'get_the_excerpt', array( $this, 'hightlight_excerpt_keywords' ), PHP_INT_MAX );
		add_action( 'press_search_auto_delete_logs', array( $this, 'auto_delete_logs' ) );
		add_action( 'excerpt_more', array( $this, 'modify_excerpt_more' ), PHP_INT_MAX );
		add_action( 'excerpt_length', array( $this, 'modify_excerpt_length' ), PHP_INT_MAX );

		add_action( 'wp_ajax_nopriv_press_seach_do_live_search', array( $this, 'do_live_search' ) );
		add_action( 'wp_ajax_press_seach_do_live_search', array( $this, 'do_live_search' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
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
		return $classes;
	}

	/**
	 * Hook to pre_get_posts
	 *
	 * @param array $query
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		global $wpdb, $press_search_db_name, $press_search_query;
		$table_index_name = $press_search_db_name['tbl_index'];
		if ( ! $query->is_admin && $query->is_main_query() && $query->is_search ) {
			$search_keywords = get_query_var( 's' );
			$origin_search_keywords = $search_keywords;
			if ( '' !== $search_keywords ) {
				$search_keywords = $press_search_query->maybe_add_synonyms_keywords( $search_keywords );
				$object_ids = $press_search_query->get_object_ids( $search_keywords );
				if ( is_array( $object_ids ) && ! empty( $object_ids ) ) {
					$query->set( 'post__in', $object_ids );
					$query->set( 'orderby', 'post__in' );
				} else {
					$query->set( 'p', PHP_INT_MIN ); // Set post id to the min int -> not found any posts.
				}
				$query->set( 's', '' );
				$this->keywords = $search_keywords;
				$this->maybe_insert_logs( $origin_search_keywords, $object_ids );
			}
		}
	}

	public function maybe_insert_logs( $search_keywords = '', $object_ids = array() ) {
		$is_enable_logs = press_search_get_setting( 'loging_enable_log', '' );
		if ( 'on' == $is_enable_logs ) {
			$insert_log = $this->insert_log( $search_keywords, count( $object_ids ) );
		}
	}
	/**
	 * Insert user search logs to db logs
	 *
	 * @param string  $keywords
	 * @param integer $result_number
	 * @return boolean
	 */
	public function insert_log( $keywords = '', $result_number = 0 ) {
		if ( ! is_search() || is_paged() ) {
			return false;
		}
		global $wpdb, $press_search_db_name;

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

		$table_logs_name = $press_search_db_name['tbl_logs'];
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
				'type' => 'text',
			)
		);
		if ( 'text' == $excerpt_length['type'] ) {
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

	public function get_post_by_keywords( $search_keywords = '' ) {
		global $press_search_query;
		$return = array();
		if ( '' !== $search_keywords ) {
			$search_keywords = $press_search_query->maybe_add_synonyms_keywords( $search_keywords );
			$object_ids = $press_search_query->get_object_ids( $search_keywords );
			$ajax_limit_items = press_search_get_setting( 'searching_ajax_limit_items', 10 );
			$args = array(
				'posts_per_page' => $ajax_limit_items,
			);
			if ( is_array( $object_ids ) && ! empty( $object_ids ) ) {
				$args['post__in'] = $object_ids;
				$args['orderby']  = 'post__in';
			} else {
				$args['p']  = PHP_INT_MIN; // Set post id to the min int -> not found any posts.
			}
			$this->keywords = $search_keywords;
			$this->maybe_insert_logs( $search_keywords, $object_ids );
			$query = new WP_Query( apply_filters( 'press_search_get_post_by_keywords', $args ) );
			$list_posttype = array();
			$html = array();
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$posttype = get_post_type();
					$posttype_object = get_post_type_object( $posttype );
					$posttype_label = $posttype_object->labels->singular_name;
					if ( ! isset( $list_posttype[ $posttype ] ) ) {
						$list_posttype[ $posttype ] = array(
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
					$list_posttype[ $posttype ]['posts'][] = $output;
				}
			}

			if ( is_array( $list_posttype ) && ! empty( $list_posttype ) ) {
				$posttype_keys = array_keys( $list_posttype );
				foreach ( $list_posttype as $key => $data ) {
					if ( count( $posttype_keys ) < 2 ) {
						$html[] = implode( '', $data['posts'] );
					} else {
						$html[] = '<div class="group-posttype group-posttype-' . esc_attr( $key ) . '">';
						$html[]     = '<div class="group-posttype-label group-posttype-label-' . esc_attr( $key ) . '">';
						$html[]         = '<span class="group-posttype-label">' . esc_attr( $data['label'] ) . '</span>';
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
		return $return;
	}

	public function do_live_search() {
		$security = ( isset( $_REQUEST['security'] ) && '' !== $_REQUEST['security'] ) ? trim( $_REQUEST['security'] ) : '';
		$keywords = ( isset( $_REQUEST['s'] ) && '' !== $_REQUEST['s'] ) ? trim( $_REQUEST['s'] ) : '';
		if ( '' == $security || ! wp_verify_nonce( $security, 'frontend-ajax-security' ) ) {
			wp_send_json_success( array( 'content' => sprintf( '<p>%s</p>', esc_html__( 'Reload the page and try again.', 'press-search' ) ) ) );
		}
		if ( '' == $keywords ) {
			wp_send_json_success( array( 'content' => sprintf( '<p>%s</p>', esc_html__( 'Sorry, but nothing matched your search terms.', 'press-search' ) ) ) );
		}
		$post_by_keywords = $this->get_post_by_keywords( $keywords );
		wp_send_json_success( array( 'content' => $post_by_keywords ) );
	}


	public function get_suggest_keyword() {
		global $wpdb, $press_search_db_name;
		$table_logs_name = $press_search_db_name['tbl_logs'];
		$return = array();
		$results = $wpdb->get_results( "SELECT DISTINCT query FROM {$table_logs_name} ORDER BY date_time DESC" ); // WPCS: unprepared SQL OK.
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
		wp_localize_script(
			'press-search',
			'PRESS_SEARCH_FRONTEND_JS',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'frontend-ajax-security' ),
				'suggest_keywords' => implode( '', $keywords ),
			)
		);
	}

}

$searching = new Press_Search_Searching();

