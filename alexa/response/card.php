<?php

namespace Alexa\Response;

/**
 * Card Class
 * Handles creating and returning a Simple app card.
 * Simple app cards contain only title and text
 */
class Card {
	/**
	 * Type of card
	 * @access public
	 * @var string
	 */
	public $type = 'Simple';
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
	 * Constructor. Sets up the Simple card.
	 * @param string $title title of card
	 * @param string $content content of card
	 * @access public
	 */
	public function __construct( $title, $content ) {
		$this->title = $title;
		$this->content = $content;
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
		);
	}
}
