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
	 * Hooks on to actions and filters for adding/removing items from an option that
	 * tracks what functionality is in a skill
	 * @param string $label
	 * @param array $options
	 */
	function __construct() {
		parent::__construct();

		add_filter( 'save_post', array( $this, 'save_post' ), 11, 3 );
		add_filter( 'fm_presave_alter_values', array( $this, 'remove_from_skill_index' ), 10, 3 );
		add_filter( 'update_post_metadata', array( $this, 'update_post_metadata' ), 10, 5 );
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
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
	public function save_post( $post_id, $post, $update ) {
		if (
			empty( $post_id )
			|| wp_is_post_revision( $post_id )
			|| ( defined( 'WP_IMPORTING' ) && WP_IMPORTING === true )
			|| ( $post->post_type !== $this->name )
			|| 'publish' !== get_post_status( $post_id )
		) {
			return;
		}

		$is_standalone = get_post_meta( $post_id, 'alexawp_skill_is_standalone', true );
		$skill_type = get_post_meta( $post_id, 'alexawp_skill_type', true );
		if ( empty( $is_standalone ) && ! empty( $skill_type ) ) {
			$old_index = $custom_skill_index = get_option( 'alexawp_skill_index_map', array() );
			$skill = '\Alexa\Skill\\' . $skill_type;
			$skill = new $skill;

			foreach ( $skill->intents as $intent ) {
				$custom_skill_index[ $intent ] = $post_id;
			}
			if ( $old_index != $custom_skill_index ) {
				update_option( 'alexawp_skill_index_map', $custom_skill_index );
			}
		}
	}

	/**
	 * Checks if going from publish to another status
	 * @param string $new_status status post is changing to
	 * @param string $old_status status post was
	 * @param Object $post post object
	 */
	function transition_post_status( $new_status, $old_status, $post ) {
		// if status is going from published to something else
		if ( ( 'publish' !== $new_status ) && ( 'publish' == $old_status ) ) {
			$this->voicewp_remove_from_skill_index( $post->ID );
		}
	}

	/**
	 * If skill type changes, remove the old data from the index
	 * @param null $null
	 * @param string $old_status status post was
	 * @param int $post_id post ID
	 * @param string $meta_key meta key being edited
	 * @param mixed $meta_value meta value being saved
	 * @param mixed $old_status optional meta value to change if multiple keys
	 * @retun null
	 */
	function update_post_metadata( $null, $post_id, $meta_key, $meta_value, $prev_value ) {
		if ( 'alexawp_skill_type' == $meta_key ) {
			$old_value = get_metadata( 'post', $post_id, $meta_key );
			// If theres a single value and the old value is the same as the new, return
			if ( ( count( $old_value ) == 1 ) && ( $old_value[0] === $meta_value ) ) {
				return $null;
			}
			// if the value is different, and the old value wasn't empty, remove the old index
			if ( ! empty( $old_value[0] ) ) {
				$this->voicewp_remove_from_skill_index( $post_id, $old_value[0] );
			}
		}
		return $null;
	}

	/**
	 * If is_standalone changes to true, we need to remove from index
	 * @param array $values meta value being saved
	 * @param object $fm_object Fieldmanager object
	 * @param array $current_values meta value previously saved
	 * @return array value to save
	 */
	function remove_from_skill_index( $values, $fm_object, $current_values ) {
		if ( isset( $fm_object->data_id ) && isset( $fm_object->name ) && 'is_standalone' === $fm_object->name ) {
			// If was standalone and is now set to not be standalone
			if ( false == $current_values[0] && $values[0] !== $current_values[0] ) {
				$this->voicewp_remove_from_skill_index( $fm_object->data_id );
			}
		}
		return $values;
	}

	/**
	 * Helper function that removes intents, which are the array keys
	 * and ID's, which are the array values, from the index
	 * @param int $post_id Post ID
	 * @param string|null $skill_type type of skill being removed
	 */
	function voicewp_remove_from_skill_index( $post_id, $skill_type = null ) {
		$skill_type = ( $skill_type ) ? $skill_type : get_post_meta( $post_id, 'alexawp_skill_type', true );
		$old_index = $custom_skill_index = get_option( 'alexawp_skill_index_map', array() );
		$skill = '\Alexa\Skill\\' . $skill_type;
		$skill = new $skill;
		foreach ( $skill->intents as $intent ) {
			if ( $post_id == $custom_skill_index[ $intent ] ) {
				unset( $custom_skill_index[ $intent ] );
			}
		}
		if ( $old_index != $custom_skill_index ) {
			update_option( 'alexawp_skill_index_map', $custom_skill_index );
		}
	}
}

$post_type_skill = new Alexawp_Post_Type_Skill();
