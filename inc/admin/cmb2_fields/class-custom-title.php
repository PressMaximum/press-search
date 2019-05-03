<?php
class Press_Search_Field_Custom_Title {
	public function __construct() {
		add_filter( 'cmb2_render_custom_title', array( $this, 'render_custom_title' ), 10, 5 );
	}

	public function render_custom_title( $field, $value, $object_id, $object_type, $field_type ) {
		return '';
	}
}

new Press_Search_Field_Custom_Title();
