<?php
/**
 * A users' Session is used to persist data about their interaction with the app
 */
namespace Alexa\Request;

class Session {
	/**
	 * User object
	 * @var Object
	 */
	public $user;
	/**
	 * Whether this is a new session or an existing session
	 * @var bool
	 */
	public $new;
	/**
	 * Session ID, null or string
	 * @var mixed
	 */
	public $session_id;
	/**
	 * Any optional attributes
	 * @var array
	 */
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
