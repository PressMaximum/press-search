<?php
class Press_Search_Indexing {
	protected $db_option_key = 'press_search_';
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
			define( 'PRESS_SEARCH_MAX_ITEM_TO_INDEX', 5 );
		}
		$index_settings = press_search_engines()->__get( 'index_settings' );
		$this->object_crawl_data = new Press_Search_Crawl_Data(
			array(
				'settings' => $index_settings,
			)
		);

		add_action( 'press_search_indexing_cronjob', array( $this, 'cron_index_data' ) );
		add_action( 'init', array( $this, 'init' ), 100 );
		add_action( 'wp_ajax_build_unindexed_data_ajax', array( $this, 'build_unindexed_data_ajax' ) );
		add_action( 'wp_ajax_build_the_index_data_ajax', array( $this, 'build_the_index_data_ajax' ) );
		add_action( 'wp_ajax_get_indexing_progress', array( $this, 'get_indexing_progress' ) );

		// add_action( 'save_post', array( $this, 'reindex_updated_post' ), PHP_INT_MAX );
		// add_action( 'delete_post', array( $this, 'delete_indexed_post' ), PHP_INT_MAX );
		// add_action( 'edited_terms', array( $this, 'reindex_updated_term' ), PHP_INT_MAX );
		// add_action( 'delete_term', array( $this, 'delete_indexed_term' ), PHP_INT_MAX, 3 );
		// add_action( 'profile_update', array( $this, 'reindex_updated_user' ), PHP_INT_MAX, 2 );
		// add_action( 'deleted_user', array( $this, 'delete_indexed_user' ), PHP_INT_MAX );
		// add_action( 'delete_attachment', array( $this, 'delete_indexed_attachment' ), PHP_INT_MAX );
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

	/**
	 * Init method
	 *
	 * @return void
	 */
	public function init() {

	}

	public function index_object_data( $object_type = 'post', $objec_to_index = array(), $re_index = false ) {
		$return = array();
		switch ( $object_type ) {
			case 'post':
				$method_get_object_ids = 'get_can_index_post_ids';
				break;
			case 'term':
				$method_get_object_ids = 'get_can_index_term_ids';
				break;
			case 'user':
				$method_get_object_ids = 'get_can_index_user_ids';
				break;
			case 'attachment':
				$method_get_object_ids = 'get_can_index_attachment_ids';
				break;
		}
		if ( empty( $object_to_index ) ) {
			$type = 'un_indexed';
			if ( $re_index ) {
				$type = 'indexed';
			}
			$object_to_index = $this->object_crawl_data->$method_get_object_ids( $type );
		}
		if ( ! empty( $object_to_index ) ) {
			foreach ( $object_to_index as $object_id ) {
				if ( is_string( get_post_status( $object_id ) ) ) {
					if ( $re_index ) {
						$this->update_object_meta_unindexed( $object_type, $object_id );
					}
					$result = $this->object_crawl_data->insert_indexing_object( $object_type, $object_id );
					if ( $result ) {
						update_object_meta_indexed( $object_type, $object_id );
						$this->last_time_index();
						$return = true;
					} else {
						$return[] = sprintf( '%s %s %d %s', esc_html__( 'Index', 'press-search' ), $object_type, $object_id, esc_html__( 'fail', 'press-search' ) );
					}
				}
			}
		}
		return $return;
	}

	public function update_object_meta_indexed( $object_type = 'post', $object_id = 0 ) {
		$this->update_object_meta( $object_type, $object_id, 'ps_indexed', 'yes' );
	}

	public function update_object_meta_unindexed( $object_type = 'post', $object_id = 0 ) {
		$this->update_object_meta( $object_type, $object_id, 'ps_indexed', 'no' );
	}

	public function update_object_meta( $object_type = 'post', $object_id = 0, $meta_key = '', $meta_value = '' ) {
		switch ( $object_type ) {
			case 'post':
			case 'attachment':
				update_post_meta( $object_id, $meta_key, $meta_value );
				break;
			case 'term':
				update_term_meta( $object_id, $meta_key, $meta_value );
				break;
			case 'user':
				update_user_meta( $object_id, $meta_key, $meta_value );
				break;
		}
	}

	/**
	 * Update last index time
	 *
	 * @return void
	 */
	public function last_time_index() {
		update_option( $this->db_option_key . 'last_time_index', current_time( 'mysql' ) );
	}

	/**
	 * Index progress report
	 *
	 * @return mixed array or string
	 */
	public function index_progress_report() {
		$static_report = '';
		if ( function_exists( 'press_search_reports' ) ) {
			$static_report = press_search_reports()->engines_static_report( $this->object_crawl_data );
		}
		return $static_report;
	}

	/**
	 * Build un-indexed data
	 *
	 * @return void
	 */
	public function build_unindexed_data_ajax() {
		$this->check_ajax_security();
		$this->indexing_data_ajax();
	}

	/**
	 * Build the index data ajax: include un-index data and indexed data
	 *
	 * @return void
	 */
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

		if ( ! $this->is_option_key_exists( $this->db_option_key . 'user_to_reindex' ) ) {
			$user_indexed = get_option( $this->db_option_key . 'user_indexed' );
			update_option( $this->db_option_key . 'user_to_reindex', $user_indexed );
		}

		if ( ! $this->is_option_key_exists( $this->db_option_key . 'attachment_to_reindex' ) ) {
			if ( $this->is_option_key_exists( $this->db_option_key . 'attachment_indexed' ) ) {
				$attachment_indexed = get_option( $this->db_option_key . 'attachment_indexed' );
				update_option( $this->db_option_key . 'attachment_to_reindex', $attachment_indexed );
			}
		}
		$this->indexing_data_ajax( 'index' );
	}

	/**
	 * Check ajax security nonce
	 *
	 * @return boolean
	 */
	public function check_ajax_security() {
		$security = ( isset( $_REQUEST['security'] ) && '' !== $_REQUEST['security'] ) ? $_REQUEST['security'] : '';
		if ( '' == $security || ! wp_verify_nonce( $security, 'admin-ajax-security' ) ) {
			wp_die();
		}
		return true;
	}

	/**
	 * Indexing data via ajax
	 *
	 * @param string $index_type
	 * @return void
	 */
	public function indexing_data_ajax( $index_type = 'unindexed' ) {
		$insert_fail_count = get_transient( 'press_search_ajax_indexing_fail_count' );
		set_transient( 'press_search_ajax_indexing', true, 60 );
		$return = false;
		$has_reindex_report = false;
		if ( 'unindexed' == $index_type ) {
			$return = $this->index_data();
			$recall_ajax = ! $this->stop_index_data();
		} else {
			$return = $this->reindex_data();
			$recall_ajax = ! $this->stop_reindex_data();
			$has_reindex_report = true;
		}
		delete_transient( 'press_search_ajax_indexing' );

		if ( is_numeric( $insert_fail_count ) && $insert_fail_count > 60 ) {
			// If insert fail many time -> stop ajax.
			set_transient( 'press_search_ajax_indexing_fail_count', 1, 60 );
			wp_send_json_error(
				array(
					'return' => 'insert_fail_too_many_times',
					'fail_count' => $insert_fail_count,
					'recall_ajax' => false,
				)
			);
		}

		if ( $return ) {
			$progress_report = press_search_reports()->index_progress_report( false, $has_reindex_report );
			$json_args = array(
				'return' => 'insert_success',
				'recall_ajax' => $recall_ajax,
				'progress_report' => $progress_report,
			);
			wp_send_json_success( $json_args );
		} else {
			// Mark the number fail times to break.
			if ( is_numeric( $insert_fail_count ) ) {
				$insert_fail_count += 1;
				set_transient( 'press_search_ajax_indexing_fail_count', $insert_fail_count, 60 );
			} else {
				set_transient( 'press_search_ajax_indexing_fail_count', 1, 60 );
			}

			wp_send_json_error(
				array(
					'return'      => 'insert_fail',
					'recall_ajax' => $recall_ajax,
					'ajax_return' => $return,
				)
			);
		}
	}

	/**
	 * Ajax indexing progress
	 *
	 * @return void
	 */
	public function get_indexing_progress() {
		$this->check_ajax_security();
		if ( false !== get_transient( 'press_search_ajax_indexing' ) ) {
			return;
		}
		$progress_report = press_search_reports()->index_progress_report( $this->object_crawl_data, false );
		wp_send_json_success( array( 'progress_report' => $progress_report ) );
	}

	/**
	 * Cronjob index data
	 *
	 * @return void
	 */
	public function cron_index_data() {
		if ( false === get_transient( 'press_search_ajax_indexing' ) ) {
			// Only run cron job index when no ajax request.
			if ( ! $this->stop_index_data() ) {
				$this->index_data();
			}
		}
	}

	/**
	 * Check if all data need to index are empty
	 *
	 * @return boolean
	 */
	public function stop_index_data() {
		$need_index_posts = $this->object_crawl_data->get_can_index_post_ids();
		$need_index_terms = $this->object_crawl_data->get_can_index_term_ids();
		$need_index_users = $this->object_crawl_data->get_can_index_user_ids();
		$need_index_attachment = $this->object_crawl_data->get_can_index_attachment_ids();
		if ( empty( $need_index_posts ) && empty( $need_index_terms ) && empty( $need_index_users ) && empty( $need_index_attachment ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check stop re-index data
	 *
	 * @return boolean
	 */
	public function stop_reindex_data() {
		$post_to_reindex = $this->object_crawl_data->get_can_index_post_ids( true );
		$term_to_reindex = $this->object_crawl_data->get_can_index_term_ids( true );
		$user_to_reindex = $this->object_crawl_data->get_can_index_user_ids( true );
		$attachment_to_reindex = $this->object_crawl_data->get_can_index_attachment_ids( true );
		if ( empty( $post_to_reindex ) && empty( $term_to_reindex ) && empty( $user_to_reindex ) && empty( $attachment_to_reindex ) && $this->stop_index_data() ) {
			return true;
		}
		return false;
	}

	/**
	 * Index data
	 *
	 * @return boolean
	 */
	public function index_data() {
		$need_index_posts = $this->object_crawl_data->get_can_index_post_ids();
		$need_index_terms = $this->object_crawl_data->get_can_index_term_ids();
		$need_index_users = $this->object_crawl_data->get_can_index_user_ids();
		if ( ! empty( $need_index_posts ) ) {
			return $this->index_object_data( 'post', $need_index_posts );
		} elseif ( ! empty( $need_index_terms ) ) {
			return $this->index_object_data( 'term', $need_index_terms );
		} elseif ( ! empty( $need_index_users ) ) {
			return $this->index_object_data( 'user', $need_index_users );
		}
		return $this->index_object_data( 'attachment' );
	}

	/**
	 * Re-index data
	 *
	 * @return boolean
	 */
	public function reindex_data() {
		$post_to_reindex = $this->object_crawl_data->get_can_index_post_ids( true );
		$term_to_reindex = $this->object_crawl_data->get_can_index_term_ids( true );
		$user_to_reindex = $this->object_crawl_data->get_can_index_user_ids( true );
		$attachment_to_reindex = $this->object_crawl_data->get_can_index_attachment_ids( true );
		if ( ! empty( $post_to_reindex ) ) {
			return $this->index_object_data( 'post', $post_to_reindex, true );
		} elseif ( ! empty( $term_to_reindex ) ) {
			return $this->index_object_data( 'term', $term_to_reindex, true );
		} elseif ( ! empty( $user_to_reindex ) ) {
			return $this->index_object_data( 'user', $user_to_reindex, true );
		} elseif ( ! empty( $attachment_to_reindex ) ) {
			return $this->index_object_data( 'attachment', $attachment_to_reindex, true );
		}
		return $this->index_data();
	}

}

