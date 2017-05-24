<?php
/**
 * Data returned to Alexa/the user in response to a request.
 * Contains the text, card, and associated info which can include session state, attributes
 */
namespace Alexa\Response;

class Response {
	public $version = '1.0';
	public $session_attributes = array();

	public $output_speech = null;
	public $card = null;
	public $reprompt = null;
	public $should_end_session = false;

	public function __construct() {
		$this->output_speech = new OutputSpeech;
	}

	/**
	 * Set output speech as text
	 * @param string $text
	 * @return \Alexa\Response\Response
	 */
	public function respond( $text ) {
		$this->output_speech = new OutputSpeech;
		$this->output_speech->text = $text;

		return $this;
	}

	/**
	 * Set up response with SSML.
	 * @param string $ssml
	 * @return \Alexa\Response\Response
	 */
	public function respond_ssml( $ssml ) {
		$this->output_speech = new OutputSpeech;
		$this->output_speech->type = 'SSML';
		$this->output_speech->ssml = $ssml;

		return $this;
	}

	/**
	 * Set up reprompt with given text
	 * @param string $text
	 * @return \Alexa\Response\Response
	 */
	public function reprompt( $text ) {
		$this->reprompt = new Reprompt;
		$this->reprompt->output_speech->text = $text;

		return $this;
	}

	/**
	 * Set up reprompt with given ssml
	 * @param string $ssml
	 * @return \Alexa\Response\Response
	 */
	public function reprompt_ssml( $ssml ) {
		$this->reprompt = new Reprompt;
		$this->reprompt->output_speech->type = 'SSML';
		$this->reprompt->output_speech->text = $ssml;

		return $this;
	}

	/**
	 * Add card information
	 * @param string $title
	 * @param string $content
	 * @param null|int $image
	 * @return \Alexa\Response\Response
	 */
	public function with_card( $title = '', $content = '', $image = null ) {

		if ( $image ) {
			$this->card = new StandardCard( $title, $content, $image );
		} else {
			$this->card = new Card( $title, $content );
		}

		return $this;
	}

	/**
	 * Set if it should end the session
	 * @param bool $should_end_session
	 * @return \Alexa\Response\Response
	 */
	public function end_session( $should_end_session = true ) {
		$this->should_end_session = $should_end_session;

		return $this;
	}

	/**
	 * Add a session attribute that will be passed in every requests.
	 * @param string $key
	 * @param mixed $value
	 */
	public function add_session_attribute( $key, $value ) {
		$this->session_attributes[ $key ] = $value;
	}

	/**
	 * Return the response as an array for JSON-ification
	 * @return array
	 */
	public function render() {
		return array(
			'version' => $this->version,
			'sessionAttributes' => $this->session_attributes,
			'response' => array(
				'outputSpeech' => $this->output_speech ? $this->output_speech->render() : null,
				'card' => $this->card ? $this->card->render() : null,
				'reprompt' => $this->reprompt ? $this->reprompt->render() : null,
				'shouldEndSession' => $this->should_end_session ? true : false,
			),
		);
	}
}
