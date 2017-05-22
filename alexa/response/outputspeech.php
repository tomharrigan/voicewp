<?php
/**
 * Defines the type of text being sent in response, either SSML or plain text
 */
namespace Alexa\Response;

class OutputSpeech {
	public $type = 'PlainText';
	public $text = '';
	public $ssml = '';

	/**
	 * Returns array of text for output with defined type of PlainText of SSML
	 * @return array
	 */
	public function render() {
		switch ( $this->type ) {
			case 'PlainText':
				return array(
					'type' => $this->type,
					'text' => $this->text,
				);
			case 'SSML':
				return array(
					'type' => $this->type,
					'ssml' => $this->ssml,
				);
		}
	}
}
