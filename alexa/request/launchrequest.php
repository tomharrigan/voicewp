<?php

namespace Alexa\Request;

class LaunchRequest extends Request {
	public $application_id;

	public function __construct( $raw_data, $application_id ) {
		parent::__construct( $raw_data, $application_id );
		$data = $this->data;

		$this->application_id = $data['session']['application']['applicationId'];
	}
}
