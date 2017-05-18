<?php

namespace Alexa\Skill;

/**
 * Class that creates a custom skill for serving facts or quotes
 */
class Quote {

	/**
	 * @var array
	 * Intents supported by this skill type
	 */
	public $intents = array(
		'FactQuote',
	);

	/**
	 * Grabs a quote/fact and responds
	 * @param int $id post id
	 * @param AlexaRequest $request
	 * @param AlexaResponse $response
	 */
	public function quote_request( $id, $request, $response ) {

		$quotes_facts = get_post_meta( $id, 'facts_quotes', true );
		$default_image = get_post_meta( $id, 'alexawp_skill_default_image', true );
		$quote_fact_index = rand( 0, count( $quotes_facts ) - 1 );
		$quote_fact = $quotes_facts[ $quote_fact_index ];
		$image = isset( $quote_fact['image'] ) ? $quote_fact['image'] : $default_image;

		$response->respond( $quote_fact['fact_quote'] )->withCard( $quote_fact['fact_quote'] . ' - ' . $quote_fact['attribution'], '', $image )->endSession();
	}
}
