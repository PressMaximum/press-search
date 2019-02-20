<?php
class Press_Search_Field_Element_Weight {
	public function __construct() {
		add_filter( 'cmb2_render_element_weight', array( $this, 'render_element_weight' ), 10, 5 );
	}

	public function render_element_weight( $field, $value, $object_id, $object_type, $field_type ) {
		$value = wp_parse_args(
			$value,
			array(
				'length' => '',
				'type'   => '',
			)
		);
		?>
		<div class="cmb-row">
			<div class=""><span class=""><?php echo esc_html__( 'Elements', 'press-search' ); ?></span></div>
			<div class=""><span class=""><?php echo esc_html__( 'Weight', 'press-search' ); ?></span></div>
		</div>
		<div class="custom-fields field-title-weight">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[title]' ),
					'id'    => $field_type->_id( '_title' ),
					'value' => $value['title'],
					'placeholder' => 8,
				)
			); ?>
		</div>
		<div class="custom-fields field-content-weight">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[content]' ),
					'id'    => $field_type->_id( '_content' ),
					'value' => $value['content'],
					'placeholder' => 5,
				)
			); ?>
		</div>
		<div class="custom-fields field-excerpt-weight">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[excerpt]' ),
					'id'    => $field_type->_id( '_excerpt' ),
					'value' => $value['excerpt'],
					'placeholder' => 8,
				)
			); ?>
		</div>
		<div class="custom-fields field-category-weight">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[category]' ),
					'id'    => $field_type->_id( '_category' ),
					'value' => $value['category'],
					'placeholder' => 8,
				)
			); ?>
		</div>
		<div class="custom-fields field-tag-weight">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[tag]' ),
					'id'    => $field_type->_id( '_tag' ),
					'value' => $value['tag'],
					'placeholder' => 8,
				)
			); ?>
		</div>
		<div class="custom-fields field-custom-field-weight">
			<?php
			echo $field_type->input(
				array(
					'name'  => $field_type->_name( '[custom_field]' ),
					'id'    => $field_type->_id( '_custom_field' ),
					'value' => $value['custom_field'],
					'placeholder' => 1,
				)
			); ?>
		</div>
		<?php
	}
}
new Press_Search_Field_Element_Weight();
