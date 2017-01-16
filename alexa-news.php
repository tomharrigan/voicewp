<?php

use Alexa\Request\IntentRequest;

class Alexa_News {
	public $numbers = [
		'First' => 1,
		'1st' => 1,
		'Second' => 2,
		'2nd' => 2,
		'Third' => 3,
		'3rd' => 3,
		'Fourth' => 4,
		'4th' => 4,
		'Fifth' => 5,
		'5th' => 5,
	];

	public $placement = [
		'First',
		'Second',
		'Third',
		'Fourth',
		'Fifth',
	];

	public function news_request( $event ) {

		$request = $event->get_request();
		$response = $event->get_response();
		$intent = $request->intentName;

		switch ( $intent ) {
			case 'Latest':
				$result = $this->endpoint_content();
				$response
					->respond( $result['content'] )
					/* translators: %s: site title */
					->withCard( sprintf( __( 'Latest on %s', 'alexawp' ), get_bloginfo( 'name' ) ) )
					->addSessionAttribute( 'post_ids', $result['ids'] );
				break;
			case 'ReadPost':
				if ( $post_number = $request->getSlot( 'PostNumberWord' ) ) {
					$post_number = $this->numbers[ $post_number ] -1;
				} elseif ( $post_number = $request->getSlot( 'PostNumber' ) ) {
					$post_number = $post_number - 1;
				}
				$post_ids = $request->session->attributes['post_ids'];
				$result = $this->endpoint_single_post( $post_ids[ $post_number ] );
				$response->respond( $result['content'] )->withCard( $result['title'] )->endSession();
				break;
			case 'AMAZON.StopIntent':
				$response->respond( __( 'Thanks for listening!', 'alexawp' ) )->endSession();
				break;
			default:
				# code...
				break;
		}
	}

	private function endpoint_single_post( $id ) {
		$single_post = get_post( $id );
		$post_content = strip_tags( strip_shortcodes( $single_post->post_content ) );
		return [
			'content' => $post_content,
			'title' => $single_post->post_title,
		];
	}

	private function endpoint_content() {
		$args = [
			'post_type' => apply_filters( 'alexawp_post_types', [ 'post' ] ),
			'posts_per_page' => 5,
			'post_status' => 'publish',
		];
		$news_posts = get_posts( $args );
		$content = '';
		$ids = [];
		if ( ! empty( $news_posts ) && ! is_wp_error( $news_posts ) ) {
			foreach ( $news_posts as $key => $news_post ) {
				$content .= $this->placement[ $key ] . ', ' . $news_post->post_title . '. ';
				$ids[] = $news_post->ID;
			}
		}

		return [
			'content' => $content,
			'ids' => $ids,
		];
	}
}
