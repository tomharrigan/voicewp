<?php

namespace Alexa\Request;

class Session {
	/** @var User */
	public $user;
	public $new;
	/** @var Application */
	public $application;
	public $sessionId;
	public $attributes = array();

	public function __construct( $data ) {
		$this->user = new User( $data['user'] );
		$this->sessionId = isset( $data['sessionId'] ) ? $data['sessionId'] : null;
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
	public function getAttribute( $key, $default = false ) {
		if ( array_key_exists( $key, $this->attributes ) ) {
			return $this->attributes[ $key ];
		} else {
			return $default;
		}
	}
}
