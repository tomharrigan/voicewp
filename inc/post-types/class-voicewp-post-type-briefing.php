<?php
/**
 * Custom post type for Skills.
 */
class Voicewp_Post_Type_Briefing extends Voicewp_Post_Type {

	/**
	 * Name of the custom post type.
	 *
	 * @var string
	 */
	public $name = 'voicewp-briefing';

	function __construct() {
		parent::__construct();

		add_action( 'save_post', array( $this, 'set_cache' ), 11, 3 );
	}

	/**
	 * Creates the post type.
	 */
	public function create_post_type() {
		register_post_type( $this->name, array(
			'labels' => array(
				'name'               => __( 'Briefings', 'voicewp' ),
				'singular_name'      => __( 'Briefing', 'voicewp' ),
				'add_new'            => __( 'Add New Briefing', 'voicewp' ),
				'add_new_item'       => __( 'Add New Briefing', 'voicewp' ),
				'edit_item'          => __( 'Edit Briefing', 'voicewp' ),
				'new_item'           => __( 'New Briefing', 'voicewp' ),
				'view_item'          => __( 'View Briefing', 'voicewp' ),
				'search_items'       => __( 'Search Briefing', 'voicewp' ),
				'not_found'          => __( 'No Briefings found', 'voicewp' ),
				'not_found_in_trash' => __( 'No Briefings found in Trash', 'voicewp' ),
				'parent_item_colon'  => __( 'Parent Briefing:', 'voicewp' ),
				'menu_name'          => __( 'Briefings', 'voicewp' ),
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
	public function set_cache( $post_id, $post, $update ) {
		// don't cache if this is a revision or an import
		if (
			empty( $post_id )
			|| wp_is_post_revision( $post_id )
			|| ( $post->post_type !== $this->name )
			|| 'publish' !== get_post_status( $post_id )
		) {
			return;
		}

		$briefing = new \Alexa\Skill\Briefing();
		// Set long cache time instead of 0 to prevent autoload
		$briefing_response = $briefing->briefing_request();
		set_transient( $this->name, $briefing_response, WEEK_IN_SECONDS );

		$briefing_categories = get_the_terms( $post_id, 'voicewp-briefing-category' );
		if ( ! empty( $briefing_categories ) && ! is_wp_error( $briefing_categories ) ) {
			foreach ( $briefing_categories as $briefing_category ) {
				set_transient( $this->name . '-' . $briefing_category->term_id, $briefing_response, WEEK_IN_SECONDS );
			}
		}
	}
}

$post_type_briefing = new Voicewp_Post_Type_Briefing();
