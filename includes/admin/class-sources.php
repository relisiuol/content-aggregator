<?php

namespace Content_Aggregator\Admin;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Sources {
	private static $instance;
	private $sources_table;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new \Content_Aggregator\Admin\Sources();
		}
		return self::$instance;
	}

	public function load() {
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'edit_post_per_page',
			)
		);
		$this->sources_table = new \Content_Aggregator\Admin\Sources_Table();
	}

	public function page() {
		$this->sources_table->prepare_items();
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '&emsp;<a class="page-title-action" href="' . esc_url( add_query_arg( 'page', 'content-aggregator-add-edit', admin_url( 'admin.php' ) ) ) . '" class="page-title-action">' . esc_html__( 'Add', 'content-aggregator' ) . '</a>';
		echo '<hr class="wp-header-end">';
		settings_errors( 'content_aggregator_sources' );
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="content-aggregator">';
		$this->sources_table->search_box( __( 'Search', 'content-aggregator' ), 'content-aggregator-search' );
		$this->sources_table->display();
		echo '</form>';
		echo '</div>';
	}
}
