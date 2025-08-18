<?php
/**
 * Content Aggregator plugin for WordPress
 *
 * @package   content-aggregator
 * @link      https://github.com/relisiuol/content-aggregator
 * @author    relisiuol <contact@relisiuol.fr>
 * @copyright 2023 - 2024 relisiuol
 * @license   GPL v3
 *
 * Plugin Name:       Content Aggregator
 * Description:       Content Aggregator is a plugin to aggregate items from RSS, Rest API & more.
 * Version:           1.0.1
 * Plugin URI:        https://github.com/relisiuol/content-aggregator
 * Author:            relisiuol
 * Author URI:        https://relisiuol.fr/
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       content-aggregator
 * Domain Path:       /languages/
 * Requires PHP:      7.4
 * Requires at least: 6.2
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

declare(strict_types=1);

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CONTENT_AGGREGATOR_VERSION', '1.0.1' );
define( 'CONTENT_AGGREGATOR_DB_VERSION', '1.0.0' );
define( 'CONTENT_AGGREGATOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'CONTENT_AGGREGATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'CONTENT_AGGREGATOR_NAMESPACE_PREFIX', 'Content_Aggregator' );

if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
	return;
}

if ( ! function_exists( 'content_aggregator_install' ) ) {
	function content_aggregator_install() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'content_aggregator_sources';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = $wpdb->prepare(
			'CREATE TABLE %i (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL,
				`url` varchar(100) NOT NULL,
				`scrap_url` varchar(255) NOT NULL,
				`unique_title` tinyint(1) NOT NULL,
				`type` tinyint(1) NOT NULL,
				`user_agent` varchar(255) NULL,
				`categories` text NULL,
				`post_status` varchar(20) NOT NULL DEFAULT "publish",
				`post_title_template` varchar(255) NULL,
				`post_date_template` varchar(255) NULL,
				`content_template` text NULL,
				`featured_image` varchar(255) NULL,
				`last_check` datetime NOT NULL,
				`last_news` varchar(255) NOT NULL,
				`redirect` tinyint(1) NOT NULL,
				`enabled` tinyint(1) NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `scrap_url` (`scrap_url`),
				KEY `url` (`url`),
				KEY `name` (`name`),
				KEY `type` (`type`),
				KEY `last_check` (`last_check`),
				KEY `last_news` (`last_news`),
				KEY `enabled` (`enabled`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
			$table_name
		);
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'content_aggregator_version', CONTENT_AGGREGATOR_DB_VERSION );
	}
}

if ( ! function_exists( 'content_aggregator_uninstall' ) ) {
	function content_aggregator_uninstall() {
		global $wpdb;
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'post_meta',
			array(
				'meta_key' => 'content_aggregator_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			),
			array(
				'%s',
			)
		);
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'post_meta',
			array(
				'meta_key' => 'content_aggregator_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			),
			array(
				'%s',
			)
		);
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'content_aggregator_sources' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		delete_option( 'content_aggregator_db_version' );
		delete_option( 'content_aggregator_settings' );
	}
}

if ( ! function_exists( 'content_aggregator_spl_autoload_register' ) ) {
	function content_aggregator_spl_autoload_register( $class_name ) {
		if ( preg_match( '/' . CONTENT_AGGREGATOR_NAMESPACE_PREFIX . '/', $class_name ) ) {
			$class_name = strtolower(
				str_replace(
					'_',
					'-',
					str_replace(
						'\\',
						DIRECTORY_SEPARATOR,
						str_replace(
							CONTENT_AGGREGATOR_NAMESPACE_PREFIX . '\\',
							'',
							$class_name
						)
					)
				)
			);
			$filename_position = strrpos( $class_name, '/' );
			if ( $filename_position ) {
				$class_name = substr_replace( $class_name, '/class-', $filename_position, 1 );
			} else {
				$class_name = 'class-' . $class_name;
			}
			require CONTENT_AGGREGATOR_DIR . 'src/' . $class_name . '.php';
		}
	}
}

if ( ! function_exists( 'content_aggregator_template_redirect' ) ) {
	function content_aggregator_template_redirect() {
		if ( is_single() ) {
			global $post, $wpdb;
			$url = get_post_meta( $post->ID, 'content_aggregator_url', true );
			$source = get_post_meta( $post->ID, 'content_aggregator_source', true );
			if ( ! empty( $url ) && ! empty( $source ) ) {
				$table_name = $wpdb->prefix . 'content_aggregator_sources';
				$redirect = $wpdb->get_var( $wpdb->prepare( 'SELECT redirect FROM %i WHERE id = %d', $table_name, $source ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $redirect ) {
					wp_redirect( $url, 302 );
					exit;
				}
			}
		}
	}
}

if ( ! function_exists( 'content_aggregator_post_link' ) ) {
	function content_aggregator_post_link( $url ) {
		if ( ! is_admin() ) {
			global $wpdb;
			$post = get_post( url_to_postid( $url ) );
			$real_url = get_post_meta( $post->ID, 'content_aggregator_url', true );
			$source = get_post_meta( $post->ID, 'content_aggregator_source', true );
			if ( ! empty( $real_url ) && ! empty( $source ) ) {
				$table_name = $wpdb->prefix . 'content_aggregator_sources';
				$redirect = $wpdb->get_var( $wpdb->prepare( 'SELECT redirect FROM %i WHERE id = %d', $table_name, $source ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $redirect ) {
					return $real_url;
				}
			}
		}
		return $url;
	}
}

if ( ! function_exists( 'content_aggregator_minimum_php_requirement' ) ) {
	function content_aggregator_minimum_php_requirement() {
		return '8.2';
	}
}

if ( ! function_exists( 'content_aggregator_site_meets_php_requirements' ) ) {
	function content_aggregator_site_meets_php_requirements() {
		return version_compare( phpversion(), content_aggregator_minimum_php_requirement(), '>=' );
	}
}

if ( ! content_aggregator_site_meets_php_requirements() ) {
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p>
					<?php
						echo wp_kses_post(
							sprintf(
								/* translators: %s: Minimum required PHP version */
								__( 'Content Aggregator requires PHP version %s or later. Please upgrade PHP or disable the plugin.', 'content-aggregator' ),
								esc_html( convert_to_blocks_minimum_php_requirement() )
							)
						);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

register_activation_hook( __FILE__, 'content_aggregator_install' );
register_uninstall_hook( __FILE__, 'content_aggregator_uninstall' );

add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'content_aggregator_version' ) !== CONTENT_AGGREGATOR_DB_VERSION ) {
			content_aggregator_install();
		}
		spl_autoload_register( 'content_aggregator_spl_autoload_register' );
	}
);

add_action(
	'init',
	function () {
		add_action( 'template_redirect', 'content_aggregator_template_redirect' );

		add_filter( 'post_link', 'content_aggregator_post_link' );

		\Content_Aggregator\Cron::get_instance();

		if ( is_admin() ) {
			\Content_Aggregator\Admin::get_instance();
		}
	}
);
