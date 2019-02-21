<?php

if ( ! function_exists( 'press_search_get_all_categories' ) ) {
	function press_search_get_all_categories( $return_type = 'slug' ) {
		$args = array(
			'hide_empty'       => 0,
			'orderby'          => 'name',
			'hierarchical'     => true,
			'show_option_none' => false,
		);
		$categories = get_categories( $args );
		$return = array();
		if ( is_array( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $cat ) {
				if ( isset( $cat->slug ) && isset( $cat->term_id ) ) {
					if ( 'slug' === $return_type ) {
						$return[ $cat->slug ] = $cat->name;
					} else {
						$return[ $cat->term_id ] = $cat->name;
					}
				}
			}
		}
		if ( empty( $return ) ) {
			$return['none'] = esc_html__( 'None', 'press-search' );
		}
		return $return;
	}
}

if ( ! function_exists( 'press_search_get_all_posts' ) ) {
	function press_search_get_all_posts( $post_type = 'post' ) {
		$args = array(
			'posts_per_page' => -1,
			'post_type' => $post_type,
			'orderby' => 'name',
			'order' => 'ASC',
		);
		$return = array();
		$posts_array = get_posts( $args );
		foreach ( $posts_array as $post ) {
			$return[ $post->ID ] = $post->post_title;
		}
		wp_reset_postdata();
		if ( empty( $return ) ) {
			$return['none'] = esc_html__( 'None', 'press-search' );
		}
		return $return;
	}
}

if ( ! function_exists( 'press_search_get_registered_posttype' ) ) {
	function press_search_get_registered_posttype() {
		$return = array();
		$all_post_type = get_post_types( '', 'names' );
		foreach ( $all_post_type as $key => $post_type ) {
			$post_type_info = get_post_type_object( $key );
			if ( isset( $post_type_info->labels->name ) && '' !== $post_type_info->labels->name ) {
				$return[ $key ] = $post_type_info->labels->name;
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'press_search_get_taxonomies' ) ) {
	function press_search_get_taxonomies() {
		$return = array();
		$all_taxonomies = get_taxonomies();
		foreach ( $all_taxonomies as $key => $tax ) {
			$tax_info = get_taxonomy( $tax );
			if ( isset( $tax_info->labels->name ) && '' !== $tax_info->labels->name ) {
				$return[ $key ] = $tax_info->labels->name;
			}
		}
		return $return;
	}
}

if ( ! function_exists( 'press_search_get_custom_field_keys' ) ) {
	function press_search_get_custom_field_keys() {
		global $wpdb;
		$return = array();
		$all_meta_keys = $wpdb->get_results( 'SELECT DISTINCT meta_key FROM wp_postmeta', ARRAY_A );

		foreach ( $all_meta_keys as $key ) {
			$return [ $key['meta_key'] ] = $key['meta_key'];
		}
		return $return;
	}
}

