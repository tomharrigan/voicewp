<?php
namespace Alexa;

use Alexa\Request\Request as AlexaRequest;
use Alexa\Response\Response as AlexaResponse;

class AlexaEvent {

	/* @var \Alexa\Request\Request */
	protected $request;
	/* @var \Alexa\Response\Response */
	protected $response;
	/**
	 * Constructor.
	 *
	 * @param Config $config
	 */
	public function __construct( AlexaRequest $request, AlexaResponse $response ) {
		$this->request = $request;
		$this->response = $response;
	}
	/**
	 * Getter for the request object.
	 *
	 * @return Request
	 */
	public function get_request() {
		return $this->request;
	}
	/**
	 * Setter for the request object.
	 *
	 * @param $request
	 */
	public function set_request( AlexaRequest $request ) {
		$this->request = $request;
	}
	/**
	 * Getter for the response object.
	 *
	 * @return Response
	 */
	public function get_response() {
		return $this->response;
	}
	/**
	 * Setter for the response object.
	 *
	 * @param $response
	 */
	public function set_response( AlexaResponse $response ) {
		$this->response = $response;
	}
}
