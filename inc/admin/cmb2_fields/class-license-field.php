<?php
class Press_Search_Field_License_Input {
	public function __construct() {
		add_filter( 'cmb2_render_license_field', array( $this, 'render_field' ), 10, 5 );
		add_filter( 'cmb2_sanitize_license_field', array( $this, 'sanitizee' ), 10, 5 );
		add_filter( 'cmb2_types_esc_license_field', array( $this, 'escapee' ), 10, 4 );
	}

	public function sanitizee( $check, $meta_value, $object_id, $field_args, $sanitize_object ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			if ( ! empty( $val ) ) {
				$meta_value[ $key ] = array_filter( array_map( 'sanitize_text_field', $val ) );
			}
		}
		return array_filter( $meta_value );
	}

	public function escapee( $check, $meta_value, $field_args, $field_object ) {
		if ( ! is_array( $meta_value ) || ! $field_args['repeatable'] ) {
			return $check;
		}
		foreach ( $meta_value as $key => $val ) {
			if ( ! empty( $val ) ) {
				$meta_value[ $key ] = array_filter( array_map( 'esc_attr', $val ) );
			}
		}
		return array_filter( $meta_value );
	}

	public function render_field( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		if ( version_compare( CMB2_VERSION, '2.2.2', '>=' ) ) {
			$field_type_object->type = new CMB2_Type_Select( $field_type_object );
		}
		if ( is_array( $field_escaped_value ) && isset( $field_escaped_value[0] ) ) {
			$saved_value = ( isset( $field_escaped_value[0] ) ) ? $field_escaped_value[0] : $field->args( 'default' );
		} else {
			$saved_value = ( isset( $field_escaped_value ) ) ? $field_escaped_value : $field->args( 'default' );
		}
		$field_name = $field_type_object->_name() . '[]';
		$a = $field_type_object->parse_args(
			'license_field',
			array(
				'type'             => 'text',
				'class'            => 'custom_license_field press_search_license_field',
				'name'             => $field_name,
				'id'               => $field_type_object->_id(),
				'desc'             => $field_type_object->_desc( true ),
				'value'            => esc_attr( $saved_value ),
				'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
			)
		);
		$attrs = $field_type_object->concat_attrs( $a, array( 'desc', 'options' ) );
		?>
		<div class="field-license-input">
			<?php echo sprintf( '<input %s />', $attrs ); ?>
		</div>
		<?php
		if ( '' !== $a['desc'] ) {
			echo sprintf( '%s', $a['desc'] );
		}
	}
}
new Press_Search_Field_License_Input();
