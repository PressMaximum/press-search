<?php do_action( 'press_search_before_live_item_wrap', get_the_ID() ); ?>
<div class="live-search-item" data-posttype="<?php echo esc_attr( $posttype ); ?>" data-posttype_label="<?php echo esc_attr( $posttype_label ); ?>">
	<?php do_action( 'press_search_before_live_item_thumbnail', get_the_ID() ); ?>
	<?php if ( has_post_thumbnail() ) { ?>
		<div class="item-thumb">
			<a href="<?php the_permalink(); ?>" class="item-thumb-link">
				<?php the_post_thumbnail(); ?>
			</a>
		</div>
	<?php } ?>
	<?php do_action( 'press_search_before_live_item_info', get_the_ID() ); ?>
	<div class="item-wrap">
		<h3 class="item-title">
			<a href="<?php the_permalink(); ?>" class="item-title-link">
				<?php the_title(); ?>
			</a>
		</h3>
		<div class="item-excerpt"><?php the_excerpt(); ?></div>
	</div>
	<?php do_action( 'press_search_after_live_item_info', get_the_ID() ); ?>
</div>
<?php do_action( 'press_search_after_live_item_wrap', get_the_ID() ); ?>
