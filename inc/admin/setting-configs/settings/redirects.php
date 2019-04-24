<?php
ob_start();
?>
<p>
	<?php esc_html_e( 'These are keywords that automatically redirect the user to custom link without search process.', 'press-search' ); ?><br/>
	<?php esc_html_e( 'For example, if you search for "jobs" it will redirect automatically to "careers" page.', 'press-search' ); ?>
</p>
<div class="cmb-row">
	<div class="cmb-th"><label><?php echo esc_html__( 'Keywords', 'press-search' ); ?></label></div>
	<div class="cmb-th"><label class=""><?php echo esc_html__( 'Url to redirect', 'press-search' ); ?></label></div>
</div>
<?php
$before_html = ob_get_contents();
ob_end_clean();

$field_redirect_post = array(
	'desc' => esc_html__( 'Redirect automatically to post, page if keywords like exactly post title', 'press-search' ),
	'id'   => 'redirects_automatic_post_page',
	'type' => 'checkbox',
);
$field_redirect_custom = array(
	'id'          => 'redirects_automatic_custom',
	'type'        => 'keyword_redirect',
	'before'      => $before_html,
	'repeatable'  => true,
	'text'        => array(
		'add_row_text' => esc_html__( 'Add', 'press-search' ),
	),
);

if ( ! ps_is__pro() ) {
	$field_redirect_post['save_field'] = false;
	$field_redirect_post['attributes'] = array(
		'readonly' => 'readonly',
		'disabled' => 'disabled',
	);
	$field_redirect_custom['save_field'] = false;
	$field_redirect_custom['attributes'] = array(
		'readonly' => 'readonly',
		'disabled' => 'disabled',
	);
}
$field = array( $field_redirect_post, $field_redirect_custom );
return $field;
