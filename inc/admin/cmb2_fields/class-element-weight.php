<?php
class Press_Search_Field_Element_Weight {
	public function __construct() {
		add_filter( 'cmb2_render_element_weight', array( $this, 'render_element_weight' ), 10, 5 );
		add_filter( 'cmb2_sanitize_element_weight', array( $this, 'sanitizee' ), 10, 5 );
		add_filter( 'cmb2_types_esc_element_weight', array( $this, 'escapee' ), 10, 4 );
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
			<div class="cmb-th"><label><?php echo esc_html__( 'Elements', 'press-search' ); ?></label></div>
			<div class="cmb-th"><label class=""><?php echo esc_html__( 'Weight', 'press-search' ); ?></label></div>
		</div>
		<div class="custom-fields field-title-weight cmb-row">
			<div class="cmb-th"><span class="element-label"><?php echo esc_html__( 'Title', 'press-search' ); ?></span></div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[title]' ),
						'id'    => $field_type->_id( '_title' ),
						'value' => ( isset( $value['title'] ) && ! empty( $value['title'] ) ) ? $value['title'] : '',
						'placeholder' => 1000,
					)
				); ?>
			</div>
		</div>
		<div class="custom-fields field-content-weight">
			<div class="cmb-th"><span class="element-label"><?php echo esc_html__( 'Content', 'press-search' ); ?></span></div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[content]' ),
						'id'    => $field_type->_id( '_content' ),
						'value' => ( isset( $value['content'] ) && ! empty( $value['content'] ) ) ? $value['content'] : '',
						'placeholder' => 0.01,
					)
				); ?>
			</div>
		</div>
		<div class="custom-fields field-excerpt-weight">
			<div class="cmb-th"><span class="element-label"><?php echo esc_html__( 'Excerpt', 'press-search' ); ?></span></div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[excerpt]' ),
						'id'    => $field_type->_id( '_excerpt' ),
						'value' => ( isset( $value['excerpt'] ) && ! empty( $value['excerpt'] ) ) ? $value['excerpt'] : '',
						'placeholder' => 0.1,
					)
				); ?>
			</div>
		</div>
		<div class="custom-fields field-category-weight">
			<div class="cmb-th"><span class="element-label"><?php echo esc_html__( 'Category', 'press-search' ); ?></span></div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[category]' ),
						'id'    => $field_type->_id( '_category' ),
						'value' => ( isset( $value['category'] ) && ! empty( $value['category'] ) ) ? $value['category'] : '',
						'placeholder' => 3,
					)
				); ?>
			</div>
		</div>
		<div class="custom-fields field-tag-weight">
			<div class="cmb-th"><span class="element-label"><?php echo esc_html__( 'Tag', 'press-search' ); ?></span></div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[tag]' ),
						'id'    => $field_type->_id( '_tag' ),
						'value' => ( isset( $value['tag'] ) && ! empty( $value['tag'] ) ) ? $value['tag'] : '',
						'placeholder' => 2,
					)
				); ?>
			</div>
		</div>
		<div class="custom-fields field-custom-field-weight">
			<div class="cmb-th"><span class="element-label"><?php echo esc_html__( 'Custom field', 'press-search' ); ?></span></div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[custom_field]' ),
						'id'    => $field_type->_id( '_custom_field' ),
						'value' => ( isset( $value['custom_field'] ) && ! empty( $value['custom_field'] ) ) ? $value['custom_field'] : '',
						'placeholder' => 0.005,
					)
				); ?>
			</div>
		</div>
		<?php
	}
}
new Press_Search_Field_Element_Weight();
