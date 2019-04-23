<div class="group-posttype list-suggest-keywords">
	<div class="group-posttype-label group-posttype-label-post_post"><span class="group-label"><?php esc_html_e( 'Try These Keywords', 'press-search' ); ?></span></div>
	<?php if ( isset( $keywords ) && is_array( $keywords ) && ! empty( $keywords ) ) { ?>
		<div class="group-posttype-items group-posttype-post_post-items">
			<?php foreach ( $keywords as $keyword ) { ?>
				<div class="live-search-item ">
					<div class="item-wrap">
						<h3 class="item-title suggest-keyword"><?php echo esc_html( $keyword ); ?></h3>
					</div>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
</div>
