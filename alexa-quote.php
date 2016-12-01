<?php

class Alexa_Quote {
	public function quote_request( $id, $event ) {

		$request = $event->get_request();
		$response = $event->get_response();

		$quotes_facts = get_post_meta( $id, 'facts_quotes', true );
		$default_image = get_post_meta( $id, 'alexawp_default_image', true );
		$quote_fact_index = rand( 0, count( $quotes_facts ) - 1 );
		$quote_fact = $quotes_facts[ $quote_fact_index ];
		$image = isset( $quote_fact['image'] ) ? $quote_fact['image'] : $default_image;

		$response->respond( $quote_fact['fact_quote'] )->withCard( $quote_fact['fact_quote'] . ' - ' . $quote_fact['attribution'], '', $image )->endSession();
	}
}
