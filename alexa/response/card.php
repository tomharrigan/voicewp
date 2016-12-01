<?php

namespace Alexa\Response;

class Card {
	public $type = 'Simple';
	public $title = '';
	public $content = '';
	public $image = null;

	public function render() {
		$card = array(
			'type' => $this->type,
			'title' => $this->title,
			'content' => $this->content,
		);
		if ( $this->image ) {
			$card['image'] = $this->image;
		}
		return $card;
	}
}
