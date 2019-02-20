<?php
class Press_Search_Setting {
	/**
	 * The option key in database.
	 *
	 * @var Press_Search_Setting_Option_Key
	 * @since 0.1.0
	 */
	protected $option_key = 'press_search_settings';

	/**
	 * The metabox prefix.
	 *
	 * @var Press_Search_Setting_Metabox_Prefix
	 * @since 0.1.0
	 */
	protected $metabox_prefix = '_press_search_';

	/**
	 * The option configs.
	 *
	 * @var Press_Search_Setting_Option_Configs
	 * @since 0.1.0
	 */
	protected $option_configs = array();

	/**
	 * The metabox configs.
	 *
	 * @var Press_Search_Setting_Metabox_Configs
	 * @since 0.1.0
	 */
	protected $metabox_configs = array();

	/**
	 * The setting tabs.
	 *
	 * @var Press_Search_Tabs
	 * @since 0.1.0
	 */
	protected $tabs = array();

	/**
	 * The current tab.
	 *
	 * @var Press_Search_Current_Tab
	 * @since 0.1.0
	 */
	protected $current_tab = array();

	/**
	 * The current section.
	 *
	 * @var Press_Search_Current_Section
	 * @since 0.1.0
	 */
	protected $current_section = array();

	/**
	 * The current page slug.
	 *
	 * @var Press_Search_Current_Page_Slug
	 * @since 0.1.0
	 */
	protected $current_page_slug = array();

	/**
	 * The menu pages.
	 *
	 * @var Press_Search_Menu_Pages
	 * @since 0.1.0
	 */
	protected $menu_pages = array();

	/**
	 * The setting fields.
	 *
	 * @var Press_Search_Setting_Fields
	 * @since 0.1.0
	 */
	protected $setting_fields = array();

	/**
	 * The single instance of the class.
	 *
	 * @var Press_Search_Setting_Instance
	 * @since 0.1.0
	 */
	protected static $_instance = null;
	/**
	 * Method __construct
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ), 90 );
		add_action( 'cmb2_admin_init', array( $this, 'register_db_settings' ), 10 );
		add_action( 'admin_init', array( $this, 'admin_init' ), 1 );

		add_action(
			'init',
			function() {
				$this->get_post_meta();
			}
		);
	}
	/**
	 * Hook to cmb2_admin_init
	 */
	public function register_db_settings() {
		register_setting( $this->option_key, $this->option_key );
		$this->init_meta_box();
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
		$current_admin_slug = '';
		if ( isset( $_GET['page'] ) && in_array( sanitize_text_field( $_GET['page'] ), $this->get_available_menu_slugs() ) ) {
			$current_admin_slug = sanitize_text_field( $_GET['page'] );
		}
		$this->current_page_slug = $current_admin_slug;
		if ( isset( $_GET['tab'] ) && sanitize_text_field( $_GET['tab'] ) !== '' ) {
			$this->current_tab = $this->option_configs[ sanitize_text_field( $_GET['tab'] ) ];
		} else {
			$current_tab_val = array();
			if ( is_array( $this->tabs ) && ! empty( $this->tabs ) ) {
				foreach ( $this->tabs as $tab ) {
					if ( $tab['menu_slug'] == $this->current_page_slug ) {
						$current_tab_val = $this->option_configs[ $tab['tab_id'] ];
						break;
					}
				}
			}
			$this->current_tab = $current_tab_val;
		}
		$current_tab = $this->current_tab;
		if ( isset( $current_tab['sub_tabs'] ) && ! empty( $current_tab['sub_tabs'] ) ) {
			if ( isset( $_GET['section'] ) && sanitize_text_field( $_GET['section'] ) !== '' ) {
				$this->current_section = $current_tab['sub_tabs'][ sanitize_text_field( $_GET['section'] ) ];
			} else {
				$this->current_section = $current_tab['sub_tabs'][ key( $current_tab['sub_tabs'] ) ];
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
	 * @param [array] $args
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
		$current_tab = $this->current_tab;
		if ( is_array( $this->tabs ) && ! empty( $this->tabs ) ) {
			?>
			<nav class="nav-tab-wrapper">
				<?php
				foreach ( $this->tabs as $tab_config ) {
					if ( $tab_config['menu_slug'] !== $this->current_page_slug ) {
						continue;
					}
					$tab_url = add_query_arg( array( 'tab' => $tab_config['tab_id'] ), menu_page_url( $this->current_page_slug, false ) );
					$extra_class = '';
					if ( $current_tab['id'] == $tab_config['tab_id'] ) {
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
	}

	/**
	 * Render sub tab
	 *
	 * @return void
	 */
	public function render_sub_tab() {
		$all_tabs = $this->tabs;
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		if ( isset( $all_tabs[ $current_tab['id'] ]['sub_tabs'] ) && ! empty( $all_tabs[ $current_tab['id'] ]['sub_tabs'] ) ) {
			$current_sub_tabs = $all_tabs[ $current_tab['id'] ]['sub_tabs'];
			?>
			<ul class="nav-sub-tabs list-sub-tabs">
				<?php
				$count_sub = 1;
				foreach ( $current_sub_tabs as $sub_tab ) {
					$sub_tab_url = add_query_arg(
						array(
							'tab' => $current_tab['id'],
							'section' => $sub_tab['tab_id'],
						),
						menu_page_url( $this->current_page_slug, false )
					);
					$section_active_class = '';
					if ( $current_section['id'] == $sub_tab['tab_id'] ) {
						$section_active_class = ' current-section';
					}
					?>
					<li>
						<a class="nav-sub-tab<?php echo esc_attr( $section_active_class ); ?>" href="<?php echo esc_url( $sub_tab_url ); ?>"><?php echo esc_html( $sub_tab['tab_title'] ); ?></a>
						<?php if ( $count_sub < count( $current_sub_tabs ) ) { ?>
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
	 * Check target page
	 *
	 * @return boolean
	 */
	public function check_target_page() {
		$all_tabs = $this->tabs;
		$current_page_slug = $this->current_page_slug;
		$current_tab = $this->current_tab;
		$current_tab_id = $current_tab['id'];
		$tab_menu_slug = $all_tabs[ $current_tab_id ]['menu_slug'];
		if ( $current_page_slug !== $tab_menu_slug ) {
			return false;
		}
		return true;
	}

	/**
	 * Render form content
	 *
	 * @return void
	 */
	public function render_form_content() {
		$target_page = $this->check_target_page();
		if ( ! $target_page ) {
			return;
		}
		$this->render_tabs();
		$this->render_sub_tab();
		?>
		<div class="form-content">
			<?php
			cmb2_metabox_form( $this->option_metabox(), $this->option_key );
			?>
		</div>
		<?php
	}
	/**
	 * Render form content with no tab
	 *
	 * @return void
	 */
	public function render_form_notab_content() {
		$setting_fields = $this->setting_fields[ $this->current_page_slug ]['fields'];
		$setting_fields = apply_filters( 'press_search_form_notab_fields', $setting_fields, $this->setting_fields, $this->current_page_slug );
		$form_args = array(
			'id'         => 'form_settings',
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->option_key ),
			),
			'show_names' => true,
			'fields'     => $setting_fields,
		);
		?>
		<div class="form-content">
			<?php
			cmb2_metabox_form( $form_args, $this->option_key );
			?>
		</div>
		<?php
	}

	/**
	 * Render setting page content
	 */
	public function page_content() {
		/**
		 * Hook press_search_before_page_setting_content
		 *
		 * @since 0.1.0
		 */
		do_action( 'press_search_before_page_setting_content' );
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
			<?php
			if ( array_key_exists( $this->current_page_slug, $this->setting_fields ) ) {
				$this->render_form_notab_content();
			} else {
				$this->render_form_content();
			}
			?>
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
	}

	/**
	 * Set option config for cmb2 form
	 *
	 * @return array
	 */
	public function option_metabox() {
		$current_tab = $this->current_tab;
		$current_section = $this->current_section;
		$current_section_id = $current_section['id'];
		$fields = array();
		if ( isset( $current_section_id ) && '' !== $current_section_id && in_array( $current_section_id, array_keys( $current_tab['sub_tabs'] ) ) ) {
			$fields = $current_section['fields'];
		} elseif ( isset( $current_tab['fields'] ) && ! empty( $current_tab['fields'] ) ) {
			$fields = $current_tab['fields'];
		}
		return array(
			'id'         => 'form_settings',
			'show_on'    => array(
				'key' => 'options-page',
				'value' => array( $this->option_key ),
			),
			'show_names' => true,
			'fields'     => $fields,
		);
	}

	/**
	 * Register tab
	 *
	 * @param [string] $tab_id
	 * @param [string] $tab_title
	 * @param [string] $menu_slug
	 * @return void
	 */
	public function register_tab( $tab_id, $tab_title, $menu_slug = '' ) {
		$this->tabs[ $tab_id ] = array(
			'tab_id'    => sanitize_text_field( $tab_id ),
			'tab_title' => $tab_title,
			'menu_slug' => $menu_slug,
		);
	}
	/**
	 * Register sub tab
	 *
	 * @param [string] $tab_id
	 * @param [string] $parent_tab_id
	 * @param [string] $tab_title
	 * @return void
	 */
	public function register_sub_tab( $tab_id, $parent_tab_id, $tab_title ) {
		if ( isset( $this->tabs[ $parent_tab_id ] ) ) {
			$this->tabs[ $parent_tab_id ]['sub_tabs'][ $tab_id ] = array(
				'tab_id' => sanitize_text_field( $tab_id ),
				'tab_title' => $tab_title,
			);
		}
	}

	/**
	 * Set tab fields configs
	 *
	 * @param [string] $tab_id
	 * @param [array]  $fields
	 * @return void
	 */
	public function set_tab_fields( $tab_id, $fields ) {
		if ( '' !== $tab_id && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'press_search_set_tab_fields', $fields, $tab_id );
			if ( isset( $this->option_configs[ $tab_id ] ) && isset( $this->option_configs[ $tab_id ]['fields'] ) ) {
				$this->option_configs[ $tab_id ]['fields'] = array_merge( $this->option_configs[ $tab_id ]['fields'], $fields );
			} else {
				$this->option_configs[ $tab_id ] = array(
					'id' => $tab_id,
					'fields' => $fields,
				);
			}
		}
	}
	/**
	 * Add setting to page without tab
	 *
	 * @param [string] $menu_slug
	 * @param [array]  $fields
	 * @return void
	 */
	public function set_setting_fields( $menu_slug, $fields ) {
		if ( '' !== $menu_slug && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'press_search_set_setting_fields', $fields, $menu_slug );
			if ( isset( $this->setting_fields[ $menu_slug ] ) && isset( $this->setting_fields[ $menu_slug ]['fields'] ) ) {
				$this->setting_fields[ $menu_slug ]['fields'] = array_merge( $this->setting_fields[ $menu_slug ]['fields'], $fields );
			} else {
				$this->setting_fields[ $menu_slug ] = array(
					'menu_slug' => $menu_slug,
					'fields'    => $fields,
				);
			}
		}
	}
	/**
	 * Add setting to page without tab via file
	 *
	 * @param [string] $menu_slug
	 * @param [array]  $file_configs
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
	 * @param [string] $menu_slug
	 * @param [array] $field
	 * @return void
	 */
	public function set_setting_field( $menu_slug, $field ) {
		if ( '' !== $menu_slug && is_array( $field ) && ! empty( $field ) ) {
			$fields = apply_filters( 'press_search_set_setting_field', $field, $menu_slug );
			if ( isset( $this->setting_fields[ $menu_slug ] ) && isset( $this->setting_fields[ $menu_slug ]['fields'] ) ) {
				$exists_fields = $this->setting_fields[ $menu_slug ]['fields'];
				$exists_fields[] = $field;
				$this->setting_fields[ $menu_slug ]['fields'] = $exists_fields;
			} else {
				$this->setting_fields[ $menu_slug ] = array(
					'menu_slug' => $menu_slug,
					'fields' => array(
						$field,
					),
				);
			}
		}
	}
	/**
	 * Set tab field
	 *
	 * @param [string] $tab_id
	 * @param [array]  $field
	 * @return void
	 */
	public function set_tab_field( $tab_id, $field ) {
		if ( '' !== $tab_id && is_array( $field ) && ! empty( $field ) ) {
			$fields = apply_filters( 'press_search_set_tab_field', $field, $tab_id );
			if ( isset( $this->option_configs[ $tab_id ] ) && isset( $this->option_configs[ $tab_id ]['fields'] ) ) {
				$exists_fields = $this->option_configs[ $tab_id ]['fields'];
				$exists_fields[] = $field;
				$this->option_configs[ $tab_id ]['fields'] = $exists_fields;
			} else {
				$this->option_configs[ $tab_id ] = array(
					'id' => $tab_id,
					'fields' => array(
						$field,
					),
				);
			}
		}
	}
	/**
	 * Set tab fields configs via file
	 *
	 * @param [string] $tab_id
	 * @param [string] $file_configs
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
	 * @param [string] $sub_tab_id
	 * @param [string] $parent_tab_id
	 * @param [array]  $fields
	 * @return void
	 */
	public function set_sub_tab_fields( $sub_tab_id, $parent_tab_id, $fields ) {
		if ( '' !== $sub_tab_id && '' !== $parent_tab_id && is_array( $fields ) && ! empty( $fields ) ) {
			$fields = apply_filters( 'press_search_set_sub_tab_fields', $fields, $sub_tab_id, $parent_tab_id );
			if ( isset( $this->option_configs[ $parent_tab_id ] ) ) {
				if ( isset( $this->option_configs[ $parent_tab_id ]['sub_tabs'][ $sub_tab_id ] ) && isset( $this->option_configs[ $parent_tab_id ]['sub_tabs'][ $sub_tab_id ]['fields'] ) ) {
					$this->option_configs[ $parent_tab_id ]['sub_tabs'][ $sub_tab_id ]['fields'] = array_merge( $this->option_configs[ $parent_tab_id ]['sub_tabs'][ $sub_tab_id ]['fields'], $fields );
				} else {
					$this->option_configs[ $parent_tab_id ]['sub_tabs'][ $sub_tab_id ] = array(
						'id' => $sub_tab_id,
						'fields' => $fields,
					);
				}
			} else {
				$this->option_configs[ $parent_tab_id ] = array(
					'id' => $parent_tab_id,
					'fields' => array(),
					'sub_tabs' => array(
						$sub_tab_id => array(
							'id' => $sub_tab_id,
							'fields' => $fields,
						),
					),
				);
			}
		}
	}
	/**
	 * Set sub tab fields via file
	 *
	 * @param [string] $sub_tab_id
	 * @param [string] $parent_tab_id
	 * @param [string] $file_configs
	 * @return void
	 */
	public function set_sub_tab_file_configs( $sub_tab_id, $parent_tab_id, $file_configs ) {
		$file_configs = apply_filters( 'press_search_set_sub_tab_file_configs', $file_configs, $sub_tab_id, $parent_tab_id );
		if ( file_exists( $file_configs ) || file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs ) ) {
			$file_config_dir = $file_configs;
			if ( ! file_exists( $file_config_dir ) ) {
				$file_config_dir = PRESS_SEARCH_DIR . 'inc/admin/setting-configs/' . $file_configs;
			}
			$configs = include $file_config_dir;
			if ( '' !== $sub_tab_id && '' !== $parent_tab_id && is_array( $configs ) && ! empty( $configs ) ) {
				$this->set_sub_tab_fields( $sub_tab_id, $parent_tab_id, $configs );
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
		if ( in_array( $field, array( 'menu_slugs', 'option_key', 'option_configs', 'tabs' ), true ) ) {
			return $this->{$field};
		}
		return null;
	}

	/**
	 * Get setting
	 *
	 * @param string $setting_key
	 * @param string $default_value
	 * @return mixed Field value or default value
	 */
	public function get_setting( $setting_key = '', $default_value = '' ) {
		if ( function_exists( 'cmb2_get_option' ) ) {
			return cmb2_get_option( $this->option_key, $setting_key, $default_value );
		} else {
			$options = get_option( $this->option_key );
			return isset( $options[ $setting_key ] ) ? $options[ $setting_key ] : $default_value;
		}
	}

	/**
	 * Add metabox configs
	 *
	 * @param [array] $args
	 * @param [array] $fields
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
	 * @param [array]  $args
	 * @param [string] $file_dir
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

	/**
	 * Get metabox value
	 *
	 * @param string  $meta_key
	 * @param integer $post_id
	 * @param string  $default_value
	 * @return mixed  Meta value or default value
	 */
	public function get_post_meta( $meta_key = '', $post_id = 0, $default_value = '' ) {
		$post_meta = get_post_meta( $this->metabox_prefix . $meta_key, $post_id, true );
		if ( ! empty( $post_meta ) ) {
			return $post_meta;
		}
		return $default_value;
	}
}

function press_search_settings() {
	return Press_Search_Setting::instance();
}

if ( file_exists( PRESS_SEARCH_DIR . 'inc/admin/setting-configs.php' ) ) {
	require_once PRESS_SEARCH_DIR . 'inc/admin/setting-configs.php';
}
// Load custom cm2 fields.
if ( file_exists( PRESS_SEARCH_DIR . 'inc/admin/cmb2_fields/init.php' ) ) {
	require_once PRESS_SEARCH_DIR . 'inc/admin/cmb2_fields/init.php';
}


