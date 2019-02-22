<?php
class Press_Search_Setting_Hooks {
	public function __construct() {
		add_action( 'press_search_page_setting_after_form_content', array( $this, 'tab_engines_static_report' ), 10 );
	}

	public function tab_engines_static_report() {
		if ( isset( $_GET['page'] ) && 'press-search-settings' == $_GET['page'] ) {
			if ( isset( $_GET['tab'] ) && 'settings_engines' == $_GET['tab'] ) {
				?>
				<div class="engine-statistic">
					<div class="engine-index-progess report-box">
						<h3 class="index-progess-heading report-heading"><?php esc_html_e( 'Index Progress', 'press-search' ); ?></h3>
						<div class="progress-bar animate blue">
							<span style="width: 33.3%"></span>
						</div>
						<ul class="index-progess-list report-list">
							<li class="index-progess-item report-item"><?php esc_html_e( '30 Entries in the index.', 'press-search' ); ?></li>
							<li class="index-progess-item report-item"><?php esc_html_e( '1141 Terms in the index.', 'press-search' ); ?></li>
							<li class="index-progess-item report-item"><?php esc_html_e( '9 Entries unindexed.', 'press-search' ); ?></li>
							<li class="index-progess-item report-item"><?php esc_html_e( 'Last activity: 2019-03-03 20:30:25', 'press-search' ); ?></li>
						</ul>
						<div class="index-progess-buttons">
							<a class="build-index custom-btn" href="#"><?php esc_html_e( 'Build The Index', 'press-search' ); ?></a>
							<a class="build-unindexed custom-btn" href="#"><?php esc_html_e( 'Build Unindexed', 'press-search' ); ?></a>
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
	}
}

new Press_Search_Setting_Hooks();
