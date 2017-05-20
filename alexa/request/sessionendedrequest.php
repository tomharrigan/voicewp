<?php

namespace Alexa\Request;

class SessionEndedRequest extends Request {
	public $reason;

	public function __construct( $raw_data ) {
		parent::__construct( $raw_data );

		$this->reason = $this->data['request']['reason'];
	}
}
