<?php


/**
 * Main instance of Press_Search_String_Process.
 *
 * Returns the main instance of Press_Search_String_Process to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Press_Search_String_Process
 */
function press_search_string() {
	return Press_Search_String_Process::instance();
}

/**
 * Main instance of Press_Search_Setting.
 *
 * Returns the main instance of Press_Search_Setting to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Press_Search_Setting
 */
function press_search_settings() {
	return Press_Search_Setting::instance();
}

/**
 * Main instance of Press_Search_Reports.
 *
 * Returns the main instance of Press_Search_Reports to prevent the need to use globals.
 *
 * @since  0.1.0
 * @return Press_Search_Reports
 */
function press_search_reports() {
	return Press_Search_Reports::instance();
}

function press_search_indexing() {
	return Press_Search_Indexing::instance();
}

function press_search_query() {
	return Press_Search_Query::instance();
}


