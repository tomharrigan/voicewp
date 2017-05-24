<?php
/**
 * @file Application.php
 *
 * The application abstraction layer to provide Application ID validation to
 * Alexa requests. Any implementations might provide their own implementations
 * via the $request->set_application_abstraction() function but must provide the
 * validate_application_id() function.
 */

namespace Alexa\Request;
use InvalidArgumentException;

class Application {
	public $application_id;

	/**
	 * Builds Application to provide app ID
	 */
	public function __construct( $application_id ) {
		$this->application_id = preg_split( '/,/', $application_id );
	}

	/**
	 * Validate that the request Application ID matches our Application.
	 * This is required as per Amazon requirements.
	 *
	 * @param $request_application_id
	 * Application ID from the Request (typically found in $data['session']['application']
	*/
	public function validate_application_id( $request_application_id = '' ) {
		if ( ! in_array( $request_application_id, $this->application_id ) ) {
			throw new InvalidArgumentException( __( 'Application Id not matched', 'voicewp' ) );
		}
	}
}
