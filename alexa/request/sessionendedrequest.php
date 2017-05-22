<?php
/**
 * Used when a user exits, an error occurs, or the user does not respond.
 * Ends the current session.
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/handling-requests-sent-by-alexa#sessionendedrequest
 */
namespace Alexa\Request;

class SessionEndedRequest extends Request {
	public $reason;

	public function __construct( $raw_data ) {
		parent::__construct( $raw_data );

		$this->reason = $this->data['request']['reason'];
	}
}
