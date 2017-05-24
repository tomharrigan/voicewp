<?php
/**
 * when the user speaks a command that maps to an intent.
 * The request object sent to your service includes the specific intent and any defined slot values.
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/handling-requests-sent-by-alexa#intentrequest
 */
namespace Alexa\Request;

class IntentRequest extends Request {
	public $intent_name;
	public $slots = array();

	/**
	 * Intent request with name of intent and any slot data passed in with the request
	 */
	public function __construct( $raw_data, $application_id ) {
		parent::__construct( $raw_data, $application_id );
		$data = $this->data;

		$this->intent_name = $data['request']['intent']['name'];

		if ( isset( $data['request']['intent']['slots'] ) ) {
			foreach ( $data['request']['intent']['slots'] as $slot ) {
				if ( isset( $slot['value'] ) ) {
					$this->slots[ $slot['name'] ] = $slot['value'];
				}
			}
		}
	}

	/**
	 * Returns the value for the requested intent slot, or $default if not
	 * found.
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getSlot( $name, $default = false ) {
		if ( array_key_exists( $name, $this->slots ) ) {
			return $this->slots[ $name ];
		} else {
			return $default;
		}
	}
}
