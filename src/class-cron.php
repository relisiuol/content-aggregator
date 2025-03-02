<?php

namespace Content_Aggregator;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Cron {
	private static $instance;
	private $settings;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new \Content_Aggregator\Cron();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->settings = \Content_Aggregator\Admin\Settings::get_instance()->get_settings();
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'content_aggregator_update_hook', array( $this, 'execute_cron_job' ) );
		$this->schedule_cron_event();
	}

	public function cron_schedules( $schedules ) {
		$new_intervals = array(
			'15m' => array(
				'interval' => 15 * 60,
				'display' => __( 'Every 15 minutes', 'content-aggregator' ),
			),
			'30m' => array(
				'interval' => 30 * 60,
				'display' => __( 'Every 30 minutes', 'content-aggregator' ),
			),
		);
		foreach ( $new_intervals as $key => $value ) {
			if ( ! isset( $schedules[ $key ] ) ) {
				$schedules[ $key ] = $value;
			}
		}
		return $schedules;
	}

	public function execute_cron_job() {
		global $wpdb;
		$original_certif_path = \WpOrg\Requests\Requests::get_certificate_path();
		if ( ! empty( $this->settings['certificate_path'] ) ) {
			$upload_dir = wp_upload_dir();
			\WpOrg\Requests\Requests::set_certificate_path( $upload_dir['basedir'] . '/certificates/' . $this->settings['certificate_path'] );
		}
		$table_name = $wpdb->prefix . 'content_aggregator_sources';
		$sources = $wpdb->get_results( // WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT id, name, scrap_url, unique_title, type, user_agent, categories, post_status, post_title_template, post_date_template, content_template, featured_image, last_check, last_news FROM %i WHERE enabled = 1 ORDER BY last_check ASC LIMIT %d',
				$table_name,
				intval( $this->settings['max_update'] )
			),
			ARRAY_A
		);
		if ( ! empty( $sources ) ) {
			$requests = array();
			foreach ( $sources as $source ) {
				$requests[] = array(
					'url' => $source['scrap_url'],
					'headers' => array(
						'User-Agent' => ( empty( $source['user_agent'] ) ? \Content_Aggregator\Admin\Add_Edit::DEFAULT_USER_AGENT : $source['user_agent'] ),
						'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
						'Accept-Language' => 'en-us,en;q=0.5',
						'Accept-Encoding' => 'gzip,deflate',
						'Accept-Charset' => 'utf-8,iso-8859-1;q=0.9,*;q=0.8',
						'Keep-Alive' => '110',
						'Connection' => 'keep-alive',
						'Cache-Control' => 'max-age=0',
					),
				);
			}
			$responses = \WpOrg\Requests\Requests::request_multiple( $requests );
			foreach ( $responses as $i => $response ) {
				$source = $sources[ $i ];
				$empty = true;
				$decoder = false;
				if ( '0' === $source['type'] ) {
					$decoder = new \Content_Aggregator\Decoders\RSS();
				} else if ( '1' === $source['type'] ) {
					$decoder = new \Content_Aggregator\Decoders\WP();
				} else if ( '2' === $source['type'] ) {
					$decoder = new \Content_Aggregator\Decoders\Atom();
				}
				if ( $decoder ) {
					$items = $decoder->decode( $response->body );
					if ( $items && ! empty( $items ) ) {
						foreach ( $items as $i => $item ) {
							if (
								! empty( $item['date'] ) &&
								! empty( $item['title'] ) &&
								! empty( $item['url'] )
							) {
								$item['url'] = filter_var( $item['url'], FILTER_VALIDATE_URL ) ? remove_query_arg(
									'utm_source',
									remove_query_arg(
										'utm_medium',
										remove_query_arg(
											'utm_campaign',
											$item['url']
										)
									)
								) : '';
								$item['content'] = preg_replace(
									array_map(
										function ( $item ) {
											return '/' . preg_quote( $item, '/' ) . '/';
										},
										array_merge(
											\Content_Aggregator\Admin\Add_Edit::TITLE_TAGS,
											\Content_Aggregator\Admin\Add_Edit::DATE_TAGS,
											\Content_Aggregator\Admin\Add_Edit::CONTENT_TAGS
										)
									),
									array(
										$item['title'],
										$source['name'],
										$item['date'],
										gmdate( 'Y-m-d H:i:s' ),
										$item['content'],
										$item['url'],
									),
									$source['content_template']
								);
								$item['title'] = preg_replace(
									array_map(
										function ( $item ) {
											return '/' . preg_quote( $item, '/' ) . '/';
										},
										array_merge(
											\Content_Aggregator\Admin\Add_Edit::TITLE_TAGS,
											\Content_Aggregator\Admin\Add_Edit::DATE_TAGS
										)
									),
									array(
										$item['title'],
										$source['name'],
										$item['date'],
										gmdate( 'Y-m-d H:i:s' ),
									),
									$source['post_title_template']
								);
								$item['date'] = ( \Content_Aggregator\Admin\AddEdit::DATE_TAGS[0] === $source['post_date_template'] ? gmdate( 'Y-m-d H:i:s' ) : $item['date'] );
								if (
									! empty( $item['date'] ) &&
									! empty( $item['title'] ) &&
									! empty( $item['url'] )
								) {
									$empty = false;
									if ( $item['url'] === $source['last_news'] ) {
										break;
									}
									if ( 0 === $i ) {
										$wpdb->update( // WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
											$table_name,
											array(
												'last_news' => $item['url'],
											),
											array(
												'id' => $source['id'],
											)
										);
									}
									$insert = true;
									if ( '1' === $source['unique_title'] ) {
										$insert = $wpdb->get_var( // WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
											$wpdb->prepare(
												'SELECT COUNT(*) FROM %i WHERE post_title = %s',
												$wpdb->posts,
												$item['title']
											)
										);
										$insert = empty( $insert );
									}
									if ( $insert ) {
										$postdata = array(
											'post_title'    => sanitize_text_field( $item['title'] ),
											'post_content'  => wp_kses_post( $item['content'] ),
											'post_date'     => $item['date'],
											'post_date_gmt' => get_gmt_from_date( $item['date'] ),
											'post_author'   => $this->get_default_author_id(),
										);
										if ( ! empty( $source['categories'] ) ) {
											$postdata['post_category'] = explode( ',', $source['categories'] );
										}
										if ( strtotime( $item['date'] ) > current_time( 'timestamp' ) ) {
											$postdata['post_status'] = ( 'publish' === $source['post_status'] ) ? 'future' : $source['post_status'];
										} else {
											$postdata['post_status'] = $source['post_status'];
										}
										$post_id = wp_insert_post( $postdata );
										if ( $post_id ) {
											add_post_meta( $post_id, 'content_aggregator_source', $source['id'] );
											add_post_meta( $post_id, 'content_aggregator_url', $item['url'] );
											if ( ! empty( $item['image'] ) && filter_var( $item['image'], FILTER_VALIDATE_URL ) ) {
												if ( ! function_exists( 'media_sideload_image' ) ) {
													require_once ABSPATH . 'wp-admin/includes/media.php';
													require_once ABSPATH . 'wp-admin/includes/file.php';
													require_once ABSPATH . 'wp-admin/includes/image.php';
												}
												$image = media_sideload_image( $item['image'], $post_id, null, 'src' );
												if ( ! is_wp_error( $image ) ) {
													$attach_id = attachment_url_to_postid( $image );
													if ( $attach_id > 0 ) {
														if ( ! set_post_thumbnail( $post_id, $attach_id ) ) {
															set_post_thumbnail( $post_id, $source['featured_image'] );
														}
													} else {
														set_post_thumbnail( $post_id, $source['featured_image'] );
													}
												} else {
													set_post_thumbnail( $post_id, $source['featured_image'] );
												}
											} else {
												set_post_thumbnail( $post_id, $source['featured_image'] );
											}
										}
									}
								}
							}
						}
					}
				}
				if ( ! $empty ) {
					$wpdb->update( // WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$table_name,
						array(
							'last_check' => current_time( 'mysql', 1 ),
						),
						array(
							'id' => $source['id'],
						)
					);
				} elseif ( strtotime( $source['last_check'] ) + $this->map_time_interval( $this->settings['expiration_date'] ) < time() ) {
					$wpdb->update(
						$table_name,
						array(
							'enabled' => 0,
						),
						array(
							'id' => $source['id'],
						)
					);
				}
			}
		}
		if ( ! empty( $this->settings['certificate_path'] ) ) {
			\WpOrg\Requests\Requests::set_certificate_path( $original_certif_path );
		}
	}

	private function schedule_cron_event() {
		$user_interval = isset( $this->settings['update_interval'] ) ? $this->settings['update_interval'] : '';
		$mapped_interval = $this->map_event_interval( $user_interval );
		if ( ! $mapped_interval ) {
			$mapped_interval = 'daily';
		}
		if ( ! wp_next_scheduled( 'content_aggregator_update_hook' ) ) {
			wp_schedule_event( time(), $mapped_interval, 'content_aggregator_update_hook' );
		}
	}

	private function map_event_interval( $user_interval ) {
		return $this->map_interval(
			$user_interval,
			array(
				'15m' => '15m',
				'30m' => '30m',
				'1h'  => 'hourly',
				'12h' => 'twicedaily',
				'1d'  => 'daily',
				'1w'  => 'weekly',
			)
		);
	}

	private function map_time_interval( $user_interval ) {
		return $this->map_interval(
			$user_interval,
			array(
				'1h'  => 3600,
				'12h' => 43200,
				'1d'  => 86400,
				'1w'  => 604800,
				'1m'  => 2419200,
			)
		);
	}

	private function map_interval( $user_interval, $interval_mapping ) {
		return isset( $interval_mapping[ $user_interval ] ) ? $interval_mapping[ $user_interval ] : false;
	}

	public static function clean_url( $url ) {
		return rtrim(
			preg_replace(
				array(
					'/utm([_a-z0-9=%]+)\&?/',
					'/source=([_a-z0-9-%]+)\&?/',
				),
				array(
					'',
					'',
				),
				$url
			),
			'&?#'
		);
	}

	private function get_default_author_id() {
		$users = get_users(
			array(
				'role'   => 'Administrator',
				'number' => 1,
				'fields' => 'ids',
			)
		);
		if ( ! empty( $users ) ) {
			return $users[0];
		}
		$fallback_user = get_users(
			array(
				'number' => 1,
				'fields' => 'ids',
			)
		);
		return ! empty( $fallback_user ) ? $fallback_user[0] : false;
	}
}
