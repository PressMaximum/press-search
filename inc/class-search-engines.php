<?php
class Press_Search_Engines {
	/**
	 * Instance of class Press_Search_Engines
	 *
	 * @var Press_Search_Engines
	 */
	protected static $_instance = null;
	/**
	 * Store engine settings
	 *
	 * @var array
	 */
	protected $engine_settings = array();
	/**
	 * Store index settings
	 *
	 * @var array
	 */
	protected $index_settings = array();
	protected $default_settings;

	/**
	 * Construction method
	 */
	public function __construct() {
		$this->default_db_settings = array(
			'engine_default' => array(
				'engines_post_type' => array( 'post', 'page' ),
			),
		);
		$this->init();
	}

	/**
	 * Instance
	 *
	 * @return Press_Search_Engines
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Init method
	 *
	 * @return void
	 */
	public function init() {
		$this->get_engine_settings();
		$this->get_index_settings();
	}

	/**
	 * Get engine settings saved in database
	 *
	 * @return array
	 */
	public function get_engine_settings() {
		$db_settings = press_search_get_setting( 'engines', $this->default_db_settings );
		if ( ! empty( $db_settings ) ) {
			$engine_name = 'default';
			foreach ( $db_settings as $key => $setting ) {
				$engine_settings = array();
				$engine_slug = 'engine_' . $engine_name;
				if ( isset( $setting['engine_slug'] ) && ! empty( $setting['engine_slug'] ) ) {
					$engine_slug = $setting['engine_slug'];
				}
				$engine_settings['name'] = 'engines_' . $engine_slug;
				if ( isset( $setting['engines_name'] ) && isset( $setting['engines_name'][0] ) && '' !== $setting['engines_name'][0] ) {
					$engine_settings['name'] = $setting['engines_name'][0];
				}
				$arr_args = array(
					'post_type' => 'engines_post_type',
					'custom_tax' => 'engines_taxonomy',
				);
				foreach ( $arr_args as $k => $key ) {
					if ( isset( $setting[ $key ] ) && ! empty( $setting[ $key ] ) ) {
						$engine_settings[ $k ] = $setting[ $key ];
					}
				}
				$text_args = array(
					'comment' => 'engines_include_comments',
					'post_author' => 'engines_index_post_author',
					'post_excerpt' => 'engines_index_post_excerpt',
					'expand_shortcodes' => 'engines_expand_shortcodes',
				);
				foreach ( $text_args as $k => $key ) {
					if ( isset( $setting[ $key ] ) && 'on' == $setting[ $key ] ) {
						$engine_settings[ $k ] = 1;
					}
				}
				if ( isset( $setting['engines_custom_fields'] ) ) {
					if ( 'none' == $setting['engines_custom_fields'] ) {
						$engine_settings['custom_field'] = 0;
					} elseif ( 'all' == $setting['engines_custom_fields'] ) {
						$engine_settings['custom_field'] = 1;
					} elseif ( 'let-me-choice' == $setting['engines_custom_fields'] ) {
						$engine_settings['custom_field'] = array();
						if ( isset( $setting['engines_choice_custom_fields'] ) && ! empty( $setting['engines_choice_custom_fields'] ) ) {
							$engine_settings['custom_field'] = $setting['engines_choice_custom_fields'];
						}
					}
				}
				$engine_settings['default_operator'] = press_search_get_var( 'default_operator' );
				$engine_settings['searching_weight'] = press_search_get_var( 'default_searching_weights' );
				$allow_operator = array(
					'and',
					'or',
				);
				if ( isset( $setting['searching_default_operator'] ) && in_array( $setting['searching_default_operator'], $allow_operator ) ) {
					$engine_settings['default_operator'] = $setting['searching_default_operator'];
				}
				if ( isset( $setting['searching_weights'] ) && is_array( $setting['searching_weights'] ) && ! empty( $setting['searching_weights'] ) ) {
					$engine_settings['searching_weight'] = $setting['searching_weights'];
				}

				$this->engine_settings[ $engine_slug ] = $engine_settings;
				$engine_name .= '_' . $key;
			}
		}
		return $this->engine_settings;
	}

	/**
	 * Get index settings
	 *
	 * @return array
	 */
	public function get_index_settings() {
		$engine_settings = $this->get_engine_settings();
		$setting_data = array(
			'post_type'          => array(),
			'custom_tax'         => array(),
			'custom_field'       => array(),
			'comment'            => 0,
			'post_author'        => 0,
			'post_excerpt'       => 0,
			'expand_shortcodes'  => 0,
			'user_meta'          => 0,
		);
		foreach ( $engine_settings as $engines ) {
			foreach ( $engines as $key => $setting ) {
				if ( is_array( $setting ) && in_array( $key, array( 'post_type', 'custom_tax', 'custom_field' ), true ) ) {
					$setting_data[ $key ] = array_merge( $setting, $setting_data[ $key ] );
				} elseif ( in_array( $key, array( 'comment', 'post_author', 'post_excerpt', 'expand_shortcodes', 'custom_field' ), true ) ) {
					if ( $setting ) {
						$setting_data[ $key ] = 1;
					}
				}
			}
		}
		foreach ( array( 'post_type', 'custom_tax', 'custom_field' ) as $k ) {
			if ( empty( $setting_data[ $k ] ) ) {
				$setting_data[ $k ] = 0;
			}
		}

		if ( ! empty( $setting_data['custom_tax'] ) ) {
			$custom_taxonomy = $setting_data['custom_tax'];
			foreach ( $custom_taxonomy as $k => $custom_tax ) {
				if ( 'category' == $custom_tax ) {
					$setting_data['category'] = 1;
					unset( $custom_taxonomy[ $k ] );
				}
				if ( 'post_tag' == $custom_tax ) {
					$setting_data['tag'] = 1;
					unset( $custom_taxonomy[ $k ] );
				}
			}
			$setting_data['custom_tax'] = $custom_taxonomy;
		}
		$this->index_settings = $setting_data;
		return $setting_data;
	}

	public function get_all_engines_name( $option_all = true ) {
		$db_settings = press_search_get_setting( 'engines', $this->default_db_settings );
		$all_engines = array();
		if ( $option_all ) {
			$all_engines[] = array(
				'slug' => 'all',
				'name' => esc_html__( 'All engines', 'press-search' ),
			);
		}
		if ( is_array( $db_settings ) && ! empty( $db_settings ) ) {
			foreach ( $db_settings as $engine ) {
				if ( isset( $engine['engine_slug'] ) && ! empty( $engine['engine_slug'] ) && isset( $engine['engines_name'] ) && isset( $engine['engines_name'][0] ) && ! empty( $engine['engines_name'][0] ) ) {
					$all_engines[] = array(
						'slug' => $engine['engine_slug'],
						'name' => $engine['engines_name'][0],
					);
				}
			}
		}
		if ( empty( $all_engines ) ) {
			$all_engines[] = array(
				'slug' => 'engine_default',
				'name' => esc_html__( 'Engine Default', 'press-search' ),
			);
		}
		return $all_engines;
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  0.1.0
	 * @param  string $field Field to retrieve.
	 * @return mixed Field value or null.
	 */
	public function __get( $field ) {
		if ( in_array( $field, array( 'engine_settings', 'index_settings' ), true ) ) {
			return $this->{$field};
		}
		return null;
	}
}

function press_search_engines() {
	return Press_Search_Engines::instance();
}

press_search_engines();
