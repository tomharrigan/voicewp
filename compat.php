<?php
/**
 * AlexaWP compatibility functionality.
 *
 * @package AlexaWP
 */

/**
 * Check whether the plugin requirements are met.
 *
 * @return \WP_Error WP_Error object with error data for unmet requirements, if any.
 */
function alexawp_check_requirements() {
	global $wp_version;

	$minimum_wp_version    = '4.4';
	$unsupported_wordpress = version_compare( $wp_version, $minimum_wp_version, '<' );
	$minimum_fm_version    = '1.0';
	$fm_defined            = defined( 'FM_VERSION' );

	$check                 = new \WP_Error;

	if ( $unsupported_wordpress ) {
		$check->add(
			'unsupported_wordpress',
			sprintf(
				/* translators: 1: minimum WordPress version, 2: current WordPress version */
				__( 'AlexaWP requires at least WordPress version %1$s. You are currently running version %2$s.', 'alexawp' ),
				$minimum_wp_version,
				$wp_version
			)
		);
	}

	if ( ! $unsupported_wordpress && ! class_exists( '\WP_REST_Response' ) ) {
		$check->add( 'no_rest_api', __( 'AlexaWP requires the WordPress REST API.', 'alexawp' ) );
	}

	if ( ! $fm_defined ) {
		$check->add( 'no_fieldmanager', __( 'AlexaWP requires the WordPress Fieldmanager plugin.', 'alexawp' ) );
	}

	if ( $fm_defined && version_compare( FM_VERSION, $minimum_fm_version, '<' ) ) {
		$check->add(
			'unsupported_fieldmanager',
			sprintf(
				/* translators: 1: minimum Fieldmanager version, 2: current Fieldmanager version */
				__( 'AlexaWP requires at least Fieldmanager version %1$s. You are currently running version %2$s.', 'alexawp' ),
				$minimum_fm_version,
				FM_VERSION
			)
		);
	}

	return $check;
}

/**
 * Print a notice for the Dashboard.
 *
 * @param array  $classes Notice HTML classes.
 * @param string $message Notice text.
 */
function alexawp_admin_notice( $classes, $message ) {
	printf( '<div class="%s"><p>%s</p></div>', esc_attr( implode( ' ', $classes ) ), esc_html( $message ) );
}

/**
 * Shim wp_generate_uuid4() if this WordPress version doesn't include it.
 *
 * @return string UUID.
 */
function alexawp_generate_uuid4() {
	if ( function_exists( 'wp_generate_uuid4' ) ) {
		return wp_generate_uuid4();
	}

	// As of WordPress 4.7.2.
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0fff ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

/**
 * Print an admin "error" notice for each unmet plugin requirement.
 */
function alexawp_print_requirements_errors() {
	foreach ( alexawp_check_requirements()->get_error_messages() as $message ) {
		alexawp_admin_notice( array( 'notice notice-error' ), $message );
	}
}
add_action( 'admin_init', 'alexawp_print_requirements_errors' );
