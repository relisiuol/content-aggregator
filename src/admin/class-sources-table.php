<?php

namespace Content_Aggregator\Admin;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there!  I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Sources_Table extends \WP_List_Table {
	public function __construct() {
		global $screen;
		parent::__construct(
			array(
				'singular' => 'content-aggregator-source',
				'plural'   => 'content-aggregator-sources',
			)
		);
		$doaction = $this->current_action();
		if ( $doaction ) {
			if ( ! check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( 'Security check failed.' );
			}
			$sendback = remove_query_arg( array( 'enabled', 'disabled', 'deleted' ), wp_get_referer() );
			if ( ! $sendback ) {
				$sendback = add_query_arg(
					'page',
					'content-aggregator',
					admin_url( 'admin.php' )
				);
			}
			$sendback = add_query_arg( 'paged', $this->get_pagenum, $sendback );
			$ids = array();
			if ( ! empty( $_GET['id'] ) ) {
				$ids = array_map( 'intval', $_GET['id'] );
			}
			if ( empty( $ids ) ) {
				wp_redirect( $sendback );
				exit;
			}
			$this->process_bulk_action( $ids, $sendback );
		} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
			if ( ! check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( 'Security check failed.' );
			}
			$sendback = add_query_arg( 'paged', $this->get_pagenum, wp_get_referer() );
			$sendback = remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $sendback );
			wp_safe_redirect( $sendback );
			exit;
		}
	}

	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'title'       => __( 'Name', 'content-aggregator' ),
			'last_check'  => __( 'Last check', 'content-aggregator' ),
			'categories'  => __( 'Categories', 'content-aggregator' ),
			'post_status' => __( 'Post status', 'content-aggregator' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'title'      => array( 'title', true, __( 'Name', 'content-aggregator' ), __( 'Table ordered by name', 'content-aggregator' ), 'asc' ),
			'last_check' => array( 'last_check', false, __( 'Last check', 'content-aggregator' ), __( 'Table ordered by last check date', 'content-aggregator' ) ),
		);
	}

	protected function get_hidden_columns() {
		$screen = get_current_screen();
		return get_hidden_columns( $screen );
	}

	public function column_default( $item, $column_name ) {
		return esc_html( $item );
	}

	protected function column_cb( $item ) {
		return '<input type="checkbox" name="id[]" value="' . esc_attr( $item['id'] ) . '" />';
	}

	protected function column_title( $item ) {
		return '<strong><a href="' . add_query_arg(
			array(
				'page' => 'content-aggregator-add-edit',
				'id' => $item['id'],
			),
			admin_url( 'admin.php' )
		) . '">' . esc_html( $item['name'] ) . '</a>' .
		'&emsp;—&emsp;<span class="post-state">' . ( $item['enabled'] ? esc_html__( 'Enabled', 'content-aggregator' ) : esc_html__( 'Disabled', 'content-aggregator' ) ) . '</span></strong>';
	}

	protected function column_last_check( $item ) {
		if ( '0000-00-00 00:00:00' === $item['last_check'] ) {
			return '-';
		}
		return date_i18n( get_option( 'date_format' ), strtotime( $item['last_check'] ) );
	}

	protected function column_categories( $item ) {
		$item['categories'] && ! empty( $item['categories'] ) ? explode( ', ', $item['categories'] ) : array();
		if ( ! is_array( $item['categories'] ) && ! empty( $item['categories'] ) ) {
			$item['categories'] = array( $item['categories'] );
		}
		$links = array();
		if ( ! empty( $item['categories'] ) ) {
			foreach ( $item['categories'] as $category ) {
				$cat = get_category( $category );
				if ( ! is_wp_error( $cat ) ) {
					$links[] = '<a href="' . esc_url( add_query_arg( 'category_name', $cat->slug, admin_url( 'edit.php' ) ) ) . '">' . esc_html( $cat->name ) . '</a>';
				}
			}
			return implode( ', ', $links );
		}
		return '-';
	}

	protected function column_post_status( $item ) {
		$status_object = get_post_status_object( $item['post_status'] );
		if ( $status_object ) {
			return esc_html( $status_object->label );
		} else {
			return esc_html__( 'Status not found', 'content-aggregator' );
		}
	}

	public function get_bulk_actions() {
		return array(
			'enable'  => __( 'Enable', 'content-aggregator' ),
			'disable' => __( 'Disable', 'content-aggregator' ),
			'delete'  => __( 'Delete permanently', 'content-aggregator' ),
		);
	}

	public function prepare_items() {
		global $wpdb;
		$per_page = $this->get_items_per_page( 'edit_post_per_page', 20 );
		$table_name = $wpdb->prefix . 'content_aggregator_sources';
		$search_term = get_search_query();
		$columns = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden = $this->get_hidden_columns();
		$orderby = ( ! empty( $_GET['orderby'] ) && array_key_exists( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ), $sortable ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'name'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = ( ! empty( $_GET['order'] ) && 'desc' === $_GET['order'] ) ? 'desc' : 'asc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = $this->get_pagenum();
		$offset = ( $current_page - 1 ) * $per_page;
		$this->items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT * FROM %i WHERE `name` LIKE %s ORDER BY %i ' . $order . ' LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$table_name,
				'%' . $wpdb->esc_like( $search_term ) . '%',
				$orderby,
				$per_page,
				$offset
			),
			ARRAY_A
		);
		$total_items = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE `name` LIKE %s',
				$table_name,
				'%' . $wpdb->esc_like( $search_term ) . '%',
			)
		);
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
		$this->_column_headers = array( $columns, $hidden, $sortable );
		if ( array_key_exists( 'enabled', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_settings_error( 'content_aggregator_sources', 'success', __( 'Source(s) enabled successfully.', 'content-aggregator' ), 'success' );
		}
		if ( array_key_exists( 'disabled', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_settings_error( 'content_aggregator_sources', 'success', __( 'Source(s) disabled successfully.', 'content-aggregator' ), 'success' );
		}
		if ( array_key_exists( 'deleted', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_settings_error( 'content_aggregator_sources', 'success', __( 'Source(s) deleted successfully.', 'content-aggregator' ), 'success' );
		}
	}

	public function has_items() {
		return ! empty( $this->items );
	}

	public function no_items() {
		esc_html_e( 'No source found.', 'content-aggregator' );
	}

	public function process_bulk_action( $ids, $sendback ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'content_aggregator_sources';
		if ( ! check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) {
			wp_die( 'Security check failed.' );
		}
		if ( 'enable' === $this->current_action() ) {
			$i = 0;
			foreach ( $ids as $id ) {
				$i += $wpdb->update( $table_name, array( 'enabled' => 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			if ( $i > 0 ) {
				$sendback = add_query_arg( 'enabled', $i, $sendback );
			}
		} elseif ( 'disable' === $this->current_action() ) {
			$i = 0;
			foreach ( $ids as $id ) {
				$i += $wpdb->update( $table_name, array( 'enabled' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
			if ( $i > 0 ) {
				$sendback = add_query_arg( 'disabled', $i, $sendback );
			}
		} elseif ( 'delete' === $this->current_action() ) {
			$i = 0;
			foreach ( $ids as $id ) {
				$i += $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
			}
			if ( $i > 0 ) {
				$sendback = add_query_arg( 'deleted', $i, $sendback );
			}
		}
		$sendback = remove_query_arg( array( 'action', 'action2', 'id' ), $sendback );
		wp_redirect( $sendback );
		exit;
	}
}
