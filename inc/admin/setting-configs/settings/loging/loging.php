<?php
return array(
	array(
		'name' => esc_html__( 'Enable login', 'press-search' ),
		'desc' => esc_html__( 'Enable', 'press-search' ),
		'id'   => 'loging_enable_login',
		'before_row' => esc_html__( 'Login will use for report and track user searches.', 'press-search' ),
		'type' => 'checkbox',
	),
	array(
		'name' => esc_html__( 'Log user IP', 'press-search' ),
		'desc' => esc_html__( 'Enable', 'press-search' ),
		'id'   => 'loging_enable_log_user_ip',
		'type' => 'checkbox',
	),
	array(
		'name'       => esc_html__( 'Exclude users', 'press-search' ),
		'desc'       => esc_html__( 'Comma-separated list of numeric user IDs or user login names that will not be logged.', 'press-search' ),
		'id'         => 'loging_exclude_users',
		'type'       => 'text',
	),
	array(
		'name'       => esc_html__( 'How many days of logs to keep in the database', 'press-search' ),
		'desc'       => esc_html__( '0 or leave emtpy to keep forever.', 'press-search' ),
		'id'         => 'loging_save_log_time',
		'type'       => 'text',
		'attributes' => array(
			'placeholder' => 0,
		),
	),
);
