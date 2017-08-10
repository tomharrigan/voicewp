<?php

namespace Alexa\Skill;

/**
 * Class that creates a Flash Briefing skill
 */
class Briefing {

	/**
	 * Gets and formats the data for a Flash Briefing response.
	 * The response can contain either text or an audio file,
	 * which itself can be from an attachment uploaded to WP,
	 * or an audio file hosted elsewhere
	 *
	 * @return array Response for Flash Briefing
	 */
	public function briefing_request( $briefing_category = null ) {
		/**
		 * Allows briefing content to be overridden for customization purposes.
		 *
		 * @since 1.1.0
		 *
		 * @param array An array of briefing items.
		 */
		$responses = apply_filters( 'voicewp_pre_get_briefing', array() );

		if ( ! empty( $responses ) ) {
			return $responses;
		}

		$args = array(
			'no_found_rows' => true,
			'post_status' => 'publish',
			'post_type' => 'voicewp-briefing',
			'posts_per_page' => 1,
			'suppress_filters' => false,
		);

		if ( ! empty( $briefing_category ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'voicewp-briefing-category',
					'field' => 'term_id',
					'terms' => $briefing_category,
				),
			);
		}

		// This logic could be both abstracted and used with array_map().
		foreach (
			get_posts( $args )
			as $post
		) {
			$response = array(
				'uid' => get_post_meta( $post->ID, 'voicewp_briefing_uuid', true ),
				'updateDate' => get_post_modified_time( 'Y-m-d\TH:i:s.\0\Z', true, $post ),
				'titleText' => $post->post_title,
				'mainText' => '',
				'redirectionUrl' => home_url(),
			);

			switch ( $source = get_post_meta( $post->ID, 'voicewp_briefing_source', true ) ) {
				case 'content' :
					$response['mainText'] = $post->post_content;
				break;

				case 'attachment_id' :
					$response['streamUrl'] = wp_get_attachment_url( get_post_meta( $post->ID, 'voicewp_briefing_attachment_id', true ) );
				break;

				case 'audio_url' :
					$response['streamUrl'] = get_post_meta( $post->ID, 'voicewp_briefing_audio_url', true );
				break;

				default :
					/**
					 * Allows for including custom parameters within flash briefing items
					 *
					 * @since 1.1.0
					 *
					 * @param array $response A single briefing item
					 * @param string $source The type of data populating this feed item
					 * @param int $post->ID ID of post object
					 * @param Object $post Post object
					 */
					$response = apply_filters( 'voicewp_briefing_source', $response, $source, $post->ID, $post );
				break;
			}

			$response['mainText'] = wp_strip_all_tags( strip_shortcodes( $response['mainText'] ) );

			if ( isset( $response['streamUrl'] ) ) {
				$response['streamUrl'] = esc_url_raw( $response['streamUrl'] );
			}

			/**
			 * Allows for filtering a flash briefing item
			 *
			 * @since 1.1.0
			 *
			 * @param array $response A single briefing item
			 * @param int $post->ID ID of post object
			 * @param Object $post Post object
			 */
			$response = apply_filters( 'voicewp_briefing_response', $response, $post->ID, $post );

			$responses[] = $response;
		}

		// Unclear whether an array with a single response is acceptable.
		if ( count( $responses ) === 1 ) {
			$responses = $responses[0];
		}

		return $responses;
	}
}
