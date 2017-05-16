<?php

namespace Alexa\Skill;

use Alexa\Request\IntentRequest;

/**
 * Class that creates a custom skill allowing WordPress content to be consumed via Alexa
 */
class News {

	public $intents = array(
		'Latest',
		'ReadPost',
		'AMAZON.StopIntent',
	);

	public function news_request( $event ) {

		$request = $event->get_request();
		$response = $event->get_response();

		if ( $request instanceof \Alexa\Request\IntentRequest ) {
			$intent = $request->intentName;
			switch ( $intent ) {
				case 'Latest':
					$term_slot = strtolower( sanitize_text_field( $request->getSlot( 'TermName' ) ) );

					$args = array(
						'post_type' => alexawp_news_post_types(),
						'posts_per_page' => 5,
						'tax_query' => array(),
					);

					if ( $term_slot ) {
						$news_taxonomies = alexawp_news_taxonomies();

						if ( $news_taxonomies ) {
							/*
							 * TODO:
							 *
							 * Support for 'name__like'?
							 * Support for an 'alias' meta field?
							 * Support for excluding terms?
							 */
							$terms = get_terms( array(
								'name' => $term_slot,
								'taxonomy' => $news_taxonomies,
							) );

							if ( $terms ) {
								// 'term_taxonomy_id' query allows omitting 'taxonomy'.
								$args['tax_query'][] = array(
									'terms' => wp_list_pluck( $terms, 'term_taxonomy_id' ),
									'field' => 'term_taxonomy_id',
								);
							}
						}
					}

					if ( $term_slot && ! $args['tax_query'] ) {
						$this->message( $response );
						break;
					}

					$result = $this->endpoint_content( $args );

					$response
						->respond( $result['content'] )
						/* translators: %s: site title */
						->withCard( sprintf( __( 'Latest on %s', 'alexawp' ), get_bloginfo( 'name' ) ) )
						->addSessionAttribute( 'post_ids', $result['ids'] );
					break;
				case 'ReadPost':
					if ( $post_number = $request->getSlot( 'PostNumberWord' ) ) {
						if ( 'second' === $post_number ) {
							/**
							* Alexa Skills Kit passes 'second' instead of '2nd'
							* unlike the case for all other numbers.
							*/
							$post_number = 2;
						} else {
							$post_number = substr( $post_number, 0, -2 );
						}
					} else {
						$post_number = $request->getSlot( 'PostNumber' );
					}

					if ( ! empty( $request->session->attributes['post_ids'] ) && ! empty( $post_number ) ) {
						$post_id = $this->get_post_id( $request->session->attributes['post_ids'], $post_number );
						$result = $this->endpoint_single_post( $post_id );
						$response->respond( $result['content'] )->withCard( $result['title'], '', $result['image'] )->endSession();
					} else {
						$this->message( $response );
					}
					break;
				case 'AMAZON.StopIntent':
					$response->respond( __( 'Thanks for listening!', 'alexawp' ) )->endSession();
					break;
				default:
					$this->custom_functionality( $intent, $request, $response );
					break;
			}
		} elseif ( $request instanceof Alexa\Request\LaunchRequest ) {
			$response->respond( __( "Ask me what's new!", 'alexawp' ) );
		}
	}

	private function custom_functionality( $intent, $request, $response ) {
		$custom_skill_index = get_option( 'alexawp_skill_index_map', array() );
		if ( isset( $custom_skill_index[ $intent ] ) ) {
			$alexawp = Alexawp::get_instance();
			$alexawp->skill_dispatch( absint( $custom_skill_index[ $intent ] ), $request, $response );
		}
	}

	private function endpoint_single_post( $id ) {
		$single_post = get_post( $id );
		$post_content = preg_replace( '|^(\s*)(https?://[^\s<>"]+)(\s*)$|im', '', strip_tags( strip_shortcodes( $single_post->post_content ) ) );
		return array(
			'content' => $post_content,
			'title' => $single_post->post_title,
			'image' => get_post_thumbnail_id( $id ),
		);
	}

	private function get_post_id( $ids, $number ) {
		$number = absint( $number ) - 1;
		return absint( $ids[ $number ] );
	}

	private function message( $response, $case = 'missing' ) {
		$response->respond( __( "Sorry! I couldn't find any news about that topic.", 'alexawp' ) )->endSession();
	}

	private function endpoint_content( $args ) {
		$news_posts = get_posts( array_merge( $args, array(
			'no_found_rows' => true,
			'post_status' => 'publish',
		) ) );

		$content = '';
		$ids = array();
		if ( ! empty( $news_posts ) && ! is_wp_error( $news_posts ) ) {
			foreach ( $news_posts as $key => $news_post ) {
				// TODO: Sounds a little strange when there's only one result.
				// Appending 'th' to any number results in proper ordinal pronunciation
				$content .= ( $key + 1 ) . 'th, ' . $news_post->post_title . '. ';
				$ids[] = $news_post->ID;
			}
		}

		return array(
			'content' => $content,
			'ids' => $ids,
		);
	}
}
