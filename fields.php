<?php
/**
 * Populates the types of skills that can be created
 */
function alexawp_fm_skill_type() {
	$fm = new Fieldmanager_Select( [
		'name' => 'skill_type',
		'first_empty' => true,
		'options' =>
		[
			'fact_quote' => __( 'Fact / Quote', 'alexawp' ),
		],
	] );
	$fm->add_meta_box( __( 'Skill Type', 'alexawp' ), [ 'alexawp-skill' ] );
}
add_action( 'fm_post_alexawp-skill', 'alexawp_fm_skill_type' );

/**
 * Add the Alexa app ID.
 */
function alexawp_fm_alexa_app_id() {
	$fm = new Fieldmanager_TextField( [
		'name' => 'alexa_app_id',
		'description' => __( 'Add the application ID given by Amazon', 'alexawp' ),
	] );
	$fm->add_meta_box( __( 'Alexa Application ID', 'alexawp' ), [ 'alexawp-skill' ] );
}
add_action( 'fm_post_alexawp-skill', 'alexawp_fm_alexa_app_id' );

/**
 * Add the Alexa app ID.
 */
function alexawp_fm_alexa_app_image() {
	$fm = new Fieldmanager_Media( [
		'name' => 'alexawp_default_image',
		'description' => __( 'Image to be used when no other is provided', 'alexawp' ),
	] );
	$fm->add_meta_box( __( 'Default App Card Image', 'alexawp' ), [ 'alexawp-skill' ] );
}
add_action( 'fm_post_alexawp-skill', 'alexawp_fm_alexa_app_image' );

/**
 * Fields for controlling flash briefing content.
 *
 * @return \Fieldmanager_Context_Post Post context.
 */
function alexawp_fm_briefing_content() {
	$post_id = ( isset( $_GET['post'] ) ) ? intval( $_GET['post'] ) : 0;
	$allowed_formats = [ 'mp3' ];

	$children = [
		new \Fieldmanager_TextArea( __( 'Text', 'alexawp' ), [
			'name' => 'text',
			'description' => __( 'Text should be under 4,500 characters.', 'alexawp' ),
			'attributes' => [
				'style' => 'width: 100%; height: 400px',
				'maxlength' => 4500,
			],
		] ),
		new \Fieldmanager_Media( __( 'Uploaded MP3', 'alexawp' ), [
			'name' => 'attachment_id',
			'mime_type' => 'audio/mpeg',
			'button_label' => __( 'Select a File', 'alexawp' ),
			'modal_button_label' => __( 'Select File', 'alexawp' ),
			'modal_title' => __( 'Select a File', 'alexawp' ),
			'selected_file_label' => __( 'Selected File:', 'alexawp' ),
			'remove_media_label' => __( 'Remove Selection', 'alexawp' ),
		] ),
		new \Fieldmanager_Link( __( 'HTTPS URL to an MP3', 'alexawp' ), [
			'name' => 'audio_url',
			'attributes' => [
				'style' => 'width: 100%;',
			],
		] ),
	];

	foreach ( $children as $key => $value ) {
		$children[ $key ]->display_if = [ 'src' => 'source', 'value' => $children[ $key ]->name ];
	}

	// Display-if control.
	$display_if = new \Fieldmanager_Radios( __( 'Source', 'alexawp' ), [
		'name' => 'source',
		'options' => wp_list_pluck( $children, 'label', 'name' ),
	] );

	// Briefing UUID, saved the first time and used thereafter.
	$uuid = new \Fieldmanager_Hidden( [
		'name' => 'uuid',
	] );

	if ( ! get_post_meta( $post_id, 'alexawp_briefing_uuid', true ) ) {
		$uuid->default_value = wp_generate_uuid4();
	}

	array_unshift( $children, $display_if, $uuid );

	$fm = new \Fieldmanager_Group( [
		'name' => 'alexawp_briefing',
		'serialize_data' => false,
		// Needs to be name => field for compat with FM's validation routines.
		'children' => array_combine( wp_list_pluck( $children, 'name' ), $children ),
	] );

	// Help text.
	if ( $post_id ) {
		$existing_audio_url = get_post_meta( $post_id, 'alexawp_briefing_audio_url', true );

		if ( ! $existing_audio_url || ! in_array( pathinfo( parse_url( $existing_audio_url, PHP_URL_PATH ), PATHINFO_EXTENSION ), $allowed_formats, true ) ) {
			$fm->children['audio_url']->description = __( 'Please make sure this is a URL to an MP3 file.', 'alexawp' );
		}

		$existing_attachment_id = get_post_meta( $post_id, 'alexawp_briefing_attachment_id', true );

		if ( $existing_attachment_id ) {
			$attachment_metadata = wp_get_attachment_metadata( $existing_attachment_id );
			$warnings = [];

			if ( ! isset( $attachment_metadata['fileformat'] ) || ! in_array( $attachment_metadata['fileformat'], $allowed_formats, true ) ) {
				$warnings[] = __( 'Please make sure this is an MP3 upload.', 'alexawp' );
			}

			if ( isset( $attachment_metadata['length'] ) && $attachment_metadata['length'] > 600 ) {
				$warnings[] = __( 'Audio should be under 10 minutes long.', 'alexawp' );
			}

			$fm->children['attachment_id']->description = implode( ' ', $warnings );
		}
	}

	return $fm->add_meta_box( __( 'Briefing Content', 'alexawp' ), fm_get_context()[1], 'normal', 'high' );
}
add_action( 'fm_post_alexawp-briefing', 'alexawp_fm_briefing_content' );

/**
 * Add facts or skills.
 */
function alexawp_fm_skill_fact_quote() {
	$fm = new Fieldmanager_Group( [
		'name' => 'facts_quotes',
		'limit' => 0,
		'add_more_label' => __( 'Add another fact or quote', 'alexawp' ),
		'children' =>
		[
			'fact_quote' => new Fieldmanager_TextField( [
				'label' => __( 'Fact / Quote', 'alexawp' ),
				'description' => __( 'Add a fact or quote', 'alexawp' ),
			] ),
			'attribution' => new Fieldmanager_TextField( [
				'label' => __( 'Attribution', 'alexawp' ),
				'description' => __( 'Add attribution if applicable', 'alexawp' ),
			] ),
			'image' => new Fieldmanager_Media( [
				'label' => __( 'Alexa App Card Image', 'alexawp' ),
			] ),
		],
	] );
	$fm->add_meta_box( __( 'Facts / Quotes', 'alexawp' ), [ 'alexawp-skill' ] );
}
add_action( 'fm_post_alexawp-skill', 'alexawp_fm_skill_fact_quote' );

/**
 * Create a settings page for the news/post consumption skill
 */
function alexawp_fm_alexa_settings() {
	$children = [
		'news_invocation' => new Fieldmanager_TextField( [
			'label' => __( 'What is the invocation name you will use for this skill?', 'alexawp' ),
			'description' => __( 'This is the name a person says when they wish to interact with your skill.', 'alexawp' ),
		] ),
		'news_id' => new Fieldmanager_TextField( [
			'label' => __( 'News skill ID', 'alexawp' ),
			'description' => __( 'Add the application ID given by Amazon', 'alexawp' ),
		] ),
	];

	$alexawp_settings = get_option( 'alexawp-settings' );

	if ( ! empty( $alexawp_settings['news_invocation'] ) ) {
		$utterances = "Latest Ask %s for the latest content\r";
		$utterances .= "Latest Ask %s for the latest articles\r";
		$utterances .= "Latest Ask %s for the latest stories\r";
		$utterances .= "Latest Ask %s for the latest news\r";
		$utterances .= "Latest Get the latest news from %s\r";
		$utterances .= "Latest Get the latest stories from %s\r";
		$utterances .= "Latest Get the latest content from %s\r";
		$utterances .= "Latest Ask %s whats up\r";
		$utterances .= "Latest Ask %s whats new\r";
		$utterances .= "ReadPost Read the {PostNumber}\r";
		$utterances .= "ReadPost Read the {PostNumber} post\r";
		$utterances .= "ReadPost Read the {PostNumber} article\r";
		$utterances .= "ReadPost Read the {PostNumber} story\r";
		$utterances .= "ReadPost Read {PostNumber}\r";
		$utterances .= "ReadPost Read the {PostNumberWord} post\r";
		$utterances .= "ReadPost Read the {PostNumberWord} article\r";
		$utterances .= "ReadPost Read the {PostNumberWord} story\r";
		$utterances .= "ReadPost Read {PostNumberWord}";
		$children['news_utterances'] = new Fieldmanager_TextArea( [
			'label' => __( 'Sample utterances', 'alexawp' ),
			'description' => __( 'Here\'s a starting point for your utterances. This can be pasted into the developer portal for your skill.', 'alexawp' ),
			'default_value' => str_replace( '%s', $alexawp_settings['news_invocation'], $utterances ),
			'skip_save' => true,
			'attributes' => [ 'readonly' => 'readonly', 'style' => 'width: 95%; height: 300px' ],
		] );
		$children['remove_last_upload'] = new Fieldmanager_Checkbox( [
			'label' => __( 'Remove last upload', 'alexawp' ),
			'skip_save' => true,
		] );
	}

	$fm = new Fieldmanager_Group( [
		'name' => 'alexawp-settings',
		'children' => $children,
	] );
	$fm->activate_submenu_page();
}
add_action( 'fm_submenu_alexawp-settings', 'alexawp_fm_alexa_settings' );
if ( function_exists( 'fm_register_submenu_page' ) ) {
	fm_register_submenu_page( 'alexawp-settings', 'tools.php', __( 'Alexa Skill Settings', 'alexawp' ), __( 'Alexa Skill Settings', 'alexawp' ), 'manage_options', 'alexawp-settings' );
}
