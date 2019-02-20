<?php
return array(
	array(
		'name'    => esc_html__( 'Site Background Color', 'cmb2' ),
		'desc'    => esc_html__( 'field description (optional)', 'cmb2' ),
		'id'      => 'general_bg_color',
		'type'    => 'colorpicker',
		'default' => '#ffffff',
	),
	array(
		'name'    => 'Test Radio',
		'desc'    => 'field description (optional)',
		'id'      => 'general_radio',
		'type'    => 'radio',
		'options' => array(
			'option1' => 'Option One',
			'option2' => 'Option Two',
			'option3' => 'Option Three',
		),
	),
);
