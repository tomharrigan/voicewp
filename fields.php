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
		$utterances .= "Latest Ask %s what's up\r";
		$utterances .= "Latest Ask %s what's new\r";
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
