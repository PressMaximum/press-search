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

		$list_fields = array(
			'title' => array(
				'name' => esc_html__( 'Title', 'press-search' ),
				'placeholder' => 1000,
			),
			'content' => array(
				'name' => esc_html__( 'Content', 'press-search' ),
				'placeholder' => 0.01,
			),
			'excerpt' => array(
				'name' => esc_html__( 'Excerpt', 'press-search' ),
				'placeholder' => 0.1,
			),
			'category' => array(
				'name' => esc_html__( 'Category', 'press-search' ),
				'placeholder' => 3,
			),
			'tag' => array(
				'name' => esc_html__( 'Tag', 'press-search' ),
				'placeholder' => 2,
			),
			'custom_field' => array(
				'name' => esc_html__( 'Custom field', 'press-search' ),
				'placeholder' => 0.005,
			),
		);
		?>
		<div class="cmb-row">
			<div class="cmb-th"><label><?php echo esc_html__( 'Elements', 'press-search' ); ?></label></div>
			<div class="cmb-th"><label class=""><?php echo esc_html__( 'Weight', 'press-search' ); ?></label></div>
		</div>
		<?php
		foreach ( $list_fields as $key => $config ) { ?>
			<div class="custom-fields field-<?php echo esc_attr( $key ); ?>-weight cmb-row">
				<div class="cmb-th"><span class="element-label"><?php echo $config['name']; ?></span></div>
				<div class="cmb-td">
					<?php
					echo $field_type->input(
						array(
							'name'  => $field_type->_name( "[{$key}]" ),
							'id'    => $field_type->_id( "_{$key}" ),
							'value' => ( isset( $value[ $key ] ) && ! empty( $value[ $key ] ) ) ? $value[ $key ] : '',
							'placeholder' => $config['placeholder'],
						)
					); ?>
				</div>
			</div>
			<?php
		}
	}
}
new Press_Search_Field_Element_Weight();
