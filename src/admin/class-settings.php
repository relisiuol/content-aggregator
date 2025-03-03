<?php

namespace Content_Aggregator\Admin;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Settings {
	private const INTERVALS = array( '15m', '30m', '1h', '12h', '1d' );
	private const EXPIRATION_DATES = array( '1h', '12h', '1d', '1w', '1m', 'never' );
	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new \Content_Aggregator\Admin\Settings();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		add_settings_section( 'content_aggregator_section', false, array( $this, 'add_settings_section' ), 'content_aggregator_page' );
		add_settings_field( 'content_aggregator_update_interval', __( 'Update interval', 'content-aggregator' ), array( $this, 'add_settings_field_update_interval' ), 'content_aggregator_page', 'content_aggregator_section' );
		add_settings_field( 'content_aggregator_max_update', __( 'Max update', 'content-aggregator' ), array( $this, 'add_settings_field_max_update' ), 'content_aggregator_page', 'content_aggregator_section' );
		add_settings_field( 'content_aggregator_expiration_date', __( 'Expiration date', 'content-aggregator' ), array( $this, 'add_settings_field_expiration_date' ), 'content_aggregator_page', 'content_aggregator_section' );
		add_settings_field( 'content_aggregator_certificate_path', __( 'Certificate path', 'content-aggregator' ), array( $this, 'add_settings_field_certificate_path' ), 'content_aggregator_page', 'content_aggregator_section' );
	}

	private static function default_settings() {
		return array(
			'update_interval' => '1h',
			'max_update'      => 10,
			'expiration_date' => '1w',
			'certificate_path' => \WpOrg\Requests\Requests::get_certificate_path(),
		);
	}

	public function get_settings( $options = false ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = get_option( 'content_aggregator_settings' );
		}
		return wp_parse_args(
			$options,
			$this->default_settings()
		);
	}

	private function update_settings( $input ) {
		$input = wp_parse_args(
			$input,
			$this->default_settings()
		);
		$input = $this->get_settings( $input );
		$success = false;
		$output = $this->get_settings();
		$input['update_interval'] = sanitize_text_field( wp_unslash( $input['update_interval'] ) );
		if ( is_string( $input['update_interval'] ) && $input['update_interval'] !== $output['update_interval'] && in_array( $input['update_interval'], self::INTERVALS, true ) ) {
			$output['update_interval'] = $input['update_interval'];
			$success = true;
			$timestamp = wp_next_scheduled( 'content_aggregator_update_hook' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'content_aggregator_update_hook' );
			}
		} elseif ( $input['update_interval'] !== $output['update_interval'] ) {
			add_settings_error( 'content_aggregator_settings', 'invalid-update-interval', __( 'Invalid update interval selected.', 'content-aggregator' ) );
		}
		$max_update = isset( $input['max_update'] ) ? intval( $input['max_update'] ) : 0;
		if ( $max_update !== $output['max_update'] && $max_update > 0 && $max_update <= 50 ) {
			$output['max_update'] = $max_update;
			$success = true;
		} elseif ( $max_update !== $output['max_update'] ) {
			add_settings_error( 'content_aggregator_settings', 'invalid-max-update', __( 'Invalid max update value. It should be a positive number, not exceeding 50.', 'content-aggregator' ) );
		}
		if ( $input['expiration_date'] !== $output['expiration_date'] && in_array( $input['expiration_date'], self::EXPIRATION_DATES, true ) ) {
			$output['expiration_date'] = $input['expiration_date'];
			$success = true;
		} elseif ( $input['expiration_date'] !== $output['expiration_date'] ) {
			add_settings_error( 'content_aggregator_settings', 'invalid-expiration-date', __( 'Invalid expiration date selected.', 'content-aggregator' ) );
		}
		$input['certificate_path'] = sanitize_text_field( wp_unslash( $input['certificate_path'] ) );
		if ( $input['certificate_path'] !== $output['certificate_path'] ) {
			$upload_dir = wp_upload_dir();
			$certificate_dir = $upload_dir['basedir'] . '/certificates/';
			$certificate_path = realpath( $certificate_dir . basename( $input['certificate_path'] ) );
			if ( \WpOrg\Requests\Requests::get_certificate_path() === $input['certificate_path'] ) {
				$output['certificate_path'] = '';
			} elseif ( $certificate_path && str_starts_with( $certificate_path, realpath( $certificate_dir ) ) && file_exists( $certificate_path ) ) {
				$args = array(
					'sslcertificates' => $certificate_path,
				);
				$response = wp_remote_get( home_url(), $args );
				if ( is_wp_error( $response ) ) {
					add_settings_error( 'content_aggregator_settings', 'invalid-certificate-path', __( 'Invalid certificate path or file. SSL certificate problem encountered.', 'content-aggregator' ) );
				} else {
					$output['certificate_path'] = $certificate_path;
					$success = true;
				}
			} else {
				add_settings_error( 'content_aggregator_settings', 'certificate-not-found', __( 'Certificate file not found.', 'content-aggregator' ) );
			}
		}
		if ( $success ) {
			add_settings_error( 'content_aggregator_settings', 'success', __( 'Settings saved successfully.', 'content-aggregator' ), 'success' );
		}
		return $output;
	}

	public function add_settings_section() {
		echo '<p>' . esc_html__( 'Configure Content Aggregator settings below.', 'content-aggregator' ) . '</p>';
	}

	public function add_settings_field_update_interval() {
		$options = $this->get_settings();
		echo '<div class="form-field form-required">';
		echo '<select id="update_interval" name="content_aggregator_settings[update_interval]">';
		foreach ( self::INTERVALS as $interval ) {
			echo '<option value="' . esc_attr( $interval ) . '"' . selected( $options['update_interval'], $interval, false ) . '>' . esc_html( self::i18n__date( $interval ) ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select how often Content Aggregator should check for updates and fetch new items.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This setting controls the frequency of background tasks for refreshing sources.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Higher frequencies may increase server load, especially with a large number of sources.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_settings_field_max_update() {
		$options = $this->get_settings();
		echo '<div class="form-field form-required">';
		echo '<input type="number" id="max_update" name="content_aggregator_settings[max_update]" value="' . esc_attr( $options['max_update'] ) . '" min="1" max="50">';
		echo '<p class="description">' . esc_html__( 'Set the maximum number of sources to be updated at each update interval.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This helps distribute updates across different intervals, reducing server load and preventing overload of the WP Cron system.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'A smaller value is beneficial for sites with numerous sources.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_settings_field_expiration_date() {
		$options = $this->get_settings();
		echo '<div class="form-field form-required">';
		echo '<select id="expiration_date" name="content_aggregator_settings[expiration_date]">';
		foreach ( self::EXPIRATION_DATES as $duration ) {
			echo '<option value="' . esc_attr( $duration ) . '"' . selected( $options['expiration_date'], $duration, false ) . '>' . esc_html( self::i18n__date( $duration ) ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Choose the duration after which an offline source is automatically deactivated.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This helps manage source efficiency by removing sources that are consistently offline for an extended period.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_settings_field_certificate_path() {
		$options = $this->get_settings();
		echo '<div class="form-field form-required">';
		echo '<input type="text" id="certificate_path" name="content_aggregator_settings[certificate_path]" class="regular-text" value="' . esc_attr( $options['certificate_path'] ? $options['certificate_path'] : \WpOrg\Requests\Requests::get_certificate_path() ) . '">';
		echo '<p class="description">' . esc_html__( 'Specify the file path to the SSL certificate(s) used for secure HTTPS connections.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This is essential for verifying the authenticity of remote resources during fetching.', 'content-aggregator' ) . '</p>';
		$upload_dir = wp_upload_dir();
		// translators: SSL certificates folder path
		echo '<p class="description">' . esc_html( sprintf( __( 'You can provide a relative path, which will be considered relative to the %s directory.', 'content-aggregator' ), $upload_dir['basedir'] . '/certificates/' ) ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Ensure the specified path is accurate to avoid connection issues.', 'content-aggregator' ) . '</p>';
		echo '<p class="description"><strong>' . esc_html__( 'Default value:', 'content-aggregator' ) . '</strong> ' . esc_html( \WpOrg\Requests\Requests::get_certificate_path() ) . '</p>';
		echo '</div>';
	}

	public function page() {
		if ( isset( $_POST['content_aggregator_reset'] ) ) {
			if ( ! isset( $_POST['content_aggregator_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['content_aggregator_settings_nonce'] ) ), 'content_aggregator_update_settings' ) ) {
				wp_die( 'Security check failed.' );
			}
			delete_option( 'content_aggregator_settings' );
			add_settings_error( 'content_aggregator_settings', 'settings_reset', __( 'Settings successfully reset.', 'content-aggregator' ), 'success' );
		} elseif ( isset( $_POST['content_aggregator_settings'] ) ) {
			if ( ! isset( $_POST['content_aggregator_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['content_aggregator_settings_nonce'] ) ), 'content_aggregator_update_settings' ) ) {
				wp_die( 'Security check failed.' );
			}
			$output = $this->update_settings( $_POST['content_aggregator_settings'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			update_option( 'content_aggregator_settings', $output );
		}
		$next_refresh = human_time_diff( wp_next_scheduled( 'content_aggregator_update_hook' ), time() );
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html( get_admin_page_title() ) . '</h1>&emsp;<span class="title-count">' . esc_html__( 'Next refresh:', 'content-aggregator' ) . ' ' . esc_html( $next_refresh ) . '</span>';
		echo '<hr class="wp-header-end">';
		settings_errors( 'content_aggregator_settings' );
		echo '<form method="post" action="' . esc_url( add_query_arg( 'page', 'content-aggregator-settings', admin_url( 'admin.php' ) ) ) . '">';
		wp_nonce_field( 'content_aggregator_update_settings', 'content_aggregator_settings_nonce' );
		settings_fields( 'content_aggregator_page' );
		do_settings_sections( 'content_aggregator_page' );
		echo '<div class="submit">';
		submit_button( __( 'Save', 'content-aggregator' ), 'primary', 'submit', false );
		echo '&emsp;';
		submit_button(
			__( 'Reset', 'content-aggregator' ),
			'secondary',
			'content_aggregator_reset',
			false,
			array( 'onclick' => 'return confirm(\'' . esc_js( __( 'Are you sure you want to reset all settings? This action cannot be undone.', 'content-aggregator' ) ) . '\');' )
		);
		echo '</div>';
		echo '</form>';
		echo '</div>';
	}

	public static function i18n__date( $date ) {
		switch ( $date ) {
			case '15m':
				return __( '15 minutes', 'content-aggregator' );
			case '30m':
				return __( '30 minutes', 'content-aggregator' );
			case '1h':
				return __( '1 hour', 'content-aggregator' );
			case '12h':
				return __( '12 hours', 'content-aggregator' );
			case '1d':
				return __( '1 day', 'content-aggregator' );
			case '1w':
				return __( '1 week', 'content-aggregator' );
			case '1m':
				return __( '1 month', 'content-aggregator' );
			case 'never':
				return __( 'Never', 'content-aggregator' );
		}
		return '--';
	}
}
