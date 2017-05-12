<?php
/**
 * Plugin Name: Alexa WP
 * Description: Create Alexa skills using your WordPress site
 * Plugin URI: https://github.com/tomharrigan/
 * Author: TomHarrigan
 * Author URI: https://alexawp.com
 * Version: 0.1
 * Text Domain: alexawp
 * License: MIT
 */

define( 'ALEXAWP_PATH', dirname( __FILE__ ) );

register_activation_hook( __FILE__, 'alexawp_activate' );
function alexawp_activate() {
	Alexawp::get_instance();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'alexawp_deactivate' );
function alexawp_deactivate() {
	flush_rewrite_rules();
}

/**
 * Compatibility requirements.
 */
require_once( ALEXAWP_PATH . '/compat.php' );

/**
 * Post Type Base Class
 */
require_once( ALEXAWP_PATH . '/post-types/class-alexawp-post-type.php' );

/**
 * Skill Post Type
 */
require_once( ALEXAWP_PATH . '/post-types/class-alexawp-post-type-skill.php' );

/**
 * Flash Briefing Post Type
 */
require_once( ALEXAWP_PATH . '/post-types/class-alexawp-post-type-briefing.php' );

/**
 * Fieldmanager custom fields
 */
function alexawp_load_fieldmanager_fields() {
	require_once( ALEXAWP_PATH . '/fields.php' );
}
add_action( 'init', 'alexawp_load_fieldmanager_fields' );

function alexawp_autoload_function( $classname ) {
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '_', '-', strtolower( $classname ) ) );

	// create the actual filepath
	$file_path = ALEXAWP_PATH . DIRECTORY_SEPARATOR . $class . '.php';

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
function alexawp_news_post_types() {
	/**
	 * Filters the post types whose content is included in the bundled News skill.
	 *
	 * @param array $post_types Post type names.
	 */
	return apply_filters( 'alexawp_post_types', array( 'post' ) );
}

/**
 * Get the taxonomies whose terms can be specified by users invoking the News skill.
 *
 * @return array Taxonomy names.
 */
function alexawp_news_taxonomies() {
	$option = get_option( 'alexawp-settings' );

	$taxonomies = ( empty( $option['latest_taxonomies'] ) ) ? array() : $option['latest_taxonomies'];

	// Nonexistant taxonomies can shortcircuit get_terms().
	return array_filter( $taxonomies, 'taxonomy_exists' );
}

spl_autoload_register( 'alexawp_autoload_function' );

add_action( 'init', array( 'Alexawp', 'get_instance' ), 0 );

use Alexa\Request\IntentRequest;
use Alexa\Request\LaunchRequest;

use Alexa\Response\Response;
use Alexa\Request\Request;
class Alexawp {
	protected static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'after_setup_theme', array( $this, 'add_image_size' ) );
		add_filter( 'allowed_http_origins', array( $this, 'allowed_http_origins' ) );
	}

	public function alexawp_is_get_request() {
		return isset( $_SERVER['REQUEST_METHOD'] )
			&& ( 'GET' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
	}

	public function alexawp_maybe_display_notice() {
		if ( $this->alexawp_is_get_request() ) {
			esc_html_e( 'To test your skill, use an Alexa enabled device or Echosim.io', 'alexawp' );
			exit();
		}
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		// Endpoint for flash briefing
		register_rest_route( 'alexawp/v1', '/skill/briefing', array(
			'callback' => array( $this, 'briefing_request' ),
			'methods' => array( 'GET' ),
		) );
		// Endpoint for News skill
		register_rest_route( 'alexawp/v1', '/skill/news', array(
			'callback' => array( $this, 'alexawp_news_request' ),
			'methods' => array( 'POST', 'GET' ),
		) );
		// Endpoint for all other skills
		register_rest_route( 'alexawp/v1', '/skill/(?P<id>\d+)', array(
			'callback' => array( $this, 'alexawp_skill_request' ),
			'methods' => array( 'POST', 'GET' ),
		) );
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
	 * Get one item from the collection
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function alexawp_skill_request( WP_REST_Request $request ) {

		$this->alexawp_maybe_display_notice();

		$body = $request->get_body();

		$id = absint( $request->get_param( 'id' ) );

		if ( ! empty( $body ) ) {
			try {
				// get config based on url
				//This ID will be made optional, first do a check for if standalone or not
				$app_id = get_post_meta( $id, 'alexawp_skill_app_id', true );
				$certificate = new \Alexa\Request\Certificate( $request->get_header( 'signaturecertchainurl' ), $request->get_header( 'signature' ), $app_id );
				$alexa = new \Alexa\Request\Request( $body, $app_id );
				$alexa->setCertificateDependency( $certificate );

				// Parse and validate the request.
				$alexa_request = $alexa->fromData();

			} catch ( InvalidArgumentException $e ) {
				return $this->fail_response( $e );
			}

			$response = new \Alexa\Response\Response;
			$event = new AlexaEvent( $alexa_request, $response );

			$this->skill_dispatch( $id, $event );

			return new WP_REST_Response( $response->render() );
		}
	}

	/**
	 * Get one item from the collection
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function alexawp_news_request( WP_REST_Request $request ) {

		$this->alexawp_maybe_display_notice();

		$body = $request->get_body();

		if ( ! empty( $body ) ) {
			try {
				$alexa_settings = get_option( 'alexawp-settings' );
				// The main amazon Application ID
				$app_id = $alexa_settings['news_id'];
				$certificate = new \Alexa\Request\Certificate( $request->get_header( 'signaturecertchainurl' ), $request->get_header( 'signature' ), $app_id );
				$alexa = new \Alexa\Request\Request( $body, $app_id );
				$alexa->setCertificateDependency( $certificate );

				// Parse and validate the request.
				$alexa_request = $alexa->fromData();
			} catch ( InvalidArgumentException $e ) {
				return $this->fail_response( $e );
			}
			$response = new \Alexa\Response\Response;
			$event = new AlexaEvent( $alexa_request, $response );

			$news = new Alexa_News();
			$news->news_request( $event );

			return new WP_REST_Response( $response->render() );
		}
	}

	public function briefing_request() {
		$briefing = new Alexa_Briefing();
		if ( false === ( $result = get_transient( 'alexawp-briefing' ) ) ) {
			$result = $briefing->briefing_request();
			set_transient( 'alexawp-briefing', $result );
		}
		return new WP_REST_Response( $result );
	}

	public function skill_dispatch( $id, $event ) {

		$skill_type = get_post_meta( $id, 'alexawp_skill_type', true );

		switch ( $skill_type ) {
			case 'fact_quote':
				$quote = new Alexa_Quote();
				$quote->quote_request( $id, $event );
				break;
			default:
				do_action( 'alexawp_custom_skill', $skill_type, $id );
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
