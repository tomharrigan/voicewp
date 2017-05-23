<?php

class Voicewp_Setup {
	/**
	 * Plugin version.
	 *
	 * @var string
	 * @access public
	 */
	public static $version = '1.0.0';

	protected static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'add_image_size' ) );
		add_filter( 'allowed_http_origins', array( $this, 'allowed_http_origins' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		foreach ( voicewp_news_post_types() as $post_type ) {
			add_action( 'publish_' . $post_type, array( $this, 'publish_clear_cache' ), 10, 2 );
		}
	}

	/**
	 * Handle clearing cache on single items when a post is edited
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post object
	 * @param bool $update Is post updated or new
	 */
	public function save_post( $post_id, $post, $update ) {
		if (
			empty( $post_id )
			|| wp_is_post_revision( $post_id )
			|| ( defined( 'WP_IMPORTING' ) && WP_IMPORTING === true )
			|| ! in_array( $post->post_type, voicewp_news_post_types() )
			|| 'publish' !== get_post_status( $post_id )
			|| false == $update
		) {
			return;
		}
		delete_transient( 'voicewp_single_' . $post_id );
	}

	/**
	 * Handle clearing cache on single items when a post is edited
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post object
	 * @param bool $update Is post updated or new
	 */
	public function publish_clear_cache( $post_id, $post ) {
		delete_transient( 'voicewp_latest' );
		$args = array(
			'post_type' => voicewp_news_post_types(),
			'posts_per_page' => 5,
			'tax_query' => array(),
		);
		$news = new \Alexa\Skill\News;
		$news->endpoint_content( $args );
	}

	/**
	 * Add image sizes for Alexa cards.
	 */
	public function add_image_size() {
		add_image_size( 'alexa-small', 720, 480, true );
		add_image_size( 'alexa-large', 1200, 800, true );
	}

	/**
	 * Add the Alexa service to the list of allowed HTTP origins
	 *
	 * @param $allowed_origins array Default allowed HTTP origins
	 * @return array allowed origin URLs
	 */
	public function allowed_http_origins( $allowed_origins ) {
		$allowed_origins[] = 'http://ask-ifr-download.s3.amazonaws.com';
		$allowed_origins[] = 'https://ask-ifr-download.s3.amazonaws.com';
		return $allowed_origins;
	}

	/**
	 * Action hook callback for plugins_loaded.
	 * Sets a plugin version.
	 * Migration functions will never run on new installs.
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		// Determine if the database version and code version are the same.
		$current_version = get_option( 'voicewp_version' );
		if ( version_compare( $current_version, self::$version, '>=' ) ) {
			return;
		}
		/**
		 * Determine if this is a legacy version
		 * If this option is empty, it's a new install, or not legacy
		 * If the option exists, trigger the update functions
		 */
		$settings = get_option( 'alexawp-settings' );
		if ( empty( $settings ) ) {
			update_option( 'voicewp_version', self::$version );
			return;
		} else {
			$this->upgrade_to_1_0_0();
		}

		// Set the database version to the current version in code.
		update_option( 'voicewp_version', self::$version );
	}

	/**
	 * Upgrades options, meta and post types to be version 1.0.0 compatible
	 *
	 * @access public
	 */
	public function upgrade_to_1_0_0() {
		$this->upgrade_post_types_and_meta();
		$this->upgrade_options();
	}

	/**
	 * Attempt to migrate meta and post types from an older version of this plugin.
	 * Changes the prefix of options, meta, post types
	 *
	 * @access public
	 */
	public function upgrade_post_types_and_meta() {
		global $wpdb, $wp_post_types;

		// array of each legacy post type with mapping of what data will change to
		$legacy = array(
			'alexawp-briefing' => array(
				'post_type' => 'voicewp-briefing',
				'postmeta' => array(
					'voicewp_briefing_source' => 'alexawp_briefing_source',
					'voicewp_briefing_uuid' => 'alexawp_briefing_uuid',
					'voicewp_briefing_audio_url' => 'alexawp_briefing_audio_url',
					'voicewp_briefing_attachment_id' => 'alexawp_briefing_attachment_id',
				),
			),
			'alexawp-skill' => array(
				'post_type' => 'voicewp-skill',
				'postmeta' => array(
					'voicewp_skill_is_standalone' => 'alexawp_skill_is_standalone',
					'voicewp_skill_type' => 'alexawp_skill_type',
					'voicewp_skill_default_image' => 'alexawp_skill_default_image',
					'voicewp_skill_app_id' => 'alexawp_skill_app_id',
				),
			),
		);

		foreach ( $legacy as $legacy_post_type => $legacy_data ) {
			register_post_type( $legacy_post_type, array(
				'public' => false,
				'rewrite' => false,
			) );
			// Check if there are existing posts of this type first
			if ( array_sum( (array) wp_count_posts( $legacy_post_type ) ) > 0 ) {
				// update related post meta to new prefix
				foreach ( $legacy_data['postmeta'] as $new_key => $old_key ) {
					$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_key = %s WHERE meta_key = %s", $new_key, $old_key ) ); // WPCS: db call ok. cache ok.
				}
				// rename the post type
				$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_type = %s WHERE post_type = %s", $legacy_data['post_type'], $legacy_post_type ) ); // WPCS: db call ok. cache ok.
				// unregister post type
				$post_type_object = get_post_type_object( $legacy_post_type );
				$post_type_object->remove_supports();
			    $post_type_object->unregister_meta_boxes();
			    $post_type_object->remove_hooks();
			    $post_type_object->unregister_taxonomies();
			    unset( $wp_post_types[ $legacy_post_type ] );
			}
		}
	}

	/**
	 * Update options from an older version of this plugin.
	 */
	public function upgrade_options() {
		$voicewp_index_settings = get_option( 'alexawp_skill_index_map' );
		if ( ! empty( $voicewp_index_settings ) ) {
			update_option( 'voicewp_skill_index_map', $voicewp_index_settings );
			delete_option( 'alexawp_skill_index_map' );
		}

		$voicewp_settings = get_option( 'alexawp-settings' );
		if ( ! empty( $voicewp_settings ) ) {
			update_option( 'voicewp-settings', $voicewp_settings );
			delete_option( 'alexawp-settings' );
		}
	}
}

Voicewp_Setup::get_instance();
