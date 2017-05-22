<?php

namespace Alexa\Request;

class Session {
	/** @var User */
	public $user;
	public $new;
	public $application;
	public $session_id;
	public $attributes = array();

	public function __construct( $data ) {
		$this->user = new User( $data['user'] );
		$this->session_id = isset( $data['sessionId'] ) ? $data['sessionId'] : null;
		$this->new = isset( $data['new'] ) ? $data['new'] : null;
		if ( ! $this->new && isset( $data['attributes'] ) ) {
			$this->attributes = $data['attributes'];
		}
	}

	/**
	 * Returns attribute value of $default.
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get_attribute( $key, $default = false ) {
		if ( array_key_exists( $key, $this->attributes ) ) {
			return $this->attributes[ $key ];
		} else {
			return $default;
		}
	}
}
