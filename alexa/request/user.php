<?php

namespace Alexa\Request;

class User {
	public $user_id;
	public $access_token;

	public function __construct( $data ) {
		$this->user_id = isset( $data['userId'] ) ? $data['userId'] : null;
		$this->access_token = isset( $data['accessToken'] ) ? $data['accessToken'] : null;
	}
}
