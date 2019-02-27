<?php

$news_post_types = voicewp_news_post_types();

// All public taxonomies associated with news post types. Could be abstracted into a function.
$eligible_news_taxonomy_objects = array_filter(
	get_taxonomies( array( 'public' => true ), 'objects' ),
	function ( $taxonomy ) use ( $news_post_types ) {
		return ( $taxonomy->label && array_intersect( $news_post_types, $taxonomy->object_type ) );
	}
);

/**
 * Creates option of user defined dictionary terms for replacement within
 * Alexa content. Uses the 'sub' element to specify pronunciations of words.
 *
 * @param array $new_value The new data.
 * @param array $old_value The old data.
 * @return array $new_value The new data.
 */
function voicewp_fm_submenu_presave_data( $new_value, $old_value ) {
	if ( empty( $new_value['user_dictionary']['dictionary'] ) || ! is_array( $new_value['user_dictionary']['dictionary'] ) ) {
		return $new_value;
	}

	$dictionary = get_option( 'voicewp_user_dictionary', array() );
	foreach ( $new_value['user_dictionary']['dictionary'] as $key => $value ) {
		if ( ! empty( $value['search'] ) ) {
			$dictionary[ $value['search'] ] = sprintf( '<sub alias="%s">%s</sub>', $value['replace'], $value['search'] );
		}
	}
	update_option( 'voicewp_user_dictionary', $dictionary );

	return $new_value;
}
add_filter( 'pre_update_option_voicewp-settings', 'voicewp_fm_submenu_presave_data', 10, 2 );

/**
 * Add the Flash Breifing URL.
 */
function voicewp_briefing_category_url() {
	$id = ( isset( $_GET['tag_ID'] ) ) ? absint( $_GET['tag_ID'] ) : 0;
	?>
	<tr class="form-field">
		<th scope="row" valign="top">
		<label for="voicewp-briefing-url"><?php esc_html_e( 'Flash Briefing URL', 'voicewp' ); ?></label></th>
		<td>
			<input class="fm-element" type="text" name="voicewp-briefing-url" id="voicewp-briefing-url" value="<?php echo esc_url( home_url( '/wp-json/voicewp/v1/skill/briefing/' . $id ) ); ?>" readonly="readonly">
		</td>
	</tr>
	<?php
}
add_action( 'voicewp-briefing-category_edit_form_fields', 'voicewp_briefing_category_url' );

/**
 * Make sure the briefing content is saved to the post object as well as post meta.
 *
 * @param  mixed    $value           The current meta value.
 * @param  int      $post_id         The post ID.
 * @param  string   $name            The meta name.
 * @param  Settings $settings_object The settings object.
 * @return mixed $value The new meta value.
 */
function voicewp_save_briefing_content( $value, $post_id, $name, $settings_object ) {
	if (
		'voicewp-briefing' === get_post_type( $post_id )
		&& 'voicewp_briefing_content' === $name
	) {
		// Prevent infinite loops.
		remove_action( 'save_post', array( $settings_object, 'save_post_fields' ) );

		// Update the post's content.
		wp_update_post( array(
			'ID' => $post_id,
			'post_content' => $value,
		) );

		// Re-add the filter.
		add_action( 'save_post', array( $settings_object, 'save_post_fields' ) );
	}

	return $value;
}
add_filter( 'voicewp_update_post_meta', 'voicewp_save_briefing_content', 10, 4 );

/**
 * Get the briefing content value from the post object content.
 *
 * @param  mixed    $value           The current meta value.
 * @param  int      $post_id         The post ID.
 * @param  string   $name            The meta name.
 * @param  Settings $settings_object The settings object.
 * @return mixed $value The new meta value.
 */
function voicewp_get_briefing_content( $value, $post_id, $name, $settings_object ) {
	if (
		'voicewp-briefing' === get_post_type( $post_id )
		&& 'voicewp_briefing_content' === $name
	) {
		$post_object = get_post( $post_id );

		if ( $post_object instanceof \WP_Post ) {
			return $post_object->post_content;
		}
	}

	return $value;
}
add_filter( 'voicewp_get_post_meta', 'voicewp_get_briefing_content', 10, 4 );

// Get the current post ID.
$post_id = ( isset( $_GET['post'] ) ) ? absint( $_GET['post'] ) : 0;

// Add Briefing settings.
$briefing_settings = new VoiceWp\Settings(
	'post',
	'voicewp_briefing',
	__( 'Briefing Settings', 'voicewp' ),
	array(
		'source' => array(
			'type' => 'select',
			'label' => __( 'Source', 'voicewp' ),
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
		),
		'content' => array(
			'type' => 'textarea',
			'label' => __( 'Text', 'voicewp' ),
			'description' => __( 'Text should be under 4,500 characters.', 'voicewp' ),
			'attributes' => array(
				'style' => 'width: 100%; height: 400px',
				'maxlength' => 4500,
			),
			'display_if' => array(
				'src' => 'voicewp_briefing_source',
				'value' => 'content',
			),
		),
		'audio_url' => array(
			'type' => 'text',
			'label' => __( 'HTTPS URL to an MP3', 'voicewp' ),
			'description' => __( 'Please make sure this is a URL to an MP3 file.', 'voicewp' ),
			'attributes' => array(
				'style' => 'width: 100%;',
			),
			'display_if' => array(
				'src' => 'voicewp_briefing_source',
				/**
				 * Allow filtering of what sources an audio link is used with
				 *
				 * @since 1.1.0
				 *
				 * @param string Comma separated list of source options to display the field for
				 */
				'value' => apply_filters( 'voicewp_briefing_audio_url_display_if', 'audio_url' ),
			),
		),
		'attachment_id' => array(
			'type' => 'media',
			'label' => __( 'Uploaded MP3', 'voicewp' ),
			'mime_type' => 'audio',
			'display_if' => array(
				'src' => 'voicewp_briefing_source',
				'value' => 'attachment_id',
			),
		),
		'uuid' => array(
			'label' => __( 'UUID', 'voicewp' ),
			'default_value' => ! get_post_meta( $post_id, 'voicewp_briefing_uuid', true ) ?: voicewp_generate_uuid4(),
			'attributes' => array(
				'readonly' => 'readonly',
			),
		),
	),
	array(
		'screen' => 'voicewp-briefing',
		'serialize_data' => false,
	)
);

// Add Skill settings.
$skill_settings = new VoiceWp\Settings(
	'post',
	'voicewp_skill',
	__( 'Skill Settings', 'voicewp' ),
	array(
		'type' => array(
			'type' => 'select',
			'label' => __( 'Skill Type', 'voicewp' ),
			'first_empty' => true,
			'options' => array(
				// Key is class name.
				'Quote' => __( 'Fact / Quote', 'voicewp' ),
			),
			'description' => __( 'What type of functionality is being added?', 'voicewp' ),
		),
		'default_image' => array(
			'type' => 'media',
			'label' => __( 'Default App Card Image', 'voicewp' ),
			'description' => __( 'Image to be used when no other is provided. App cards can be displayed within the Alexa app when she responds to a user request.', 'voicewp' ),
		),
		'is_standalone' => array(
			'type' => 'checkbox',
			'label' => __( 'This is a standalone skill', 'voicewp' ),
			'description' => __( 'Will this be its own skill or is this part of another skill?', 'voicewp' ),
		),
		'app_id' => array(
			'label' => __( 'Alexa Application ID', 'voicewp' ),
			'description' => __( 'Add the application ID given by Amazon', 'voicewp' ),
			'display_if' => array(
				'src' => 'voicewp_skill_is_standalone',
				'value' => true,
			),
		),
		'readonly_skill_url' => array(
			'label' => __( 'This is the endpoint URL of your skill. Paste this within the configuration tab for your skill in the developer portal.', 'voicewp' ),
			'default_value' => home_url( '/wp-json/voicewp/v1/skill/' ) . $post_id,
			'attributes' => array(
				'readonly' => 'readonly',
				'style' => 'width: 95%;',
			),
			'display_if' => array(
				'src' => 'voicewp_skill_is_standalone',
				'value' => true,
			),
		),
	),
	array(
		'screen' => 'voicewp-skill',
		'serialize_data' => false,
	)
);

// Add Skill settings.
$skill_fact_quote_settings = new VoiceWp\Settings(
	'post',
	'facts_quotes',
	__( 'Facts / Quotes', 'voicewp' ),
	array(
		'facts_quotes' => array(
			'type' => 'group',
			'limit' => 0,
			'add_more_label' => __( 'Add another fact or quote', 'voicewp' ),
			'children' => array(
				'fact_quote' => array(
					'label' => __( 'Fact / Quote', 'voicewp' ),
					'description' => __( 'Add a fact or quote', 'voicewp' ),
				),
				'attribution' => array(
					'label' => __( 'Attribution', 'voicewp' ),
					'description' => __( 'Add attribution if applicable', 'voicewp' ),
				),
				'image' => array(
					'type' => 'media',
					'label' => __( 'Alexa App Card Image', 'voicewp' ),
				),
			),
		),
	),
	array(
		'screen' => 'voicewp-skill',
		'add_to_prefix' => false,
	)
);

// Add Option settings.
$option_settings = new VoiceWp\Settings(
	'options',
	'voicewp-settings',
	__( 'VoiceWP', 'voicewp' ),
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
		'news_id' => array(
			'label' => __( 'News skill ID', 'voicewp' ),
			'description' => __( 'Add the application ID given by Amazon', 'voicewp' ),
			'attributes' => array(
				'style' => 'width: 95%;',
			),
		),
		'latest_taxonomies' => array(
			'type' => 'checkboxes',
			'label' => __( 'Allow people to ask for content from specific:', 'voicewp' ),
			'options' => wp_list_pluck( $eligible_news_taxonomy_objects, 'label', 'name' ),
		),
		'user_dictionary' => array(
			'type' => 'group',
			'label' => __( 'Word Pronunciation Substitutions', 'voicewp' ),
			'children' => array(
				'dictionary' => array(
					'type' => 'group',
					'limit' => 0,
					'description' => __( "This allows you to define a global dictionary of words, phrases, abbreviations that Alexa should pronounce a certain way. For example, perhaps every occurrance of the state abreviation 'TN' should be pronounced as 'Tennessee', or 'NYC should be read as 'New York City' or the chemical 'Mg' read as 'Magnesium'. ", 'voicewp' ),
					'children' => array(
						'search' => array(
							'description' => __( 'Phrase to pronounce differently', 'voicewp' ),
							'attributes' => array(
								'style' => 'width: 45%;',
							),
						),
						'replace' => array(
							'description' => __( 'How the above phrase should be pronounced.', 'voicewp' ),
							'attributes' => array(
								'style' => 'width: 45%;',
							),
						),
					),
				),
			),
		),
		'interaction_model' => array(
			'type' => 'group',
			'label' => __( 'Interaction Model', 'voicewp' ),
			'children' => array(
				'news_intent_schema' => array(
					'type' => 'textarea',
					'label' => __( 'The Intent Schema for your News skill. Add this to your news skill in the <a href="https://developer.amazon.com" target="_blank">Amazon developer console</a>.', 'voicewp' ),
					'escape' => array( 'label' => 'wp_kses_post' ),
					'default_value' => file_get_contents( __DIR__ . '/../speechAssets/news/IntentSchema.json', FILE_USE_INCLUDE_PATH ),
					'attributes' => array(
						'readonly' => 'readonly',
						'style' => 'width: 100%; height: 300px; font-family: monospace;',
					),
				),
				'custom_slot_types' => array(
					'type' => 'group',
					'label' => __( 'Custom Slot Types', 'voicewp' ),
					'children' => array(
						'custom_slot_type_children' => array(
							'type' => 'group',
							'description' => __( 'These slot types must be added to your news skill in the Amazon developer portal.', 'voicewp' ),
							'children' => array(
								'VOICEWP_POST_NUMBER_WORD' => array(
									'label' => __( 'Type', 'voicewp' ),
									'default_value' => 'VOICEWP_POST_NUMBER_WORD',
									'attributes' => array(
										'readonly' => 'readonly',
										'style' => 'width: 50%; font-family: monospace;',
									),
								),
								'VOICEWP_POST_NUMBER_WORD_values' => array(
									'type' => 'textarea',
									'label' => __( 'Values', 'voicewp' ),
									'default_value' => "first\nsecond\nthird\nfourth\nfifth",
									'attributes' => array(
										'readonly' => 'readonly',
										'style' => 'width: 50%; height: 150px; font-family: monospace;',
									),
								),
								'VOICEWP_TERM_NAME' => array(
									'label' => __( 'Type', 'voicewp' ),
									'default_value' => 'VOICEWP_TERM_NAME',
									'attributes' => array(
										'readonly' => 'readonly',
										'style' => 'width: 50%; font-family: monospace;',
									),
								),
								'VOICEWP_TERM_NAME_values' => array(
									'type' => 'textarea',
									'label' => __( 'Values', 'voicewp' ),
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
									'attributes' => array(
										'readonly' => 'readonly',
										'style' => 'width: 50%; height: 150px; font-family: monospace;',
									),
								),
							),
						),
					),
				),
				'news_utterances' => array(
					'type' => 'textarea',
					'label' => __( 'Here\'s a starting point for your skill\'s Sample Utterances. You can add these to your news skill in the <a href="https://developer.amazon.com" target="_blank">Amazon developer console</a>.', 'voicewp' ),
					'escape' => array( 'label' => 'wp_kses_post' ),
					'default_value' => file_get_contents( __DIR__ . '/../speechAssets/news/Utterances.txt', FILE_USE_INCLUDE_PATH ),
					'skip_save' => true,
					'attributes' => array(
						'readonly' => 'readonly',
						'style' => 'width: 100%; height: 300px;',
					),
				),
			),
		),
	),
	array(
		'parent_page' => 'tools.php',
	)
);
