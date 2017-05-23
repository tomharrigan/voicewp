<?php
/**
 * Plugin Name: VoiceWP
 * Description: Create Alexa skills using your WordPress site
 * Plugin URI: https://github.com/tomharrigan/
 * Author: TomHarrigan
 * Author URI: https://voicewp.com
 * Version: 0.2
 * Text Domain: voicewp
 * License: MIT
 */

define( 'VOICEWP_PATH', dirname( __FILE__ ) );

register_activation_hook( __FILE__, 'voicewp_activate' );
function voicewp_activate() {
	Voicewp::get_instance();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'voicewp_deactivate' );
function voicewp_deactivate() {
	flush_rewrite_rules();
}

/**
 * Compatibility requirements.
 */
require_once( VOICEWP_PATH . '/compat.php' );

/**
 * Post Type Base Class
 */
require_once( VOICEWP_PATH . '/post-types/class-voicewp-post-type.php' );

/**
 * Skill Post Type
 */
require_once( VOICEWP_PATH . '/post-types/class-voicewp-post-type-skill.php' );

/**
 * Flash Briefing Post Type
 */
require_once( VOICEWP_PATH . '/post-types/class-voicewp-post-type-briefing.php' );

/**
 * Fieldmanager custom fields
 */
function voicewp_load_fieldmanager_fields() {
	require_once( VOICEWP_PATH . '/voicewp-fieldmanager-content-textarea.php' );
	require_once( VOICEWP_PATH . '/fields.php' );
}
add_action( 'init', 'voicewp_load_fieldmanager_fields' );

/**
 * Load a class from within the plugin based on a class name.
 *
 * @param string $classname Class name to load.
 */
function voicewp_autoload_function( $classname ) {
	if ( class_exists( $classname ) || 0 !== strpos( $classname, 'Alexa' ) ) {
		return;
	}
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '_', '-', strtolower( $classname ) ) );

	// create the actual filepath
	$file_path = VOICEWP_PATH . DIRECTORY_SEPARATOR . $class . '.php';

	// check if the file exists
	if ( file_exists( $file_path ) ) {
		// require once on the file
		require_once $file_path;
	}
}

/**
 * Get the post types whose content is included in the bundled News skill.
 *
 * @return array Post type names.
 */
function voicewp_news_post_types() {
	/**
	 * Filters the post types whose content is included in the bundled News skill.
	 *
	 * @param array $post_types Post type names.
	 */
	return apply_filters( 'voicewp_post_types', array( 'post' ) );
}

/**
 * Get the taxonomies whose terms can be specified by users invoking the News skill.
 *
 * @return array Taxonomy names.
 */
function voicewp_news_taxonomies() {
	$option = get_option( 'voicewp-settings' );

	$taxonomies = ( empty( $option['latest_taxonomies'] ) ) ? array() : $option['latest_taxonomies'];

	// Nonexistant taxonomies can shortcircuit get_terms().
	return array_filter( $taxonomies, 'taxonomy_exists' );
}

spl_autoload_register( 'voicewp_autoload_function' );

class Voicewp_Setup {
	/**
	 * Plugin version.
	 *
	 * @var string
	 * @access public
	 */
	public static $version = '1.0.0';

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'add_image_size' ) );
		add_filter( 'allowed_http_origins', array( $this, 'allowed_http_origins' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
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
	 * Changes the meta key prefix
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
	 * Attempt to migrate options from an older version of this plugin.
	 *
	 * @access public
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
new Voicewp_Setup();

add_action( 'init', array( 'Voicewp', 'get_instance' ), 0 );

use Alexa\Request\IntentRequest;
use Alexa\Request\LaunchRequest;

use Alexa\Response\Response;
use Alexa\Request\Request;
class Voicewp {
	protected static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 10, 3 );
	}

	public function voicewp_is_get_request() {
		return isset( $_SERVER['REQUEST_METHOD'] )
			&& ( 'GET' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
	}

	public function voicewp_maybe_display_notice() {
		if ( $this->voicewp_is_get_request() ) {
			esc_html_e( 'To test your skill, use an Alexa enabled device or Echosim.io', 'voicewp' );
			exit();
		}
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		// Endpoint for flash briefing
		register_rest_route( 'voicewp/v1', '/skill/briefing', array(
			'callback' => array( $this, 'briefing_request' ),
			'methods' => array( 'GET' ),
		) );
		// Endpoint for News skill
		register_rest_route( 'voicewp/v1', '/skill/news', array(
			'callback' => array( $this, 'voicewp_news_request' ),
			'methods' => array( 'POST', 'GET' ),
		) );
		// Endpoint for all other skills
		register_rest_route( 'voicewp/v1', '/skill/(?P<id>\d+)', array(
			'callback' => array( $this, 'voicewp_skill_request' ),
			'methods' => array( 'POST', 'GET' ),
		) );
	}

	/**
	 * Allows request to be hijacked if a legact alexawp route is being used
	 * redirects legacy routes to new voicewp route.
	 * @param null $null
	 * @param WP_REST_Server $that
	 * @param WP_REST_Request $request
	 * @return mixed
	 */
	public function rest_pre_dispatch( $null, $that, $request ) {
		if ( 0 === strpos( $request->get_route(), '/alexawp' ) ) {
			$route = str_replace( 'alexawp', 'voicewp', $request->get_route() );
			$request->set_route( $route );
			return rest_do_request( $request );
		}
		return null;
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function voicewp_skill_request( WP_REST_Request $request ) {

		$this->voicewp_maybe_display_notice();

		$body = $request->get_body();

		$id = absint( $request->get_param( 'id' ) );

		if ( ! empty( $body ) ) {
			try {
				// get config based on url
				//This ID will be made optional, first do a check for if standalone or not
				$app_id = get_post_meta( $id, 'voicewp_skill_app_id', true );
				$certificate = new \Alexa\Request\Certificate( $request->get_header( 'signaturecertchainurl' ), $request->get_header( 'signature' ), $app_id );
				$alexa = new \Alexa\Request\Request( $body, $app_id );
				$alexa->set_certificate_dependency( $certificate );

				// Parse and validate the request.
				$alexa_request = $alexa->from_data();

			} catch ( InvalidArgumentException $e ) {
				return $this->fail_response( $e );
			}

			$response_object = new \Alexa\Response\Response;
			$event = new AlexaEvent( $alexa_request, $response_object );

			$request = $event->get_request();
			$response = $event->get_response();

			$this->skill_dispatch( $id, $request, $response );

			return new WP_REST_Response( $response_object->render() );
		}
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function voicewp_news_request( WP_REST_Request $request ) {

		$this->voicewp_maybe_display_notice();

		$body = $request->get_body();

		if ( ! empty( $body ) ) {
			try {
				$alexa_settings = get_option( 'alexawp-settings' );
				// The main amazon Application ID
				$app_id = $alexa_settings['news_id'];
				$certificate = new \Alexa\Request\Certificate( $request->get_header( 'signaturecertchainurl' ), $request->get_header( 'signature' ), $app_id );
				$alexa = new \Alexa\Request\Request( $body, $app_id );
				$alexa->set_certificate_dependency( $certificate );

				// Parse and validate the request.
				$alexa_request = $alexa->from_data();
			} catch ( InvalidArgumentException $e ) {
				return $this->fail_response( $e );
			}
			$response = new \Alexa\Response\Response;
			$event = new AlexaEvent( $alexa_request, $response );

			$news = new \Alexa\Skill\News;
			$news->news_request( $event );

			return new WP_REST_Response( $response->render() );
		}
	}

	public function briefing_request() {
		if ( false === ( $result = get_transient( 'voicewp-briefing' ) ) ) {
			$briefing = new \Alexa\Skill\Briefing;
			$result = $briefing->briefing_request();
			set_transient( 'voicewp-briefing', $result );
		}
		return new WP_REST_Response( $result );
	}

	public function skill_dispatch( $id, $request, $response ) {

		$skill_type = get_post_meta( $id, 'alexawp_skill_type', true );

		switch ( $skill_type ) {
			case 'fact_quote':
				$quote = new \Alexa\Skill\Quote;
				$quote->quote_request( $id, $request, $response );
				break;
			default:
				do_action( 'voicewp_custom_skill', $skill_type, $id );
				break;
		}
	}

	private function fail_response( $e ) {
		return new WP_REST_Response(
			array(
				'version' => '1.0',
				'response' => array(
					'outputSpeech' => array(
						'type' => 'PlainText',
						'text' => $e->getMessage(),
					),
					'shouldEndSession' => true,
				),
				'sessionAttributes' => array(),
			),
			200
		);
	}
}
