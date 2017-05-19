<?php

namespace Alexa\Response;

/**
 * StandardCard Class
 * Handles creating and returning a Standard app card.
 * Standard app cards can contain an image, which differentiates them from Simple cards.
 */
class StandardCard {
	/**
	 * Type of card
	 * @access public
	 * @var string
	 */
	public $type = 'Standard';
	/**
	 * Title of card
	 * @access public
	 * @var string
	 */
	public $title = '';
	/**
	 * Content of card
	 * @access public
	 * @var string
	 */
	public $content = '';
	/**
	 * Holds the card images
	 * @access public
	 * @var null
	 */
	public $image = null;

	/**
	 * Constructor. Sets up the Standard card.
	 * @param string $title title of card
	 * @param string $content content of card
	 * @param int $image ID of image for card
	 * @access public
	 */
	public function __construct( $title, $content, $image ) {
		$this->title = $title;
		$this->content = $content;
		if ( false === ( $images = get_transient( 'voicewp_card_image_' . $image ) ) ) {
			$images = array(
				'smallImageUrl' => wp_get_attachment_image_src( absint( $image ), 'alexa-small' )[0],
				'largeImageUrl' => wp_get_attachment_image_src( absint( $image ), 'alexa-large' )[0],
			);
			set_transient( 'voicewp_card_image_' . $image, $images, 60 * MINUTE_IN_SECONDS );
		}
		$this->image = $images;
	}

	/**
	 * Sends the properties of the card for rendering
	 * @return array
	 */
	public function render() {
		return array(
			'type' => $this->type,
			'title' => $this->title,
			'content' => $this->content,
			'image' => $this->image,
		);
	}
}
