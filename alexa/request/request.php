<?php

namespace Alexa\Request;

use RuntimeException;
use InvalidArgumentException;
use DateTime;

use Alexa\Request\Certificate;
use Alexa\Request\Application;

class Request {

	public $request_id;
	public $timestamp;
	/** @var Session */
	public $session;
	public $data;
	public $raw_data;
	public $applicationId;

	/**
	 * Set up Request with RequestId, timestamp (DateTime) and user (User obj.)
	 * @param type $data
	 */
	public function __construct( $raw_data, $applicationId ) {
		if ( ! is_string( $raw_data ) ) {
			throw new InvalidArgumentException( 'Alexa Request requires the raw JSON data to validate request signature' );
		}

		// Decode the raw data into a JSON array.
		$data = json_decode( $raw_data, true );
		$this->data = $data;
		$this->raw_data = $raw_data;

		$this->request_id = $data['request']['requestId'];
		$this->timestamp = new DateTime( $data['request']['timestamp'] );
		$this->session = new Session( $data['session'] );

		$this->applicationId = $applicationId;

	}

	/**
	 * Accept the certificate validator dependency in order to allow people
	 * to extend it to for example cache their certificates.
	 * @param \Alexa\Request\Certificate $certificate
	 */
	public function setCertificateDependency( \Alexa\Request\Certificate $certificate ) {
		$this->certificate = $certificate;
	}

	/**
	 * Accept the application validator dependency in order to allow people
	 * to extend it.
	 * @param \Alexa\Request\Application $application
	 */
	public function setApplicationDependency( \Alexa\Request\Application $application ) {
		$this->application = $application;
	}

	/**
	 * Instance the correct type of Request, based on the $jons->request->type
	 * value.
	 * @param type $data
	 * @return \Alexa\Request\Request   base class
	 * @throws RuntimeException
	 */
	public function fromData() {
		$data = $this->data;

		// Instantiate a new Certificate validator if none is injected
		// as our dependency.
		if ( ! isset( $this->certificate ) ) {
			$this->certificate = new Certificate( $_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE'] );
		}
		if ( ! isset( $this->application ) ) {
			$this->application = new Application( $this->applicationId );
		}

		// We need to ensure that the request Application ID matches our Application ID.
		$this->application->validateApplicationId( $data['session']['application']['applicationId'] );
		// Validate that the request signature matches the certificate.
		$this->certificate->validate_request( $this->raw_data );


		$requestType = $data['request']['type'];
		if ( ! class_exists( '\\Alexa\\Request\\' . $requestType ) ) {
			throw new RuntimeException( 'Unknown request type: ' . $requestType );
		}

		$className = '\\Alexa\\Request\\' . $requestType;

		$request = new $className( $this->raw_data, $this->applicationId );
		return $request;
	}
}
