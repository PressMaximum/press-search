<?php
class Press_Search_Setting {
	/**
	 * The option key in database.
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $option_key = 'press_search_settings';

	/**
	 * The metabox prefix.
	 *
	 * @var string
	 * @since 0.1.0
	 */
	protected $metabox_prefix = '_press_search_';

	/**
	 * The metabox configs.
	 *
	 * @var array
	 * @since 0.1.0
	 */
	protected $metabox_configs = array();


	/**
	 * The current tab.
	 *
	 * @var array
	 * @since 0.1.0
	 */
	protected $current_tab = array();

	/**
	 * The current section.
	 *
	 * @var array
	 * @since 0.1.0
	 */
	protected $current_section = array();

	/**
	 * The current page slug.
	 *
	 * @var array
	 * @since 0.1.0
	 */
	protected $current_page_slug = array();

	/**
	 * The menu pages.
	 *
	 * @var array
	 * @since 0.1.0
	 */
	protected $menu_pages = array();


	/**
	 * The single instance of the class.
	 *
	 * @var Press_Search_Setting
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * The tab settings
	 *
	 * @var array
	 */
	protected $tab_settings = array();
	/**
	 * The setting fields
	 *
	 * @var array
	 */
	protected $setting_fields = array();
	/**
	 * Method __construct
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ), PHP_INT_MAX );
		add_action( 'cmb2_admin_init', array( $this, 'register_db_settings' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}
	/**
	 * Hook to cmb2_admin_init
	 */
	public function register_db_settings() {
		register_setting( $this->option_key, $this->option_key );
		$this->init_meta_box();
	}

	public function admin_body_class( $classes ) {
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		if ( ! empty( $current_tab ) && isset( $current_tab['tab_id'] ) && '' !== $current_tab['tab_id'] ) {
			$classes .= sprintf( ' current_tab_%s', $current_tab['tab_id'] );
		}
		if ( ! empty( $current_section ) && isset( $current_section['sub_tab_id'] ) && '' !== $current_section['sub_tab_id'] ) {
			$classes .= sprintf( ' current_section_%s', $current_section['sub_tab_id'] );
		}

		return $classes;
	}

	/**
	 * Init meta box
	 *
	 * @return void
	 */
	public function init_meta_box() {
		if ( is_array( $this->metabox_configs ) && ! empty( $this->metabox_configs ) ) {
			$metabox_configs = apply_filters( 'press_search_register_metabox', $this->metabox_configs );
			foreach ( $metabox_configs as $config ) {
				$default_args = array(
					'id'            => $this->metabox_prefix . 'metabox',
					'title'         => esc_html__( 'Metabox', 'press-search' ),
					'object_types'  => array( 'post', 'page' ),
				);
				$args = wp_parse_args( $config['args'], $default_args );

				$meta_box = new_cmb2_box( $args );
				foreach ( $config['fields'] as $field ) {
					$field['id'] = $this->metabox_prefix . $field['id'];
					$meta_box->add_field( $field );
				}
			}
		}
	}

	/**
	 * Admin init
	 *
	 * @return void
	 */
	public function admin_init() {
		// Set current menu slug.
		$current_admin_slug = '';
		if ( isset( $_GET['page'] ) && in_array( sanitize_text_field( $_GET['page'] ), $this->get_available_menu_slugs() ) ) {
			$current_admin_slug = sanitize_text_field( $_GET['page'] );
		}
		$this->current_page_slug = $current_admin_slug;
		$tab_settings = $this->tab_settings;
		// Set current tab.
		$current_page_tabs = array();
		if ( isset( $tab_settings[ $this->current_page_slug ] ) && ! empty( $tab_settings[ $this->current_page_slug ] ) ) {
			$current_page_tabs = $tab_settings[ $this->current_page_slug ];
			$this->current_tab = $current_page_tabs[ key( $current_page_tabs ) ];
		}
		if ( isset( $_GET['tab'] ) && sanitize_text_field( $_GET['tab'] ) !== '' ) {
			$tab_key = sanitize_text_field( $_GET['tab'] );
			if ( isset( $current_page_tabs[ $tab_key ] ) ) {
				$this->current_tab = $current_page_tabs[ $tab_key ];
			}
		}
		// Set current section.
		$this->current_section = array();
		if ( isset( $this->current_tab['sub_tabs'] ) && ! empty( $this->current_tab['sub_tabs'] ) ) {
			$tab_sub_tabs = $this->current_tab['sub_tabs'];
			$this->current_section = $tab_sub_tabs[ key( $tab_sub_tabs ) ];

			if ( isset( $_GET['section'] ) && sanitize_text_field( $_GET['section'] ) !== '' ) {
				$sub_tab_key = sanitize_text_field( $_GET['section'] );
				if ( isset( $tab_sub_tabs[ $sub_tab_key ] ) ) {
					$this->current_section = $tab_sub_tabs[ $sub_tab_key ];
				}
			}
		}
	}

	/**
	 * Get all registered menu slugs
	 *
	 * @return array
	 */
	public function get_available_menu_slugs() {
		$menu_slug = array();
		foreach ( $this->menu_pages as $menu ) {
			if ( isset( $menu['menu_slug'] ) && '' !== $menu['menu_slug'] ) {
				$menu_slug[] = $menu['menu_slug'];
			}
		}
		return $menu_slug;
	}

	/**
	 * Instance
	 *
	 * @return Press_Search_Setting
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Add menu setting page
	 */
	public function add_menu_pages() {
		add_menu_page( esc_html__( 'PressSEARCH', 'press-search' ), esc_html__( 'PressSEARCH', 'press-search' ), 'manage_options', 'press-search-settings', null, null, 2 );
		foreach ( $this->menu_pages as $menu_page ) {
			$default = array(
				'page_title' => '',
				'menu_title' => '',
				'capability' => 'manage_options',
				'menu_slug'  => '',
				'parent_slug' => '',
				'icon_url'   => '',
				'position'   => null,
			);
			$args = wp_parse_args( $menu_page, $default );
			if ( is_null( $args['parent_slug'] ) || '' == $args['parent_slug'] ) {
				add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], array( $this, 'page_content' ), $args['icon_url'], $args['position'] );
			} else {
				add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], array( $this, 'page_content' ) );
			}
		}
	}

	/**
	 * Add setting page
	 *
	 * @param array $args
	 * @return void
	 */
	public function add_settings_page( $args ) {
		$this->menu_pages[] = $args;
	}

	/**
	 * Render tabs
	 *
	 * @return void
	 */
	public function render_tabs() {
		$current_menu_slug = $this->current_page_slug;
		if ( '' == $current_menu_slug ) {
			return;
		}
		if ( isset( $this->tab_settings[ $current_menu_slug ] ) && ! empty( $this->tab_settings[ $current_menu_slug ] ) ) {
			$all_page_tabs = $this->tab_settings[ $current_menu_slug ];
		} else {
			return;
		}
		$current_tab = $this->current_tab;
		?>
		<nav class="nav-tab-wrapper">
			<?php
			foreach ( $all_page_tabs as $tab_config ) {
				$tab_url = add_query_arg( array( 'tab' => $tab_config['tab_id'] ), menu_page_url( $this->current_page_slug, false ) );
				$extra_class = '';
				if ( isset( $current_tab['tab_id'] ) && $current_tab['tab_id'] == $tab_config['tab_id'] ) {
					$extra_class = ' nav-tab-active';
				}
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>" id="<?php echo esc_attr( $tab_config['tab_id'] ); ?>" class="nav-tab<?php echo esc_attr( $extra_class ); ?>"><?php echo esc_html( $tab_config['tab_title'] ); ?></a>
				<?php
			}
			?>
		</nav>
		<?php
	}

	/**
	 * Render sub tab
	 *
	 * @return void
	 */
	public function render_sub_tab() {
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;

		if ( isset( $current_tab['sub_tabs'] ) && ! empty( $current_tab['sub_tabs'] ) ) {
			?>
			<ul class="nav-sub-tabs list-sub-tabs">
				<?php
				$count_sub = 1;
				$count_all = count( $current_tab['sub_tabs'] );
				foreach ( $current_tab['sub_tabs'] as $sub_tab ) {
					$link_target = '_self';
					if ( isset( $sub_tab['custom_link'] ) && isset( $sub_tab['custom_link']['link'] ) && ! empty( $sub_tab['custom_link']['link'] ) ) {
						$sub_tab_url = $sub_tab['custom_link']['link'];
						$link_target = $sub_tab['custom_link']['target'] ? $sub_tab['custom_link']['target'] : '_self';
					} else {
						$sub_tab_url = add_query_arg(
							array(
								'tab' => $current_tab['tab_id'],
								'section' => $sub_tab['sub_tab_id'],
							),
							menu_page_url( $this->current_page_slug, false )
						);
					}
					$section_active_class = '';
					if ( $current_section['sub_tab_id'] == $sub_tab['sub_tab_id'] ) {
						$section_active_class = ' current-section';
					}
					?>
					<li>
						<a class="nav-sub-tab<?php echo esc_attr( $section_active_class ); ?>" href="<?php echo esc_url( $sub_tab_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo esc_html( $sub_tab['sub_tab_title'] ); ?></a>
						<?php if ( $count_sub < $count_all ) { ?>
							<span class="subtab-separator"><?php echo esc_html__( ' | ', 'press-search' ); ?></span>
						<?php } ?>
					</li>
					<?php
					$count_sub++;
				}
				?>
			</ul>
			<?php
		}
	}

	/**
	 * Call callback fn if has cb function
	 *
	 * @return bool
	 */
	public function maybe_do_tab_callback() {
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		$has_callback = false;

		if ( ! empty( $current_tab ) ) {
			$cb_fn = '';
			if ( isset( $current_tab['callback_func'] ) && ! empty( $current_tab['callback_func'] ) ) {
				$cb_fn = $current_tab['callback_func'];
			}
			if ( ! empty( $current_section ) ) {
				if ( isset( $current_section['callback_func'] ) && ! empty( $current_section['callback_func'] ) ) {
					$cb_fn = $current_section['callback_func'];
				}
			}
			if ( '' !== $cb_fn ) {
				if ( is_string( $cb_fn ) ) {
					if ( function_exists( $cb_fn ) ) {
						call_user_func( $cb_fn, $current_tab );
						$has_callback = true;
					}
				}
				if ( is_array( $cb_fn ) && count( $cb_fn ) == 2 ) {
					if ( method_exists( $cb_fn[0], $cb_fn[1] ) ) {
						call_user_func_array( $cb_fn, $current_tab );
						$has_callback = true;
					}
				}
			}
		}

		return $has_callback;
	}
	/**
	 * Render form content
	 *
	 * @return void
	 */
	public function render_form_content() {
		$this->render_tabs();
		$this->render_sub_tab();
		$option_metabox = $this->option_metabox();
		?>
		<div class="form-content">
			<?php
			/**
			 * Hook press_search_before_cmb2_form_content
			 *
			 * @since 0.1.0
			 */
			do_action( 'press_search_before_cmb2_form_content' );

			$did_callback = $this->maybe_do_tab_callback();
			if ( ! empty( $option_metabox ) && ! $did_callback ) {
				cmb2_metabox_form( $option_metabox, $this->option_key );
			}
			/**
			 * Hook press_search_after_cmb2_form_content
			 *
			 * @since 0.1.0
			 */
			do_action( 'press_search_after_cmb2_form_content' );
			?>
		</div>
		<?php
	}


	/**
	 * Render setting page content
	 */
	public function page_content() {
		$current_page_slug = $this->current_page_slug;
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		$hook_name = '';
		if ( '' !== $current_page_slug ) {
			$hook_name = sprintf( '_%s', $current_page_slug );
		}
		if ( ! empty( $current_tab ) && isset( $current_tab['tab_id'] ) && '' !== $current_tab['tab_id'] ) {
			$hook_name .= sprintf( '_%s', $current_tab['tab_id'] );
		}
		if ( ! empty( $current_section ) && isset( $current_section['sub_tab_id'] ) && '' !== $current_section['sub_tab_id'] ) {
			$hook_name .= sprintf( '_%s', $current_section['sub_tab_id'] );
		}

		/**
		 * Hook press_search_before_page_setting_content
		 *
		 * @since 0.1.0
		 */
		do_action( 'press_search_before_page_setting_content' );

		if ( '' !== $hook_name ) {
			do_action( "press_search_before_{$hook_name}_content" );
		}
		?>
		<div class="wrap">
			<?php
				/**
				 * Hook press_search_page_setting_before_title
				 *
				 * @since 0.1.0
				 */
				do_action( 'press_search_page_setting_before_title' );
			?>
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php
				/**
				 * Hook press_search_page_setting_after_title
				 *
				 * @since 0.1.0
				 */
				do_action( 'press_search_page_setting_after_title' );
			?>

			<?php
				/**
				 * Hook press_search_page_setting_before_form_content
				 *
				 * @since 0.1.0
				 */
				do_action( 'press_search_page_setting_before_form_content' );
			?>
			<?php $this->render_form_content(); ?>
			<?php
				/**
				 * Hook press_search_page_setting_after_form_content
				 *
				 * @since 0.1.0
				 */
				do_action( 'press_search_page_setting_after_form_content' );
			?>
			<div class="clear"></div>
		</div>
		<?php
		/**
		 * Hook press_search_after_page_setting_content
		 *
		 * @since 0.1.0
		 */
		do_action( 'press_search_after_page_setting_content' );
		if ( '' !== $hook_name ) {
			do_action( "press_search_after_{$hook_name}_content" );
		}
	}

	/**
	 * Set option config for cmb2 form
	 *
	 * @return array
	 */
	public function option_metabox() {
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		$all_setting_fields = $this->setting_fields;
		$setting_fields = array();

		if ( isset( $current_tab['tab_id'] ) && '' !== $current_tab['tab_id'] ) {
			$tab_key = 'tab_' . $current_tab['tab_id'];
			$no_tab_key = 'notab_' . $current_tab['tab_id'];
			if ( isset( $current_section['sub_tab_id'] ) && '' !== $current_section['sub_tab_id'] ) {
				$tab_key = "subtab_{$current_tab['tab_id']}_{$current_section['sub_tab_id']}";
			}
			if ( isset( $all_setting_fields[ $tab_key ] ) && ! empty( $all_setting_fields[ $tab_key ] ) ) {
				$setting_fields = $all_setting_fields[ $tab_key ];
			} elseif ( isset( $all_setting_fields[ $no_tab_key ] ) && ! empty( $all_setting_fields[ $no_tab_key ] ) ) {
				$setting_fields = $all_setting_fields[ $no_tab_key ];
			}
		}
		if ( ! empty( $setting_fields ) ) {
			return array(
				'id'         => 'form_settings',
				'show_on'    => array(
					'key' => 'options-page',
					'value' => array( $this->option_key ),
				),
				'save_button' => esc_html__( 'Save changes', 'press-search' ),
				'show_names' => true,
				'fields'     => $setting_fields,
			);
		} else {
			return array();
		}
	}

	/**
	 * Register tab
	 *
	 * @param string $menu_slug
	 * @param string $tab_id
	 * @param string $tab_title
	 * @param string $callback_function
	 * @return void
	 */
	public function register_tab( $menu_slug = '', $tab_id = '', $tab_title = '', $callback_function = null ) {
		$tab_data = array(
			'tab_id'    => sanitize_text_field( $tab_id ),
			'tab_title' => $tab_title,
		);
		if ( null !== $callback_function && '' !== $callback_function ) {
			$tab_data['callback_func'] = $callback_function;
		}
		if ( isset( $this->tab_settings[ $menu_slug ] ) ) {
			$this->tab_settings[ $menu_slug ][ $tab_id ] = $tab_data;
		} else {
			$this->tab_settings[ $menu_slug ] = array(
				$tab_id => $tab_data,
			);
		}
	}
	/**
	 * Register sub tab
	 *
	 * @param string $parent_tab_id
	 * @param string $sub_tab_id
	 * @param string $sub_tab_title
	 * @param array  $custom_link
	 * @param array  $callback_function
	 * @return void
	 */
	public function register_sub_tab( $parent_tab_id, $sub_tab_id, $sub_tab_title, $custom_link = array(), $callback_function = null ) {
		$menu_slug_key = '';
		foreach ( $this->tab_settings as $key => $tab_settings ) {
			if ( isset( $tab_settings[ $parent_tab_id ] ) ) {
				$menu_slug_key = $key;
			}
		}

		$sub_tab_data = array(
			'sub_tab_id'    => sanitize_text_field( $sub_tab_id ),
			'sub_tab_title' => $sub_tab_title,
		);
		if ( null !== $callback_function && '' !== $callback_function ) {
			$sub_tab_data['callback_func'] = $callback_function;
		}
		if ( '' !== $custom_link ) {
			$sub_tab_data['custom_link'] = $custom_link;
		}

		if ( '' !== $menu_slug_key ) {
			if ( isset( $this->tab_settings[ $menu_slug_key ][ $parent_tab_id ]['sub_tabs'] ) ) {
				$this->tab_settings[ $menu_slug_key ][ $parent_tab_id ]['sub_tabs'][ $sub_tab_id ] = $sub_tab_data;
			} else {
				$this->tab_settings[ $menu_slug_key ][ $parent_tab_id ]['sub_tabs'] = array(
					$sub_tab_id => $sub_tab_data,
				);
			}
		}
	}

	/**
	 * Set tab fields configs
	 *
	 * @param string $tab_id
	 * @param array  $fields
	 * @return void
	 */
	public function set_tab_fields( $tab_id, $fields ) {
		if ( '' !== $tab_id && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'press_search_set_tab_fields', $fields, $tab_id );
			$tab_key = 'tab_' . $tab_id;

			if ( isset( $this->setting_fields[ $tab_key ] ) ) {
				$this->setting_fields[ $tab_key ] = array_merge( $this->setting_fields[ $tab_key ], $fields );
			} else {
				$this->setting_fields[ $tab_key ] = $fields;
			}
		}
	}
	/**
	 * Add setting to page without tab
	 *
	 * @param string $menu_slug
	 * @param array  $fields
	 * @return void
	 */
	public function set_setting_fields( $menu_slug, $fields ) {
		if ( '' !== $menu_slug && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'press_search_set_setting_fields', $fields, $menu_slug );
			$tab_key = 'notab_' . $menu_slug;
			if ( isset( $this->setting_fields[ $tab_key ] ) ) {
				$this->setting_fields[ $tab_key ] = array_merge( $this->setting_fields[ $tab_key ], $fields );
			} else {
				$this->setting_fields[ $tab_key ] = $fields;
			}
		}
	}

	/**
	 * Add setting to page without tab via file
	 *
	 * @param string $menu_slug
	 * @param array  $file_configs
	 * @return void
	 */
	public function set_setting_file_configs( $menu_slug, $file_configs ) {
		$file_configs = apply_filters( 'press_search_set_setting_file_configs', $file_configs, $menu_slug );
		if ( file_exists( $file_configs ) || file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs;

			}
			$configs = include $file_config_dir;
			if ( '' !== $menu_slug && is_array( $configs ) && ! empty( $configs ) ) {
				$this->set_setting_fields( $menu_slug, $configs );
			}
		}
	}
	/**
	 * Add setting to page with one file each call
	 *
	 * @param string $menu_slug
	 * @param array  $field
	 * @return void
	 */
	public function set_setting_field( $menu_slug, $field ) {
		if ( '' !== $menu_slug && is_array( $field ) && ! empty( $field ) ) {
			$fields = apply_filters( 'press_search_set_setting_field', $field, $menu_slug );

			$tab_key = 'notab_' . $menu_slug;

			if ( isset( $this->setting_fields[ $tab_key ] ) ) {
				$this->setting_fields[ $tab_key ][] = $field;
			} else {
				$this->setting_fields[ $tab_key ] = array(
					$field,
				);
			}
		}
	}
	/**
	 * Set tab field
	 *
	 * @param string $tab_id
	 * @param array  $field
	 * @return void
	 */
	public function set_tab_field( $tab_id, $field ) {
		if ( '' !== $tab_id && is_array( $field ) && ! empty( $field ) ) {
			$fields = apply_filters( 'press_search_set_tab_field', $field, $tab_id );
			$tab_key = 'tab_' . $tab_id;
			if ( isset( $this->setting_fields[ $tab_key ] ) ) {
				$this->setting_fields[ $tab_key ][] = $field;
			} else {
				$this->setting_fields[ $tab_key ] = array(
					$field,
				);
			}
		}
	}
	/**
	 * Set tab fields configs via file
	 *
	 * @param string $tab_id
	 * @param string $file_configs
	 * @return void
	 */
	public function set_tab_file_configs( $tab_id, $file_configs ) {
		$file_configs = apply_filters( 'press_search_set_tab_file_configs', $file_configs, $tab_id );
		if ( file_exists( $file_configs ) || file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs;

			}
			$configs = include $file_config_dir;
			if ( '' !== $tab_id && is_array( $configs ) && ! empty( $configs ) ) {
				$this->set_tab_fields( $tab_id, $configs );
			}
		}
	}
	/**
	 * Set sub tab fields
	 *
	 * @param string $parent_tab_id
	 * @param string $sub_tab_id
	 * @param array  $fields
	 * @return void
	 */
	public function set_sub_tab_fields( $parent_tab_id, $sub_tab_id, $fields ) {
		if ( '' !== $sub_tab_id && '' !== $parent_tab_id && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'press_search_set_sub_tab_fields', $fields, $sub_tab_id, $parent_tab_id );
			$tab_key = "subtab_{$parent_tab_id}_$sub_tab_id";
			if ( isset( $this->setting_fields[ $tab_key ] ) ) {
				$this->setting_fields[ $tab_key ] = array_merge( $this->setting_fields[ $tab_key ], $fields );
			} else {
				$this->setting_fields[ $tab_key ] = $fields;
			}
		}
	}
	/**
	 * Set sub tab fields via file
	 *
	 * @param string $parent_tab_id
	 * @param string $sub_tab_id
	 * @param string $file_configs
	 * @return void
	 */
	public function set_sub_tab_file_configs( $parent_tab_id, $sub_tab_id, $file_configs ) {
		$file_configs = apply_filters( 'press_search_set_sub_tab_file_configs', $file_configs, $sub_tab_id, $parent_tab_id );
		if ( file_exists( $file_configs ) || file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs;
			}
			$configs = include $file_config_dir;
			if ( '' !== $sub_tab_id && '' !== $parent_tab_id && is_array( $configs ) && ! empty( $configs ) ) {
				$this->set_sub_tab_fields( $parent_tab_id, $sub_tab_id, $configs );
			}
		}
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  0.1.0
	 * @param  string $field Field to retrieve.
	 * @return mixed Field value or null.
	 */
	public function __get( $field ) {
		if ( in_array( $field, array( 'menu_slugs', 'option_key', 'option_configs', 'tabs', 'current_tab', 'metabox_prefix' ), true ) ) {
			return $this->{$field};
		}
		return null;
	}

	/**
	 * Add metabox configs
	 *
	 * @param array $args
	 * @param array $fields
	 * @return void
	 */
	public function add_meta_box( $args, $fields ) {
		$this->metabox_configs[] = array(
			'args' => $args,
			'fields' => $fields,
		);
	}

	/**
	 * Add metabox config with fields defined in a file
	 *
	 * @param array  $args
	 * @param string $file_dir
	 * @return void
	 */
	public function add_meta_box_file( $args, $file_dir ) {
		$file_configs = apply_filters( 'press_search_add_meta_box_file', $file_dir, $args );
		if ( file_exists( $file_configs ) || file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs;
			}
			$configs = include $file_config_dir;
			if ( is_array( $args ) && ! empty( $args ) && is_array( $configs ) && ! empty( $configs ) ) {
				$this->add_meta_box( $args, $configs );
			}
		}
	}
}

if ( file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs.php' ) ) {
	require_once PRESS_SEARCH_DIR . 'inc/admin/setting-configs.php';
}
// Load custom cm2 fields.
if ( file_exists( PRESS_SEARCH_DIR . 'inc/admin/cmb2_fields/init.php' ) ) {
	require_once PRESS_SEARCH_DIR . 'inc/admin/cmb2_fields/init.php';
}

if ( file_exists( PRESS_SEARCH_DIR . 'inc/admin/class-setting-hooks.php' ) ) {
	require_once PRESS_SEARCH_DIR . 'inc/admin/class-setting-hooks.php';
}
