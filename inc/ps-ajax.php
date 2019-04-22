<?php

define( 'DOING_AJAX', true );
if ( ! isset( $_REQUEST['action'] ) ) {
	die( '-1' );
}
require_once '../../../../wp-load.php';
header( 'Content-Type: application/json' );
// Disable caching.
header( 'Cache-Control: no-cache' );
header( 'Pragma: no-cache' );
$action = esc_attr( trim( $_REQUEST['action'] ) );
// A bit of security.
$allowed_actions = array(
	'press_seach_do_live_search',
	'press_search_ajax_insert_log',
);
if ( in_array( $action, $allowed_actions ) ) {
	if ( is_user_logged_in() ) {
		do_action( 'ps_ajax_' . $action ); // @codingStandardsIgnoreLine .
	} else {
		do_action( 'ps_ajax_nopriv_' . $action ); // @codingStandardsIgnoreLine .
	}
} else {
	die( '-1' );
}
