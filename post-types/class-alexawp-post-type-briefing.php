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

	function __construct() {
		parent::__construct();

		add_action( 'save_post_' . $this->name, array( $this, 'set_cache' ) );
	}

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( $this->name, array(
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
			'menu_icon' => 'dashicons-microphone',
			'public' => false,
			'publicly_queryable' => true,
			'show_in_menu' => true,
			'show_ui' => true,
			'supports' => array( 'title' ),
		) );
	}

	/**
	 * Cache the briefing
	 * @param $post_id ID of current post
	 */
	public function set_cache( $post_id ) {
		// don't cache if this is a revision or an import
		if (
			empty( $post_id )
			|| wp_is_post_revision( $post_id )
			|| ( defined( 'WP_IMPORTING' ) && WP_IMPORTING === true )
		) {
			return;
		}

		$briefing = new \Alexa\Skill\Briefing();
		set_transient( $this->name, $briefing->briefing_request() );
	}
}

$post_type_briefing = new Alexawp_Post_Type_Briefing();
