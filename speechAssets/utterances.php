<?php

$term_name_slot = '{TermName}';
$post_number_slot = '{PostNumber}';
$post_number_ordinal_slot = '{PostNumberWord}';

$ordinals = array(
	esc_html__( 'first', 'voicewp' ),
	esc_html__( 'second', 'voicewp' ),
	esc_html__( 'third', 'voicewp' ),
	esc_html__( 'fourth', 'voicewp' ),
	esc_html__( 'fifth', 'voicewp' ),
);

$news_utterances = array(
	'Latest' => array(
		esc_html__( 'can I have the latest content', 'voicewp' ),
		esc_html__( 'can I have the latest articles', 'voicewp' ),
		esc_html__( 'can I have the latest stories', 'voicewp' ),
		esc_html__( 'can I have the latest news', 'voicewp' ),
		esc_html__( 'what is the latest content', 'voicewp' ),
		esc_html__( 'what are the latest articles', 'voicewp' ),
		esc_html__( 'what are the latest stories', 'voicewp' ),
		esc_html__( 'what is the latest news', 'voicewp' ),
		esc_html__( "what's the latest content", 'voicewp' ),
		esc_html__( "what're the latest articles", 'voicewp' ),
		esc_html__( "what're the latest stories", 'voicewp' ),
		esc_html__( "what's the latest news", 'voicewp' ),
		esc_html__( 'the latest content', 'voicewp' ),
		esc_html__( 'the latest articles', 'voicewp' ),
		esc_html__( 'the latest stories', 'voicewp' ),
		esc_html__( 'the latest news', 'voicewp' ),
		esc_html__( 'give me the latest content', 'voicewp' ),
		esc_html__( 'give me the latest articles', 'voicewp' ),
		esc_html__( 'give me the latest stories', 'voicewp' ),
		esc_html__( 'give me the latest news', 'voicewp' ),
		esc_html__( 'get the latest content', 'voicewp' ),
		esc_html__( 'get the latest articles', 'voicewp' ),
		esc_html__( 'get the latest stories', 'voicewp' ),
		esc_html__( 'get the latest news', 'voicewp' ),
		esc_html__( "what's up", 'voicewp' ),
		esc_html__( "what's new", 'voicewp' ),
		esc_html__( 'read the latest content', 'voicewp' ),
		esc_html__( 'read the latest articles', 'voicewp' ),
		esc_html__( 'read the latest stories', 'voicewp' ),
		esc_html__( 'read the latest news', 'voicewp' ),
		esc_html__( 'read me the latest content', 'voicewp' ),
		esc_html__( 'read me the latest articles', 'voicewp' ),
		esc_html__( 'read me the latest stories', 'voicewp' ),
		esc_html__( 'read me the latest news', 'voicewp' ),
		esc_html__( 'tell me the latest articles', 'voicewp' ),
		esc_html__( 'tell me the latest stories', 'voicewp' ),
		esc_html__( 'tell me the latest news', 'voicewp' ),
	),
	'LatestTerm' => array(
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'the latest %s articles', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'the latest %s content', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'the latest %s news', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'the latest %s stories', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( '%s content', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( '%s articles', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( '%s stories', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( '%s news', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get the latest %s articles', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get the latest %s content', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get the latest %s news', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get the latest %s stories', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get %s articles', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get %s content', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get %s news', 'voicewp' ), $term_name_slot ),
		/* translators: %s: taxonomy term used in Alexa utterance for latest content */
		sprintf( esc_html__( 'Get %s stories', 'voicewp' ), $term_name_slot ),
	),
	'ReadPost' => array(
		/* translators: %s: number */
		sprintf( esc_html__( 'Read the %s', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read the %s post', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read the %s article', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read the %s story', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read %s', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read %s to me', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read me the %s', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read me the %s post', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read me the %s article', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read me the %s story', 'voicewp' ), $post_number_slot ),
		/* translators: %s: number */
		sprintf( esc_html__( 'Read me %s', 'voicewp' ), $post_number_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s post', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s article', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s story', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read %s', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read me the %s', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read me the %s post', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read me the %s article', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read me the %s story', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read me %s', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s to me', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s post to me', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s article to me', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read the %s story to me', 'voicewp' ), $post_number_ordinal_slot ),
		/* translators: %s: ordinal number */
		sprintf( esc_html__( 'Read %s to me', 'voicewp' ), $post_number_ordinal_slot ),
	),
);
