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
	];

	$alexawp_settings = get_option( 'alexawp-settings' );
	$saved_invocation = ( ! empty( $alexawp_settings['news_invocation'] ) );

	if ( $saved_invocation ) {
		$utterances = [
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
		];

		$children['news_utterances'] = new Fieldmanager_TextArea( [
			'label' => __( "Here's a starting point for your skill's Sample Utterances. You can add these to your news skill in the Amazon developer portal.", 'alexawp' ),
			'default_value' => implode( "\r", $utterances ),
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
					],
					[
						'intent' => 'ReadPost',
						'slots'  => [
							[
								'name' => 'PostNumber',
								'type' => 'AMAZON.NUMBER',
							],
							[
								'name' => 'PostNumberWord',
								'type' => 'ALEXAWP.POST_NUMBER_WORD',
							],
						],
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
					'type' => new \Fieldmanager_TextField( 'Type', [
						'default_value' => 'ALEXAWP.POST_NUMBER_WORD',
						'attributes' => array_merge(
							$readonly,
							[ 'style' => 'width: 50%; font-family: monospace' ]
						),
					] ),
					'values' => new \Fieldmanager_TextArea( 'Values', [
						'default_value' => "first\nsecond\nthird\nfourth\nfifth",
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
