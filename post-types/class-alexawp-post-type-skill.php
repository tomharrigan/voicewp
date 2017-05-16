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

	function __construct() {
		parent::__construct();

		add_filter( 'save_post', array( $this, 'skill_index' ), 11, 3 );
	}

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( $this->name, array(
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
			'menu_icon' => 'dashicons-awards',
			'public' => true,
			'supports' => array( 'title' ),
		) );
	}

	/**
	 * Handle saving an array of custom intents
	 *
	 * @param int $post_id Post ID
	 * @param object $post Post object
	 * @param bool $update Is post updated or new
	 */
	public function skill_index( $post_id, $post, $update ) {
		if (
			empty( $post_id )
			|| wp_is_post_revision( $post_id )
			|| ( defined( 'WP_IMPORTING' ) && WP_IMPORTING === true )
			|| ( $post->post_type !== $this->name )
		) {
			return;
		}

		$is_standalone = get_post_meta( $post_id, 'alexawp_skill_is_standalone', true );
		$skill_type = get_post_meta( $post_id, 'alexawp_skill_type', true );
		if ( empty( $is_standalone ) && ! empty( $skill_type ) ) {
			$custom_skill_index = get_option( 'alexawp_skill_index_map', array() );
			$stuff = new \Alexa\Skill\Quote;
			foreach ( $stuff->intents as $intent ) {
				$custom_skill_index[ $intent ] = $post_id;
			}
			update_option( 'alexawp_skill_index_map', $custom_skill_index );
		}
	}
}

$post_type_skill = new Alexawp_Post_Type_Skill();
