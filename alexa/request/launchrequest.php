<?php

namespace Alexa\Request;

class LaunchRequest extends Request {
	public $applicationId;

	public function __construct( $raw_data, $applicationId ) {
				parent::__construct( $raw_data, $applicationId );
				$data = $this->data;

		$this->applicationId = $data['session']['application']['applicationId'];
	}
}
