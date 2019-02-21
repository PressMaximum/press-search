<?php
class Press_Search_Field_Keyword_Redirect {
	public function __construct() {
		add_filter( 'cmb2_render_keyword_redirect', array( $this, 'render_keyword_redirect_field' ), 10, 5 );
		add_filter( 'cmb2_sanitize_keyword_redirect', array( $this, 'sanitizee' ), 10, 5 );
		add_filter( 'cmb2_types_esc_keyword_redirect', array( $this, 'escapee' ), 10, 4 );
	}

	public function render_keyword_redirect_field( $field, $value, $object_id, $object_type, $field_type ) {
		$value = wp_parse_args(
			$value,
			array(
				'keyword'        => '',
				'url_redirect'   => '',
			)
		);
		?>
		<div class="custom-fields cmb-row">
			<div class="cmb-th">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[keyword]' ),
						'id'    => $field_type->_id( '_keyword' ),
						'value' => $value['keyword'],
						'placeholder' => esc_attr__( 'Keyword', 'press-search' ),
					)
				); ?>
			</div>
			<div class="cmb-td">
				<?php
				echo $field_type->input(
					array(
						'name'  => $field_type->_name( '[url_redirect]' ),
						'id'    => $field_type->_id( '_url_redirect' ),
						'value' => $value['url_redirect'],
						'placeholder' => esc_attr__( 'URL to redirect', 'press-search' ),
					)
				); ?>
			</div>
		</div>
		<?php
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
}
new Press_Search_Field_Keyword_Redirect();
