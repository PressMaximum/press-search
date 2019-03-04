<?php
class Press_Search_Indexing {
	protected $db_option_key = 'press_search_';
	protected $ajax_url;
	/**
	 * Object Press_Search_Crawl_Data
	 *
	 * @var Press_Search_Crawl_Data
	 */
	protected $object_crawl_data;
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Indexing
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * Construction
	 */
	public function __construct() {
		if ( ! defined( 'PRESS_SEARCH_MAX_ITEM_TO_INDEX' ) ) {
			define( 'PRESS_SEARCH_MAX_ITEM_TO_INDEX', 1 );
		}
		$this->ajax_url = admin_url( 'admin-ajax.php' );
		add_action( 'wp', array( $this, 'cronjob_activation' ) );
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		add_action( 'press_search_indexing_cronjob', array( $this, 'cron_index_data' ) );
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'wp_ajax_build_unindexed_data_ajax', array( $this, 'build_unindexed_data_ajax' ) );
		add_action( 'wp_ajax_build_the_index_data_ajax', array( $this, 'build_the_index_data_ajax' ) );
		add_action( 'wp_ajax_get_indexing_progress', array( $this, 'get_indexing_progress' ) );
		add_action( 'wp_ajax_clear_option_data_ajax', array( $this, 'clear_option_data_ajax' ) );

		add_action( 'save_post', array( $this, 'reindex_updated_post' ), PHP_INT_MAX );
		add_action( 'delete_post', array( $this, 'delete_indexed_post' ), PHP_INT_MAX );
		add_action( 'edited_terms', array( $this, 'reindex_updated_term' ), PHP_INT_MAX, 2 );
		add_action( 'delete_term', array( $this, 'delete_indexed_term' ), PHP_INT_MAX );
		add_action( 'profile_update', array( $this, 'reindex_updated_user' ), PHP_INT_MAX, 2 );
		add_action( 'delete_user', array( $this, 'delete_indexed_user' ), PHP_INT_MAX );

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

	public function init() {
		// Code here.
		$index_settings = press_search_engines()->__get( 'index_settings' );
		$this->object_crawl_data = new Press_Search_Crawl_Data(
			array(
				'settings' => $index_settings,
			)
		);
		$this->save_post_to_index();
		$this->save_term_to_index();
	}

	public function save_post_to_index() {
		$post_to_index = $this->object_crawl_data->get_all_post_ids();
		$key_post_index = $this->db_option_key . 'post_to_index';

		if ( ! $this->is_option_key_exists( $key_post_index ) ) {
			update_option( $key_post_index, $post_to_index );
		} else {
			$key_post_indexed = $this->db_option_key . 'post_indexed';
			$db_post_index = get_option( $key_post_index, array() );
			$db_post_indexed = get_option( $key_post_indexed, array() );
			$diff_post_index = array_diff( $post_to_index, $db_post_index );
			$index_id_new = array();
			foreach ( $diff_post_index as $diff_id ) {
				if ( ! in_array( $diff_id, $db_post_indexed ) ) {
					$index_id_new[] = $diff_id;
				}
			}
			if ( ! empty( $index_id_new ) ) {
				$new_index_ids = array_merge( $db_post_index, $index_id_new );
				update_option( $key_post_index, $new_index_ids );
			}
		}
	}

	public function save_term_to_index() {
		$term_to_index = $this->object_crawl_data->get_all_terms_ids();
		$key_term_index = $this->db_option_key . 'term_to_index';

		if ( ! $this->is_option_key_exists( $key_term_index ) ) {
			update_option( $key_term_index, $term_to_index );
		} else {
			$key_term_indexed = $this->db_option_key . 'term_indexed';
			$db_term_index = get_option( $key_term_index, array() );
			$db_term_indexed = get_option( $key_term_indexed, array() );
			$diff_term_index = array_diff( $term_to_index, $db_term_index );

			$index_id_new = array();
			foreach ( $diff_term_index as $diff_id ) {
				if ( ! in_array( $diff_id, $db_term_indexed ) ) {
					$index_id_new[] = $diff_id;
				}
			}
			if ( ! empty( $index_id_new ) ) {
				$new_index_ids = array_merge( $db_term_index, $index_id_new );
				update_option( $key_term_index, $new_index_ids );
			}
		}
	}

	public function is_option_key_exists( $option_key = '' ) {
		if ( null !== get_option( $option_key, null ) ) {
			return true;
		}
		return false;
	}

	public function array_slice( &$array = array(), $number_element = 1 ) {
		$slice = array_slice( $array, 0, $number_element );
		if ( ! empty( $array ) ) {
			foreach ( $array as $k => $v ) {
				if ( in_array( $v, $slice ) ) {
					unset( $array[ $k ] );
				}
			}
		}
		return $slice;
	}

	public function array_filter( $array = array(), $value ) {
		if ( is_array( $array ) && ! empty( $array ) ) {
			foreach ( $array as $k => $v ) {
				if ( $value === $v ) {
					unset( $array[ $k ] );
				}
			}
		}
		return $array;
	}

	public function reindex_post_data() {
		$return = false;
		$post_indexed = get_option( $this->db_option_key . 'post_indexed', array() );
		$post_to_reindex = get_option( $this->db_option_key . 'post_to_reindex', array() );
		$post_need_reindex = $post_to_reindex;
		if ( ! empty( $post_to_reindex ) ) {
			$index_ids = $this->array_slice( $post_to_reindex, PRESS_SEARCH_MAX_ITEM_TO_INDEX );
			if ( is_array( $index_ids ) && ! empty( $index_ids ) ) {
				foreach ( $index_ids as $post_id ) {
					if ( 'publish' == get_post_status( $post_id ) ) {
						// Delete this post from indexed and it data.
						$post_indexed = $this->array_filter( $post_indexed, $post_id );
						$this->object_crawl_data->delete_indexed_object( 'post', $post_id );
						update_option( $this->db_option_key . 'post_indexed', $post_indexed );
						// Re index.
						$return = $this->object_crawl_data->insert_indexing_post( $post_id );
						if ( $return ) {
							// Remove this post from list need reindex .
							$post_need_reindex = $this->array_filter( $post_need_reindex, $post_id );
							update_option( $this->db_option_key . 'post_to_reindex', $post_need_reindex );
							// Update this post to indexed list.
							$post_indexed[] = $post_id;
							update_option( $this->db_option_key . 'post_indexed', $post_indexed );
							$this->update_reindex_object_count( 'post' );
							$this->last_time_index();
						}
					}
				}
			}
		}
		return $return;
	}

	public function reindex_term_data() {
		$return = false;
		$term_indexed = get_option( $this->db_option_key . 'term_indexed', array() );
		$term_to_reindex = get_option( $this->db_option_key . 'term_to_reindex', array() );
		$term_need_reindex = $term_to_reindex;
		if ( ! empty( $term_to_reindex ) ) {
			$index_ids = $this->array_slice( $term_to_reindex, PRESS_SEARCH_MAX_ITEM_TO_INDEX );
			if ( is_array( $index_ids ) && ! empty( $index_ids ) ) {
				foreach ( $index_ids as $term_id ) {
					if ( $this->object_crawl_data->term_exists( $term_id ) ) {
						// Delete this term from indexed and it data.
						$term_indexed = $this->array_filter( $term_indexed, $term_id );
						update_option( $this->db_option_key . 'term_indexed', $term_indexed );
						$this->object_crawl_data->delete_indexed_object( 'term', $term_id );
						// Re index.
						$return = $this->object_crawl_data->insert_indexing_term( $term_id );
						if ( $return ) {
							// Remove this term from list need reindex .
							$term_need_reindex = $this->array_filter( $term_need_reindex, $term_id );
							update_option( $this->db_option_key . 'term_to_reindex', $term_need_reindex );
							// Update this term to indexed list.
							$term_indexed[] = $term_id;
							update_option( $this->db_option_key . 'term_indexed', $term_indexed );
							$this->update_reindex_object_count( 'term' );
							$this->last_time_index();
						}
					}
				}
			}
		}
		return $return;
	}

	public function reindex_user_data() {

	}

	public function update_reindex_object_count( $type = 'post' ) {
		switch ( $type ) {
			case 'post':
				$db_key = 'post_reindexed_count';
				break;
			case 'term':
				$db_key = 'term_reindexed_count';
				break;
			case 'user':
				$db_key = 'user_reindexed_count';
				break;
		}
		$object_indexed_count = get_option( $this->db_option_key . $db_key, 0 );
		$object_indexed_count += 1;
		update_option( $this->db_option_key . $db_key, $object_indexed_count );
	}

	public function index_post_data() {
		$return = false;
		$post_to_index = get_option( $this->db_option_key . 'post_to_index', array() );
		if ( ! empty( $post_to_index ) ) {
			$index_ids = $this->array_slice( $post_to_index, PRESS_SEARCH_MAX_ITEM_TO_INDEX );
			if ( is_array( $index_ids ) && ! empty( $index_ids ) ) {
				foreach ( $index_ids as $first_post_id ) {
					if ( is_string( get_post_status( $first_post_id ) ) ) {
						$return = $this->object_crawl_data->insert_indexing_post( $first_post_id );
						if ( $return ) {
							update_option( $this->db_option_key . 'post_to_index', $post_to_index );
							$db_post_indexed = (array) get_option( $this->db_option_key . 'post_indexed', array() );
							$db_post_indexed[] = $first_post_id;
							$post_indexed = array_unique( $db_post_indexed );
							update_option( $this->db_option_key . 'post_indexed', $post_indexed );
							$this->last_time_index();
						}
					}
				}
			}
		}
		return $return;
	}

	public function index_term_data() {
		$term_to_index = get_option( $this->db_option_key . 'term_to_index', array() );
		$return = false;
		if ( ! empty( $term_to_index ) ) {
			$index_ids = $this->array_slice( $term_to_index, PRESS_SEARCH_MAX_ITEM_TO_INDEX );
			foreach ( $index_ids as $first_id ) {
				if ( $this->object_crawl_data->term_exists( $first_id ) ) {
					$return = $this->object_crawl_data->insert_indexing_term( $first_id );
					if ( $return ) {
						update_option( $this->db_option_key . 'term_to_index', $term_to_index );
						$db_term_indexed = (array) get_option( $this->db_option_key . 'term_indexed', array() );
						$db_term_indexed[] = $first_id;
						$term_indexed = array_unique( $db_term_indexed );
						update_option( $this->db_option_key . 'term_indexed', $term_indexed );
						$this->last_time_index();
					}
				}
			}
		}
		return $return;
	}

	public function index_user_data() {

	}

	public function last_time_index() {
		update_option( $this->db_option_key . 'last_time_index', current_time( 'mysql' ) );
	}

	public function index_progress_report() {
		$static_report = '';
		if ( function_exists( 'press_search_reports' ) ) {
			$static_report = press_search_reports()->engines_static_report();
		}
		return $static_report;
	}

	public function build_unindexed_data_ajax() {
		$this->check_ajax_security();
		$this->indexing_data_ajax();
	}

	public function build_the_index_data_ajax() {
		$this->check_ajax_security();
		if ( ! $this->is_option_key_exists( $this->db_option_key . 'post_to_reindex' ) ) {
			$post_indexed = get_option( $this->db_option_key . 'post_indexed' );
			update_option( $this->db_option_key . 'post_to_reindex', $post_indexed );
		}
		if ( ! $this->is_option_key_exists( $this->db_option_key . 'term_to_reindex' ) ) {
			$term_indexed = get_option( $this->db_option_key . 'term_indexed' );
			update_option( $this->db_option_key . 'term_to_reindex', $term_indexed );
		}
		$this->indexing_data_ajax( 'index' );
	}

	public function check_ajax_security() {
		$security = ( isset( $_REQUEST['security'] ) && '' !== $_REQUEST['security'] ) ? $_REQUEST['security'] : '';
		if ( '' == $security || ! wp_verify_nonce( $security, 'admin-ajax-security' ) ) {
			wp_die();
		}
		return true;
	}

	public function clear_option_data_ajax() {
		$this->check_ajax_security();
		delete_option( $this->db_option_key . 'post_to_reindex' );
		delete_option( $this->db_option_key . 'term_to_reindex' );
		delete_option( $this->db_option_key . 'post_reindexed_count' );
		delete_option( $this->db_option_key . 'term_reindexed_count' );
		wp_send_json_success();
		wp_die();
	}

	public function indexing_data_ajax( $index_type = 'unindexed' ) {
		set_transient( 'press_search_ajax_indexing', true, 60 );
		$return = false;
		$has_reindex_report = false;
		if ( 'unindexed' == $index_type ) {
			$return = $this->index_data();
			$recall_ajax = ! $this->stop_index_data();
		} elseif ( 'index' == $index_type ) {
			$return = $this->reindex_data();
			$recall_ajax = ! $this->stop_reindex_data();
			$has_reindex_report = true;
		}
		delete_transient( 'press_search_ajax_indexing' );
		if ( $return ) {
			$progress_report = press_search_reports()->index_progress_report( false, $has_reindex_report );
			$json_args = array(
				'return' => 'insert_success',
				'recall_ajax' => $recall_ajax,
				'progress_report' => $progress_report,
			);
			wp_send_json_success( $json_args );
		} else {
			wp_send_json_error(
				array(
					'return' => 'insert_fail',
					'recall_ajax' => $recall_ajax,
				)
			);
		}
		wp_die();
	}

	public function get_indexing_progress() {
		$this->check_ajax_security();
		if ( false !== get_transient( 'press_search_ajax_indexing' ) ) {
			return;
		}
		$progress_report = press_search_reports()->index_progress_report( false );
		wp_send_json_success( array( 'progress_report' => $progress_report ) );
		wp_die();
	}

	public function add_custom_schedules( $schedules ) {
		$schedules['press_search_everyminute'] = array(
			'interval' => 60,
			'display' => esc_html__( 'Press Search Every Minute', 'press-search' ),
		);
		return $schedules;
	}

	public function cronjob_activation() {
		if ( ! wp_next_scheduled( 'press_search_indexing_cronjob' ) ) {
			wp_schedule_event( time(), 'press_search_everyminute', 'press_search_indexing_cronjob' );
		}
	}

	public function cronjob_deactivation() {
		$timestamp = wp_next_scheduled( 'press_search_indexing_cronjob' );
		wp_unschedule_event( $timestamp, 'press_search_indexing_cronjob' );
	}

	public function cron_index_data() {
		if ( false === get_transient( 'press_search_ajax_indexing' ) ) {
			// Only run cron job index when no ajax request.
			if ( ! $this->stop_index_data() ) {
				$this->index_data();
			}
		}
	}

	public function stop_index_data() {
		$need_index_posts = get_option( $this->db_option_key . 'post_to_index', array() );
		$need_index_terms = get_option( $this->db_option_key . 'term_to_index', array() );
		if ( empty( $need_index_posts ) && empty( $need_index_terms ) ) {
			return true;
		}
		return false;
	}

	public function is_stop_index_data() {
		return $this->stop_index_data();
	}

	public function stop_reindex_data() {
		$post_to_reindex = get_option( $this->db_option_key . 'post_to_reindex', array() );
		$term_to_reindex = get_option( $this->db_option_key . 'term_to_reindex', array() );
		if ( empty( $post_to_reindex ) && empty( $term_to_reindex ) && $this->stop_index_data() ) {
			return true;
		}
		return false;
	}

	public function index_data() {
		$need_index_posts = get_option( $this->db_option_key . 'post_to_index', array() );
		if ( ! empty( $need_index_posts ) ) {
			return $this->index_post_data();
		}
		return $this->index_term_data();
	}

	public function reindex_data() {
		$post_to_reindex = get_option( $this->db_option_key . 'post_to_reindex', array() );
		$term_to_reindex = get_option( $this->db_option_key . 'term_to_reindex', array() );
		if ( ! empty( $post_to_reindex ) ) {
			return $this->reindex_post_data();
		} elseif ( ! empty( $term_to_reindex ) ) {
			return $this->reindex_term_data();
		}
		return $this->index_data();
	}

	public function reindex_updated_object( $object_type = 'post', $object_id = 0 ) {
		$this->object_crawl_data->delete_indexed_object( $object_type, $object_id );
		switch ( $object_type ) {
			case 'post':
				$return = $this->object_crawl_data->insert_indexing_post( $object_id );
				break;
			case 'term':
				$return = $this->object_crawl_data->insert_indexing_term( $object_id );
				break;
			case 'user':
				$return = $this->object_crawl_data->insert_indexing_user( $object_id );
				break;
		}
		$this->remove_db_indexed_object( $object_type, $object_id );
		if ( $return ) {
			$this->add_db_indexed_object( $object_type, $object_id );
		}
		return $return;
	}

	public function reindex_updated_post( $post_id ) {
		return $this->reindex_updated_object( 'post', $post_id );
	}

	public function reindex_updated_term( $term_id, $taxonomy ) {
		return $this->reindex_updated_object( 'term', $term_id );
	}

	public function reindex_updated_user( $user_id, $old_user_data ) {
		return $this->reindex_updated_object( 'user', $user_id );
	}

	public function delete_indexed_post( $post_id ) {
		$result = $this->object_crawl_data->delete_indexed_object( 'post', $post_id );
		$this->remove_db_indexed_object( 'post', $post_id );
	}

	public function delete_indexed_term( $term_id ) {
		$this->object_crawl_data->delete_indexed_object( 'term', $term_id );
		$this->remove_db_indexed_object( 'term', $term_id );
	}

	public function delete_indexed_user( $user_id ) {
		$this->object_crawl_data->delete_indexed_object( 'user', $user_id );
		$this->remove_db_indexed_object( 'user', $user_id );
	}

	public function remove_db_indexed_object( $type = 'post', $object_id = 0 ) {
		switch ( $type ) {
			case 'post':
				$db_key = 'post_indexed';
				break;
			case 'term':
				$db_key = 'term_indexed';
				break;
			case 'user':
				$db_key = 'user_indexed';
				break;
		}
		$db_data = get_option( $this->db_option_key . $db_key, array() );
		$indexed_data = $this->array_filter( $db_data, $object_id );
		update_option( $this->db_option_key . $db_key, $indexed_data );
	}

	public function add_db_indexed_object( $type = 'post', $object_id = 0 ) {
		switch ( $type ) {
			case 'post':
				$db_key = 'post_indexed';
				break;
			case 'term':
				$db_key = 'term_indexed';
				break;
			case 'user':
				$db_key = 'user_indexed';
				break;
		}
		$db_data = get_option( $this->db_option_key . $db_key, array() );
		$db_data[] = $object_id;
		$indexed_data = array_unique( $db_data );
		update_option( $this->db_option_key . $db_key, $indexed_data );
	}
}

if ( ! function_exists( 'press_search_indexing' ) ) {
	function press_search_indexing() {
		return Press_Search_Indexing::instance();
	}
}
add_action( 'plugins_loaded', 'press_search_indexing_init', 2 );
function press_search_indexing_init() {
	$GLOBALS['press_search_indexing'] = press_search_indexing();
}
// $press_search_indexing = new Press_Search_Indexing();
register_deactivation_hook( __FILE__, array( press_search_indexing(), 'cronjob_deactivation' ) );

