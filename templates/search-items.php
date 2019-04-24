<?php do_action( 'press_search_before_live_item_wrap', get_the_ID() ); ?>
<?php
$is_has_thumbnail = false;
$item_extra_class = array();
if ( in_array( 'show-thumbnail', $ajax_item_display ) && has_post_thumbnail() ) {
	$is_has_thumbnail = true;
	$item_extra_class[] = 'item-has-thumbnail';
}
?>
<div class="live-search-item <?php echo esc_attr( implode( ' ', $item_extra_class ) ); ?>" data-posttype="<?php echo esc_attr( $posttype ); ?>" data-posttype_label="<?php echo esc_attr( $posttype_label ); ?>">
	<?php do_action( 'press_search_before_live_item_thumbnail', get_the_ID() ); ?>
	<?php if ( $is_has_thumbnail ) { ?>
		<div class="item-thumb">
			<?php
			$post_thumb_url = get_the_post_thumbnail_url();
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
		</h3>
		<?php if ( in_array( 'show-excerpt', $ajax_item_display ) ) { ?>
			<div class="item-excerpt"><?php the_excerpt(); ?></div>
		<?php } ?>
	</div>
	<?php do_action( 'press_search_after_live_item_info', get_the_ID() ); ?>
</div>
<?php do_action( 'press_search_after_live_item_wrap', get_the_ID() ); ?>
