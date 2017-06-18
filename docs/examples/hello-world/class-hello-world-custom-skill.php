<?php

use Alexa\Request\IntentRequest;
use Alexa\Request\LaunchRequest;

use Alexa\Response\Response;
use Alexa\Request\Request;
/**
 * Class that creates a custom skill allowing WordPress content to be consumed via Alexa
 */
class Hello_World_Custom_Skill {

	private $app_id = ''; // Add your Amazon app ID here

	/**
	 * Constructor. Registers action hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( 'voicewp/v1', '/hello-world', array(
			'callback' => array( $this, 'hello_world_skill' ),
			'methods' => array( 'POST', 'GET' ),
		) );
	}

	/**
	 * Main functionality of the Hello World skill
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function hello_world_skill( WP_REST_Request $request ) {
		// Allows us to leverage the core plugin to handle the grunt work
		$voicewp_instance = Voicewp::get_instance();
		// Prevents people from being able to hit the skill directly in browser
		$voicewp_instance->voicewp_maybe_display_notice();

		// Gets the Alexa Request and Response objects for us to use in our skill
		list( $request, $response ) = $voicewp_instance->voicewp_get_request_response_objects( $request, $this->app_id );

		// The main functionality of your skill
		$this->hello_world( $request, $response );

		//Send the result to the user
		return new WP_REST_Response( $response->render() );

	}

	/**
	 * Figures out what kind of intent we're dealing with from the request
	 * Handles grabbing the needed data and delivering the response
	 * @param AlexaEvent $event
	 */
	public function hello_world( $request, $response ) {
		/**
		 * Check the type of request.
		 *
		 * If it's a LaunchRequest, they didn't ask for anything spefically,
		 * A LaunchRequest occurs by the user saying 'Alexa, open Hello World' for example
		 *
		 * If it's an IntentRequest, the user said something in addition to opening the skill.
		 * In our below example, we've added the ability to include your name,
		 * so Alexa can respond with 'Hello, {your name}' instead of 'Hello World'
		 * This example also shows how to support built-in intents such as 'Help', 'Stop', and 'Cancel'.
		 */
		if ( $request instanceof \Alexa\Request\IntentRequest ) {
			$intent = $request->intent_name;
			switch ( $intent ) {
				case 'Hello':
					$response
						->respond( __( 'Hello world!', 'voicewp' ) )
						/* translators: %s: site title */
						->with_card( sprintf( __( 'Hello from %s', 'voicewp' ), get_bloginfo( 'name' ) ) )
						->end_session();
					break;
				case 'AMAZON.StopIntent':
				case 'AMAZON.CancelIntent':
					$response->respond( __( 'Goodbye!', 'voicewp' ) );
					$response->end_session();
					break;
				case 'AMAZON.HelpIntent':
					$response->respond( __( 'What do you want help with? I only say hello.', 'voicewp' ) );
					break;
				default:
					break;
			}
		} elseif ( $request instanceof \Alexa\Request\LaunchRequest ) {
			$response
				->respond( __( 'Hello world!', 'voicewp' ) )
				/* translators: %s: site title */
				->with_card( sprintf( __( 'Hello from %s', 'voicewp' ), get_bloginfo( 'name' ) ) )
				->end_session();
		}
	}
}
new Hello_World_Custom_Skill();
