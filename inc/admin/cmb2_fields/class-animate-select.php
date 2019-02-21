<?php
class Press_Search_Field_Animate_Select {
	public function __construct() {
		add_filter( 'cmb2_render_animate_select', array( $this, 'render_field' ), 10, 5 );
		add_filter( 'cmb2_sanitize_animate_select', array( $this, 'sanitize' ), 10, 4 );
		add_filter( 'cmb2_types_esc_animate_select', array( $this, 'escaped_value' ), 10, 3 );
	}

	public function render_field( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}
		$a = $field_type_object->parse_args(
			'animate_select',
			array(
				'multiple'         => 'multiple',
				'style'            => 'width: 99%',
				'class'            => 'animate_select custom_animate_select press_search_animate_select',
				'name'             => $field_type_object->_name() . '[]',
				'id'               => $field_type_object->_id(),
				'desc'             => $field_type_object->_desc( true ),
				'options'          => $this->get_multiselect_options( $field_escaped_value, $field_type_object ),
				'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
			)
		);
		$attrs = $field_type_object->concat_attrs( $a, array( 'desc', 'options' ) );
		$add_button_text = $field->args( 'text', 'add_value' ) ? $field->args( 'text', 'add_value' ) : esc_html__( 'Add', 'press-search' );
		$select_placeholder = $field->args( 'text', 'select_placeholder' ) ? $field->args( 'text', 'select_placeholder' ) : esc_html__( 'Select a value', 'press-search' );
		$selected_value = '';
		if ( is_array( $field_escaped_value ) && ! empty( $field_escaped_value ) ) {
			$all_options = $field_type_object->field->options();
			foreach ( $field_escaped_value as $selected_val ) {
				if ( isset( $all_options[ $selected_val ] ) && '' !== $all_options[ $selected_val ] ) {
					$selected_value .= '<span class="selected-value-item" data-option_value="' . esc_attr( $selected_val ) . '">' . esc_html( $all_options[ $selected_val ] ) . '<span class="dashicons dashicons-no-alt remove-val"></span></span>';
				}
			}
		}
		$selectable_options = $this->get_option_list( $field_escaped_value, $field_type_object );
		$selectable_options_none = '<option value="">' . esc_html( $select_placeholder ) . '</option>';
		$selectable_options = $selectable_options_none . $selectable_options;

		$ul_select_html = sprintf( '<div class="selected-values">%s</div><select %s>%s</select><span class="select-add-val">%s</span>', $selected_value, 'class="single-select-box"', $selectable_options, $add_button_text );
		$hidden_select_html = sprintf( '<div class="hidden"><select%s>%s</select></div>', $attrs, $a['options'] );
		echo sprintf( '<div class="animate-selected-field">%s%s</div>%s', $ul_select_html, $hidden_select_html, $a['desc'] );
	}

	public function get_option_list( $field_escaped_value = array(), $field_type_object ) {
		$options = (array) $field_type_object->field->options();
		if ( ! empty( $field_escaped_value ) ) {
			$options = $this->sort_array_by_array( $options, $field_escaped_value );
		}
		$option_items = '';
		foreach ( $options as $option_value => $option_label ) {
			if ( ! in_array( $option_value, (array) $field_escaped_value, true ) ) {
				$option = array(
					'value' => $option_value,
					'label' => $option_label,
				);
				$option_items .= $field_type_object->select_option( $option );
			}
		}
		return $option_items;
	}

	public function get_multiselect_options( $field_escaped_value = array(), $field_type_object ) {
		$options = (array) $field_type_object->field->options();
		if ( ! empty( $field_escaped_value ) ) {
			$options = $this->sort_array_by_array( $options, $field_escaped_value );
		}
		$selected_items = '';
		$other_items = '';
		foreach ( $options as $option_value => $option_label ) {
			$option = array(
				'value' => $option_value,
				'label' => $option_label,
			);
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

	public function sanitize( $check, $meta_value, $object_id, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			$meta_value[ $key ] = array_map( 'sanitize_text_field', $val );
		}
		return $meta_value;
	}

	public function escaped_value( $check, $meta_value, $field_args ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			$meta_value[ $key ] = array_map( 'esc_attr', $val );
		}
		return $meta_value;
	}
}
new Press_Search_Field_Animate_Select();
