<?php

class Press_Search_Pro_Updater {

	private $api_url = 'https://pressmaximum.com/';
	public $option_key = '';
	public $args;
	public $file;
	public $name;
	public $renewal_url = 'checkout/?edd_license_key=%1$s&download_id=%2$s';
	public $enter_key_url;
	public $updater;

	function __construct( $item_id, $plugin_file = null, $enter_key_url = '' ) {

		$this->enter_key_url = $enter_key_url;
		$this->renewal_url   = $this->api_url . '/' . $this->renewal_url;
		$this->file          = $plugin_file;

		require_once dirname( __FILE__ ) . '/class-edd-sl-plugin-updater.php';
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( $this->file );
		/**
		 * @see https://codex.wordpress.org/Function_Reference/get_plugin_data
		 */
		$this->args = $data;
		$this->args['item_id'] = $item_id;
		$this->args['plugin_basename'] = plugin_basename( $plugin_file );
		$this->args['plugin_slug'] = basename( $plugin_file, '.php' );

		$this->option_key = 'pm_license_' . $this->args['plugin_slug'];

		add_action( 'wp_ajax_' . $this->option_key, array( $this, 'ajax' ) );
		do_action( 'press_search_pro_updater_init', $this );

		if ( is_admin() ) {
			add_action( 'press_search_license_box', array( $this, 'box_license' ), 1 );
			remove_action( 'after_plugin_row_' . $this->args['plugin_basename'], 'wp_plugin_update_row', 10 );
			add_action( 'after_plugin_row_' . $this->args['plugin_basename'], array( $this, 'show_update_notification' ), 10, 2 );
			add_action( 'after_plugin_row_' . $this->args['plugin_basename'], array( $this, 'remove_wp_notice' ), 1, 2 );

			add_action( 'admin_head', array( $this, 'css' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		$this->int_auto_update();

		if ( isset( $_REQUEST['force-check'] ) ) {
			delete_transient( $this->updater->cache_key );
			delete_option( $this->updater->cache_key );
		}
	}


	/**
	 * Show update nofication row -- needed for multisite subsites, because WP won't tell you otherwise.
	 *
	 * @see wp_plugin_update_row
	 * @param string $file
	 * @param array  $plugin
	 */
	public function show_update_notification( $file, $plugin ) {

		if ( is_network_admin() ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( is_multisite() ) {
			return;
		}

		if ( $this->args['plugin_basename'] != $file ) {
			return;
		}

		if ( empty( $this->updater ) ) {
			return;
		}

		// Remove our filter on the site transient.
		remove_filter( 'pre_set_site_transient_update_plugins', array( $this->updater, 'check_update' ), 10 );

		$update_cache = get_site_transient( 'update_plugins' );

		$update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

		if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->args['plugin_basename'] ] ) ) {

			$version_info = $this->updater->get_cached_version_info();
			if ( false === $version_info ) {
				$message = $this->get_message();
				$_data = array(
					'slug' => $this->updater->slug,
					'beta' => $this->updater->beta,
				);
				if ( 'success' != $message['type'] ) {
					$_data['license'] = '';
				}
				$version_info = $this->updater->api_request( 'plugin_latest_version', $_data );
				$this->updater->set_version_info_cache( $version_info );
			}

			if ( ! is_object( $version_info ) ) {
				return;
			}

			if ( version_compare( $this->args['Version'], $version_info->new_version, '<' ) ) {
				$update_cache->response[ $this->args['plugin_basename'] ] = $version_info;
			}

			$update_cache->last_checked = current_time( 'timestamp' );
			$update_cache->checked[ $this->args['plugin_basename'] ] = $this->args['Version'];

			set_site_transient( 'update_plugins', $update_cache );

		} else {

			$version_info = $update_cache->response[ $this->args['plugin_basename'] ];

		}

		// Restore our filter.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this->updater, 'check_update' ) );

		if ( ! empty( $update_cache->response[ $this->args['plugin_basename'] ] ) && version_compare( $this->args['Version'], $version_info->new_version, '<' ) ) {

			if ( is_network_admin() || ! is_multisite() ) {
				if ( is_network_admin() ) {
					$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
				} else {
					$active_class = is_plugin_active( $file ) ? ' active' : '';
				}

				$message = $this->get_message();
				$data = $this->get_save_data();
				$license_data = $data['data'];
				$licence = isset( $data['license'] ) ? $data['license'] : '';

				// build a plugin list row, with update notification.
				echo '<tr class="plugin-update-tr ' . $active_class . '" id="' . $this->args['plugin_slug'] . '-update" data-slug="' . $this->args['plugin_slug'] . '" data-plugin="' . $file . '">';
				echo '<td colspan="3" class="plugin-update colspanchange">';
				echo '<div class="update-message notice inline notice-warning notice-alt">';
				echo '<p>';
				$changelog_link = self_admin_url( 'index.php?edd_sl_action=view_plugin_changelog&plugin=' . $this->args['plugin_basename'] . '&slug=' . $this->args['plugin_slug'] . '&TB_iframe=true&width=772&height=911' );

				if ( 'success' == $message['type'] ) {
					if ( empty( $version_info->download_link ) ) {
						printf(
							__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s.', 'pbe' ),
							esc_html( $version_info->name ),
							'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
							esc_html( $version_info->new_version ),
							'</a>'
						);
					} else {
						printf(
							__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s or %5$supdate now%6$s.', 'pbe' ),
							esc_html( $version_info->name ),
							'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
							esc_html( $version_info->new_version ),
							'</a>',
							'<a class="update-link" href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->args['plugin_basename'], 'upgrade-plugin_' . $this->args['plugin_basename'] ) ) . '">',
							'</a>'
						);
					}
				} else {
					$enter_key_url = $this->enter_key_url;
					$msg_type = 'none';
					if ( ! $license_data ) {
						$msg_type = 'notice';
					} else {
						if ( false === $license_data['success'] ) {
							if ( 'expired' == $license_data['error'] ) {
								$msg_type = 'expired';
							}
						}
					}

					if ( 'expired' == $msg_type ) {
						$renewal_url = sprintf( $licence, $this->args['item_id'] );
						printf(
							__( 'There is a new version of %1$s available. %2$s.', 'pbe' ),
							$version_info->name,
							'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">' . sprintf( __( 'View version %1$s details', 'pbe' ), $version_info->new_version ) . '</a>'
						);

						echo '<br/>';
						printf(
							__( '<strong>Your License Has Expired</strong> — Updates are only available to those with an active license. %2$s or %3$s.', 'pbe' ),
							$version_info->name,
							'<strong><a target="_blank" href="' . esc_url( $renewal_url ) . '">' . __( 'Click here to Renewal', 'pbe' ) . '</a></strong>',
							'<a target="_blank"  href="' . esc_url( $enter_key_url ) . '">' . __( 'Check my license again ', 'pbe' ) . '</a>'
						);
					} else {
						printf(
							__( 'There is a new version of %1$s available. %2$s. Automatic update is unavailable for this plugin. %3$s', 'pbe' ),
							$version_info->name,
							'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">' . sprintf( __( 'View version %1$s details', 'pbe' ), $version_info->new_version ) . '</a>',
							'<strong><a target="_blank" href="' . esc_url( $enter_key_url ) . '">' . __( 'Enter valid license key for automatic updates', 'pbe' ) . '</a></strong>'
						);
					}
				}

				do_action( "in_plugin_update_message-{$file}", $plugin, $version_info );
				echo '</p>';
				echo '</div></td></tr>';

			}
		} // end if version compare
	}

	function remove_wp_notice() {
		remove_action( 'after_plugin_row_' . $this->args['plugin_basename'], 'wp_plugin_update_row', 10 );
	}

	function ajax() {
		if ( isset( $_REQUEST['pm_action'] ) ) {
			check_ajax_referer( $this->option_key, '_nonce' );
			$key = sanitize_text_field( $_REQUEST['license'] );
			if ( 'deactivate' == $_REQUEST['pm_action'] ) {
				$r = $this->deactivate_license( $key );
				$type = 'successs';
				if ( $r ) {
					$message = '<p>' . __( 'License deactivated.', 'pbe' ) . '<p>';
				} else {
					$type = 'error';
					$message = '<p class="error">' . __( 'An error occurred, please try again.', 'pbe' ) . '<p>';
				}
				wp_send_json(
					array(
						'type' => $type,
						'message' => '',
						'html' => $message,
					)
				);
			} else {
				$this->active( $key );
			}
			wp_send_json( $this->get_message() );
		}

		die();
	}

	function int_auto_update() {
		$data = $this->get_save_data();
		$license_key = '';
		if ( $data['license'] ) {
			$license_key = $data['license'];
		}
		$this->updater = new Press_Search_EDD_SL_Plugin_Updater(
			$this->api_url,
			$this->file,
			array(
				'version' => $this->args['Version'],  // current version number.
				'license' => $license_key,        // license key (used get_option above to retrieve from DB).
				'item_id' => $this->args['item_id'],      // ID of the product.
				'author'  => $this->args['Author'],      // Author of this plugin.
				'beta'    => false,
			)
		);
	}

	function get_remote( $action = '', $api_params = array() ) {
		$api_params['edd_action'] = $action;
		// Call the custom API.
		$response = wp_remote_post(
			$this->api_url,
			array(
				'timeout' => 15,
				'sslverify' => false,
				'body' => $api_params,
			)
		);
		if ( is_wp_error( $response ) && 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $license_data ) || ! is_array( $license_data ) ) {
			return false;
		}
		return $license_data;
	}

	function check_license( $license = '' ) {
		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license,
			// 'item_name'  => urlencode( $this->args['Name'] ),
			'item_id'  => $this->args['item_id'], // The name of our product in EDD.
			'url'        => home_url(),
		);
		$license_data = $this->get_remote( 'check_license', $api_params );
		return $license_data;
	}

	function get_message( $for_notice = false ) {
		$data = $this->get_save_data();
		$license_data = $data['data'];
		if ( ! $data['license'] ) {
			$type = '';
			if ( $for_notice ) {
				$type = 'error';
				$message = __( 'Please activate your license to get feature updates and premium support.', 'pbe' );
			} else {
				$message = '';
			}
			return array(
				'type' => $type,
				'message' => $message,
				'html' => '',
			);
		}

		$type = 'error';
		$message = __( 'Invalid license.', 'pbe' );
		if ( ! $license_data['success'] ) {
			switch ( $license_data['error'] ) {
				case 'expired':
					if ( 'invalid' == $license_data['license'] ) {
						$message = __( 'Invalid license.', 'pbe' );
					} else {
						$message = __( 'Your license key has expired.', 'pbe' );
					}
					break;
				case 'disabled':
				case 'revoked':
					$message = __( 'Your license key has been disabled.', 'pbe' );
					break;
				case 'license_not_activable':
					$message = __( 'License not activable', 'pbe' );
					break;
				case 'missing':
					$message = __( 'Invalid license.', 'pbe' );
					break;
				case 'invalid':
				case 'site_inactive':
					$message = __( 'Your license is not active for this URL.', 'pbe' );
					break;

				case 'item_name_mismatch':
					$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'pbe' ), $this->args['Name'] );
					break;
				case 'no_activations_left':
					$message = __( 'Your license key has reached its activation limit.', 'pbe' );
					break;
				default:
					$message = __( 'An error occurred, please try again.', 'pbe' );
					break;
			}
		} else {
			$type = 'success';

			if ( 'lifetime' == $license_data['expires'] ) {
				$message = __( 'Your license will never expire.', 'pbe' );
			} else {
				$message = sprintf(
					__( 'Your license key will expire on %s.', 'pbe' ),
					date_i18n( get_option( 'date_format' ), strtotime( $license_data['expires'], current_time( 'timestamp' ) ) )
				);
			}

			if ( current_time( 'timestamp' ) > strtotime( $license_data['expires'], current_time( 'timestamp' ) ) ) {
				$type = 'error';
			}
		}

		return array(
			'type' => $type,
			'message' => $message,
			'html' => $message ? '<p class="' . esc_attr( $type ) . '">' . $message . '</p>' : '',
		);
	}

	function active( $license ) {

		// data to send in our API request.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			// 'item_name'  => urlencode( $this->args['Name'] ), // the name of our product in EDD
			'item_id'  => $this->args['item_id'], // the name of our product in EDD.
			'url'        => home_url(),
		);

		$license_data = $this->get_remote( 'activate_license', $api_params );
		$data = array(
			'license' => $license,
			'data' => $license_data,
		);

		update_option( $this->option_key, $data );
		return $license_data;

	}

	function get_save_data() {
		$data = get_option( $this->option_key );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$data = wp_parse_args(
			$data,
			array(
				'license' => '',
				'data' => array(),
			)
		);
		return $data;
	}

	function deactivate_license( $license = null ) {
		// Retrieve the license from the database.
		if ( ! $license ) {
			$license_data = $this->get_save_data();
			$license = $license_data['license'];
		}

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			// 'item_name'  => urlencode( $this->args['Name'] ), // the name of our product in EDD.
			'item_id'  => $this->args['item_id'], // the name of our product in EDD.
			'url'        => home_url(),
		);

		$license_data = $this->get_remote( 'deactivate_license', $api_params );
		if ( $license_data && isset( $license_data['license'] ) && 'deactivated' == $license_data['license'] ) {
			delete_option( $this->option_key );
			return true;
		} else {
			return false;
		}

	}


	function css() {
		?>
		<style type="text/css">
			.pm-info {
				font-size: 1.2em;
			}
			.pm-license-input-key {
				font-size: 1.3em;
				padding: 5px;
			}
			.pm-license-info .success{
				color: green;
			}
			.pm-license-info .error {
				color: red;
			}
			.pm-box .pm-hide {
				display: none;
			}
			.pm-notice {
				position: relative;
			}
			.pm-notice .pm-active-now{
				position: absolute;
				top: 50%;
				right: 1em;
				transform: translateY( -50% );
			}
			.pm-notice p {
				width: 70%;
			}
			.pm-notice:after {
				clear: both;
				display: block;
				content: '';
			}
		</style>
		<?php
	}

	function admin_notices() {
		$info = $this->get_message( true );
		if ( 'error' != $info['type'] ) {
			return;
		}
		?>
		<div id="pm-notice-for-<?php echo esc_attr( $this->args['plugin_slug'] ); ?>" class="pm-notice notice notice-warning">
			<?php if ( $this->enter_key_url ) { ?>
				<a href="<?php echo esc_url( $this->enter_key_url ); ?>" class="pm-active-now button button-primary"><?php _e( 'Activate License', 'pbe' ); ?></a>
			<?php } ?>
			<p>
			<?php echo sprintf( '%s <strong>%s</strong>', esc_html__( 'You are current using ', 'press-search' ), press_search_get_var( 'plugin_name' ) ); ?><br/>
				<?php echo $info['message']; ?>
			</p>
		</div>
		<?php
	}

	function box_license() {
		$data = $this->get_save_data();
		$message = false;
		if ( $data['license'] ) {
			$message = $this->get_message( $data['data'] );
		}
		?>
		<div class="pm-box">
			<div class="pm-box-content">
				<div class="pm-box-content pm-license-form">
					<p class="pm-info"><?php _e( 'To receive updates, please enter your valid license key.', 'pbe' ); ?></p>
					<p>
						<input name="pm-license" placeholder="<?php esc_attr_e( 'Enter your license key', 'pbe' ); ?>" value="<?php echo esc_attr( $data['license'] ); ?>" type="text" class="pm-license-input-key regular-text">
					</p>
					<div class="pm-license-info pm-msg">
						<?php
						if ( $message ) {
							echo $message['html'];
						}
						?>
					</div>
					<p class="">
						<a href="#" class="button button-primary pm-license-save"><?php _e( 'Activate', 'pbe' ); ?></a>
						<a href="#" class="<?php echo 'success' == $message['type'] ? '' : 'pm-hide'; ?> button button-secondary pm-license-deactivate"><?php _e( 'Deactivate', 'pbe' ); ?></a>
					</p>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				var pm_updater_nonce = <?php echo json_encode( wp_create_nonce( $this->option_key ) ); ?>;
				var placeholder = <?php echo json_encode( __( 'Enter your license key', 'pbe' ) ); ?>;
				var action = <?php echo json_encode( $this->option_key ); ?>;
				var notice_id = <?php echo json_encode( 'pm-notice-for-' . esc_attr( $this->args['plugin_slug'] ) ); ?>;

				var secretKey = function( key ){
					if ( ! key ) {
						return placeholder;
					}
					var string = key;
					var l = string.length;
					var show = 5;
					var sub = string.substr(-show);
					var s = '';
					for( var i = 0; i < l - show ; i++ ){
						s+= "\267";//  dot character
					}
					s+=sub;
					return s;
				};

				var hidden_key =  $('.pm-license-form .pm-license-input-key' ).val();
				$('.pm-license-form  .pm-license-input' ).attr( 'placeholder', secretKey( hidden_key ) ).val('');

				$('.pm-license-save').on('click', function (e) {
					var button = $(this);
					var form = $(this).closest('.pm-license-form');
					e.preventDefault();
					var hidden_key =  $('.pm-license-input-key', form).val();
					var key = $('.pm-license-input', form).val() || '';
					if ( ! key ) {
						key = hidden_key;
					}
					$('.pm-license-input-key', form).val( key );
					$('.pm-license-input', form).attr( 'value', '' );
					$('.pm-license-input', form).attr( 'placeholder', secretKey( key ) );

					button.addClass( 'updating-message' );
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: action,
							license: key,
							pm_action: 'save',
							_nonce: pm_updater_nonce
						},
						success: function (res) {
							button.removeClass( 'updating-message' );
							if ( res.type === 'success' ) {
								$( '.pm-license-deactivate', form ).removeClass( 'pm-hide' );
								$( '#' + notice_id ).remove();
							} else {
								$('.pm-license-input', form).attr( 'placeholder', secretKey( '' ) );
								$( '.pm-license-deactivate', form ).addClass( 'pm-hide' );
							}
							$( '.pm-license-info', form ).html( res.html );
						}
					});
				});

				$('.pm-license-deactivate').on('click', function (e) {
					var form = $(this).closest('.pm-license-form');
					var button = $( this );
					e.preventDefault();
					var key = $('.ppm-license-input-key', form ).val() || '';
					$('.pm-license-input', form ).attr( 'placeholder', secretKey('') ).val('');
					button.addClass( 'updating-message' );
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: action,
							license: key,
							pm_action: 'deactivate',
							_nonce: pm_updater_nonce
						},
						success: function (res) {
							button.removeClass( 'updating-message' );
							if ( res.type === 'success' ) {
								$( '#' + notice_id ).remove();
								$( '.pm-license-deactivate', form ).removeClass( 'pm-hide' );
							} else {
								$( '.pm-license-deactivate', form ).addClass( 'pm-hide' );
							}
							$( '.pm-license-info', form ).html( res.html );
						}
					});
				});

			});
		</script>
		<?php
	}
}
