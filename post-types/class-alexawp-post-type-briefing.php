<?php
/**
 * Custom post type for Skills.
 */
class Alexawp_Post_Type_Briefing extends Alexawp_Post_Type {

	/**
	 * Name of the custom post type.
	 *
	 * @var string
	 */
	public $name = 'alexawp-briefing';

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( $this->name, [
			'labels' => array(
				'name'               => __( 'Briefings', 'alexawp' ),
				'singular_name'      => __( 'Briefing', 'alexawp' ),
				'add_new'            => __( 'Add New Briefing', 'alexawp' ),
				'add_new_item'       => __( 'Add New Briefing', 'alexawp' ),
				'edit_item'          => __( 'Edit Briefing', 'alexawp' ),
				'new_item'           => __( 'New Briefing', 'alexawp' ),
				'view_item'          => __( 'View Briefing', 'alexawp' ),
				'search_items'       => __( 'Search Briefing', 'alexawp' ),
				'not_found'          => __( 'No Briefings found', 'alexawp' ),
				'not_found_in_trash' => __( 'No Briefings found in Trash', 'alexawp' ),
				'parent_item_colon'  => __( 'Parent Briefing:', 'alexawp' ),
				'menu_name'          => __( 'Briefings', 'alexawp' ),
			),
			'public' => false,
			'show_in_menu' => true,
			'show_ui' => true,
			'supports' => [ 'title' ],
		] );
	}
}

$post_type_briefing = new Alexawp_Post_Type_Briefing();
