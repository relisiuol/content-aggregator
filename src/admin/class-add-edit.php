<?php

namespace Content_Aggregator\Admin;

if ( ! function_exists( 'add_action' ) || ! defined( 'ABSPATH' ) || ! defined( 'CONTENT_AGGREGATOR_DIR' ) ) {
	echo 'Hi there! I&apos;m just a plugin, not much I can do when called directly.';
	exit;
}

class Add_Edit {
	const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';
	const TITLE_TAGS = array(
		'__TITLE__',
		'__SOURCE_NAME__',
	);
	const DATE_TAGS = array(
		'__NOW__',
		'__DATE__',
	);
	const CONTENT_TAGS = array(
		'__CONTENT__',
		'__URL__',
	);
	private static $instance;
	private $source;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new \Content_Aggregator\Admin\Add_Edit();
		}
		return self::$instance;
	}

	public function load() {
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $id > 0 ) {
			$this->source = $this->get_source( $id );
		} else {
			$this->source = false;
		}
		add_action( 'in_admin_footer', array( $this, 'in_admin_footer' ) );
		add_settings_section( 'content_aggregator_section_source', false, false, 'content_aggregator_page_source' );
		add_settings_field( 'content_aggregator_source_name', __( 'Name', 'content-aggregator' ), array( $this, 'add_source_name' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-name' ) );
		add_settings_field( 'content_aggregator_source_url', __( 'URL', 'content-aggregator' ), array( $this, 'add_source_url' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-url' ) );
		add_settings_field( 'content_aggregator_source_scrap_url', __( 'Source URL', 'content-aggregator' ), array( $this, 'add_source_scrap_url' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-scrap_url' ) );
		add_settings_field( 'content_aggregator_source_type', __( 'Type', 'content-aggregator' ), array( $this, 'add_source_type' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-type' ) );
		add_settings_field( 'content_aggregator_source_unique_title', __( 'Unique title', 'content-aggregator' ), array( $this, 'add_source_unique_title' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-unique_title' ) );
		add_settings_field( 'content_aggregator_source_user_agent', __( 'User-Agent', 'content-aggregator' ), array( $this, 'add_source_user_agent' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-user_agent' ) );
		add_settings_field( 'content_aggregator_source_categories', __( 'Categories', 'content-aggregator' ), array( $this, 'add_source_categories' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-categories' ) );
		add_settings_field( 'content_aggregator_source_post_status', __( 'Post status', 'content-aggregator' ), array( $this, 'add_source_post_status' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-post_status' ) );
		add_settings_field( 'content_aggregator_source_post_title_template', __( 'Post title template', 'content-aggregator' ), array( $this, 'add_source_post_title_template' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-post_title_template' ) );
		add_settings_field( 'content_aggregator_source_post_date_template', __( 'Post date template', 'content-aggregator' ), array( $this, 'add_source_post_date_template' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-post_date_template' ) );
		add_settings_field( 'content_aggregator_source_content_template', __( 'Post content template', 'content-aggregator' ), array( $this, 'add_source_content_template' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-content_template' ) );
		add_settings_field( 'content_aggregator_source_featured_image', __( 'Default featured image', 'content-aggregator' ), array( $this, 'add_source_featured_image' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-featured_image' ) );
		add_settings_field( 'content_aggregator_source_redirect', __( 'Redirect', 'content-aggregator' ), array( $this, 'add_source_redirect' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-redirect' ) );
		add_settings_field( 'content_aggregator_source_enabled', __( 'Enabled', 'content-aggregator' ), array( $this, 'add_source_enabled' ), 'content_aggregator_page_source', 'content_aggregator_section_source', array( 'label_for' => 'content_aggregator_source-enabled' ) );
		add_filter( 'admin_title', array( $this, 'admin_title' ), 10, 2 );
		$this->highlight_menu();
	}

	public function highlight_menu() {
		global $pagenow, $parent_file, $plugin_page, $submenu;
		$menu_slug = 'content-aggregator';
		$plugin_page = $menu_slug; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( isset( $submenu[ $menu_slug ] ) ) {
			foreach ( $submenu[ $menu_slug ] as $key => $menu_item ) {
				if ( $menu_slug === $menu_item[2] ) {
					$parent_file = $menu_slug; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					break;
				}
			}
		}
	}

	public function admin_title( $admin_title, $title = '' ) {
		return ( $this->source ? __( 'Edit source', 'content-aggregator' ) : __( 'Add source', 'content-aggregator' ) ) . ' &lsaquo; ' . $admin_title;
	}

	private static function default_source() {
		return array(
			'name'                => '',
			'url'                 => '',
			'scrap_url'           => '',
			'type'                => '0',
			'unique_title'        => '0',
			'user_agent'          => self::DEFAULT_USER_AGENT,
			'categories'          => '',
			'post_status'         => 'publish',
			'post_title_template' => self::TITLE_TAGS[0],
			'post_date_template'  => self::DATE_TAGS[0],
			'content_template'    => self::CONTENT_TAGS[0],
			'featured_image'      => '',
			'redirect'            => false,
			'enabled'             => false,
		);
	}

	private function update_source( $input ) {
		global $wpdb;
		$success = true;
		$input = wp_parse_args(
			array_filter(
				$input,
				function ( $v ) {
					return in_array(
						$v,
						array_keys( $this->default_source() )
					);
				},
				ARRAY_FILTER_USE_KEY
			),
			array()
		);
		$input['name'] = isset( $input['name'] ) ? sanitize_text_field( wp_unslash( $input['name'] ) ) : '';
		if ( empty( $input['name'] ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-name', __( 'Name is required.', 'content-aggregator' ) );
		}
		$input['url'] = isset( $input['url'] ) ? sanitize_url( wp_unslash( $input['url'] ), array( 'http', 'https' ) ) : '';
		if ( empty( $input['url'] ) || ! filter_var( $input['url'], FILTER_VALIDATE_URL ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-url', __( 'URL is missing or invalid.', 'content-aggregator' ) );
		}
		$input['scrap_url'] = isset( $input['scrap_url'] ) ? sanitize_url( wp_unslash( $input['scrap_url'] ), array( 'http', 'https' ) ) : '';
		if ( empty( $input['scrap_url'] ) || ! filter_var( $input['scrap_url'], FILTER_VALIDATE_URL ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-scrap_url', __( 'Source URL is missing or invalid.', 'content-aggregator' ) );
		}
		$input['type'] = isset( $input['type'] ) ? intval( $input['type'] ) : '0';
		if ( ! in_array( $input['type'], array( '0', '1' ) ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-type', __( 'Source type is missing or invalid.', 'content-aggregator' ) );
		}
		$input['unique_title'] = isset( $input['unique_title'] ) && $input['unique_title'] ? '1' : '0';
		$input['user_agent'] = isset( $input['user_agent'] ) ? sanitize_text_field( wp_unslash( $input['user_agent'] ) ) : '';
		if ( empty( $input['user_agent'] ) || ! $this->source || $this->source['user_agent'] !== $input['user_agent'] ) {
			if ( empty( $input['user_agent'] ) ) {
				add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'missing-user_agent', __( 'User-Agent is missing.', 'content-aggregator' ) );
			} else {
				$response = wp_remote_get( home_url(), array( 'user-agent' => $input['user_agent'] ) );
				if ( is_wp_error( $response ) ) {
					$success = false;
					add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-user_agent', __( 'User-Agent is invalid.', 'content-aggregator' ) );
				}
			}
		}
		$input['categories'] = isset( $input['categories'] ) ? array_map( 'intval', $input['categories'] ) : array();
		if ( ! empty( $input['categories'] ) && $success ) {
			foreach ( $input['categories'] as $category_id ) {
				if ( ! term_exists( $category_id, 'category' ) ) {
					$success = false;
					add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-categories', __( 'One or more selected categories are invalid.', 'content-aggregator' ) );
					break;
				}
			}
			$input['categories'] = (string) implode( ', ', $input['categories'] );
		}
		$input['post_status'] = isset( $input['post_status'] ) ? sanitize_key( wp_unslash( $input['post_status'] ) ) : '';
		if ( ! get_post_status_object( $input['post_status'] ) || ! get_post_status_object( $input['post_status'] )->show_in_admin_status_list ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-post_status', __( 'Post status is invalid or missing.', 'content-aggregator' ) );
		}
		$input['post_title_template'] = isset( $input['post_title_template'] ) ? sanitize_text_field( wp_unslash( $input['post_title_template'] ) ) : '';
		if ( ! preg_match( '/' . implode( '|', array_merge( self::TITLE_TAGS, self::DATE_TAGS ) ) . '/', $input['post_title_template'] ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-post_title_template', __( 'Post title template is invalid or missing.', 'content-aggregator' ) );
		}
		$input['post_date_template'] = isset( $input['post_date_template'] ) ? sanitize_text_field( wp_unslash( $input['post_date_template'] ) ) : '';
		if ( ! preg_match( '/' . implode( '|', self::DATE_TAGS ) . '/', $input['post_date_template'] ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-post_date_template', __( 'Post date template is invalid or missing.', 'content-aggregator' ) );
		}
		$input['content_template'] = isset( $input['content_template'] ) ? wp_kses_post( wp_unslash( $input['content_template'] ) ) : '';
		if ( ! preg_match( '/' . implode( '|', array_merge( self::TITLE_TAGS, self::DATE_TAGS, self::CONTENT_TAGS ) ) . '/', $input['content_template'] ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-content_template', __( 'Post content template is invalid or missing.', 'content-aggregator' ) );
		}
		$input['featured_image'] = isset( $input['featured_image'] ) ? intval( $input['featured_image'] ) : '';
		if ( empty( $input['featured_image'] ) || ! wp_get_attachment_image( $input['featured_image'] ) ) {
			$success = false;
			add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'invalid-featured_image', __( 'Featured image is invalid or missing.', 'content-aggregator' ) );
		}
		$input['redirect'] = isset( $input['redirect'] ) && $input['redirect'] ? true : false;
		$input['enabled']  = isset( $input['enabled'] ) && $input['enabled'] ? true : false;
		if ( $success ) {
			$this->source = wp_parse_args(
				$input,
				wp_parse_args(
					( $this->source ? $this->source : array() ),
					$this->default_source()
				)
			);
			$table_name = $wpdb->prefix . 'content_aggregator_sources';
			$format = array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' );
			if ( ! empty( $this->source['id'] ) ) {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$table_name,
					$input,
					array(
						'id' => $this->source['id'],
					),
					$format,
					array( '%d' )
				);
				add_settings_error( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'success', __( 'Source saved successfully.', 'content-aggregator' ), 'success' );
			} else {
				$count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(id) FROM %i WHERE scrap_url = %s', $table_name, $input['scrap_url'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $count > 0 ) {
					add_settings_error( 'content_aggregator_source', 'invalid-scrap_url', __( 'Source URL is already used.', 'content-aggregator' ) );
				} else {
					$input['last_news'] = '';
					$id = $wpdb->insert( $table_name, $input, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					if ( $id ) {
						$this->source['id'] = $wpdb->insert_id;
						;
						wp_redirect(
							add_query_arg(
								array(
									'page' => 'content-aggregator-add-edit',
									'id' => $this->source['id'],
								),
								admin_url( 'admin.php' )
							)
						);
						exit;
					}
				}
			}
		}
	}

	public function in_admin_footer() {
		wp_enqueue_media();
		wp_enqueue_style( 'select2', CONTENT_AGGREGATOR_URL . 'dist/css/select2.min.css', array(), '4.0.13' );
		wp_enqueue_style( 'content-aggregator', CONTENT_AGGREGATOR_URL . 'dist/css/content-aggregator.min.css', array( 'select2' ), CONTENT_AGGREGATOR_VERSION );
		wp_enqueue_script( 'select2', CONTENT_AGGREGATOR_URL . 'dist/js/select2.min.js', array( 'jquery' ), '4.0.13', array( 'in_footer' => true ) );
		wp_enqueue_script( 'content-aggregator', CONTENT_AGGREGATOR_URL . 'dist/js/content-aggregator.min.js', array( 'jquery-ui-autocomplete', 'select2', 'wp-i18n' ), CONTENT_AGGREGATOR_VERSION, array( 'in_footer' => true ) );
		wp_localize_script(
			'content-aggregator',
			'contentAggregator',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'content-aggregator-ajax' ),
			)
		);
		wp_set_script_translations( 'content-aggregator', 'content-aggregator', CONTENT_AGGREGATOR_DIR . 'languages/' );
	}

	public function add_source_name() {
		echo '<div class="form-field form-required">';
		echo '<input type="text" name="content_aggregator_source[name]" id="content_aggregator_source-name" value="' . ( $this->source ? esc_attr( $this->source['name'] ) : '' ) . '" />';
		// translators: __SOURCE_NAME__: Placeholder for the source name used in templates.
		echo '<p class="description">' . esc_html( sprintf( __( 'Specify the source name to be used in title templates as %s.', 'content-aggregator' ), self::TITLE_TAGS[1] ) ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This dynamic approach allows for more personalized content creation.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_url() {
		echo '<div class="form-field form-required">';
		echo '<input type="url" name="content_aggregator_source[url]" id="content_aggregator_source-url" value="' . ( $this->source ? esc_attr( $this->source['url'] ) : '' ) . '" />';
		echo '<p class="description">' . esc_html__( 'This is the main source URL.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_scrap_url() {
		echo '<div class="form-field form-required">';
		echo '<input type="url" name="content_aggregator_source[scrap_url]" id="content_aggregator_source-scrap_url" value="' . ( $this->source ? esc_attr( $this->source['scrap_url'] ) : '' ) . '" />';
		echo '<p class="description">' . esc_html__( 'This is the URL from which the content will be scraped.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This URL is the direct source of the content and differs from main site URL.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_type() {
		$current_type = $this->source ? $this->source['type'] : '';
		echo '<div class="form-field form-required">';
		echo '<select name="content_aggregator_source[type]" id="content_aggregator_source-type">';
		echo '<option value="0" ' . selected( $current_type, '0', false ) . '>RSS</option>';
		echo '<option value="1" ' . selected( $current_type, '1', false ) . '>' . esc_html__( 'WordPress', 'content-aggregator' ) . '</option>';
		echo '<option value="2" ' . selected( $current_type, '2', false ) . '>Atom RSS</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Choose the type of the source. Type determines how the content is processed and analyzed.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Once you paste a source URL, it will try to determine the right type.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_unique_title() {
		echo '<div class="form-field">';
		echo '<input type="checkbox" name="content_aggregator_source[unique_title]" id="content_aggregator_source-unique_title" value="1" ' . ( $this->source && $this->source['unique_title'] ? 'checked' : '' ) . ' />';
		echo '<p class="description">' . esc_html__( 'Enable to ensure all fetched posts have unique titles.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This feature helps prevent duplicate content issues and improves SEO.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_user_agent() {
		echo '<div class="form-field form-required">';
		echo '<textarea name="content_aggregator_source[user_agent]" id="content_aggregator_source-user_agent" rows="3" cols="50">' . esc_html( $this->source && ! empty( $this->source['user_agent'] ) ? $this->source['user_agent'] : self::DEFAULT_USER_AGENT ) . '</textarea>';
		// translators: %s: Placeholder for an user-agent.
		echo '<p class="description">' . esc_html__( 'Enter a custom user agent for requests. Leave blank to use default.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'A valid user agent ensures compatibility and successful data retrieval.', 'content-aggregator' ) . '</p>';
		echo '<p class="description"><strong>' . esc_html__( 'Default value:', 'content-aggregator' ) . '</strong> ' . esc_html( self::DEFAULT_USER_AGENT ) . '</p>';
		echo '</div>';
	}

	public function add_source_categories() {
		$categories = get_terms(
			array(
				'taxonomy' => 'category',
				'hide_empty' => false,
			)
		);
		$selected_categories = $this->source && ! empty( $this->source['categories'] ) ? explode( ', ', $this->source['categories'] ) : array();
		echo '<div class="form-field form-required">';
		echo '<select name="content_aggregator_source[categories][]" id="content_aggregator_source-categories" multiple="multiple">';
		foreach ( $categories as $category ) {
			echo '<option value="' . esc_attr( $category->term_id ) . '" ' . ( in_array( $category->term_id, $selected_categories ) ? 'selected' : '' ) . '>' . esc_html( $category->name ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Assign categories automatically to new posts.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_post_status() {
		$post_statuses = get_post_stati(
			array(
				'show_in_admin_status_list' => true,
				'_builtin' => true,
			),
			'objects'
		);
		$current_status = $this->source ? esc_attr( $this->source['post_status'] ) : 'publish';
		echo '<div class="form-field form-required">';
		echo '<select name="content_aggregator_source[post_status]" id="content_aggregator_source-post_status">';
		foreach ( $post_statuses as $status ) {
			echo '<option value="' . esc_attr( $status->name ) . '" ' . selected( $current_status, $status->name, false ) . '>' . esc_html( $status->label ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select the default post status for new posts fetched from the source.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This determines how posts appear on your website and who can see them, based on WordPress visibility settings.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_post_title_template() {
		echo '<div class="form-field form-required">';
		echo '<input type="text" name="content_aggregator_source[post_title_template]" id="content_aggregator_source-post_title_template" value="' . esc_attr( $this->source && ! empty( $this->source['post_title_template'] ) ? $this->source['post_title_template'] : self::TITLE_TAGS[0] ) . '" data-tags="' . esc_attr( wp_json_encode( array_merge( self::TITLE_TAGS, self::DATE_TAGS ) ) ) . '" />';
		echo '<p class="description">';
		echo esc_html(
			sprintf(
				// translators: Placeholders for dynamic tags used in title templates.
				__( 'Create a title template using dynamic tags such as %s.', 'content-aggregator' ),
				implode( __( ' and ', 'content-aggregator' ), self::TITLE_TAGS )
			)
		);
		echo '</p>';
		echo '<p class="description">';
		echo esc_html(
			sprintf(
				// translators: Placeholders for dynamic tags used in title templates.
				__( 'Incorporate date information with %s to keep titles timely and relevant.', 'content-aggregator' ),
				implode( __( ' and ', 'content-aggregator' ), self::DATE_TAGS )
			)
		);
		echo '</p>';
		echo '</div>';
	}

	public function add_source_post_date_template() {
		echo '<div class="form-field form-required">';
		echo '<select name="content_aggregator_source[post_date_template]" id="content_aggregator_source-post_date_template">';
		foreach ( self::DATE_TAGS as $date_tag ) {
			echo '<option value="' . esc_attr( $date_tag ) . '" ' . selected( ( $this->source && ! empty( $this->source['post_date_template'] ) ? $this->source['post_date_template'] : '' ), $date_tag, false ) . '>' . esc_html( $date_tag ) . '</option>';
		}
		echo '</select>';
		// translators: __DATE__, __NOW__: Placeholders for dynamic tags used in date templates.
		echo '<p class="description">' . esc_html( sprintf( __( 'Design your date display using %s.', 'content-aggregator' ), implode( __( ' and ', 'content-aggregator' ), self::DATE_TAGS ) ) ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This template ensures dates are consistently formatted across your content.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_content_template() {
		$tags = array(
			self::TITLE_TAGS[0]   => 'The title of the original content.',
			self::TITLE_TAGS[1]   => 'The name of the content source.',
			self::DATE_TAGS[0]    => 'The current date when the content is fetched.',
			self::DATE_TAGS[1]    => 'The publication date of the original content.',
			self::CONTENT_TAGS[0] => 'The main body of the content.',
			self::CONTENT_TAGS[1] => 'The URL of the original content.',
		);
		echo '<div class="form-field form-required">';
		echo '<textarea name="content_aggregator_source[content_template]" id="content_aggregator_source-content_template" rows="4" cols="50" data-tags="' . esc_attr( wp_json_encode( array_merge( self::TITLE_TAGS, self::DATE_TAGS, self::CONTENT_TAGS ) ) ) . '">' . esc_html( $this->source && ! empty( $this->source['content_template'] ) ? $this->source['content_template'] : self::CONTENT_TAGS[0] ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Define the content structure with placeholders for a rich, dynamic presentation.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Utilize tags for dates, titles, and URLs to weave together engaging posts.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Available tags:', 'content-aggregator' ) . '</p>';
		echo '<ul>';
		echo '<li><p class="description">' . esc_html( self::TITLE_TAGS[0] . ' • ' . __( 'The original item\'s title.', 'content-aggregator' ) ) . '</p></li>';
		echo '<li><p class="description">' . esc_html( self::TITLE_TAGS[1] . ' • ' . __( 'The source\'s name providing the content.', 'content-aggregator' ) ) . '</p></li>';
		echo '<li><p class="description">' . esc_html( self::DATE_TAGS[0] . ' • ' . __( 'The current date when retrieving items.', 'content-aggregator' ) ) . '</p></li>';
		echo '<li><p class="description">' . esc_html( self::DATE_TAGS[1] . ' • ' . __( 'The publication date.', 'content-aggregator' ) ) . '</p></li>';
		echo '<li><p class="description">' . esc_html( self::CONTENT_TAGS[0] . ' • ' . __( 'The main source\'s content, if available.', 'content-aggregator' ) ) . '</p></li>';
		echo '<li><p class="description">' . esc_html( self::CONTENT_TAGS[1] . ' • ' . __( 'URL to the original content.', 'content-aggregator' ) ) . '</p></li>';
		echo '</ul>';
		echo '</div>';
	}

	public function add_source_featured_image() {
		$image_id = $this->source ? esc_attr( $this->source['featured_image'] ) : '';
		echo '<div class="form-field form-required content-aggregator-image-selector">';
		echo '<button type="button" class="button select-image" id="content_aggregator_source-featured_image">' . esc_html__( 'Select Image', 'content-aggregator' ) . '</button>';
		echo '<input type="hidden" name="content_aggregator_source[featured_image]" value="' . esc_attr( $image_id ) . '" />';
		echo '<div id="image-preview" style="margin-top: 10px; max-width: 250px;">';
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, 'thumbnail' );
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Provide a default featured image.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'This image enhances posts, ensuring a visually appealing presentation when no other image is reachable.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_redirect() {
		echo '<div class="form-field">';
		echo '<input type="checkbox" name="content_aggregator_source[redirect]" id="content_aggregator_source-redirect" value="1" ' . ( $this->source && $this->source['redirect'] ? 'checked' : '' ) . ' />';
		echo '<p class="description">' . esc_html__( 'Toggle this to redirect visitors to the original item\'s url instead of having a single page.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function add_source_enabled() {
		echo '<div class="form-field">';
		echo '<input type="checkbox" name="content_aggregator_source[enabled]" id="content_aggregator_source-enabled" value="1" ' . ( $this->source && $this->source['enabled'] ? 'checked' : '' ) . ' />';
		echo '<p class="description">' . esc_html__( 'Toggle this to enable or disable the source.', 'content-aggregator' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'When enabled, the source will be actively queried for new item.', 'content-aggregator' ) . '</p>';
		echo '</div>';
	}

	public function get_source( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'content_aggregator_sources';
		$source = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $source ) {
			$source = wp_parse_args(
				$source,
				$this->default_source()
			);
			return $source;
		}
		return false;
	}

	public function page() {
		if ( isset( $_POST['content_aggregator_source'] ) && is_array( $_POST['content_aggregator_source'] ) ) {
			if ( ! isset( $_POST['content_aggregator_source_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['content_aggregator_source_nonce'] ) ), 'content_aggregator_update_source' . ( $this->source ? '_' . $this->source['id'] : '' ) ) ) {
				wp_die( 'Security check failed.' );
			}
			$this->update_source( $_POST['content_aggregator_source'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		$page_args = array(
			'page' => 'content-aggregator-add-edit',
		);
		if ( $this->source ) {
			$page_args['id'] = $this->source['id'];
		}
		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html( $this->admin_title( get_admin_page_title() ) ) . '</h1>';
		echo '<hr class="wp-header-end">';
		settings_errors( 'content_aggregator_source' . ( $this->source ? '_' . $this->source['id'] : '' ) );
		echo '<form method="post" action="' . esc_url( add_query_arg( $page_args, admin_url( 'admin.php' ) ) ) . '">';
		wp_nonce_field( 'content_aggregator_update_source' . ( $this->source ? '_' . $this->source['id'] : '' ), 'content_aggregator_source_nonce' );
		settings_fields( 'content_aggregator_page_source' );
		do_settings_sections( 'content_aggregator_page_source' );
		submit_button( __( 'Save', 'content-aggregator' ) );
		echo '</form>';
		echo '</div>';
	}
}
