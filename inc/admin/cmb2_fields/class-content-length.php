<?php
class Press_Search_Field_Content_Length {
	public function __construct() {
		add_filter( 'cmb2_render_content_length', array( $this, 'render_content_length_field' ), 10, 5 );
	}

	public function render_select_option( $selected_value = false, $list_values = array() ) {
		$option_html = '';
		foreach ( $list_values as $key => $val ) {
			$option_html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $selected_value, $key, false ) . '>' . esc_html( $val ) . '</option>';
		}
		return $option_html;
	}

	public function render_content_length_field( $field, $value, $object_id, $object_type, $field_type ) {
		$value = wp_parse_args(
			$value,
			array(
				'length' => '',
				'type'   => '',
			)
		);
		?>
		<div class="alignleft field_input_length">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[length]' ),
					'id'    => $field_type->_id( '_length' ),
					'value' => $value['length'],
					'placeholder' => 30,
				)
			); ?>
		</div>
		<div class="alignleft field_input_type">
			<?php
			$select_type_vals = array(
				'text'      => esc_html__( 'Text', 'press-search' ),
				'character' => esc_html__( 'Character', 'press-search' ),
			);
			echo $field_type->select(
				array(
					'name'    => $field_type->_name( '[type]' ),
					'id'      => $field_type->_id( '_type' ),
					'value'   => $value['type'],
					'options' => $this->render_select_option( $value['type'], $select_type_vals ),
				)
			); ?>
		</div>
		<?php
	}
}
new Press_Search_Field_Content_Length();
