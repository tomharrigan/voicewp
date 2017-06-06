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
	public function briefing_request() {
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

		// This logic could be both abstracted and used with array_map().
		foreach (
			get_posts( array(
				'meta_query' => array(
					array(
						'key' => 'voicewp_briefing_source',
						'compare' => '!=',
						'value' => '',
					),
				),
				'no_found_rows' => true,
				'post_status' => 'publish',
				'post_type' => 'voicewp-briefing',
				'posts_per_page' => 1,
				'suppress_filters' => false,
			) )
			as $post
		) {
			$response = array(
				'uid' => get_post_meta( $post->ID, 'voicewp_briefing_uuid', true ),
				'updateDate' => get_post_modified_time( 'Y-m-d\TH:i:s.\0\Z', true, $post ),
				'titleText' => get_the_title( $post ),
				'mainText' => '',
				'redirectionUrl' => home_url(),
			);

			switch ( get_post_meta( $post->ID, 'voicewp_briefing_source', true ) ) {
				case 'content' :
					$response['mainText'] = $post->post_content;
				break;

				case 'attachment_id' :
					$response['streamUrl'] = wp_get_attachment_url( get_post_meta( $post->ID, 'voicewp_briefing_attachment_id', true ) );
				break;

				case 'audio_url' :
					$response['streamUrl'] = get_post_meta( $post->ID, 'voicewp_briefing_audio_url', true );
				break;
			}

			$response['mainText'] = wp_strip_all_tags( strip_shortcodes( $response['mainText'] ) );

			if ( isset( $response['streamUrl'] ) ) {
				$response['streamUrl'] = esc_url_raw( $response['streamUrl'] );
			}

			$responses[] = $response;
		}

		// Unclear whether an array with a single response is acceptable.
		if ( count( $responses ) === 1 ) {
			$responses = $responses[0];
		}

		return $responses;
	}
}
