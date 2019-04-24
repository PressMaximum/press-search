<div class="report-overview">
	<?php do_action( 'press_search_report_filters_bars', $all_engines_name, $filter_link_args, $filter_date, $filter_search_engine ); ?>
	<div class="report-chart">
		<canvas id="detail-report-chart"></canvas>
	</div>
	<div class="report-search-results">
		<?php
		$table_args = array(
			array(
				'title' => esc_html__( 'Searches Log', 'press-search' ),
				'class' => 'col-38',
				'page_slug' => 'press-search-report',
				'tab_slug' => 'searches-log',
				'render_cb' => 'render_search_logs_table',
			),
			array(
				'title' => esc_html__( 'Popular Searches', 'press-search' ),
				'class' => 'col-38',
				'page_slug' => 'press-search-report',
				'tab_slug' => 'popular-searches',
				'render_cb' => 'render_popular_search_table',
			),
			array(
				'title' => esc_html__( 'No Results', 'press-search' ),
				'class' => 'col-24',
				'page_slug' => 'press-search-report',
				'tab_slug' => 'no-results',
				'render_cb' => 'render_no_search_table',
			),
		);
		foreach ( $table_args as $table ) {
			?>
			<div class="col <?php echo esc_attr( $table['class'] ); ?>">
				<div class="col-label">
					<?php
						$view_all_args = array(
							'page' => $table['page_slug'],
							'tab' => $table['tab_slug'],
						);
						$view_all_link = add_query_arg( $view_all_args, admin_url( 'admin.php' ) );
					?>
					<h3 class="view-all-title"><?php echo $table['title']; // WPCS: XSS ok. ?><a href="<?php echo esc_url( $view_all_link ); ?>" class="report-view-all"><?php esc_html_e( 'View all', 'press-search' ); ?></a></h3>
					<?php
						$render_cb = $table['render_cb'];
						press_search_reports()->$render_cb( 10, false );
					?>
				</div>
			</div>
			<?php
		}
		?>
	</div>
</div>
