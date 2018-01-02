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
	public function news_request( $request, $response ) {

		if ( $request instanceof \Alexa\Request\IntentRequest ) {
			$intent = $request->intent_name;
			switch ( $intent ) {
				case 'LatestTerm':
					$term_slot = strtolower( sanitize_text_field( $request->getSlot( 'TermName' ) ) );
					if ( $term_slot ) {
						$news_taxonomies = voicewp_news_taxonomies();

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
					// No break. Logic continues into Latest case
				case 'Latest':
					/* Since the above switch statement doesn't break,
					 * it will continue running into this block,
					 * which allows the below $tax_query var to be set,
					 * so at first glance it may look slightly confusing,
					 * but this keeps the code DRY
					 */

					$args = array(
						'post_type' => voicewp_news_post_types(),
						'posts_per_page' => 5,
					);

					if ( isset( $tax_query ) ) {
						$args['tax_query'] = array( $tax_query );
					}

					$result = $this->endpoint_content( $args );

					$voicewp_settings = get_option( 'voicewp-settings' );
					$skill_name = ( ! empty( $voicewp_settings['skill_name'] ) ) ? $voicewp_settings['skill_name'] : get_bloginfo( 'name' );
					$prompt = ( ! empty( $voicewp_settings['list_prompt'] ) ) ? $voicewp_settings['list_prompt'] : __( 'Which article would you like to hear?', 'voicewp' );

					$response
						->respond( $result['content'] . $prompt )
						/* translators: %s: site title */
						->with_card( sprintf( __( 'Latest from %s', 'voicewp' ), $skill_name ), ( ( ! empty( $result['card_content'] ) ) ? $result['card_content'] : '' ) )
						->add_session_attribute( 'post_ids', $result['ids'] );
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
						if ( ! $post_id ) {
							$this->message( $response, 'number_slot_error', $request );
						} else {
							$result = $this->endpoint_single_post( $post_id );
							$response
								->respond_ssml( $result['content'] )
								->with_card( $result['title'], '', $result['image'] )
								->end_session();
						}
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
		$custom_skill_index = get_option( 'voicewp_skill_index_map', array() );
		if ( isset( $custom_skill_index[ $intent ] ) ) {
			$voicewp = Voicewp::get_instance();
			$voicewp->skill_dispatch( absint( $custom_skill_index[ $intent ] ), $request, $response );
		}
	}

	/**
	 * Gets formatted post data that will be served in the response
	 * @param int $id ID of post to get data for
	 * @return array Data from the post being returned
	 */
	private function endpoint_single_post( $id ) {
		$transient_key = 'voicewp_single_' . $id;
		if ( false === ( $result = get_transient( $transient_key ) ) ) {
			$single_post = get_post( $id );
			$result = $this->format_single_post( $id, $single_post );
			// Set long cache time instead of 0 to prevent autoload
			set_transient( $transient_key, $result, WEEK_IN_SECONDS );
		}
		return $result;
	}

	/**
	 * Packages up the post data that will be served in the response
	 * @param int $id ID of post to get data for
	 * @param Object $single_post Post object
	 * @return array Data from the post being returned
	 */
	public function format_single_post( $id, $single_post ) {
		$voicewp_instance = \Voicewp_Setup::get_instance();
		// Strip shortcodes and markup other than SSML
		$post_content = html_entity_decode( wp_kses( strip_shortcodes( preg_replace(
			array(
				'|^(\s*)(https?://[^\s<>"]+)(\s*)$|im',
				'/<script\b[^>]*>(.*?)<\/script>/is',
			),
			'',
			$single_post->post_content
		) ), $voicewp_instance::$ssml ) );
		// Apply user defined dictionary to content as ssml
		$dictionary = get_option( 'voicewp_user_dictionary', array() );
		if ( ! empty( $dictionary ) ) {
			$dictionary_keys = array_map( function( $key ) {
				return ' ' . $key;
			}, array_keys( $dictionary ) );
			$post_content = str_ireplace( $dictionary_keys, $dictionary, $post_content );
		}
		return array(
			'content' => sprintf( '<speak>%s</speak>', $post_content ),
			'title' => $single_post->post_title,
			'image' => get_post_thumbnail_id( $id ),
		);
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
		if ( ! array_key_exists( $number, $ids ) ) {
			return;
		}
		return absint( $ids[ $number ] );
	}

	/**
	 * Deliver a message to user
	 * @param AlexaResponse $response
	 * @param string $case The type of message to return
	 */
	private function message( $response, $case = 'missing', $request = false ) {
		$voicewp_settings = get_option( 'voicewp-settings' );
		if ( isset( $voicewp_settings[ $case ] ) ) {
			$response->respond( $voicewp_settings[ $case ] );
		} elseif ( 'number_slot_error' == $case ) {
			$response
				->respond( __( 'You can select between one and five, please select an item within that range.', 'voicewp' ) )
				->add_session_attribute( 'post_ids', $request->session->get_attribute( 'post_ids' ) );
		} else {
			$response->respond( __( "Sorry! I couldn't find any news about that topic. Try asking something else!", 'voicewp' ) );
		}
		if ( 'stop_intent' === $case ) {
			$response->end_session();
		}
	}

	/**
	 * Creates output when a user asks for a list of posts.
	 * Delivers an array containing a numbered list of post titles
	 * to choose from and a subarray of IDs that get set in an attribute
	 * @param array $response
	 * @return array array of post IDs and titles
	 */
	public function endpoint_content( $args ) {
		$transient_key = isset( $args['tax_query'][0]['terms'][0] ) ? 'voicewp_latest_' . $args['tax_query'][0]['terms'][0] : 'voicewp_latest';
		if ( false === ( $result = get_transient( $transient_key ) ) ) {
			$news_posts = get_posts( array_merge( $args, array(
				'no_found_rows' => true,
				'post_status' => 'publish',
			) ) );

			$content = $card_content = '';
			$ids = array();
			if ( ! empty( $news_posts ) && ! is_wp_error( $news_posts ) ) {

				foreach ( $news_posts as $key => $news_post ) {
					// Appending 'th' to any number results in proper ordinal pronunciation
					// TODO: Sounds a little strange when there's only one result.
					$content .= ( $key + 1 ) . 'th, ' . $news_post->post_title . '. ';
					$card_content .= ( $key + 1 ) . '. ' . $news_post->post_title . "\n";
					$ids[] = $news_post->ID;
				}
			}

			$result = array(
				'content' => $content,
				'ids' => $ids,
				'card_content' => $card_content,
			);
			/**
			 * If this is the main latest feed, the content will be cleared
			 * when a post is published. We're setting a very long defined cache time
			 * so that if it's on a site without external object cache, it won't be autoloaded.
			 * For taxonomy feeds, cache for 15 minutes
			 */
			$expiration = ( 'voicewp_latest' == $transient_key ) ? WEEK_IN_SECONDS : 15 * MINUTE_IN_SECONDS;

			set_transient( $transient_key, $result, $expiration );
		}
		return $result;
	}
}
