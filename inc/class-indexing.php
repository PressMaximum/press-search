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
	public function __construct() {
		$this->ajax_url = admin_url( 'admin-ajax.php' );
		add_action( 'wp', array( $this, 'cronjob_activation' ) );
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		add_action( 'press_search_indexing_cronjob', array( $this, 'cron_index_data' ) );
		// do_action_ref_array( $event->hook, $event->args ) .
		// wp_unschedule_event( $event->next_call, $event->hook, $event->args ).
		add_action( 'init', array( $this, 'init' ), 1 );
		add_action( 'wp_ajax_build_unindexed_data_ajax', array( $this, 'build_unindexed_data_ajax' ) );
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

	public function index_post_data() {
		$return = false;
		$post_to_index = get_option( $this->db_option_key . 'post_to_index', array() );
		if ( ! empty( $post_to_index ) ) {
			$first_post_id = array_shift( $post_to_index );
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
		return $return;
	}

	public function index_term_data() {
		$term_to_index = get_option( $this->db_option_key . 'term_to_index', array() );
		$return = false;
		if ( ! empty( $term_to_index ) ) {
			$first_id = array_shift( $term_to_index );
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
		return $return;
	}

	public function index_user_data() {

	}

	public function last_time_index() {
		update_option( $this->db_option_key . 'last_time_index', current_time( 'mysql' ) );
	}

	public function build_unindexed_data_ajax() {
		$security = ( isset( $_REQUEST['security'] ) && '' !== $_REQUEST['security'] ) ? $_REQUEST['security'] : '';
		if ( '' == $security || ! wp_verify_nonce( $security, 'admin-ajax-security' ) ) {
			return;
		}
		$recall_ajax = ! $this->stop_index_data();
		set_transient( 'press_search_ajax_indexing', true, 60 );
		$return = $this->index_data();
		delete_transient( 'press_search_ajax_indexing' );
		if ( $return ) {
			wp_send_json_success(
				array(
					'return' => 'insert_success',
					'recall_ajax' => $recall_ajax,
				)
			);
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

	public function index_data() {
		$need_index_posts = get_option( $this->db_option_key . 'post_to_index', array() );
		if ( ! empty( $need_index_posts ) ) {
			return $this->index_post_data();
		}
		return $this->index_term_data();
	}
}

$press_search_indexing = new Press_Search_Indexing();

register_deactivation_hook( __FILE__, array( $press_search_indexing, 'cronjob_deactivation' ) );

