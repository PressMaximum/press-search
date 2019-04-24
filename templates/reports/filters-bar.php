<div class="filter-bars">
	<select class="filter-item" disabled="disabled">
		<option><?php esc_html_e( 'All engines', 'press-search' ); ?></option>
		<option><?php esc_html_e( 'Engine name', 'press-search' ); ?></option>
	</select>
	<?php
	$fixed_date = array(
		'current_year' => esc_html__( 'This Year', 'press-search' ),
		'current_month' => esc_html__( 'This Month', 'press-search' ),
		'last_7_days' => esc_html__( 'Last 7 Days', 'press-search' ),
	);
	foreach ( $fixed_date as $k => $title ) {
		echo sprintf( '<button class="button disabled filter-item" disabled="disabled">%s</button>', $title );
	}
	?>
	<div class="custom-date filter-item">
		<input type="text" autocomplete="off" autocorrect="off" class="report-date-picker" spellcheck="false" placeholder="<?php esc_attr_e( 'From', 'press-search' ); ?>" disabled="disabled"/>
		<input type="text" autocomplete="off" autocorrect="off" class="report-date-picker" spellcheck="false" id="report-date-to" class="report-date-picker" autocapitalize="none" placeholder="<?php esc_attr_e( 'To', 'press-search' ); ?>" disabled="disabled"/>
		<button class="get-report button" disabled="disabled"><?php esc_html_e( 'Go', 'press-search' ); ?></button>
	</div>
</div>
