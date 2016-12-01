<?php

//namespace Alexa_Quote;

class Alexa_Briefing {
	public function briefing_request() {

		$briefing = get_posts( [
			'posts_per_page' => 1,
			'post_type' => 'alexawp-briefing',
			'post_status' => 'publish',
		] );

		if ( $briefing && ! empty( $briefing[0] ) ) {
			$response = [
				'uid' => uniqid(),
				'updateDate' => date( 'Y-m-d', $briefing[0]->post_modified ) . 'T' . date( 'H:i:s', $briefing[0]->post_modified ) . '.0Z',
				'titleText' => $briefing[0]->post_title,
				'mainText' => $briefing[0]->post_content,
				'redirectionUrl' => home_url(),
			];
		}

		return $response;

	}
}
