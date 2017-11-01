<?php
/**
 * Add the Alexa app ID.
 * Populates the types of skills that can be created
 */
function voicewp_fm_alexa_app_settings() {

	$post_id = ( isset( $_GET['post'] ) ) ? absint( $_GET['post'] ) : 0;

	$children = array(
		new \Fieldmanager_Select( __( 'Skill Type', 'voicewp' ), array(
			'name' => 'type',
			'first_empty' => true,
			'options' => array(
				// Key is class name
				'Quote' => __( 'Fact / Quote', 'voicewp' ),
			),
			'description' => __( 'What type of functionality is being added?', 'voicewp' ),
		) ),
		new \Fieldmanager_Media( __( 'Default App Card Image', 'voicewp' ), array(
			'name' => 'default_image',
			'description' => __( 'Image to be used when no other is provided. App cards can be displayed within the Alexa app when she responds to a user request.', 'voicewp' ),
		) ),
		new \Fieldmanager_Checkbox( array(
			'name' => 'is_standalone',
			'label' => __( 'This is a standalone skill', 'voicewp' ),
			'description' => __( 'Will this be its own skill or is this part of another skill?', 'voicewp' ),
		) ),
		new \Fieldmanager_TextField( __( 'Alexa Application ID', 'voicewp' ), array(
			'name' => 'app_id',
			'description' => __( 'Add the application ID given by Amazon', 'voicewp' ),
			'display_if' => array(
				'src' => 'is_standalone',
				'value' => true,
			),
		) ),
	);

	// If there's a post ID, output the REST endpoint for use in the amazon developer portal
	if ( $post_id ) {
		$children['readonly_skill_url'] = new \Fieldmanager_TextField( array(
			'label' => __( 'This is the endpoint URL of your skill. Paste this within the configuration tab for your skill in the developer portal.', 'voicewp' ),
			'default_value' => home_url( '/wp-json/voicewp/v1/skill/' ) . $post_id,
			'skip_save' => true,
			'attributes' => array(
				'readonly' => 'readonly',
				'style' => 'width: 95%;',
			),
			'display_if' => array(
				'src' => 'is_standalone',
				'value' => true,
			),
		) );
	}

	$fm = new \Fieldmanager_Group( array(
		'name' => 'voicewp_skill',
		'serialize_data' => false,
		// Needs to be name => field for compat with FM's validation routines.
		'children' => array_combine( wp_list_pluck( $children, 'name' ), $children ),
	) );
	$context = fm_get_context();
	return $fm->add_meta_box( __( 'Skill Settings', 'voicewp' ), $context[1], 'normal', 'high' );
}
add_action( 'fm_post_voicewp-skill', 'voicewp_fm_alexa_app_settings' );

/**
 * Fields for controlling flash briefing content.
 *
 * @return \Fieldmanager_Context_Post Post context.
 */
function voicewp_fm_briefing_content() {
	$post_id = ( isset( $_GET['post'] ) ) ? intval( $_GET['post'] ) : 0;
	$allowed_formats = array( 'mp3' );

	$children = array(
		// Display-if control.
		'source' => new \Fieldmanager_Radios( __( 'Source', 'voicewp' ), array(
			/**
			 * Allows for filtering the available sources that
			 * can be used for populating a flash briefing
			 *
			 * @since 1.1.0
			 *
			 * @param array Flash briefing source options
			 */
			'options' => apply_filters( 'voicewp_briefing_source_options', array(
				'content' => __( 'Text', 'voicewp' ),
				'audio_url' => __( 'HTTPS URL to an MP3', 'voicewp' ),
				'attachment_id' => __( 'Uploaded MP3', 'voicewp' ),
			) ),
			/**
			 * String defining the default content source of a flash briefing
			 *
			 * @since 1.1.0
			 *
			 * @param string default value
			 */
			'default_value' => apply_filters( 'voicewp_default_briefing_source', 'content' ),
		) ),
		'content' => new \VoiceWP_Fieldmanager_Content_TextArea( __( 'Text', 'voicewp' ), array(
			'description' => __( 'Text should be under 4,500 characters.', 'voicewp' ),
			'attributes' => array(
				'style' => 'width: 100%; height: 400px',
				'maxlength' => 4500,
			),
			'display_if' => array(
				'src' => 'source',
				'value' => 'content',
			),
		) ),
		'audio_url' => new \Fieldmanager_Link( __( 'HTTPS URL to an MP3', 'voicewp' ), array(
			'attributes' => array(
				'style' => 'width: 100%;',
			),
			'display_if' => array(
				'src' => 'source',
				/**
				 * Allow filtering of what sources an audio link is used with
				 *
				 * @since 1.1.0
				 *
				 * @param string Comma separated list of source options to display the field for
				 */
				'value' => apply_filters( 'voicewp_briefing_audio_url_display_if', 'audio_url' ),
			),
		) ),
		'attachment_id' => new \Fieldmanager_Media( __( 'Uploaded MP3', 'voicewp' ), array(
			'mime_type' => 'audio',
			'button_label' => __( 'Select a File', 'voicewp' ),
			'modal_button_label' => __( 'Select File', 'voicewp' ),
			'modal_title' => __( 'Select a File', 'voicewp' ),
			'display_if' => array(
				'src' => 'source',
				'value' => 'attachment_id',
			),
		) ),
		'uuid' => new \Fieldmanager_Hidden( array() ),
	);

	if ( ! get_post_meta( $post_id, 'voicewp_briefing_uuid', true ) ) {
		$children['uuid']->default_value = voicewp_generate_uuid4();
	}

	/**
	 * Allow addition, removal, or modification of briefing fields
	 *
	 * @since 1.1.0
	 *
	 * @param array $children The Fieldmanager fields used with a flash briefing
	 */
	$children = apply_filters( 'voicewp_briefing_fields', $children );

	$fm = new \Fieldmanager_Group( array(
		'name' => 'voicewp_briefing',
		'serialize_data' => false,
		// Needs to be name => field for compat with FM's validation routines.
		'children' => $children,
	) );

	// Help text.
	if ( $post_id ) {
		$existing_audio_url = get_post_meta( $post_id, 'voicewp_briefing_audio_url', true );

		if ( ! $existing_audio_url || ! in_array( pathinfo( parse_url( $existing_audio_url, PHP_URL_PATH ), PATHINFO_EXTENSION ), $allowed_formats, true ) ) {
			$fm->children['audio_url']->description = __( 'Please make sure this is a URL to an MP3 file.', 'voicewp' );
		}

		$existing_attachment_id = get_post_meta( $post_id, 'voicewp_briefing_attachment_id', true );

		if ( $existing_attachment_id ) {
			$attachment_metadata = wp_get_attachment_metadata( $existing_attachment_id );
			$warnings = array();

			if ( ! isset( $attachment_metadata['fileformat'] ) || ! in_array( $attachment_metadata['fileformat'], $allowed_formats, true ) ) {
				$warnings[] = __( 'Please make sure this is an MP3 upload.', 'voicewp' );
			}

			if ( isset( $attachment_metadata['length'] ) && $attachment_metadata['length'] > ( 10 * MINUTE_IN_SECONDS ) ) {
				$warnings[] = __( 'Audio should be under 10 minutes long.', 'voicewp' );
			}

			$fm->children['attachment_id']->description = implode( ' ', $warnings );
		}
	}

	$context = fm_get_context();
	return $fm->add_meta_box( __( 'Briefing Content', 'voicewp' ), $context[1], 'normal', 'high' );
}
add_action( 'fm_post_voicewp-briefing', 'voicewp_fm_briefing_content' );

/**
 * Add facts or skills.
 */
function voicewp_fm_skill_fact_quote() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'facts_quotes',
		'limit' => 0,
		'extra_elements' => 0,
		'add_more_label' => __( 'Add another fact or quote', 'voicewp' ),
		'children' => array(
			'fact_quote' => new Fieldmanager_TextField( array(
				'label' => __( 'Fact / Quote', 'voicewp' ),
				'description' => __( 'Add a fact or quote', 'voicewp' ),
			) ),
			'attribution' => new Fieldmanager_TextField( array(
				'label' => __( 'Attribution', 'voicewp' ),
				'description' => __( 'Add attribution if applicable', 'voicewp' ),
			) ),
			'image' => new Fieldmanager_Media( array(
				'label' => __( 'Alexa App Card Image', 'voicewp' ),
			) ),
		),
	) );
	$fm->add_meta_box( __( 'Facts / Quotes', 'voicewp' ), array( 'voicewp-skill' ) );
}
add_action( 'fm_post_voicewp-skill', 'voicewp_fm_skill_fact_quote' );

/**
 * Create a settings page for the news/post consumption skill
 */
function voicewp_fm_alexa_settings() {
	$readonly = array( 'readonly' => 'readonly' );

	$news_post_types = voicewp_news_post_types();
	// All public taxonomies associated with news post types. Could be abstracted into a function.
	$eligible_news_taxonomy_objects = array_filter(
		get_taxonomies( array( 'public' => true ), 'objects' ),
		function ( $taxonomy ) use ( $news_post_types ) {
			return ( $taxonomy->label && array_intersect( $news_post_types, $taxonomy->object_type ) );
		}
	);

	$children = array(
		'skill_name' => new Fieldmanager_TextField( array(
			'label' => __( 'Skill name', 'voicewp' ),
			'description' => __( 'Optional name of skill. If empty, site name will be used instead.', 'voicewp' ),
			'attributes' => array(
				'style' => 'width: 95%;',
			),
		) ),
		'launch_request' => new Fieldmanager_TextArea( array(
			'label' => __( 'Welcome message', 'voicewp' ),
			'description' => __( 'This is the message a person hears when they open your skill with an utterance such as "Alexa, open {your skill name}"', 'voicewp' ),
			'default_value' => __( 'Welcome to the {put your skill name here} Skill. This skill allows you to listen to content from {your site name}. You can ask questions like: What are the latest articles? ... Now, what can I help you with.', 'voicewp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 70px;' ),
		) ),
		'help_intent' => new Fieldmanager_TextArea( array(
			'label' => __( 'Help message', 'voicewp' ),
			'description' => __( "This is the message a person hears when they ask your skill for 'help'", 'voicewp' ),
			'default_value' => __( "{put your skill name here} provides you with the latest content from {your site name}. You can ask me for the latest articles, and then select an item from the list by saying, for example, 'read the 3rd article' Or you can also say exit... What can I help you with?", 'voicewp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 70px;' ),
		) ),
		'list_prompt' => new Fieldmanager_TextArea( array(
			'label' => __( 'List Prompt', 'voicewp' ),
			'description' => __( 'This message prompts the user to select a piece of content to be read after hearing the headlines.', 'voicewp' ),
			'default_value' => __( 'Which article would you like to hear?', 'voicewp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 50px;' ),
		) ),
		'stop_intent' => new Fieldmanager_TextArea( array(
			'label' => __( 'Stop message', 'voicewp' ),
			'description' => __( 'You can optionally provide a message when a person is done with your skill.', 'voicewp' ),
			'default_value' => __( 'Thanks for listening!', 'voicewp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 50px;' ),
		) ),
		'news_id' => new Fieldmanager_TextField( array(
			'label' => __( 'News skill ID', 'voicewp' ),
			'description' => __( 'Add the application ID given by Amazon', 'voicewp' ),
			'attributes' => array(
				'style' => 'width: 95%;',
			),
		) ),
		'latest_taxonomies' => new \Fieldmanager_Checkboxes( array(
			'label' => __( 'Allow people to ask for content from specific:', 'voicewp' ),
			'options' => wp_list_pluck( $eligible_news_taxonomy_objects, 'label', 'name' ),
		) ),
	);

	$children['user_dictionary'] = new Fieldmanager_Group( array(
		'label' => __( 'Word Pronunciation Substitutions', 'voicewp' ),
		'collapsible' => true,
		'description' => __( "This allows you to define a global dictionary of words, phrases, abbreviations that Alexa should pronounce a certain way. For example, perhaps every occurrance of the state abreviation 'TN' should be pronounced as 'Tennessee', or 'NYC should be read as 'New York City' or the chemical 'Mg' read as 'Magnesium'. ", 'voicewp' ),
		'description_after_element' => false,
		'children' => array(
			'dictionary' => new Fieldmanager_Group( array(
				'limit' => 0,
				'extra_elements' => 0,
				'label' => __( 'Phrase / Word / Abbreviation', 'voicewp' ),
				'label_macro' => array( '%s', 'search' ),
				'add_more_label' => __( 'Add another phrase, word, or abbreviation', 'voicewp' ),
				'collapsible' => true,
				'children' => array(
					'search' => new Fieldmanager_TextField( array(
						'description' => __( 'Phrase to pronounce differently', 'voicewp' ),
						'attributes' => array(
							'style' => 'width: 45%;',
						),
					) ),
					'replace' => new Fieldmanager_TextField( array(
						'description' => __( 'How the above phrase should be pronounced.', 'voicewp' ),
						'attributes' => array(
							'style' => 'width: 45%;',
						),
					) ),
				),
			) ),
		),
	) );

	$interaction_model = array();

	$interaction_model['news_intent_schema'] = new \Fieldmanager_TextArea( array(
		'label' => __( 'The Intent Schema for your News skill. Add this to your news skill in the <a href="https://developer.amazon.com" target="_blank">Amazon developer console</a>.', 'voicewp' ),
		'escape' => array( 'label' => 'wp_kses_post' ),
		'default_value' => file_get_contents( __DIR__ . '/../speechAssets/news/IntentSchema.json', FILE_USE_INCLUDE_PATH ),
		'skip_save' => true,
		'attributes' => array_merge(
			$readonly,
			array( 'style' => 'width: 100%; height: 300px; font-family: monospace;' )
		),
	) );

	$interaction_model['custom_slot_types'] = new \Fieldmanager_Group( array(
		'label' => __( 'Custom Slot Types', 'voicewp' ),
		'children' => array(
			new \Fieldmanager_Group( array(
				'name' => 'custom_slot_type_children',
				'description' => __( 'These slot types must be added to your news skill in the Amazon developer portal.', 'voicewp' ),
				'children' => array(
					new \Fieldmanager_TextField( __( 'Type', 'voicewp' ), array(
						'name' => 'VOICEWP_POST_NUMBER_WORD',
						'default_value' => 'VOICEWP_POST_NUMBER_WORD',
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; font-family: monospace' )
						),
					) ),
					new \Fieldmanager_TextArea( __( 'Values', 'voicewp' ), array(
						'name' => 'VOICEWP_POST_NUMBER_WORD_values',
						'default_value' => "first\nsecond\nthird\nfourth\nfifth",
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; height: 150px; font-family: monospace;' )
						),
					) ),
					new \Fieldmanager_TextField( __( 'Type', 'voicewp' ), array(
						'name' => 'VOICEWP_TERM_NAME',
						'default_value' => 'VOICEWP_TERM_NAME',
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; font-family: monospace' )
						),
					) ),
					new \Fieldmanager_TextArea( __( 'Values', 'voicewp' ), array(
						'name' => 'VOICEWP_TERM_NAME_values',
						'default_value' => implode(
							"\n",
							// Generate sample terms from all available taxonomies.
							// We want someone to add this slot even if they haven't
							// turned on taxonomies so it's already there if they do.
							array_values( array_unique( array_map( 'strtolower', wp_list_pluck( get_terms( array(
								'number' => 100,
								'order' => 'DESC',
								'orderby' => 'count',
								'taxonomy' => array_values( wp_list_pluck( $eligible_news_taxonomy_objects, 'name' ) ),
							) ), 'name' ) ) ) )
						),
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; height: 150px; font-family: monospace;' )
						),
					) ),
				),
			) ),
		),
		'skip_save' => true,
	) );

	$interaction_model['news_utterances'] = new Fieldmanager_TextArea( array(
		'label' => __( 'Here\'s a starting point for your skill\'s Sample Utterances. You can add these to your news skill in the <a href="https://developer.amazon.com" target="_blank">Amazon developer console</a>.', 'voicewp' ),
		'escape' => array( 'label' => 'wp_kses_post' ),
		'default_value' => file_get_contents( __DIR__ . '/../speechAssets/news/Utterances.txt', FILE_USE_INCLUDE_PATH ),
		'skip_save' => true,
		'attributes' => array_merge(
			$readonly,
			array( 'style' => 'width: 100%; height: 300px;' )
		),
	) );

	$children['interaction_model'] = new \Fieldmanager_Group( array(
		'label' => __( 'Interaction Model', 'voicewp' ),
		'collapsible' => true,
		'children' => $interaction_model,
	) );

	$fm = new Fieldmanager_Group( array(
		'name' => 'voicewp-settings',
		'children' => $children,
	) );
	$fm->activate_submenu_page();
}
add_action( 'fm_submenu_voicewp-settings', 'voicewp_fm_alexa_settings' );
if ( function_exists( 'fm_register_submenu_page' ) ) {
	fm_register_submenu_page( 'voicewp-settings', 'tools.php', __( 'Alexa Skill Settings', 'voicewp' ), __( 'Alexa Skill Settings', 'voicewp' ), 'manage_options', 'voicewp-settings' );
}

/**
 * Creates option of user defined dictionary terms for replacement within
 * Alexa content. Uses the 'sub' element to specify pronunciations of words.
 *
 * @param array $data FM data
 * @return array
 */
function voicewp_fm_submenu_presave_data( $data ) {
	if ( empty( $data['user_dictionary']['dictionary'] ) || ! is_array( $data['user_dictionary']['dictionary'] ) ) {
		return $data;
	}

	$dictionary = get_option( 'voicewp_user_dictionary', array() );
	foreach ( $data['user_dictionary']['dictionary'] as $key => $value ) {
		if ( ! empty( $value['search'] ) ) {
			$dictionary[ $value['search'] ] = sprintf( '<sub alias="%s">%s</sub>', $value['replace'], $value['search'] );
		}
	}
	update_option( 'voicewp_user_dictionary', $dictionary );
	return $data;
}
add_filter( 'fm_submenu_presave_data', 'voicewp_fm_submenu_presave_data' );

/*
 * Display a readonly field with URL of category briefing
 */
function voicewp_briefing_category_url() {
	$id = ( isset( $_GET['tag_ID'] ) ) ? absint( $_GET['tag_ID'] ) : 0;
	$fm = new Fieldmanager_TextField( array(
		'name' => 'briefing_url',
		'default_value' => home_url( '/wp-json/voicewp/v1/skill/briefing/' . $id ),
		'attributes' => array( 'readonly' => 'readonly' ),
	) );
	$fm->add_term_meta_box( __( 'Flash Briefing URL', 'voicewp' ), array( 'voicewp-briefing-category' ) );
}
add_action( 'fm_term_voicewp-briefing-category', 'voicewp_briefing_category_url' );
