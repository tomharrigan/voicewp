<?php
/**
 * Used if session remains open after sending the response,
 * but the user does not say anything that maps to an intent.
 * Prompts user for further input
 */
namespace Alexa\Response;

class Reprompt {
	public $output_speech;

	/**
	 * Sets up a reprompt with output speech
	 */
	public function __construct() {
		$this->output_speech = new OutputSpeech;
	}

	/**
	 * Delivers the text to a user in order to get further input
	 * @return array
	 */
	public function render() {
		return array(
			'outputSpeech' => $this->output_speech->render(),
		);
	}
}
