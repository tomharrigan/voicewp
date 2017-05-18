<?php
/**
 * Add the Alexa app ID.
 * Populates the types of skills that can be created
 */
function alexawp_fm_alexa_app_settings() {

	$children = array(
		new \Fieldmanager_Checkbox( array(
			'name' => 'is_standalone',
			'label' => __( 'This is a standalone skill', 'alexawp' ),
			'description' => __( 'Create as a separate skill or part of main skill.', 'alexawp' ),
		) ),
		new \Fieldmanager_TextField( __( 'Alexa Application ID', 'alexawp' ), array(
			'name' => 'app_id',
			'description' => __( 'Add the application ID given by Amazon', 'alexawp' ),
			'display_if' => array(
				'src' => 'is_standalone',
				'value' => true,
			),
		) ),
		new \Fieldmanager_Select( __( 'Skill Type', 'alexawp' ), array(
			'name' => 'type',
			'first_empty' => true,
			'options' => array(
				// Key is class name
				'Quote' => __( 'Fact / Quote', 'alexawp' ),
			),
		) ),
		new \Fieldmanager_Media( __( 'Default App Card Image', 'alexawp' ), array(
			'name' => 'default_image',
			'description' => __( 'Image to be used when no other is provided', 'alexawp' ),
		) ),
	);

	$fm = new \Fieldmanager_Group( array(
		'name' => 'alexawp_skill',
		'serialize_data' => false,
		// Needs to be name => field for compat with FM's validation routines.
		'children' => array_combine( wp_list_pluck( $children, 'name' ), $children ),
	) );
	$context = fm_get_context();
	return $fm->add_meta_box( __( 'Skill Settings', 'alexawp' ), $context[1], 'normal', 'high' );
}
add_action( 'fm_post_alexawp-skill', 'alexawp_fm_alexa_app_settings' );

/**
 * Fields for controlling flash briefing content.
 *
 * @return \Fieldmanager_Context_Post Post context.
 */
function alexawp_fm_briefing_content() {
	$post_id = ( isset( $_GET['post'] ) ) ? intval( $_GET['post'] ) : 0;
	$allowed_formats = array( 'mp3' );

	$children = array(
		new \AlexaWP_Fieldmanager_Content_TextArea( __( 'Text', 'alexawp' ), array(
			'description' => __( 'Text should be under 4,500 characters.', 'alexawp' ),
			'attributes' => array(
				'style' => 'width: 100%; height: 400px',
				'maxlength' => 4500,
			),
		) ),
		new \Fieldmanager_Media( __( 'Uploaded MP3', 'alexawp' ), array(
			'name' => 'attachment_id',
			'mime_type' => 'audio/mpeg',
			'button_label' => __( 'Select a File', 'alexawp' ),
			'modal_button_label' => __( 'Select File', 'alexawp' ),
			'modal_title' => __( 'Select a File', 'alexawp' ),
			'selected_file_label' => __( 'Selected File:', 'alexawp' ),
			'remove_media_label' => __( 'Remove Selection', 'alexawp' ),
		) ),
		new \Fieldmanager_Link( __( 'HTTPS URL to an MP3', 'alexawp' ), array(
			'name' => 'audio_url',
			'attributes' => array(
				'style' => 'width: 100%;',
			),
		) ),
	);

	foreach ( $children as $key => $value ) {
		$children[ $key ]->display_if = array(
			'src' => 'source',
			'value' => $children[ $key ]->name,
		);
	}

	// Display-if control.
	$display_if = new \Fieldmanager_Radios( __( 'Source', 'alexawp' ), array(
		'name' => 'source',
		'options' => wp_list_pluck( $children, 'label', 'name' ),
	) );

	// Briefing UUID, saved the first time and used thereafter.
	$uuid = new \Fieldmanager_Hidden( array(
		'name' => 'uuid',
	) );

	if ( ! get_post_meta( $post_id, 'alexawp_briefing_uuid', true ) ) {
		$uuid->default_value = alexawp_generate_uuid4();
	}

	array_unshift( $children, $display_if, $uuid );

	$fm = new \Fieldmanager_Group( array(
		'name' => 'alexawp_briefing',
		'serialize_data' => false,
		// Needs to be name => field for compat with FM's validation routines.
		'children' => array_combine( wp_list_pluck( $children, 'name' ), $children ),
	) );

	// Help text.
	if ( $post_id ) {
		$existing_audio_url = get_post_meta( $post_id, 'alexawp_briefing_audio_url', true );

		if ( ! $existing_audio_url || ! in_array( pathinfo( parse_url( $existing_audio_url, PHP_URL_PATH ), PATHINFO_EXTENSION ), $allowed_formats, true ) ) {
			$fm->children['audio_url']->description = __( 'Please make sure this is a URL to an MP3 file.', 'alexawp' );
		}

		$existing_attachment_id = get_post_meta( $post_id, 'alexawp_briefing_attachment_id', true );

		if ( $existing_attachment_id ) {
			$attachment_metadata = wp_get_attachment_metadata( $existing_attachment_id );
			$warnings = array();

			if ( ! isset( $attachment_metadata['fileformat'] ) || ! in_array( $attachment_metadata['fileformat'], $allowed_formats, true ) ) {
				$warnings[] = __( 'Please make sure this is an MP3 upload.', 'alexawp' );
			}

			if ( isset( $attachment_metadata['length'] ) && $attachment_metadata['length'] > ( 10 * MINUTE_IN_SECONDS ) ) {
				$warnings[] = __( 'Audio should be under 10 minutes long.', 'alexawp' );
			}

			$fm->children['attachment_id']->description = implode( ' ', $warnings );
		}
	}

	$context = fm_get_context();
	return $fm->add_meta_box( __( 'Briefing Content', 'alexawp' ), $context[1], 'normal', 'high' );
}
add_action( 'fm_post_alexawp-briefing', 'alexawp_fm_briefing_content' );

/**
 * Add facts or skills.
 */
function alexawp_fm_skill_fact_quote() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'facts_quotes',
		'limit' => 0,
		'extra_elements' => 0,
		'add_more_label' => __( 'Add another fact or quote', 'alexawp' ),
		'children' => array(
			'fact_quote' => new Fieldmanager_TextField( array(
				'label' => __( 'Fact / Quote', 'alexawp' ),
				'description' => __( 'Add a fact or quote', 'alexawp' ),
			) ),
			'attribution' => new Fieldmanager_TextField( array(
				'label' => __( 'Attribution', 'alexawp' ),
				'description' => __( 'Add attribution if applicable', 'alexawp' ),
			) ),
			'image' => new Fieldmanager_Media( array(
				'label' => __( 'Alexa App Card Image', 'alexawp' ),
			) ),
		),
	) );
	$fm->add_meta_box( __( 'Facts / Quotes', 'alexawp' ), array( 'alexawp-skill' ) );
}
add_action( 'fm_post_alexawp-skill', 'alexawp_fm_skill_fact_quote' );

/**
 * Create a settings page for the news/post consumption skill
 */
function alexawp_fm_alexa_settings() {
	$readonly = array( 'readonly' => 'readonly' );

	$news_post_types = alexawp_news_post_types();
	// All public taxonomies associated with news post types. Could be abstracted into a function.
	$eligible_news_taxonomy_objects = array_filter(
		get_taxonomies( array( 'public' => true ), 'objects' ),
		function ( $taxonomy ) use ( $news_post_types ) {
			return ( $taxonomy->label && array_intersect( $news_post_types, $taxonomy->object_type ) );
		}
	);

	$children = array(
		'launch_request' => new Fieldmanager_TextArea( array(
			'label' => __( 'Welcome message', 'alexawp' ),
			'description' => __( 'This is the message a person hears when they open your skill with an utterance such as "Alexa, open {your skill name}"', 'alexawp' ),
			'default_value' => __( 'Welcome to the {put your skill name here} Skill. This skill allows you to listen to content from {your site name}. You can ask questions like: What are the latest articles? ... Now, what can I help you with.', 'alexawp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 100px;' ),
		) ),
		'help_intent' => new Fieldmanager_TextArea( array(
			'label' => __( 'Help message', 'alexawp' ),
			'description' => __( "This is the message a person hears when they ask your skill for 'help'", 'alexawp' ),
			'default_value' => __( "{put your skill name here} provides you with the latest content from {your site name}. You can ask me for the latest articles, and then select an item from the list by saying, for example, 'read the 3rd article' Or you can also say exit... What can I help you with?", 'alexawp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 100px;' ),
		) ),
		'stop_intent' => new Fieldmanager_TextArea( array(
			'label' => __( 'Stop message', 'alexawp' ),
			'description' => __( 'You can optionally provide a message when a person is done with your skill.', 'alexawp' ),
			'default_value' => __( 'Thanks for listening!', 'alexawp' ),
			'attributes' => array( 'style' => 'width: 95%; height: 100px;' ),
		) ),
		'news_id' => new Fieldmanager_TextField( array(
			'label' => __( 'News skill ID', 'alexawp' ),
			'description' => __( 'Add the application ID given by Amazon', 'alexawp' ),
			'attributes' => array(
				'style' => 'width: 95%;',
			),
		) ),
		'latest_taxonomies' => new \Fieldmanager_Checkboxes( array(
			'label' => __( 'Allow people to ask for content from specific:', 'alexawp' ),
			'options' => wp_list_pluck( $eligible_news_taxonomy_objects, 'label', 'name' ),
		) ),
	);

	$children['news_utterances'] = new Fieldmanager_TextArea( array(
		'label' => __( "Here's a starting point for your skill's Sample Utterances. You can add these to your news skill in the Amazon developer portal.", 'alexawp' ),
		'default_value' => file_get_contents( 'speechAssets/Utterances.txt', FILE_USE_INCLUDE_PATH ),
		'skip_save' => true,
		'attributes' => array_merge(
			$readonly,
			array( 'style' => 'width: 95%; height: 300px;' )
		),
	) );

	$children['news_intent_schema'] = new \Fieldmanager_TextArea( array(
		'label' => __( 'The Intent Schema for your News skill. Add this to your news skill in the Amazon developer portal.', 'alexawp' ),
		'default_value' => file_get_contents( 'speechAssets/IntentSchema.json', FILE_USE_INCLUDE_PATH ),
		'skip_save' => true,
		'attributes' => array_merge(
			$readonly,
			array( 'style' => 'width: 95%; height: 300px; font-family: monospace;' )
		),
	) );

	$children['custom_slot_types'] = new \Fieldmanager_Group( array(
		'label' => __( 'Custom Slot Types', 'alexawp' ),
		'children' => array(
			new \Fieldmanager_Group( array(
				'name' => 'custom_slot_type_children',
				'description' => __( 'These slot types must be added to your news skill in the Amazon developer portal.', 'alexawp' ),
				'children' => array(
					new \Fieldmanager_TextField( __( 'Type', 'alexawp' ), array(
						'name' => 'ALEXAWP_POST_NUMBER_WORD',
						'default_value' => 'ALEXAWP_POST_NUMBER_WORD',
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; font-family: monospace' )
						),
					) ),
					new \Fieldmanager_TextArea( __( 'Values', 'alexawp' ), array(
						'name' => 'ALEXAWP_POST_NUMBER_WORD_values',
						'default_value' => "first\nsecond\nthird\nfourth\nfifth",
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; height: 150px; font-family: monospace;' )
						),
					) ),
					new \Fieldmanager_TextField( __( 'Type', 'alexawp' ), array(
						'name' => 'ALEXAWP_TERM_NAME',
						'default_value' => 'ALEXAWP_TERM_NAME',
						'attributes' => array_merge(
							$readonly,
							array( 'style' => 'width: 50%; font-family: monospace' )
						),
					) ),
					new \Fieldmanager_TextArea( __( 'Values', 'alexawp' ), array(
						'name' => 'ALEXAWP_TERM_NAME_values',
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

	$fm = new Fieldmanager_Group( array(
		'name' => 'alexawp-settings',
		'children' => $children,
	) );
	$fm->activate_submenu_page();
}
add_action( 'fm_submenu_alexawp-settings', 'alexawp_fm_alexa_settings' );
if ( function_exists( 'fm_register_submenu_page' ) ) {
	fm_register_submenu_page( 'alexawp-settings', 'tools.php', __( 'Alexa Skill Settings', 'alexawp' ), __( 'Alexa Skill Settings', 'alexawp' ), 'manage_options', 'alexawp-settings' );
}
