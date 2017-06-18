<?php
/**
 * Class file for VoiceWP_Fieldmanager_Content_TextArea.
 *
 * @package VoiceWP
 */

/**
 * A Fieldmanager_TextArea masquerading as the post content field.
 */
class VoiceWP_Fieldmanager_Content_TextArea extends \Fieldmanager_TextArea {
	/**
	 * Set up.
	 *
	 * @param string $label   Field label.
	 * @param array  $options Field options.
	 */
	public function __construct( $label = '', $options = array() ) {
		parent::__construct( $label, $options );
		$this->name = 'content';
		$this->skip_save = true;
	}

	/**
	 * Use the field's $name property as its "name" form attribute, bypassing the tree.
	 *
	 * @param string $multiple Unused.
	 * @return string
	 */
	public function get_form_name( $multiple = '' ) {
		return $this->name;
	}

	/**
	 * Content form element.
	 *
	 * @param  mixed $value Unused.
	 * @return string       HTML
	 */
	public function form_element( $value = '' ) {
		return parent::form_element( get_post()->post_content );
	}
}
