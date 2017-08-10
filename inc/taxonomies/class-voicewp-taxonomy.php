<?php

/**
 * Abstract class for VoiceWP taxonomy classes
 */
abstract class VoiceWP_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * Object types for this taxonomy
	 *
	 * @var array
	 */
	public $object_types = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Create the taxonomy
		add_action( 'init', array( $this, 'create_taxonomy' ) );
	}

	/**
	 * Create the taxonomy.
	 */
	abstract public function create_taxonomy();

}
