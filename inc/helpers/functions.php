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
		$return = array(
			'post' => esc_html__( 'Post', 'press-search' ),
			'page' => esc_html__( 'Page', 'press-search' ),
		);
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$all_post_type = get_post_types( $args, 'names' );
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
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$return = array(
			'category' => esc_html__( 'Category', 'press-search' ),
			'post_tag' => esc_html__( 'Post tag', 'press-search' ),
		);
		$all_taxonomies = get_taxonomies( $args );
		foreach ( $all_taxonomies as $key => $tax ) {
			$tax_info = get_taxonomy( $tax );
			if ( isset( $tax_info->labels->name ) && '' !== $tax_info->labels->name ) {
				$return[ $key ] = $tax_info->labels->name;
			}
		}
		return $return;
	}
}
if ( ! function_exists( 'press_search_engines_taxonomy_options_cb' ) ) {
	function press_search_engines_taxonomy_options_cb() {
		return press_search_get_taxonomies();
	}
}

if ( ! function_exists( 'press_search_engines_post_type_options_cb' ) ) {
	function press_search_engines_post_type_options_cb() {
		return press_search_get_registered_posttype();
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



if ( ! function_exists( 'press_search_get_post_meta' ) ) {
	function press_search_get_post_meta( $meta_key = '', $post_id = 0, $default_value = '' ) {
		$press_search_setting = press_search_settings();
		$metabox_prefix = $press_search_setting->__get( 'metabox_prefix' );
		$post_meta = get_post_meta( $metabox_prefix . $meta_key, $post_id, true );
		if ( ! empty( $post_meta ) ) {
			return $post_meta;
		}
		return $default_value;
	}
}

if ( ! function_exists( 'press_search_get_setting' ) ) {
	function press_search_get_setting( $setting_key = '', $default_value = '' ) {
		$press_search_setting = press_search_settings();
		$option_key = $press_search_setting->__get( 'option_key' );

		if ( function_exists( 'cmb2_get_option' ) ) {
			return cmb2_get_option( $option_key, $setting_key, $default_value );
		} else {
			$options = get_option( $option_key );
			return isset( $options[ $setting_key ] ) ? $options[ $setting_key ] : $default_value;
		}
	}
}

if ( ! function_exists( 'press_search_get_template_path' ) ) {
	function press_search_get_template_path( $file_name ) {
		if ( false === strpos( $file_name, '.php' ) ) {
			$file_name .= '.php';
		}
		$file_path = press_search_get_var( 'plugin_dir' ) . 'templates/' . $file_name;
		$file_path = apply_filters( 'press_search_template_file_path', $file_path, $file_name );
		return $file_path;
	}
}

if ( ! function_exists( 'press_search_get_template' ) ) {
	function press_search_get_template( $file_name = '', $args = array() ) {
		$file_path = press_search_get_template_path( $file_name );
		if ( file_exists( $file_path ) ) {
			if ( ! empty( $args ) ) {
				extract( $args ); // @codingStandardsIgnoreLine .
			}
			include $file_path;
		} else {
			esc_html_e( 'Your template does not exists', 'press-search' );
		}
	}
}

if ( ! function_exists( 'press_search_upgrade_notice' ) ) {
	function press_search_upgrade_notice( $title = '', $content = '' ) {
		$pro_url = press_search_get_var( 'upgrade_pro_url' );
		?>
		<div class="upgrade-pro-notice">
			<h3>
			<?php
				if( '' !== $title ) {
					echo wp_kses_post( $title );
				} else {
					esc_html_e( 'Searching Reports is a PRO feature.', 'press-search' );
				}
			?>
			</h3>
			<p>
				<?php
					if ( '' !== $content ) {
						echo wp_kses_post( $content );
					} else {
						esc_html_e( 'Please upgrade to the PRO version to unlock and have more awesome features.', 'press-search' );
					}
				?>
			</p>
			<p><a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" class="upgrade-pro-link"><?php esc_html_e( 'Upgrade Now', 'press-search' ); ?></a></p>
		</div>
		<?php
	}
}

