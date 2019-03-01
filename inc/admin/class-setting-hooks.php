<?php
class Press_Search_Setting_Hooks {
	public function __construct() {
		add_action( 'press_search_after__press-search-settings_engines_content', array( $this, 'tab_engines_static_report' ), 10 );
	}

	public function tab_engines_static_report() {
		$progress = press_search_reports()->get_indexing_progress();
		?>
		<div class="engine-statistic">
			<div class="engine-index-progess report-box">
				<h3 class="index-progess-heading report-heading"><?php esc_html_e( 'Index Progress', 'press-search' ); ?></h3>
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
					<li class="index-progess-item report-item">
						<?php
							echo sprintf( '%s %s', esc_html__( 'Last activity: ', 'press-search' ), esc_html( $progress['last_activity'] ) );
						?>
					</li>
				</ul>
				<div class="index-progess-buttons">
					<a class="build-index custom-btn" id="build_data_index" href="#"><?php esc_html_e( 'Build The Index', 'press-search' ); ?></a>
					<a class="build-unindexed custom-btn" id="build_data_unindexed" href="#"><?php esc_html_e( 'Build Unindexed', 'press-search' ); ?></a>
				</div>
			</div>
			<div class="engine-stats report-box">
				<h3 class="stats-heading report-heading"><?php esc_html_e( 'Stats', 'press-search' ); ?></h3>
				<ul class="stats-list report-list">
					<li class="stat-item report-item"><?php esc_html_e( '304 Searches today.', 'press-search' ); ?></li>
					<li class="stat-item report-item"><?php esc_html_e( '100 Searches with no results.', 'press-search' ); ?></li>
				</ul>
				<a class="stats-detail custom-btn" href="#"><?php esc_html_e( 'View Details', 'press-search' ); ?></a>
			</div>
		</div>
		<?php
	}
}

new Press_Search_Setting_Hooks();
