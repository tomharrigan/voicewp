<?php
/**
 * @file Certificate.php
 * Validate the request signature
 * Based on code from alexa-app: https://github.com/develpr/alexa-app by Kevin Mitchell
 * Certificate validation requirements outlined at
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/developing-an-alexa-skill-as-a-web-service
 */

namespace Alexa\Request;

use RuntimeException;
use InvalidArgumentException;
use DateTime;

class Certificate {

	/**
	 * Timestamp tolerance of a request can be no more than 150 seconds
	 */
	const TIMESTAMP_VALID_TOLERANCE_SECONDS = 60;
	/**
	 * The protocol of a request must be equal to https
	 */
	const SIGNATURE_VALID_PROTOCOL = 'https';
	/**
	 * The hostname of a request must equal s3.amazonaws.com case insensitive
	 */
	const SIGNATURE_VALID_HOSTNAME = 's3.amazonaws.com';
	/**
	 * The path of a request starts with /echo.api/
	 */
	const SIGNATURE_VALID_PATH = '/echo.api/';
	/**
	 * If a port is defined in the URL of a request, the port is equal to 443.
	 */
	const SIGNATURE_VALID_PORT = 443;
	/**
	 * This domain is present in the Subject Alternative Names (SANs)
	 * section of the signing certificate
	 */
	const ECHO_SERVICE_DOMAIN = 'echo-api.amazon.com';
	/**
	 * A SHA-1 hash value from the full HTTPS request body
	 * is generated to produce the derived hash value
	 * The asserted hash value and derived hash values
	 * are then compared to ensure that they match.
	 */
	const ENCRYPT_METHOD = 'sha1WithRSAEncryption';

	public $certificate_url;
	public $certificate_content;
	public $request_signature;
	public $app_id;

	/**
	 * @param string $certificate_url
	 * @param string $signature
	 * @param string $app_id App ID. used for caching
	 */
	public function __construct( $certificate_url, $signature, $app_id = '' ) {
		$this->certificate_url = $certificate_url;
		$this->request_signature = $signature;
		$this->app_id = $app_id;
	}

	/**
	 * If an exception is not thrown, we have a valid request.
	 * @param string $request_data Raw JSON
	 */
	public function validate_request( $request_data ) {

		$request_parsed = json_decode( $request_data, true );
		// Validate the entire request by:

		// 1. Checking the timestamp.
		$this->validate_timestamp( $request_parsed['request']['timestamp'] );

		// 2. Checking if the certificate URL is correct.
		$this->verify_signature_certificate_url();

		// 3. Checking if the certificate is not expired and has the right SAN
		$this->validate_certificate();

		// 4. Verifying the request signature
		$this->validate_request_signature( $request_data );

	}

	/**
	 * Check if request is whithin the allowed time.
	 * @param string $timestamp
	 * @throws InvalidArgumentException
	 */
	public function validate_timestamp( $timestamp ) {
		$now = new DateTime;
		$timestamp = new DateTime( $timestamp );
		$difference_in_seconds = $now->getTimestamp() - $timestamp->getTimestamp();

		if ( $difference_in_seconds > self::TIMESTAMP_VALID_TOLERANCE_SECONDS ) {
			throw new InvalidArgumentException( __( 'Request timestamp was too old. Possible replay attack.', 'voicewp' ) );
		}
	}

	/**
	 * Parses the certificate and checks the date and SANs
	 * @throws InvalidArgumentException
	 */
	public function validate_certificate() {
		$this->certificate_content = $this->get_certificate();
		$parsed_certificate = $this->parse_certificate( $this->certificate_content );

		if ( ! $parsed_certificate || ! $this->validate_certificate_date( $parsed_certificate ) || ! $this->validate_certificate_san( $parsed_certificate, static::ECHO_SERVICE_DOMAIN ) ) {
			throw new InvalidArgumentException( __( "The remote certificate doesn't contain a valid SANs in the signature or is expired.", 'voicewp' ) );
		}
	}

	/**
	 * Verify signature
	 * @params string $request_data string of data used to generate previous signature
	 * @throws InvalidArgumentException
	 */
	public function validate_request_signature( $request_data ) {
		$cert_key = openssl_pkey_get_public( $this->certificate_content );
		$signature = base64_decode( $this->request_signature );

		$valid = openssl_verify( $request_data, $signature, $cert_key, self::ENCRYPT_METHOD );
		if ( ! $valid ) {
			throw new InvalidArgumentException( __( 'Request signature could not be verified', 'voicewp' ) );
		}
	}

	/**
	 * Returns true if the ceertificate is not expired.
	 *
	 * @param array $parsed_certificate
	 * @return boolean
	 */
	public function validate_certificate_date( array $parsed_certificate ) {
		$valid_from = $parsed_certificate['validFrom_time_t'];
		$valid_to = $parsed_certificate['validTo_time_t'];
		$time = time();
		return ( $valid_from <= $time && $time <= $valid_to );
	}

	/**
	 * Returns true if the configured service domain is present/valid, false if invalid/not present
	 * @param array $parsed_certificate
	 * @return boolean
	 */
	public function validate_certificate_san( array $parsed_certificate, $amazon_service_domain ) {
		if ( false === strpos( $parsed_certificate['extensions']['subjectAltName'], $amazon_service_domain ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Verify URL of the certificate
	 * @throws InvalidArgumentException
	 * @author Emanuele Corradini <emanuele@evensi.com>
	 */
	public function verify_signature_certificate_url() {
		$url = parse_url( $this->certificate_url );

		if ( static::SIGNATURE_VALID_PROTOCOL !== $url['scheme'] ) {
			throw new InvalidArgumentException( __( "Protocol isn't secure. Request isn't from Alexa.", 'voicewp' ) );
		} else if ( static::SIGNATURE_VALID_HOSTNAME !== $url['host'] ) {
			throw new InvalidArgumentException( __( "Certificate isn't from Amazon. Request isn't from Alexa.", 'voicewp' ) );
		} else if ( 0 !== strpos( $url['path'], static::SIGNATURE_VALID_PATH ) ) {
			throw new InvalidArgumentException( sprintf( esc_html__( "Certificate isn't in '%s' folder. Request isn't from Alexa.", 'voicewp' ), static::SIGNATURE_VALID_PATH ) );
		} else if ( isset( $url['port'] ) && static::SIGNATURE_VALID_PORT !== $url['port'] ) {
			throw new InvalidArgumentException( sprintf( esc_html__( "Port isn't %s. Request isn't from Alexa.", 'voicewp' ), static::SIGNATURE_VALID_PORT ) );
		}
	}

	/**
	 * Parse the X509 certificate
	 * @param string $certificate The certificate contents
	 * @return array The certificate contents
	 */
	public function parse_certificate( $certificate ) {
		return openssl_x509_parse( $certificate );
	}

	/**
	 * Return the certificate to the underlying code by fetching it from its location. Cached for the defined duration of TIMESTAMP_VALID_TOLERANCE_SECONDS
	 * @return string
	 */
	public function get_certificate() {
		$certificate_id = 'voicewp' . md5( $this->certificate_url . $this->app_id );
		if ( ! $certificate = get_transient( $certificate_id ) ) {
			$certificate = $this->fetch_certificate();
			set_transient( $certificate_id, $certificate, self::TIMESTAMP_VALID_TOLERANCE_SECONDS );
		}
		return $certificate;
	}

	/**
	 * Perform the actual download of the certificate
	 * @return string
	 */
	public function fetch_certificate() {
		$st = wp_remote_get( $this->certificate_url );
		$st = wp_remote_retrieve_body( $st );

		// Return the certificate contents
		return $st;
	}
}
