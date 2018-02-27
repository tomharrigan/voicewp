<?php

class Voicewp_Setup {
	/**
	 * Plugin version.
	 *
	 * @var string
	 * @access public
	 */
	public static $version = '1.0.0';

	protected static $instance;

	/**
	 * Array of SSML to allow in content markup.
	 *
	 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/speech-synthesis-markup-language-ssml-reference
	 *
	 * @var array
	 * @access public
	 */
	public static $ssml = array(
		'amazon:effect' => array(
			'name' => array(),
		),
		'audio' => array(
			'src' => array(),
		),
		'break' => array(
			'strenth' => array(),
			'time' => array(),
		),
		'emphasis' => array(
			'level' => array(),
		),
		'phoneme' => array(
			'alphabet' => array(),
			'ph' => array(),
		),
		'prosody' => array(
			'rate' => array(),
			'pitch' => array(),
			'volume' => array(),
		),
		'say-as' => array(
			'interpret-as' => array(),
			'format' => array(),
		),
		'sub' => array(
			'alias' => array(),
		),
		'w' => array(
			'role' => array(),
		),
	);

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'add_image_size' ) );
		add_filter( 'allowed_http_origins', array( $this, 'allowed_http_origins' ) );
		add_filter( 'tiny_mce_before_init', array( $this, 'tiny_mce_before_init' ) );
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		foreach ( voicewp_news_post_types() as $post_type ) {
			add_action( 'publish_' . $post_type, array( $this, 'publish_clear_cache' ), 10, 2 );
		}

		// Add settings.
		new VoiceWp\Settings(
			'options',
			'voicewp-settings-new',
			__( 'Voice WP', 'voicewp' ),
			array(
				'skill_name' => array(
					'label' => __( 'Skill name', 'voicewp' ),
					'description' => __( 'Optional name of skill. If empty, site name will be used instead.', 'voicewp' ),
					'attributes' => array(
						'style' => 'width: 95%;',
					),
				),
				'launch_request' => array(
					'type' => 'textarea',
					'label' => __( 'Welcome message', 'voicewp' ),
					'description' => __( 'This is the message a person hears when they open your skill with an utterance such as "Alexa, open {your skill name}"', 'voicewp' ),
					'default_value' => __( 'Welcome to the {put your skill name here} Skill. This skill allows you to listen to content from {your site name}. You can ask questions like: What are the latest articles? ... Now, what can I help you with.', 'voicewp' ),
					'attributes' => array(
						'style' => 'width: 95%; height: 70px;',
					),
				),
				'help_intent' => array(
					'type' => 'textarea',
					'label' => __( 'Help message', 'voicewp' ),
					'description' => __( "This is the message a person hears when they ask your skill for 'help'", 'voicewp' ),
					'default_value' => __( "{put your skill name here} provides you with the latest content from {your site name}. You can ask me for the latest articles, and then select an item from the list by saying, for example, 'read the 3rd article' Or you can also say exit... What can I help you with?", 'voicewp' ),
					'attributes' => array( 'style' => 'width: 95%; height: 70px;' ),
				),
				'list_prompt' => array(
					'type' => 'textarea',
					'label' => __( 'List Prompt', 'voicewp' ),
					'description' => __( 'This message prompts the user to select a piece of content to be read after hearing the headlines.', 'voicewp' ),
					'default_value' => __( 'Which article would you like to hear?', 'voicewp' ),
					'attributes' => array( 'style' => 'width: 95%; height: 50px;' ),
				),
				'stop_intent' => array(
					'type' => 'textarea',
					'label' => __( 'Stop message', 'voicewp' ),
					'description' => __( 'You can optionally provide a message when a person is done with your skill.', 'voicewp' ),
					'default_value' => __( 'Thanks for listening!', 'voicewp' ),
					'attributes' => array( 'style' => 'width: 95%; height: 50px;' ),
				),
			),
			array(
				'parent_page' => 'tools.php',
			)
		);
	}

	/**
	 * Handle clearing cache on single items when a post is edited
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post object
	 * @param bool $update Is post updated or new
	 */
	public function save_post( $post_id, $post, $update ) {
		if (
			empty( $post_id )
			|| wp_is_post_revision( $post_id )
			|| ( defined( 'WP_IMPORTING' ) && WP_IMPORTING === true )
			|| ! in_array( $post->post_type, voicewp_news_post_types() )
			|| 'publish' !== get_post_status( $post_id )
			|| false == $update
		) {
			return;
		}
		delete_transient( 'voicewp_single_' . $post_id );
	}

	/**
	 * Handle clearing cache on single items when a post is edited
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post object
	 * @param bool $update Is post updated or new
	 */
	public function publish_clear_cache( $post_id, $post ) {
		delete_transient( 'voicewp_latest' );
		$args = array(
			'post_type' => voicewp_news_post_types(),
			'posts_per_page' => 5,
			'tax_query' => array(),
		);
		$news = new \Alexa\Skill\News;
		$news->endpoint_content( $args );
	}

	/**
	 * Add image sizes for Alexa cards.
	 */
	public function add_image_size() {
		add_image_size( 'alexa-small', 720, 480, true );
		add_image_size( 'alexa-large', 1200, 800, true );
	}

	/**
	 * Add the Alexa service to the list of allowed HTTP origins
	 *
	 * @param $allowed_origins array Default allowed HTTP origins
	 * @return array allowed origin URLs
	 */
	public function allowed_http_origins( $allowed_origins ) {
		$allowed_origins[] = 'http://ask-ifr-download.s3.amazonaws.com';
		$allowed_origins[] = 'https://ask-ifr-download.s3.amazonaws.com';
		return $allowed_origins;
	}

	/**
	 * Add SSML as valid elements within tinymce
	 *
	 * @param array $settings TinyMCE settings
	 * @return array
	 */
	public function tiny_mce_before_init( $settings ) {

		if ( ! isset( $settings['extended_valid_elements'] ) ) {
			$settings['extended_valid_elements'] = '';
		}
		if ( ! isset( $settings['custom_elements'] ) ) {
			$settings['custom_elements'] = '';
		}

		foreach ( self::$ssml as $tag => $attributes ) {
			// tilda character denotes rendering as span rather than div
			$settings['custom_elements'] .= ',~' . $tag;
			if ( ! empty( $attributes ) ) {
				$settings['extended_valid_elements'] .= ',' . $tag . '[' . implode( '|', array_keys( $attributes ) ) . ']';
			}
		}
		$settings['extended_valid_elements'] = ltrim( $settings['extended_valid_elements'], ',' );
		$settings['custom_elements'] = ltrim( $settings['custom_elements'], ',' );

		return $settings;
	}

	/**
	 * Action hook callback for plugins_loaded.
	 * Sets a plugin version.
	 * Migration functions will never run on new installs.
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		// Determine if the database version and code version are the same.
		$current_version = get_option( 'voicewp_version' );
		if ( version_compare( $current_version, self::$version, '>=' ) ) {
			return;
		}

		// Set the database version to the current version in code.
		update_option( 'voicewp_version', self::$version );
	}
}

Voicewp_Setup::get_instance();
