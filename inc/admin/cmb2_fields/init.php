<?php

$custom_field_types_file = array(
	'class-custom-select2.php',
	'class-animate-select.php',
	'class-content-length.php',
	'class-element-weight.php',
	'class-custom-title.php',
	'class-keyword-redirect.php',
	'class-editable-input.php',
	'class-license-field.php',
);

foreach ( $custom_field_types_file as $file_name ) {
	$file_path = press_search_get_var( 'plugin_dir' ) . 'inc/admin/cmb2_fields/' . $file_name;
	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
}

