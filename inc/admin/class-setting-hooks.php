<?php
class Press_Search_Setting_Hooks {
	public function __construct() {
		add_action( 'press_search_after__press-search-settings_engines_content', array( $this, 'tab_engines_static_report' ), 10 );
	}

	public function tab_engines_static_report() {
		if ( function_exists( 'press_search_reports' ) ) {
			?>
			<div class="engine-statistic-wrapper">
				<?php press_search_reports()->engines_static_report(); ?>
			</div>
			<?php
		}
	}
}

new Press_Search_Setting_Hooks();
