<?php do_action( 'press_search_before_live_item_wrap', get_the_ID() ); ?>
<?php
$maybe_display_thumb = false;
$item_extra_class = array();
if ( in_array( 'show-thumbnail', $ajax_item_display ) && has_post_thumbnail() ) {
	$maybe_display_thumb = true;
}
$maybe_display_thumb = apply_filters( 'press_search_live_search_item_is_has_thumb', $maybe_display_thumb, $posttype, get_the_ID(), has_post_thumbnail() );
if ( $maybe_display_thumb && has_post_thumbnail() ) {
	$item_extra_class['has_thumb'] = 'item-has-thumbnail';
} else {
	$item_extra_class['has_thumb'] = 'item-no-thumbnail';
}

?>
<div class="live-search-item <?php echo esc_attr( implode( ' ', $item_extra_class ) ); ?>" data-posttype="<?php echo esc_attr( $posttype ); ?>" data-posttype_label="<?php echo esc_attr( $posttype_label ); ?>" data-href="<?php the_permalink(); ?>">
	<?php do_action( 'press_search_before_live_item_thumbnail', get_the_ID() ); ?>
	<?php
	$post_thumb_url = get_the_post_thumbnail_url( get_the_ID(), array( 100, 100 ) );
	if ( $maybe_display_thumb && ! empty( $post_thumb_url ) ) { ?>
		<div class="item-thumb">
			<?php
			$post_thumb_url = apply_filters( 'press_search_live_search_item_post_thumb_url', $post_thumb_url, $posttype, get_the_ID() );
			if ( ! empty( $post_thumb_url ) ) {
				echo sprintf( '<a href="%s" style="%s" class="item-thumb-link"></a>', get_the_permalink(), 'background-image: url(' . $post_thumb_url . ');' );
			}
			?>
		</div>
	<?php } ?>
	<?php do_action( 'press_search_before_live_item_info', get_the_ID() ); ?>
	<div class="item-wrap">
		<h3 class="item-title">
			<a href="<?php the_permalink(); ?>" class="item-title-link">
				<?php the_title(); ?>
			</a>
			<?php do_action( 'press_search_live_search_item_after_title_link', $posttype, get_the_ID() ); ?>
		</h3>
		<?php if ( in_array( 'show-excerpt', $ajax_item_display ) ) { ?>
			<div class="item-excerpt"><?php the_excerpt(); ?></div>
		<?php } ?>
	</div>
	<?php do_action( 'press_search_after_live_item_info', get_the_ID() ); ?>
</div>
<?php do_action( 'press_search_after_live_item_wrap', get_the_ID() ); ?>
