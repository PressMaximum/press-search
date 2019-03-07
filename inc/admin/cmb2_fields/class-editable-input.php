<?php
class Press_Search_Field_Editable_Input {
	public function __construct() {
		add_filter( 'cmb2_render_editable_input', array( $this, 'render_field' ), 10, 5 );
		add_filter( 'cmb2_sanitize_editable_input', array( $this, 'sanitizee' ), 10, 5 );
		add_filter( 'cmb2_types_esc_editable_input', array( $this, 'escapee' ), 10, 4 );
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
			'editable_input',
			array(
				'type'             => 'hidden',
				'class'            => 'custom_editable_input press_search_editable_input',
				'name'             => $field_name,
				'id'               => $field_type_object->_id(),
				'desc'             => $field_type_object->_desc( true ),
				'value'            => esc_attr( $saved_value ),
				'data-placeholder' => $field->args( 'attributes', 'placeholder' ) ? $field->args( 'attributes', 'placeholder' ) : $field->args( 'description' ),
			)
		);
		$attrs = $field_type_object->concat_attrs( $a, array( 'desc', 'options' ) );
		$extra_text_text = $field->args( 'extra_text', 'text' ) ? $field->args( 'extra_text', 'text' ) : '';
		$extra_text_link = $field->args( 'extra_text', 'link' ) ? $field->args( 'extra_text', 'link' ) : '#';
		$extra_text_target = $field->args( 'extra_text', 'target' ) ? $field->args( 'extra_text', 'target' ) : '_self';
		?>
		<div class="field-editable-input">
			<div class="display-title-wrap">
				<div class="title-wrap">
					<span class="display-title"><?php echo esc_html( $saved_value ); ?></span>
					<?php echo sprintf( '<input %s />', $attrs ); ?>
					<span class="editable-icon do-an-action action-edit">
						<span class="dashicons dashicons-edit action-edit"></span>
					</span>
				</div>
				<?php
				if ( '' !== $extra_text_text ) {
					echo sprintf( '<a href="%s" class="extra-text-link">%s</a>', esc_url( $extra_text_link ), $extra_text_text );
				}
				?>
			</div>
		</div>
		<?php
		if ( '' !== $a['desc'] ) {
			echo sprintf( '%s', $a['desc'] );
		}
	}
}
new Press_Search_Field_Editable_Input();
