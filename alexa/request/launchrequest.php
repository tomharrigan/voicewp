<?php
/**
 * When the user invokes the skill with the invocation name, but does not provide any command mapping to an intent.
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/handling-requests-sent-by-alexa#launchrequest
 */
namespace Alexa\Request;

class LaunchRequest extends Request {
	public $application_id;

	/**
	 * Used when an initial request is made without intent
	 * @param string $raw_data
	 * @param string $application_id
	 */
	public function __construct( $raw_data, $application_id ) {
		parent::__construct( $raw_data, $application_id );
		$data = $this->data;

		$this->application_id = $data['session']['application']['applicationId'];
	}
}
