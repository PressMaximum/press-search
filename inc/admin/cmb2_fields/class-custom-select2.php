<?php
class Press_Search_Field_Custom_Select2 {
	public function __construct() {
		add_filter( 'cmb2_render_custom_multiselect2', array( $this, 'render_custom_multiselect2' ), 10, 5 );
		add_filter( 'cmb2_sanitize_custom_multiselect2', array( $this, 'multiselect_sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_custom_multiselect2', array( $this, 'multiselect_escaped_value' ), 10, 3 );
	}

	public function render_custom_multiselect2( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();
		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}
		$a = $field_type_object->parse_args(
			'custom_multiselect2',
			array(
				'multiple'         => 'multiple',
				'style'            => 'width: 99%',
				'class'            => 'custom_select2 custom_multiselect2 press_search_select2',
				'name'             => $field_type_object->_name() . '[]',
				'id'               => $field_type_object->_id(),
				'desc'             => $field_type_object->_desc( true ),
				'options'          => $this->get_multiselect_options( $field_escaped_value, $field_type_object ),
				'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
			)
		);
		$attrs = $field_type_object->concat_attrs( $a, array( 'desc', 'options' ) );
		echo sprintf( '<select%s>%s</select>%s', $attrs, $a['options'], $a['desc'] );
	}


	public function get_multiselect_options( $field_escaped_value = array(), $field_type_object ) {
		$options = (array) $field_type_object->field->options();
		// If we have selected items, we need to preserve their order.
		if ( ! empty( $field_escaped_value ) ) {
			$options = $this->sort_array_by_array( $options, $field_escaped_value );
		}
		$selected_items = '';
		$other_items = '';
		foreach ( $options as $option_value => $option_label ) {
			// Clone args & modify for just this item.
			$option = array(
				'value' => $option_value,
				'label' => $option_label,
			);
			// Split options into those which are selected and the rest.
			if ( in_array( $option_value, (array) $field_escaped_value ) ) {
				$option['checked'] = true;
				$selected_items .= $field_type_object->select_option( $option );
			} else {
				$other_items .= $field_type_object->select_option( $option );
			}
		}
		return $selected_items . $other_items;
	}

	public function sort_array_by_array( array $array, array $orderArray ) {
		$ordered = array();
		foreach ( $orderArray as $key ) {
			if ( array_key_exists( $key, $array ) ) {
				$ordered[ $key ] = $array[ $key ];
				unset( $array[ $key ] );
			}
		}
		return $ordered + $array;
	}

	public function multiselect_sanitize( $check, $meta_value, $object_id, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			$meta_value[ $key ] = array_map( 'sanitize_text_field', $val );
		}
		return $meta_value;
	}

	public function multiselect_escaped_value( $check, $meta_value, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			$meta_value[ $key ] = array_map( 'esc_attr', $val );
		}
		return $meta_value;
	}

	public function setup_admin_scripts() {
		wp_register_script( 'select2', PRESS_SEARCH_URL . 'assets/js/libs/select2.min.js', array( 'jquery-ui-sortable' ), '4.0.3' );
		wp_register_style( 'select2', PRESS_SEARCH_URL . 'assets/css/libs/select2.min.css', array(), '4.0.3' );
	}
}
new Press_Search_Field_Custom_Select2();
