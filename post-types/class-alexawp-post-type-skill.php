<?php
/**
 * Custom post type for Skills.
 */
class Alexawp_Post_Type_Skill extends Alexawp_Post_Type {

	/**
	 * Name of the custom post type.
	 *
	 * @var string
	 */
	public $name = 'alexawp-skill';

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( $this->name, [
			'labels' => array(
				'name'               => __( 'Skills', 'alexawp' ),
				'singular_name'      => __( 'Skill', 'alexawp' ),
				'add_new'            => __( 'Add New Skill', 'alexawp' ),
				'add_new_item'       => __( 'Add New Skill', 'alexawp' ),
				'edit_item'          => __( 'Edit Skill', 'alexawp' ),
				'new_item'           => __( 'New Skill', 'alexawp' ),
				'view_item'          => __( 'View Skill', 'alexawp' ),
				'search_items'       => __( 'Search Skills', 'alexawp' ),
				'not_found'          => __( 'No Skills found', 'alexawp' ),
				'not_found_in_trash' => __( 'No Skills found in Trash', 'alexawp' ),
				'parent_item_colon'  => __( 'Parent Skill:', 'alexawp' ),
				'menu_name'          => __( 'Skills', 'alexawp' ),
			),
			'public' => true,
			'supports' => [ 'title' ],
		] );
	}
}

$post_type_skill = new Alexawp_Post_Type_Skill();
