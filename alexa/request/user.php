<?php
/**
 * For keeping track of the user through their Session
 */
namespace Alexa\Request;

class User {
	public $user_id;
	public $access_token;
	/**
	 * Store the user ID and access token if exists for keeping track of user through session
	 */
	public function __construct( $data ) {
		$this->user_id = isset( $data['userId'] ) ? $data['userId'] : null;
		$this->access_token = isset( $data['accessToken'] ) ? $data['accessToken'] : null;
	}
}
