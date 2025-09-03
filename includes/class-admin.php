<?php

namespace Content_Aggregator;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Admin {
	private static $instance;
	private $page;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new \Content_Aggregator\Admin();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'wp_ajax_content_aggregator', array( $this, 'wp_ajax' ) );
	}

	public function admin_menu() {
		$title = __( 'Content Aggregator', 'content-aggregator' );
		$suffix = ' &lsaquo; ' . $title;
		$menu_slug = 'content-aggregator';
		$pages = $this->pages();
		$hook = add_menu_page(
			__( 'Sources', 'content-aggregator' ) . $suffix,
			$title,
			'manage_options',
			$menu_slug,
			array( $this, 'page' ),
			'dashicons-admin-site',
			6
		);
		add_action( 'load-' . $hook, array( $this, 'load_page' ) );
		foreach ( $pages as $page_key => $page_args ) {
			$menu          = array_key_exists( 'menu', $page_args ) ? $page_args['menu'] : $title;
			$submenu       = array_key_exists( 'menu_slug', $page_args ) ? $page_args['menu_slug'] : $menu_slug;
			$menu_title    = array_key_exists( 'title', $page_args ) ? $page_args['title'] : ( array_key_exists( 'menu', $page_args ) ? $menu . $suffix : $title );
			$real_page_key = array_key_exists( 'key', $page_args ) ? $page_args['key'] : $page_key;
			$hook = add_submenu_page(
				$submenu,
				$menu_title,
				$menu,
				'manage_options',
				$real_page_key,
				array( $this, 'page' )
			);
			add_action( 'load-' . $hook, array( $this, 'load_page' ) );
		}
		$this->preload_page();
	}

	public function preload_page() {
		global $pagenow, $plugin_page;
		if ( 'admin.php' === $pagenow && ( 'content-aggregator' === $plugin_page || in_array( $plugin_page, array_keys( $this->pages() ), true ) ) ) {
			$page = isset( $plugin_page ) && in_array( $plugin_page, array_keys( $this->pages() ), true ) ? str_replace( 'content-aggregator-', '', sanitize_file_name( $plugin_page ) ) : '';
			if ( empty( $page ) || ! file_exists( CONTENT_AGGREGATOR_DIR . 'includes/admin/class-' . $page . '.php' ) ) {
				$page = 'sources';
			}
			$page = '\\Content_Aggregator\\Admin\\' . str_replace(
				'-',
				'_',
				ucwords(
					$page,
					"- \t\r\n\f\v"
				)
			);
			$this->page = $page::get_instance();
		}
	}

	public function load_page() {
		if ( $this->page && method_exists( $this->page, 'load' ) ) {
			$this->page->load();
		}
	}

	public function page() {
		if ( $this->page && method_exists( $this->page, 'page' ) ) {
			$this->page->page();
		}
	}

	private function pages() {
		return array(
			'content-aggregator-sources'  => array(
				'key'  => 'content-aggregator',
				'menu' => __( 'Sources', 'content-aggregator' ),
			),
			'content-aggregator-add-edit' => array(
				'menu_slug' => '',
			),
			'content-aggregator-settings' => array(
				'menu' => __( 'Settings', 'content-aggregator' ),
			),
			'content-aggregator-parsers'  => array(
				'menu' => __( 'Parsers', 'content-aggregator' ),
			),
		);
	}

	public function plugin_action_links( $links, $file ) {
		if ( basename( dirname( $file ) ) === 'content-aggregator' ) {
			$links = array_merge(
				array(
					'<a href="' . esc_url( add_query_arg( 'page', 'content-aggregator-settings', admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Settings', 'content-aggregator' ) . '</a>',
				),
				$links
			);
		}
		return $links;
	}

	public function wp_ajax() {
		if ( isset( $_GET['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
			if ( wp_verify_nonce( $nonce, 'content-aggregator-ajax' ) && current_user_can( 'manage_options' ) ) {
				if ( isset( $_GET['url'] ) && ! empty( $_GET['url'] ) ) {
					$base_url = sanitize_text_field( wp_unslash( $_GET['url'] ) );
					if ( ! empty( $base_url ) && filter_var( $base_url, FILTER_VALIDATE_URL ) ) {
						$result = \Content_Aggregator\Decoders\Auto_Detector::detect( $base_url );
						if ( $result ) {
							wp_send_json_success( $result );
						}
					}
				}
				wp_send_json_error( array( 'success' => 0 ) );
			}
		}
		wp_die( esc_html__( 'You are not authorized to perform this action.', 'content-aggregator' ), '', array( 'response' => 403 ) );
	}
}
