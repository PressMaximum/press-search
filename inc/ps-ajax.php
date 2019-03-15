<?php

define( 'DOING_AJAX', true );

if ( ! isset( $_REQUEST['action'] ) ) {
	die( '-1' );
}

// make sure you update this line.
// to the relative location of the wp-load.php.
require_once '../../../../wp-load.php';

// Typical headers.
//header( 'Content-Type: text/html' );
header( 'Content-Type: application/json' );
send_nosniff_header();

// Disable caching.
header( 'Cache-Control: no-cache' );
header( 'Pragma: no-cache' );

$action = esc_attr( trim( $_REQUEST['action'] ) );

// A bit of security.
$allowed_actions = array(
	'press_seach_do_live_search',
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
