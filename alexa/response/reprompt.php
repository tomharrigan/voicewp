<?php

namespace Alexa\Response;

class Reprompt {
	public $output_speech;

	public function __construct() {
		$this->output_speech = new OutputSpeech;
	}

	public function render() {
		return array(
			'outputSpeech' => $this->output_speech->render(),
		);
	}
}
