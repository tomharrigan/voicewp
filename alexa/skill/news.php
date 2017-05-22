<?php

namespace Alexa\Skill;

use Alexa\Request\IntentRequest;

/**
 * Class that creates a custom skill allowing WordPress content to be consumed via Alexa
 */
class News {

	/**
	 * @var array
	 * Intents supported by this skill type
	 */
	public $intents = array(
		'Latest',
		'LatestTerm',
		'ReadPost',
		'AMAZON.StopIntent',
		'AMAZON.HelpIntent',
		'AMAZON.CancelIntent',
	);

	/**
	 * Figures out what kind of intent we're dealing with from the request
	 * Handles grabbing the needed data and delivering the response
	 * @param AlexaEvent $event
	 */
	public function news_request( $event ) {

		$request = $event->get_request();
		$response = $event->get_response();

		if ( $request instanceof \Alexa\Request\IntentRequest ) {
			$intent = $request->intent_name;
			switch ( $intent ) {
				case 'LatestTerm':
					$term_slot = strtolower( sanitize_text_field( $request->getSlot( 'TermName' ) ) );
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
								$tax_query = array(
									'terms' => wp_list_pluck( $terms, 'term_taxonomy_id' ),
									'field' => 'term_taxonomy_id',
								);
							}
						}
						if ( ! isset( $tax_query ) ) {
							$this->message( $response );
							break;
						}
					}
				case 'Latest':

					$args = array(
						'post_type' => alexawp_news_post_types(),
						'posts_per_page' => 5,
						'tax_query' => isset( $tax_query ) ? $tax_query : array(),
					);

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
						$response
							->respond( $result['content'] )
							->withCard( $result['title'], '', $result['image'] )
							->endSession();
					} else {
						$this->message( $response );
					}
					break;
				case 'AMAZON.StopIntent':
				case 'AMAZON.CancelIntent':
					$this->message( $response, 'stop_intent' );
					break;
				case 'AMAZON.HelpIntent':
					$this->message( $response, 'help_intent' );
					break;
				default:
					$this->skill_intent( $intent, $request, $response );
					break;
			}
		} elseif ( $request instanceof \Alexa\Request\LaunchRequest ) {
			$this->message( $response, 'launch_request' );
		}
	}

	/**
	 * Handles intents that come from outside the main set of News skill intents
	 * @param string $intent name of the intent to handle
	 * @param AlexaRequest $request
	 * @param AlexaResponse $response
	 */
	private function skill_intent( $intent, $request, $response ) {
		$custom_skill_index = get_option( 'alexawp_skill_index_map', array() );
		if ( isset( $custom_skill_index[ $intent ] ) ) {
			$alexawp = Alexawp::get_instance();
			$alexawp->skill_dispatch( absint( $custom_skill_index[ $intent ] ), $request, $response );
		}
	}

	/**
	 * Packages up the post data that will be served in the response
	 * @param int $id ID of post to get data for
	 * @return array Data from the post being returned
	 */
	private function endpoint_single_post( $id ) {
		$transient_key = 'voicewp_single_' . $id;
		if ( false === ( $result = get_transient( $transient_key ) ) ) {
			$single_post = get_post( $id );
			$post_content = preg_replace( '|^(\s*)(https?://[^\s<>"]+)(\s*)$|im', '', strip_tags( strip_shortcodes( $single_post->post_content ) ) );
			$result = array(
				'content' => $post_content,
				'title' => $single_post->post_title,
				'image' => get_post_thumbnail_id( $id ),
			);
			// TODO: If content of the post changes,
			// repopulate this cache entry with the fresh data
			set_transient( $transient_key, $result, 60 * MINUTE_IN_SECONDS );
		}
		return $result;
	}

	/**
	 * Gets a post ID from an array based on user input.
	 * Handles the offset between user selection of post in a list,
	 * and zero based index of array
	 * @param array $ids Array of IDs that were listed to the user
	 * @param in $number User selection from list
	 * @return int The post the user asked for
	 */
	private function get_post_id( $ids, $number ) {
		$number = absint( $number ) - 1;
		return absint( $ids[ $number ] );
	}

	/**
	 * Deliver a message to user
	 * @param AlexaResponse $response
	 * @param string $case The type of message to return
	 */
	private function message( $response, $case = 'missing' ) {
		$alexawp_settings = get_option( 'alexawp-settings' );
		if ( isset( $alexawp_settings[ $case ] ) ) {
			$response->respond( $alexawp_settings[ $case ] );
		} else {
			$response->respond( __( "Sorry! I couldn't find any news about that topic. Try asking something else!", 'alexawp' ) );
		}
		if ( 'stop_intent' === $case ) {
			$response->endSession();
		}
	}

	/**
	 * Creates output when a user asks for a list of posts.
	 * Delivers an array containing a numbered list of post titles
	 * to choose from and a subarray of IDs that get set in an attribute
	 * @param array $response
	 * @return array array of post IDs and titles
	 */
	private function endpoint_content( $args ) {
		$transient_key = isset( $args['tax_query']['terms'][0] ) ? 'voicewp_latest_' . $args['tax_query']['terms'][0] : 'voicewp_latest';
		if ( false === ( $result = get_transient( $transient_key ) ) ) {
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

			$result = array(
				'content' => $content,
				'ids' => $ids,
			);
			// TODO: hook on 'publish_*' to clear cache entry when a post type served
			// in news content is published and remove the cache time here
			set_transient( $transient_key, $result, 15 * MINUTE_IN_SECONDS );
		}
		return $result;
	}
}
