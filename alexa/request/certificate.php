<?php

/**
 * @file Certificate.php
 * Validate the request signature
 * Based on code from alexa-app: https://github.com/develpr/alexa-app by Kevin Mitchell
 * */

namespace Alexa\Request;

use RuntimeException;
use InvalidArgumentException;
use DateTime;

class Certificate {
	const TIMESTAMP_VALID_TOLERANCE_SECONDS = 30;
	const SIGNATURE_VALID_PROTOCOL = 'https';
	const SIGNATURE_VALID_HOSTNAME = 's3.amazonaws.com';
	const SIGNATURE_VALID_PATH = '/echo.api/';
	const SIGNATURE_VALID_PORT = 443;
	const ECHO_SERVICE_DOMAIN = 'echo-api.amazon.com';
	const ENCRYPT_METHOD = 'sha1WithRSAEncryption';

	public $request_id;
	public $timestamp;
	/** @var Session */
	public $session;
	public $certificate_url;
	public $certificate_content;
	public $request_signature;
	public $request_data;
	public $app_id;

	/**
	 * @param type $certificateUri
	 */
	public function __construct( $certificate_url, $signature, $app_id = '' ) {
		$this->certificate_url = $certificate_url;
		$this->request_signature = $signature;
		$this->app_id = $app_id;
	}

	public function validate_request( $request_data ) {

		// Set required http status code 400 for certificate error
		status_header( 400 );

		$request_parsed = json_decode( $request_data, true );
		// Validate the entire request by:

		// 1. Checking the timestamp.
		$this->validate_timestamp( $request_parsed['request']['timestamp'] );

		// 2. Checking if the certificate URL is correct.
		$this->verify_signature_certificate_url();

		// 3. Checking if the certificate is not expired and has the right SAN
		$this->validate_certificate();

		// 4. Verifying the request signature
		$this->validaterequest_signature( $request_data );

		// Set http status code back to 200
		status_header( 200 );
	}

	/**
	 * Check if request is whithin the allowed time.
	 * @throws InvalidArgumentException
	 */
	public function validate_timestamp( $timestamp ) {
		$now = new DateTime;
		$timestamp = new DateTime( $timestamp );
		$difference_in_seconds = $now->getTimestamp() - $timestamp->getTimestamp();

		if ( $difference_in_seconds > self::TIMESTAMP_VALID_TOLERANCE_SECONDS ) {
			throw new InvalidArgumentException( 'Request timestamp was too old. Possible replay attack.' );
		}
	}

	public function validate_certificate() {
		$this->certificate_content = $this->get_certificate();
		$parsed_certificate = $this->parse_certificate( $this->certificate_content );

		if ( ! $this->validate_certificate_date( $parsed_certificate ) || ! $this->validate_certificate_san( $parsed_certificate, static::ECHO_SERVICE_DOMAIN ) ) {
			throw new InvalidArgumentException( "The remote certificate doesn't contain a valid SANs in the signature or is expired." );
		}
	}
	/*
	 * @params $request_data
	 * @throws InvalidArgumentException
	 */
	public function validaterequest_signature( $request_data ) {
		$cert_key = openssl_pkey_get_public( $this->certificate_content );
		$signature = base64_decode( $this->request_signature );

		$valid = openssl_verify( $request_data, $signature, $cert_key, self::ENCRYPT_METHOD );
		if ( ! $valid ) {
			throw new InvalidArgumentException( 'Request signature could not be verified' );
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
		} else {
			return true;
		}
	}

	/**
	 * Verify URL of the certificate
	 * @throws InvalidArgumentException
	 * @author Emanuele Corradini <emanuele@evensi.com>
	 */
	public function verify_signature_certificate_url() {
		$url = parse_url( $this->certificate_url );

		if ( static::SIGNATURE_VALID_PROTOCOL !== $url['scheme'] ) {
			throw new InvalidArgumentException( 'Protocol isn\'t secure. Request isn\'t from Alexa.' );
		} else if ( static::SIGNATURE_VALID_HOSTNAME !== $url['host'] ) {
			throw new InvalidArgumentException( 'Certificate isn\'t from Amazon. Request isn\'t from Alexa.' );
		} else if ( 0 !== strpos( $url['path'], static::SIGNATURE_VALID_PATH ) ) {
			throw new InvalidArgumentException( 'Certificate isn\'t in "' . static::SIGNATURE_VALID_PATH . '" folder. Request isn\'t from Alexa.' );
		} else if ( isset( $url['port'] ) && static::SIGNATURE_VALID_PORT !== $url['port'] ) {
			throw new InvalidArgumentException( 'Port isn\'t ' . static::SIGNATURE_VALID_PORT. '. Request isn\'t from Alexa.' );
		}
	}


	/**
	 * Parse the X509 certificate
	 * @param $certificate The certificate contents
	 */
	public function parse_certificate( $certificate ) {
		return openssl_x509_parse( $certificate );
	}

	/**
	 * Return the certificate to the underlying code by fetching it from its location. Cached for 1 hour.
	 */
	public function get_certificate() {
		$certificate_id = 'alexawp' . md5( $this->certificate_url . $this->app_id );
		if ( ! $certificate = get_transient( $certificate_id ) ) {
			$certificate = $this->fetch_certificate();
			set_transient( $certificate_id, $certificate, 3600 );
		}
		return $certificate;
	}

	/**
	 * Perform the actual download of the certificate
	 */
	public function fetch_certificate() {
		if ( ! function_exists( 'curl_init' ) ) {
			throw new InvalidArgumentException( 'CURL is required to download the Signature Certificate.' );
		}
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->certificate_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$st = curl_exec( $ch );
		curl_close( $ch );

		// Return the certificate contents;
		return $st;
	}
}
