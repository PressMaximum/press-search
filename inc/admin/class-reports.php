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

	}

	public function get_indexing_progress() {
		$db_data = array(
			'post_unindex' => get_option( $this->db_option_key . 'post_to_index', array() ),
			'post_indexed' => get_option( $this->db_option_key . 'post_indexed', array() ),
			'term_unindex' => get_option( $this->db_option_key . 'term_to_index', array() ),
			'term_indexed' => get_option( $this->db_option_key . 'term_indexed', array() ),
		);
		foreach ( $db_data as $k => $v ) {
			$db_data[ $k ] = count( $v );
		}
		$total_posts = $db_data['post_unindex'] + $db_data['post_indexed'];
		$total_terms = $db_data['term_unindex'] + $db_data['term_indexed'];
		$total_items = $total_posts + $total_terms;
		$total_items_indexed = $db_data['post_indexed'] + $db_data['term_indexed'];
		$percent_progress = ( $total_items_indexed / $total_items ) * 100;
		$return = array(
			'percent_progress'  => ( is_float( $percent_progress ) ) ? number_format( $percent_progress, 2 ) : $percent_progress,
			'post_indexed'      => $db_data['post_indexed'],
			'post_unindex'      => $db_data['post_unindex'],
			'term_indexed'      => $db_data['term_indexed'],
			'term_unindex'      => $db_data['term_unindex'],
			'last_activity'     => get_option( $this->db_option_key . 'last_time_index', esc_html__( 'No data', 'press-search' ) ),
		);
		return $return;
	}

	public function engines_static_report() {
		$press_search_indexing = $GLOBALS['press_search_indexing'];
		?>
		<div class="engine-statistic">
			<div class="engine-index-progess report-box">
				<h3 class="index-progess-heading report-heading"><?php esc_html_e( 'Index Progress', 'press-search' ); ?></h3>
				<div class="index-progress-wrap">
					<?php $this->index_progress_report(); ?>
				</div>
				<?php
				$unindexed_class = '';
				if ( $press_search_indexing->is_stop_index_data() ) {
					$unindexed_class = 'prevent-click';
				}
				?>
				<div class="index-progess-buttons">
					<a class="build-index custom-btn" id="build_data_index" href="#"><?php esc_html_e( 'Build The Index', 'press-search' ); ?></a>
					<a class="build-unindexed custom-btn <?php echo esc_attr( $unindexed_class ); ?>" id="build_data_unindexed" href="#"><?php esc_html_e( 'Build Unindexed', 'press-search' ); ?></a>
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
			<span style="width: <?php echo esc_attr( $progress['percent_progress'] ); ?>%"></span>
		</div>
		<ul class="index-progess-list report-list">
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s', esc_html( $progress['post_indexed'] ), esc_html__( 'Entries in the index.', 'press-search' ) );
				?>
			</li>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s', esc_html( $progress['term_indexed'] ), esc_html__( 'Terms in the index.', 'press-search' ) );
				?>
			</li>
			<?php if ( $progress['post_unindex'] > 0 ) { ?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s', esc_html( $progress['post_unindex'] ), esc_html__( 'Entries unindexed.', 'press-search' ) );
				?>
			</li>
			<?php } ?>
			<?php if ( $progress['term_unindex'] > 0 ) { ?>
			<li class="index-progess-item report-item">
				<?php
					echo sprintf( '%s %s', esc_html( $progress['term_unindex'] ), esc_html__( 'Terms unindexed.', 'press-search' ) );
				?>
			</li>
			<?php } ?>
			<?php
			if ( isset( $reindex ) && $reindex ) {
				$post_reindexed_count = get_option( $this->db_option_key . 'post_reindexed_count', 0 );
				$term_reindexed_count = get_option( $this->db_option_key . 'term_reindexed_count', 0 );

				if ( $post_reindexed_count > 0 ) {
					?>
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s', esc_html( $post_reindexed_count ), esc_html__( 'Entries re-indexed.', 'press-search' ) );
						?>
					</li>
					<?php
				}

				if ( $term_reindexed_count > 0 ) {
					?>
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s', esc_html( $term_reindexed_count ), esc_html__( 'Terms re-indexed.', 'press-search' ) );
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

	public function engine_stats_report() {
		?>
		<div class="engine-stats report-box">
			<h3 class="stats-heading report-heading"><?php esc_html_e( 'Stats', 'press-search' ); ?></h3>
			<ul class="stats-list report-list">
				<li class="stat-item report-item"><?php esc_html_e( '304 Searches today.', 'press-search' ); ?></li>
				<li class="stat-item report-item"><?php esc_html_e( '100 Searches with no results.', 'press-search' ); ?></li>
			</ul>
			<a class="stats-detail custom-btn" href="#"><?php esc_html_e( 'View Details', 'press-search' ); ?></a>
		</div>
		<?php
	}

	public function engines_tab_content() {
		esc_html_e( 'Engines tab content', 'press-search' );
	}

	public function engines_popular_search_content() {
		esc_html_e( 'Popular searches tab content', 'press-search' );
	}

	public function engines_no_results_content() {
		esc_html_e( 'No result tab content', 'press-search' );
	}

	public function logging_subtab_report_content() {
		esc_html_e( 'Logging subtab reports content', 'press-search' );
	}

}
