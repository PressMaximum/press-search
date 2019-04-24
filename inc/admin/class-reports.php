<?php

class Press_Search_Reports {
	/**
	 * The single instance of the class
	 *
	 * @var Press_Search_Reports
	 * @since 0.1.0
	 */
	protected static $_instance = null;

	protected $db_option_key = 'press_search_';
	/**
	 * Instance
	 *
	 * @return Press_Search_Reports
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->db_option_key = press_search_get_var( 'db_option_key' );
		add_action( 'load-presssearch_page_press-search-report', array( $this, 'custom_screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
		add_action( 'press_search_report_filters_bars', array( $this, 'get_report_filter_bar' ), 10, 4 );
		add_action( 'press_search_before_render_report_tab', array( $this, 'notice_upgrade_to_pro' ) );
	}

	public function get_indexing_progress() {
		$db_data = array();
		$object_index_count = get_option( $this->db_option_key . 'index_count', array() );
		$db_data = array(
			'post_unindex' => ( ! empty( $object_index_count ) && isset( $object_index_count['post']['un_indexed'] ) ) ? $object_index_count['post']['un_indexed'] : 0,
			'post_indexed' => ( ! empty( $object_index_count ) && isset( $object_index_count['post']['indexed'] ) ) ? $object_index_count['post']['indexed'] : 0,
			'term_unindex' => ( ! empty( $object_index_count ) && isset( $object_index_count['term']['un_indexed'] ) ) ? $object_index_count['term']['un_indexed'] : 0,
			'term_indexed' => ( ! empty( $object_index_count ) && isset( $object_index_count['term']['indexed'] ) ) ? $object_index_count['term']['indexed'] : 0,
			'user_unindex' => ( ! empty( $object_index_count ) && isset( $object_index_count['user']['un_indexed'] ) ) ? $object_index_count['user']['un_indexed'] : 0,
			'user_indexed' => ( ! empty( $object_index_count ) && isset( $object_index_count['user']['indexed'] ) ) ? $object_index_count['user']['indexed'] : 0,
			'attachment_unindex' => ( ! empty( $object_index_count ) && isset( $object_index_count['attachment']['un_indexed'] ) ) ? $object_index_count['attachment']['un_indexed'] : 0,
			'attachment_indexed' => ( ! empty( $object_index_count ) && isset( $object_index_count['attachment']['indexed'] ) ) ? $object_index_count['attachment']['indexed'] : 0,
		);
		$total_posts = $db_data['post_unindex'] + $db_data['post_indexed'];
		$total_terms = $db_data['term_unindex'] + $db_data['term_indexed'];
		$total_users = $db_data['user_unindex'] + $db_data['user_indexed'];
		$total_attachments = $db_data['attachment_unindex'] + $db_data['attachment_indexed'];
		$total_items = $total_posts + $total_terms + $total_users + $total_attachments;
		$total_items_indexed = $db_data['post_indexed'] + $db_data['term_indexed'] + $db_data['user_indexed'] + $db_data['attachment_indexed'];
		$percent_progress = 0;
		if ( $total_items > 0 ) {
			$percent_progress = ( $total_items_indexed / $total_items ) * 100;
		}
		$return = array(
			'percent_progress'      => ( is_float( $percent_progress ) ) ? number_format( $percent_progress, 2 ) : $percent_progress,
			'post_indexed'          => $db_data['post_indexed'],
			'post_unindex'          => $db_data['post_unindex'],
			'term_indexed'          => $db_data['term_indexed'],
			'term_unindex'          => $db_data['term_unindex'],
			'user_indexed'          => $db_data['user_indexed'],
			'user_unindex'          => $db_data['user_unindex'],
			'attachment_indexed'    => $db_data['attachment_indexed'],
			'attachment_unindex'    => $db_data['attachment_unindex'],
			'last_activity'     => get_option( $this->db_option_key . 'last_time_index', esc_html__( 'No data', 'press-search' ) ),
		);
		return $return;
	}

	public function engines_static_report() {
		global $press_search_indexing;
		?>
		<div class="engine-statistic">
			<div class="engine-index-progess report-box">
				<h3 class="index-progess-heading report-heading"><?php esc_html_e( 'Index Progress', 'press-search' ); ?></h3>
				<div class="index-progress-wrap">
					<?php $this->index_progress_report(); ?>
				</div>
				<?php
				$unindexed_class = '';
				if ( $press_search_indexing->stop_index_data() ) {
					$unindexed_class = 'prevent-click';
				}
				?>
				<div class="index-progess-buttons">
					<a class="build-index custom-btn button install-now" id="build_data_index" href="#"><?php esc_html_e( 'Build The Index', 'press-search' ); ?></a>
					<a class="build-unindexed custom-btn button install-now <?php echo esc_attr( $unindexed_class ); ?>" id="build_data_unindexed" href="#"><?php esc_html_e( 'Build Unindexed', 'press-search' ); ?></a>
				</div>
			</div>
			<?php $this->engine_stats_report(); ?>
		</div>
		<?php
	}

	public function index_progress_report( $echo = true, $reindex = false ) {
		$progress = $this->get_indexing_progress();
		ob_start();
		?>
		<div class="progress-bar animate blue">
			<span data-width="<?php echo esc_attr( $progress['percent_progress'] ); ?>" style="width: <?php echo esc_attr( $progress['percent_progress'] ); ?>%" data-percent="<?php echo esc_attr( $progress['percent_progress'] ); ?>%"></span>
		</div>
		<ul class="index-progess-list report-list">
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['post_indexed'] ), _n( 'Entry', 'Entries', $progress['post_indexed'], 'press-search' ), esc_html__( ' in the index.', 'press-search' ) );
				?>
			</li>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['term_indexed'] ), _n( 'Term', 'Terms', $progress['term_indexed'], 'press-search' ), esc_html__( ' in the index.', 'press-search' ) );
				?>
			</li>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['user_indexed'] ), _n( 'User', 'Users', $progress['user_indexed'], 'press-search' ), esc_html__( ' in the index.', 'press-search' ) );
				?>
			</li>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['attachment_indexed'] ), _n( 'Attachment', 'Attachments', $progress['attachment_indexed'], 'press-search' ), esc_html__( ' in the index.', 'press-search' ) );
				?>
			</li>
			<?php if ( $progress['post_unindex'] > 0 ) { ?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['post_unindex'] ), _n( 'Entry', 'Entries', $progress['post_unindex'], 'press-search' ), esc_html__( ' unindexed.', 'press-search' ) );
				?>
			</li>
			<?php } ?>
			<?php if ( $progress['term_unindex'] > 0 ) { ?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['term_unindex'] ), _n( 'Term', 'Terms', $progress['term_unindex'], 'press-search' ), esc_html__( ' unindexed.', 'press-search' ) );
				?>
			</li>
			<?php } ?>
			<?php if ( $progress['user_unindex'] > 0 ) { ?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['user_unindex'] ), _n( 'User', 'Users', $progress['user_unindex'], 'press-search' ), esc_html__( ' unindexed.', 'press-search' ) );
				?>
			</li>
			<?php } ?>
			<?php if ( $progress['attachment_unindex'] > 0 ) { ?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s %s', esc_html( $progress['attachment_unindex'] ), _n( 'Attachment', 'Attachments', $progress['attachment_unindex'], 'press-search' ), esc_html__( ' unindexed.', 'press-search' ) );
				?>
			</li>
			<?php } ?>
			<?php
			if ( isset( $reindex ) && $reindex ) {
				$object_reindex_count = get_option( $this->db_option_key . 'object_reindex_count', array() );

				$post_reindexed_count = ( isset( $object_reindex_count['post'] ) && is_array( $object_reindex_count['post'] ) ) ? count( array_unique( $object_reindex_count['post'] ) ) : 0;
				$term_reindexed_count = ( isset( $object_reindex_count['term'] ) && is_array( $object_reindex_count['term'] ) ) ? count( array_unique( $object_reindex_count['term'] ) ) : 0;
				$user_reindexed_count = ( isset( $object_reindex_count['user'] ) && is_array( $object_reindex_count['user'] ) ) ? count( array_unique( $object_reindex_count['user'] ) ) : 0;
				$attachment_reindexed_count = ( isset( $object_reindex_count['attachment'] ) && is_array( $object_reindex_count['attachment'] ) ) ? count( array_unique( $object_reindex_count['attachment'] ) ) : 0;

				if ( $post_reindexed_count > 0 ) {
					?>
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s %s', esc_html( $post_reindexed_count ), _n( 'Entry', 'Entries', $post_reindexed_count, 'press-search' ), esc_html__( ' re-indexed.', 'press-search' ) );
						?>
					</li>
					<?php
				}

				if ( $term_reindexed_count > 0 ) {
					?>
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s %s', esc_html( $term_reindexed_count ), _n( 'Term', 'Terms', $term_reindexed_count, 'press-search' ), esc_html__( ' re-indexed.', 'press-search' ) );
						?>
					</li>
					<?php
				}

				if ( $user_reindexed_count > 0 ) {
					?>
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s %s', esc_html( $user_reindexed_count ), _n( 'User', 'Users', $user_reindexed_count, 'press-search' ), esc_html__( ' re-indexed.', 'press-search' ) );
						?>
					</li>
					<?php
				}

				if ( $attachment_reindexed_count > 0 ) {
					?>
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s %s', esc_html( $attachment_reindexed_count ), _n( 'Attachment', 'Attachments', $attachment_reindexed_count, 'press-search' ), esc_html__( ' re-indexed.', 'press-search' ) );
						?>
					</li>
					<?php
				}
			}
			?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s', esc_html__( 'Last activity: ', 'press-search' ), esc_html( $progress['last_activity'] ) );
				?>
			</li>
		</ul>
		<?php
		$content = ob_get_contents();
		ob_get_clean();
		if ( $echo ) {
			echo wp_kses_post( $content );
		} else {
			return $content;
		}
	}

	public function get_today_number_searches() {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$today = date( 'Y-m-d' );

		$return = array();
		$return = 0;
		$count = $wpdb->get_var( "SELECT COUNT( query ) AS total FROM {$table_logs_name} WHERE DATE(`date_time`) = CURDATE()" ); // WPCS: unprepared SQL OK.
		if ( is_numeric( $count ) && $count > 0 ) {
			$return = $count;
		}
		return $return;
	}

	public function get_today_number_searches_no_result() {
		global $wpdb;
		$table_logs_name = press_search_get_var( 'tbl_logs' );
		$today = date( 'Y-m-d' );
		$return = array();
		$return = 0;
		$count = $wpdb->get_var( "SELECT COUNT( query ) AS total FROM {$table_logs_name} WHERE DATE(`date_time`) = CURDATE() AND hits = 0" ); // WPCS: unprepared SQL OK.
		if ( is_numeric( $count ) && $count > 0 ) {
			$return = $count;
		}
		return $return;
	}

	public function engine_stats_report() {
		$count = $this->get_today_number_searches();
		$count_no_hits = $this->get_today_number_searches_no_result();
		$link_args = array(
			'page' => 'press-search-report'
		);
		$view_detail = add_query_arg( $link_args, admin_url( 'admin.php' ) );
		?>
		<div class="engine-stats report-box">
			<h3 class="stats-heading report-heading"><?php esc_html_e( 'Stats', 'press-search' ); ?></h3>
			<ul class="stats-list report-list">
				<li class="stat-item report-item"><?php echo sprintf( '%d %s', $count, esc_html__( 'Searches today.', 'press-search' ) ); ?></li>
				<?php if ( $count_no_hits > 0 ) { ?>
					<li class="stat-item report-item"><?php echo sprintf( '%d %s', $count_no_hits, esc_html__( 'Searches with no results.', 'press-search' ) ); ?></li>
				<?php } ?>
			</ul>
			<a class="stats-detail custom-btn" href="<?php echo esc_url( $view_detail ); ?>"><?php esc_html_e( 'View Details', 'press-search' ); ?></a>
		</div>
		<?php
	}

	public function get_popular_search( $limit = 20, $orderby = 'query_count', $order = 'desc' ) {
		if ( ps_is__pro() && function_exists( 'press_search_report_pro' ) ) {
			$result = press_search_report_pro()->get_popular_search( $limit, $orderby, $order );
		} else {
			$result = press_search_report_faker()->get_popular_search( $limit );
		}
		return $result;
	}

	public function get_no_results_search( $limit = 20, $orderby = 'date_time', $order = 'desc' ) {
		if ( ps_is__pro() && function_exists( 'press_search_report_pro' ) ) {
			$result = press_search_report_pro()->get_no_results_search( $limit, $orderby, $order );
		} else {
			$result = press_search_report_faker()->get_no_results_search( $limit );
		}
		return $result;
	}

	public function engines_tab_content() {
		do_action( 'press_search_before_render_report_tab', 'overview' );
		$filter_search_engine = 'all';
		$filter_date = '';
		if ( isset( $_GET['search_engine'] ) ) {
			$filter_search_engine = sanitize_text_field( wp_unslash( $_GET['search_engine'] ) );
		}

		if ( isset( $_GET['date'] ) ) {
			$filter_date = sanitize_text_field( wp_unslash( $_GET['date'] ) );
			if ( false !== strpos( $filter_date, 'to' ) ) {
				$filter_date = explode( 'to', $filter_date );
			}
		}
		$all_engines_name = press_search_engines()->get_all_engines_name();
		$filter_link_args = array();
		if ( isset( $_GET ) ) {
			$get_vars = array_map( 'sanitize_text_field', wp_unslash( $_GET ) );
			if ( is_array( $get_vars ) && ! empty( $get_vars ) ) {
				foreach ( $get_vars as $_key => $_val ) {
					$filter_link_args[ $_key ] = $_val;
				}
			}
		}
		if ( isset( $filter_link_args['paged'] ) ) {
			unset( $filter_link_args['paged'] );
		}

		$pass_args = array(
			'all_engines_name' => $all_engines_name,
			'filter_date' => $filter_date,
			'filter_search_engine' => $filter_search_engine,
			'filter_link_args' => $filter_link_args,
		);
		press_search_get_template( 'reports/overview.php', $pass_args );
	}

	public function engines_search_log_content() {
		do_action( 'press_search_before_render_report_tab', 'search_logs' );
		press_search_report_search_logs()->prepare_items();
		press_search_report_search_logs()->display();
	}

	public function engines_popular_search_content() {
		do_action( 'press_search_before_render_report_tab', 'popular_searches' );
		press_search_report_table_popular_searches()->prepare_items();
		press_search_report_table_popular_searches()->display();
	}

	public function engines_no_results_content() {
		do_action( 'press_search_before_render_report_tab', 'no_results' );
		press_search_report_table_no_results()->prepare_items();
		press_search_report_table_no_results()->display();
	}

	public function logging_subtab_report_content() {
		esc_html_e( 'Logging subtab reports content', 'press-search' );
	}

	public function render_search_logs_table( $limit = 20, $enable_count = true ) {
		$result = $this->get_search_logs( $limit );
		$data = array(
			'result' => $result,
			'enable_count' => $enable_count,
		);
		press_search_get_template( 'reports/searches-logs.php', $data );
	}

	public function render_popular_search_table( $limit = 20, $enable_count = true ) {
		$result = $this->get_popular_search( $limit );
		$data = array(
			'result' => $result,
			'enable_count' => $enable_count,
		);
		press_search_get_template( 'reports/popular-searches.php', $data );
	}

	public function render_no_search_table( $limit = 20, $enable_count = true ) {
		$result = $this->get_no_results_search( $limit );
		$data = array(
			'result' => $result,
			'enable_count' => $enable_count,
		);
		press_search_get_template( 'reports/no-results.php', $data );
	}

	public function get_search_logs( $limit = 20, $args = array() ) {
		if ( ps_is__pro() && function_exists( 'press_search_report_pro' ) ) {
			$result = press_search_report_pro()->get_search_logs( $limit, $args );
		} else {
			$result = press_search_report_faker()->get_search_logs( $limit );
		}
		return $result;
	}

	public function search_logs_for_chart() {
		if ( ps_is__pro() && function_exists( 'press_search_report_pro' ) ) {
			$result = press_search_report_pro()->search_logs_for_chart();
		} else {
			$result = press_search_report_faker()->search_logs_for_chart();
		}
		return $result;
	}

	public function custom_screen_options() {
		$arguments = array(
			'label'     => esc_html__( 'Items Per Page', 'press-search' ),
			'default'   => 20,
			'option'    => 'press_search_report_items_per_page',
		);
		add_screen_option( 'per_page', $arguments );
	}

	public function get_screen_items_per_page() {
		$per_page = get_user_meta( get_current_user_id(), 'press_search_report_items_per_page', true );
		if ( is_numeric( $per_page ) && $per_page > 0 ) {
			return $per_page;
		}
		return 20;
	}

	public function set_screen_option( $status, $option, $value ) {
		if ( 'press_search_report_items_per_page' == $option ) {
			return $value;
		}
		return $status;
	}

	public function get_report_filter_bar( $all_engines_name, $filter_link_args, $filter_date, $filter_search_engine ) {
		if ( ps_is__pro() ) {
			$args = array(
				'all_engines_name' => $all_engines_name,
				'filter_link_args' => $filter_link_args,
				'filter_date' => $filter_date,
				'filter_search_engine' => $filter_search_engine,
			);
			press_search_get_template( 'reports/filters-bar-pro.php', $args );
		} else {
			press_search_get_template( 'reports/filters-bar.php', array() );
		}
	}

	public function notice_upgrade_to_pro() {
		if ( ! ps_is__pro() ) {
			press_search_upgrade_notice();
		}
	}
}





