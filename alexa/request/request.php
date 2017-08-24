<?php
/**
 * This is the base request, used for types of requests such as IntentRequest and LaunchRequest
 * Stores the data of a request and makes sure the request is valid
 */
namespace Alexa\Request;

use RuntimeException;
use InvalidArgumentException;
use DateTime;

use Alexa\Request\Certificate;
use Alexa\Request\Application;

class Request {

	public $timestamp;
	/** @var Session */
	public $session;
	public $data;
	public $raw_data;
	public $application_id;

	/**
	 * Set up Request with timestamp (DateTime) and user (User obj.)
	 * @param string $raw_data
	 * @param string $application_id
	 */
	public function __construct( $raw_data, $application_id = null ) {
		if ( ! is_string( $raw_data ) ) {
			throw new InvalidArgumentException( __( 'Alexa Request requires the raw JSON data to validate request signature', 'voicewp' ) );
		}

		// Decode the raw data into a JSON array.
		$data = json_decode( $raw_data, true );
		$this->data = $data;
		$this->raw_data = $raw_data;

		$this->timestamp = new DateTime( $data['request']['timestamp'] );
		$this->session = new Session( $data['session'] );

		$this->application_id = ( is_null( $application_id ) && isset( $data['session']['application']['applicationId'] ) )
			? $data['session']['application']['applicationId']
			: $application_id;

	}

	/**
	 * Accept the certificate validator dependency in order to allow people
	 * to extend it to for example cache their certificates.
	 * @param \Alexa\Request\Certificate $certificate
	 */
	public function set_certificate_dependency( \Alexa\Request\Certificate $certificate ) {
		$this->certificate = $certificate;
	}

	/**
	 * Accept the application validator dependency in order to allow people
	 * to extend it.
	 * @param \Alexa\Request\Application $application
	 */
	public function set_application_dependency( \Alexa\Request\Application $application ) {
		$this->application = $application;
	}

	/**
	 * Instance the correct type of Request, based on the $jons->request->type
	 * value.
	 * @param type $data
	 * @return \Alexa\Request\Request   base class
	 * @throws RuntimeException
	 */
	public function from_data() {
		$data = $this->data;

		// Instantiate a new Certificate validator if none is injected
		// as our dependency.
		if ( ! isset( $this->certificate ) && ( isset( $_SERVER ) && isset( $_SERVER['HTTP_SIGNATURECERTCHAINURL'] ) && isset( $_SERVER['HTTP_SIGNATURE'] ) ) ) {
			$this->certificate = new Certificate( esc_url_raw( wp_unslash( $_SERVER['HTTP_SIGNATURECERTCHAINURL'] ) ), sanitize_text_field( wp_unslash( $_SERVER['HTTP_SIGNATURE'] ) ) );
		}
		if ( ! isset( $this->application ) ) {
			$this->application = new Application( $this->application_id );
		}

		// We need to ensure that the request Application ID matches our Application ID.
		$this->application->validate_application_id( $data['session']['application']['applicationId'] );
		// Validate that the request signature matches the certificate.
		$this->certificate->validate_request( $this->raw_data );

		$request_type = $data['request']['type'];
		if ( ! class_exists( '\\Alexa\\Request\\' . $request_type ) ) {
			throw new RuntimeException( sprintf( esc_html__( 'Unknown request type: %s', 'voicewp' ), $request_type ) );
		}

		$class_name = '\\Alexa\\Request\\' . $request_type;

		$request = new $class_name( $this->raw_data, $this->application_id );
		return $request;
	}
}
