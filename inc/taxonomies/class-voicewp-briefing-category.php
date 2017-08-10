<?php

/**
 * Taxonomy for Flash Briefing categories.
 */
class VoiceWP_Taxonomy_Briefing_Category extends VoiceWP_Taxonomy {

	/**
	 * Name of the taxonomy.
	 *
	 * @var string
	 */
	public $name = 'voicewp-briefing-category';

	/**
	 * Object types for this taxonomy
	 *
	 * @var array
	 */
	public $object_types;


	/**
	 * Build the taxonomy object.
	 */
	public function __construct() {
		$this->object_types = array( 'voicewp-briefing' );

		parent::__construct();
	}

	/**
	 * Creates the taxonomy.
	 */
	public function create_taxonomy() {
		register_taxonomy( $this->name, $this->object_types, array(
			'labels' => array(
				'name'                  => __( 'Briefing Categories', 'people' ),
				'singular_name'         => __( 'Briefing Category', 'people' ),
				'search_items'          => __( 'Search Briefing Categories', 'people' ),
				'popular_items'         => __( 'Popular Briefing Categories', 'people' ),
				'all_items'             => __( 'All Briefing Categories', 'people' ),
				'parent_item'           => __( 'Parent Briefing Category', 'people' ),
				'parent_item_colon'     => __( 'Parent Briefing Category', 'people' ),
				'edit_item'             => __( 'Edit Briefing Category', 'people' ),
				'view_item'             => __( 'View Briefing Category', 'people' ),
				'update_item'           => __( 'Update Briefing Category', 'people' ),
				'add_new_item'          => __( 'Add New Briefing Category', 'people' ),
				'new_item_name'         => __( 'New Briefing Category Name', 'people' ),
				'add_or_remove_items'   => __( 'Add or remove Briefing Categories', 'people' ),
				'choose_from_most_used' => __( 'Choose from most used Briefing Categories', 'people' ),
				'menu_name'             => __( 'Briefing Categories', 'people' ),
			),
			'rewrite' => false,
			'public' => false,
			'show_ui' => true,
			'show_tagcloud' => false,
			'hierarchical' => true,
		) );
	}
}

$taxonomy_briefing_category = new VoiceWP_Taxonomy_Briefing_Category();
