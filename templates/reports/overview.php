<div class="report-overview">
	<div class="filter-bars">
		<select class="filter-item">
			<option><?php esc_html_e( 'All engines', 'press_search' ); ?></option>
		</select>
		<a href="#" class="button filter-item"><?php esc_html_e( 'This Year', 'press_search' ); ?></a>
		<a href="#" class="button filter-item"><?php esc_html_e( 'This Month', 'press_search' ); ?></a>
		<a href="#" class="button filter-item"><?php esc_html_e( 'Last 7 Days', 'press_search' ); ?></a>
		<div class="custom-date filter-item">
			<input type="date" name=""/>
			<button class="get-report button"><?php esc_html_e( 'Go', 'press_search' ); ?></button>
		</div>
	</div>
	<div class="report-char">Chart</div>
	<div class="report-search-results">
		<div class="col">
			<div class="col-label">
				<h4><?php esc_html_e( 'Popular Searches', 'press_search' ); ?></h4>
				<?php press_search_reports()->render_popular_search_table( 5, false ); ?>
			</div>
		</div>
		<div class="col">
			<div class="col-label">
				<h4><?php esc_html_e( 'No Results', 'press_search' ); ?></h4>
				<?php press_search_reports()->render_no_search_table( 5, false ); ?>
			</div>
		</div>
	</div>
</div>
