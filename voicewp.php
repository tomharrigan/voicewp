<?php
/**
 * Plugin Name: VoiceWP
 * Description: Create Alexa skills using your WordPress site
 * Plugin URI: https://github.com/tomharrigan/
 * Author: TomHarrigan
 * Author URI: https://voicewp.com
 * Version: 1.0.0
 * Text Domain: voicewp
 * License: MIT
 */

define( 'VOICEWP_PATH', dirname( __FILE__ ) );

register_activation_hook( __FILE__, 'voicewp_activate' );
function voicewp_activate() {
	Voicewp::get_instance();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'voicewp_deactivate' );
function voicewp_deactivate() {
	flush_rewrite_rules();
}

/**
 * Compatibility requirements.
 */
require_once( VOICEWP_PATH . '/inc/compat.php' );

/**
 * Post Type Base Class
 */
require_once( VOICEWP_PATH . '/inc/post-types/class-voicewp-post-type.php' );

/**
 * Skill Post Type
 */
require_once( VOICEWP_PATH . '/inc/post-types/class-voicewp-post-type-skill.php' );

/**
 * Flash Briefing Post Type
 */
require_once( VOICEWP_PATH . '/inc/post-types/class-voicewp-post-type-briefing.php' );

/**
 * Taxonomy Base Class
 */
require_once( VOICEWP_PATH . '/inc/taxonomies/class-voicewp-taxonomy.php' );

/**
 * Briefing Category Taxonomy
 */
require_once( VOICEWP_PATH . '/inc/taxonomies/class-voicewp-briefing-category.php' );

/**
 * Fieldmanager custom fields
 */
function voicewp_load_fieldmanager_fields() {
	if ( defined( 'FM_VERSION' ) ) {
		require_once( VOICEWP_PATH . '/inc/voicewp-fieldmanager-content-textarea.php' );
		require_once( VOICEWP_PATH . '/inc/fields.php' );
	}
}
add_action( 'init', 'voicewp_load_fieldmanager_fields' );

require_once( VOICEWP_PATH . '/inc/class-voicewp-setup.php' );

require_once( VOICEWP_PATH . '/inc/class-voicewp.php' );

add_action( 'init', array( 'Voicewp', 'get_instance' ), 0 );

/**
 * Load a class from within the plugin based on a class name.
 *
 * @param string $classname Class name to load.
 */
function voicewp_autoload_function( $classname ) {
	if ( class_exists( $classname ) || 0 !== strpos( $classname, 'Alexa' ) ) {
		return;
	}
	$class = str_replace( '\\', DIRECTORY_SEPARATOR, str_replace( '_', '-', strtolower( $classname ) ) );

	// create the actual filepath
	$file_path = VOICEWP_PATH . DIRECTORY_SEPARATOR . $class . '.php';

	// check if the file exists
	if ( file_exists( $file_path ) ) {
		// require once on the file
		require_once $file_path;
	}
}

/**
 * Get the post types whose content is included in the bundled News skill.
 *
 * @return array Post type names.
 */
function voicewp_news_post_types() {
	/**
	 * Filters the post types whose content is included in the bundled News skill.
	 *
	 * @param array $post_types Post type names.
	 */
	return apply_filters( 'voicewp_post_types', array( 'post' ) );
}

/**
 * Get the taxonomies whose terms can be specified by users invoking the News skill.
 *
 * @return array Taxonomy names.
 */
function voicewp_news_taxonomies() {
	$option = get_option( 'voicewp-settings' );

	$taxonomies = ( empty( $option['latest_taxonomies'] ) ) ? array() : $option['latest_taxonomies'];

	// Nonexistant taxonomies can shortcircuit get_terms().
	return array_filter( $taxonomies, 'taxonomy_exists' );
}

spl_autoload_register( 'voicewp_autoload_function' );
