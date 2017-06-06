<?php

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

	/**
	 * Constructor. Registers action hooks.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'rest_pre_dispatch', array( $this, 'rest_pre_dispatch' ), 10, 3 );
	}

	/**
	 * Whether the request is a GET request
	 * @return bool
	 */
	public function voicewp_is_get_request() {
		return isset( $_SERVER['REQUEST_METHOD'] )
			&& ( 'GET' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
	}

	/**
	 * Display a notice if an endpoint is viewed directly in browser
	 */
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
				$alexa_settings = get_option( 'voicewp-settings' );
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

	/**
	 * Returns a flash briefing
	 *
	 * @return WP_REST_Response
	 */
	public function briefing_request() {
		if ( false === ( $result = get_transient( 'voicewp-briefing' ) ) ) {
			$briefing = new \Alexa\Skill\Briefing;
			$result = $briefing->briefing_request();
			// Set long cache time instead of 0 to prevent autoload
			set_transient( 'voicewp-briefing', $result, WEEK_IN_SECONDS );
		}
		return new WP_REST_Response( $result );
	}

	/**
	 * Figures out what kind of skill is being
	 * dealt with and dispatches appropriately
	 *
	 * @return WP_REST_Response
	 */
	public function skill_dispatch( $id, $request, $response ) {

		$skill_type = get_post_meta( $id, 'voicewp_skill_type', true );

		switch ( $skill_type ) {
			case 'Quote':
			case 'fact_quote':
				$quote = new \Alexa\Skill\Quote;
				$quote->quote_request( $id, $request, $response );
				break;
			default:
				do_action( 'voicewp_custom_skill', $skill_type, $id, $request, $response );
				break;
		}
	}

	/**
	 * In case of error, return response
	 *
	 * @return WP_REST_Response
	 */
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
