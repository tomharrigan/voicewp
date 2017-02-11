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

			if ( isset( $attachment_metadata['length'] ) && $attachment_metadata['length'] > ( 10 * MINUTE_IN_SECONDS ) ) {
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
	$readonly = [ 'readonly' => 'readonly' ];

	$news_post_types = alexawp_news_post_types();
	// All public taxonomies associated with news post types. Could be abstracted into a function.
	$eligible_news_taxonomy_objects = array_filter(
		get_taxonomies( [ 'public' => true ], 'objects' ),
		function ( $taxonomy ) use ( $news_post_types ) {
			return array_intersect( $news_post_types, $taxonomy->object_type );
		}
	);

	$children = [
		'news_invocation' => new Fieldmanager_TextField( [
			'label' => __( 'What is the invocation name you will use for this skill?', 'alexawp' ),
			'description' => __( 'This is the name a person says when they wish to interact with your skill.', 'alexawp' ),
		] ),
		'news_id' => new Fieldmanager_TextField( [
			'label' => __( 'News skill ID', 'alexawp' ),
			'description' => __( 'Add the application ID given by Amazon', 'alexawp' ),
			'attributes' => [
				'style' => 'width: 95%;',
			],
		] ),
		'latest_taxonomies' => new \Fieldmanager_Checkboxes( [
			'label' => __( 'Allow people to ask for content from specific:', 'alexawp' ),
			'options' => wp_list_pluck( $eligible_news_taxonomy_objects, 'label', 'name' ),
		] ),
	];

	$alexawp_settings = get_option( 'alexawp-settings' );
	$saved_invocation = ( ! empty( $alexawp_settings['news_invocation'] ) );

	if ( $saved_invocation ) {
		$children['news_utterances'] = new Fieldmanager_TextArea( [
			'label' => __( "Here's a starting point for your skill's Sample Utterances. You can add these to your news skill in the Amazon developer portal.", 'alexawp' ),
			'default_value' => implode(
				"\r",
				[
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest content', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest articles', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest stories', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest news', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Get the latest news from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Get the latest stories from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Get the latest content from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Ask %1$s what\'s up', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name */
					'Latest ' . sprintf( __( 'Ask %1$s what\'s new', 'alexawp' ), $alexawp_settings['news_invocation'] ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest %2$s articles', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest %2$s content', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest %2$s news', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for the latest %2$s stories', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for %2$s articles', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for %2$s content', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for %2$s news', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s for %2$s stories', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s about %2$s articles', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s about %2$s content', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s about %2$s news', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Ask %1$s about %2$s stories', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get the latest %2$s articles from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get the latest %2$s content from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get the latest %2$s news from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get the latest %2$s stories from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get %2$s articles from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get %2$s content from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get %2$s news from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: skill invocation name, 2: search term */
					'Latest ' . sprintf( __( 'Get %2$s stories from %1$s', 'alexawp' ), $alexawp_settings['news_invocation'], '{TermName}' ),
					/* translators: 1: cardinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s', 'alexawp' ), '{PostNumber}' ),
					/* translators: 1: cardinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s post', 'alexawp' ), '{PostNumber}' ),
					/* translators: 1: cardinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s article', 'alexawp' ), '{PostNumber}' ),
					/* translators: 1: cardinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s story', 'alexawp' ), '{PostNumber}' ),
					/* translators: 1: cardinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read %1$s', 'alexawp' ), '{PostNumber}' ),
					/* translators: 1: ordinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s post', 'alexawp' ), '{PostNumberWord}' ),
					/* translators: 1: ordinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s article', 'alexawp' ), '{PostNumberWord}' ),
					/* translators: 1: ordinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read the %1$s story', 'alexawp' ), '{PostNumberWord}' ),
					/* translators: 1: ordinal number of the post to read */
					'ReadPost ' . sprintf( __( 'Read %1$s', 'alexawp' ), '{PostNumberWord}' ),
				]
			),
			'skip_save' => true,
			'attributes' => array_merge(
				$readonly,
				[ 'style' => 'width: 95%; height: 300px;' ]
			),
		] );
	}

	$children['news_intent_schema'] = new \Fieldmanager_TextArea( [
		'label' => __( 'The Intent Schema for your News skill. Add this to your news skill in the Amazon developer portal.', 'alexawp' ),
		'default_value' => wp_json_encode(
			[
				'intents' => [
					[
						'intent' => 'Latest',
						'slots' => [
							[
								'name' => 'TermName',
								'type' => 'ALEXAWP_TERM_NAME',
							],
						],
					],
					[
						'intent' => 'ReadPost',
						'slots' => [
							[
								'name' => 'PostNumber',
								'type' => 'AMAZON.NUMBER',
							],
							[
								'name' => 'PostNumberWord',
								'type' => 'ALEXAWP_POST_NUMBER_WORD',
							],
						],
					],
					[
						'intent' => 'AMAZON.StopIntent',
					],
				],
			],
			JSON_PRETTY_PRINT
		),
		'skip_save' => true,
		'attributes' => array_merge(
			$readonly,
			[ 'style' => 'width: 95%; height: 300px; font-family: monospace;' ]
		),
	] );

	$children['custom_slot_types'] = new \Fieldmanager_Group( [
		'label' => __( 'Custom Slot Types', 'alexawp' ),
		'children' => [
			new \Fieldmanager_Group( [
				'description' => __( 'These slot types must be added to your news skill in the Amazon developer portal.', 'alexawp' ),
				'children' => [
					new \Fieldmanager_TextField( __( 'Type', 'alexawp' ), [
						'default_value' => 'ALEXAWP_POST_NUMBER_WORD',
						'attributes' => array_merge(
							$readonly,
							[ 'style' => 'width: 50%; font-family: monospace' ]
						),
					] ),
					new \Fieldmanager_TextArea( __( 'Values', 'alexawp' ), [
						'default_value' => "first\nsecond\nthird\nfourth\nfifth",
						'attributes' => array_merge(
							$readonly,
							[ 'style' => 'width: 50%; height: 150px; font-family: monospace;' ]
						),
					] ),
					new \Fieldmanager_TextField( __( 'Type', 'alexawp' ), [
						'default_value' => 'ALEXAWP_TERM_NAME',
						'attributes' => array_merge(
							$readonly,
							[ 'style' => 'width: 50%; font-family: monospace' ]
						),
					] ),
					new \Fieldmanager_TextArea( __( 'Values', 'alexawp' ), [
						'default_value' => implode(
							"\n",
							// Generate sample terms from all available taxonomies.
							// We want someone to add this slot even if they haven't
							// turned on taxonomies so it's already there if they do.
							array_values( array_unique( array_map( 'strtolower', wp_list_pluck( get_terms( [
								'number' => 100,
								'order' => 'DESC',
								'orderby' => 'count',
								'taxonomy' => array_values( wp_list_pluck( $eligible_news_taxonomy_objects, 'name' ) ),
							] ), 'name' ) ) ) )
						),
						'attributes' => array_merge(
							$readonly,
							[ 'style' => 'width: 50%; height: 150px; font-family: monospace;' ]
						),
					] ),
				],
			] ),
		],
		'skip_save' => true,
	] );

	if ( $saved_invocation ) {
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
