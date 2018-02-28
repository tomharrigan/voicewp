<?php
/**
 * This file contains logice related to creating the custom submenu settings page.
 *
 * @package VoiceWP
 */

namespace VoiceWP;

/**
 * Settings class use to create a new settings page.
 */
class Settings {
	/**
	 * The setting context.
	 *
	 * @var string
	 */
	private $_context = '';

	/**
	 * The setting name.
	 *
	 * @var string
	 */
	private $_name = '';

	/**
	 * The setting title.
	 *
	 * @var string
	 */
	private $_title = '';

	/**
	 * The setting fields.
	 *
	 * @var array
	 */
	private $_fields = array();

	/**
	 * The setting args.
	 *
	 * @var string
	 */
	private $_args = '';

	/**
	 * Cached data if the field value.
	 *
	 * @var mixed
	 */
	private $_retrieved_data = null;

	/**
	 * Setup the class.
	 *
	 * @param string $context The settings type.
	 * @param string $name    The settings name.
	 * @param string $title   The settings title.
	 * @param array  $fields  The settings fields.
	 * @param array  $args    The settings args.
	 */
	public function __construct( $context, $name, $title, $fields, $args = array() ) {
		$this->_context = $context;
		$this->_name    = $name;
		$this->_title   = $title;
		$this->_fields  = $this->sanitize_fields( $fields );
		$this->_args    = $args;

		// Prime the cache.
		$this->get_data();

		// Add scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		if ( 'options' === $this->_context ) {
			add_action( 'admin_menu', array( $this, 'add_options_page' ) );
			add_action( 'admin_init', array( $this, 'add_options_fields' ) );
		}
	}

	/**
	 * Add the scripts.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'voicewp-settings-css', VOICEWP_URL . '/client/css/admin/settings.css' );
		wp_enqueue_script( 'voicewp-settings-js', VOICEWP_URL . '/client/js/admin/settings.js', [ 'jquery' ] );
	}

	/**
	 * Add the settings page.
	 */
	public function add_options_page() {
		// No parent page.
		if ( empty( $this->_args['parent_page'] ) ) {
			return;
		}

		add_submenu_page(
			$this->_args['parent_page'],
			$this->_title,
			$this->_title,
			'manage_options',
			$this->_name,
			function () {
				?>
				<div class="wrap">
					<h2><?php echo esc_html( $this->_title ); ?></h2>

					<form method="POST" action="options.php">
						<?php settings_fields( $this->get_options_group_name() ); ?>
						<?php do_settings_sections( $this->_name ); ?>
						<?php submit_button(); ?>
					</form>
				</div>
				<?php
			}
		);
	}

	/**
	 * Add the settings to the page.
	 */
	public function add_options_fields() {
		if ( empty( $this->_fields ) ) {
			return;
		}

		register_setting( $this->get_options_group_name(), $this->_name );

		add_settings_section(
			$this->get_options_section_name(),
			'',
			'',
			$this->_name
		);

		$this->add_options_field( $this->_name, $this->_fields, false, $this->get_data() );
	}

	/**
	 * Add an options field.
	 *
	 * @param string $option_name   The option name.
	 * @param array  $fields        The array of fields.
	 * @param bool   $is_group      Whether or not this is a group.
	 * @param mixed  $current_value The current value of the field.
	 */
	public function add_options_field( $option_name, $fields, $is_group = false, $current_value = null ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return;
		}

		foreach ( $fields as $name => $field ) {

			// Group field.
			if (
				'group' === $field['type']
				&& ! empty( $field['children'] )
				&& is_array( $field['children'] )
			) {
				$full_option_name = $this->get_field_name( $option_name, $field );

				add_settings_section(
					$full_option_name . '-section',
					$field['label'] ?? '',
					function () use ( $full_option_name, $field ) {
						// Add description.
						if ( ! empty( $field['description'] ) ) {
							echo '<p>' . esc_html( $field['description'] ) . '</p>';
						}
					},
					$this->_name
				);

				// Only add the children as separate fields if this group is a not
				// repeater group.
				if ( 0 === $field['limit'] || 1 < $field['limit'] ) {
					add_settings_field(
						$name,
						$field['label'] ?? '',
						function () use ( $full_option_name, $field, $current_value ) {
							$this->render_group( $full_option_name, $field, $current_value );
						},
						$this->_name,
						$full_option_name . '-section'
					);
				} else {
					$this->add_options_field( $full_option_name, $field['children'], true, $this->get_field_value( $field, $current_value ) );
				}

				continue;
			}

			add_settings_field(
				$name,
				$field['label'] ?? '',
				function () use ( $option_name, $field ) {
					$this->render_field( $this->get_field_name( $option_name, $field ), $field );
				},
				$this->_name,
				$is_group ? $option_name . '-section' : $this->get_options_section_name()
			);
		}
	}

	/**
	 * Santizie the fields.
	 *
	 * @param array $fields The current fields.
	 * @return array The sanitized fields.
	 */
	public function sanitize_fields( array $fields ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return [];
		}

		foreach ( $fields as $name => &$field ) {
			$field = $this->sanitize_field( $name, $field );

			if (
				'group' === $field['type']
				&& ! empty( $field['children'] )
				&& is_array( $field['children'] )
			) {
				$field['children'] = $this->sanitize_fields( $field['children'] );
			}
		}

		return $fields;
	}

	/**
	 * Sanitize a field to ensure it has a certain shape.
	 *
	 * @param  string $name  The field name.
	 * @param  array  $field The field array.
	 * @return array         The sanitized field array.
	 */
	public function sanitize_field( string $name, array $field ) : array {
		$field = wp_parse_args( $field, array(
			'name'           => $name,
			'type'           => 'text',
			'limit'          => 1,
			'add_more_label' => ( isset( $field['type'] ) && 'group' === $field['type'] ) ? __( 'Add group', 'voicewp' ) : __( 'Add field', 'voicewp' ),
			'is_group'       => false,
		) );

		if ( 'group' === $field['type'] ) {
			$field['is_group'] = true;
		}

		return $field;
	}

	/**
	 * Renders the field group.
	 *
	 * @param string $name        The field name.
	 * @param string $group       The field to be rendered.
	 * @param array  $group_value The current group value.
	 */
	public function render_group( $name, $group, $group_value ) {
		if ( empty( $group['children'] ) || ! is_array( $group['children'] ) ) {
			return '';
		}

		// Get the current group value.
		$group_value = $this->get_field_value( $group, $group_value );

		$repeater = ( 0 === $group['limit'] || 1 < $group['limit'] );

		// Check if this is a repeater field.
		if ( $repeater ) {
			echo '<div class="voicewpjs-repeating-group" data-repeater-name="' . esc_attr( $name ) . '[voicewp-index]">';

			if ( ! empty( $group_value ) && is_array( $group_value ) ) {
				foreach ( $group_value as $index => $value ) {
					$this->render_group_children( $name . "[{$index}]", $group, $value );
				}
			} else {
				$this->render_group_children( $name . '[0]', $group, $group_value );
			}

			echo $this->add_another( $name, $group ) . '</div>'; // WPCS: XSS okay.
		} else {
			$this->render_group_children( $name, $group, $group_value );
		}
	}

	/**
	 * Render the children fields in a group.
	 *
	 * @param string $name The field name.
	 * @param array  $group The group.
	 * @param array  $value The group value.
	 */
	public function render_group_children( $name, $group, $value ) {
		ob_start();
		foreach ( $group['children'] as $child ) {
			if ( $child['is_group'] ) {
				$this->render_group( $name, $child, $value );
				continue;
			}

			$child['value'] = $this->get_field_value( $child, $value );

			$this->render_field( $this->get_field_name( $name, $child ), $child );
		}

		if ( 0 === $group['limit'] || 1 < $group['limit'] ) {
			$repeater_html = $this->wrap_with_multi_tools( $group, ob_get_clean() );
		}

		echo '<div class="voicewp-wrapper">';
		echo $repeater_html; // WPCS: XSS okay.
		echo '</div>';
	}

	/**
	 * Renders the field.
	 *
	 * @param string $name  The field name.
	 * @param string $field The field to be rendered.
	 */
	public function render_field( $name, $field ) {
		if ( empty( $field ) ) {
			return;
		}

		if ( ! isset( $field['value'] ) ) {
			$field_value = $this->get_field_value( $field );
		} else {
			$field_value = $field['value'];
		}

		// Get the base name.
		if ( 0 === $field['limit'] || 1 < $field['limit'] ) {
			$base_name = '';
		} else {
			$base_name = $field['name'];
		}

		$field_html = '';

		// Render the correct field type.
		switch ( $field['type'] ) {
			case 'checkboxes':
				if ( empty( $field['options'] ) ) {
					break;
				}

				foreach ( $field['options'] as $value => $label ) {
					$field_html .= sprintf(
						'<p><input type="checkbox" name="%1$s[]" data-base-name="%2$s" class="voicewp-item" value="%3$s" %4$s %5$s /><label>%6$s</label></p>',
						esc_attr( $name ),
						esc_attr( $base_name ),
						esc_attr( $value ),
						! empty( $field_value ) && in_array( $value, $field_value, true ) ? 'checked="checked"' : '',
						! empty( $field['attributes'] ) ? $this->add_attributes( $field['attributes'] ) : '', // Escaped internally.
						esc_html( $label )
					); // WPCS XSS okay.
				}
				break;
			case 'textarea':
				$field_html .= sprintf(
					'<textarea name="%1$s" id="%1$s" data-base-name="%2$s" class="voicewp-item" rows="5" cols="20" %4$s>%3$s</textarea>',
					esc_attr( $name ),
					esc_attr( $base_name ),
					esc_html( $field_value ),
					! empty( $field['attributes'] ) ? $this->add_attributes( $field['attributes'] ) : '' // Escaped internally.
				); // WPCS XSS okay.
				break;
			case 'text':
			default:
				$field_html .= sprintf(
					'<input type="text" name="%1$s" data-base-name="%2$s" id="%1$s" class="voicewp-item" value="%3$s" %4$s />',
					esc_attr( $name ),
					esc_attr( $base_name ),
					esc_attr( $field_value ),
					! empty( $field['attributes'] ) ? $this->add_attributes( $field['attributes'] ) : '' // Escaped internally.
				); // WPCS XSS okay.

				break;
		}

		// The field description.
		if ( ! empty( $field['description'] ) ) {
			$field_html .= sprintf(
				'<p class="description">%1$s</p>',
				esc_html( $field['description'] )
			);
		}

		// Wrap the field with tools as needed.
		if ( 0 === $field['limit'] || 1 < $field['limit'] ) {
			$field_html = $this->wrap_with_multi_tools( $field, $field_html );
		}

		echo $field_html; // WPCS XSS okay.
	}

	/**
	 * Wrap a chunk of HTML with "remove" and "move" buttons if applicable.
	 *
	 * @param  array  $field   The current field.
	 * @param  string $html    HTML to wrap.
	 * @param  array  $classes An array of classes.
	 * @return string Wrapped HTML.
	 */
	public function wrap_with_multi_tools( $field, $html, $classes = array() ) {
		$classes[] = 'voicewpjs-removable';
		$out = sprintf( '<div class="%s">', implode( ' ', $classes ) );

		$out .= '<div class="voicewpjs-removable-element">';
		$out .= $html;
		$out .= '</div>';

		if ( 0 === $field['limit'] ) {
			$out .= $this->get_remove_handle();
		}

		$out .= '</div>';
		return $out;
	}

	/**
	 * Return HTML for the remove handle (multi-tools); a separate function to override.
	 *
	 * @return string
	 */
	public function get_remove_handle() {
		return sprintf( '<a href="#" class="voicewpjs-remove" title="%1$s"><span class="screen-reader-text">%1$s</span></a>', esc_attr__( 'Remove', 'voicewp' ) );
	}

	/**
	 * Generates HTML for the "Add Another" button.
	 *
	 * @param string $name  The field name.
	 * @param array  $field The field.
	 * @return string Button HTML.
	 */
	public function add_another( $name, $field ) {
		$classes = array( 'voicewp-add-another', 'voicewp-' . $name . '-add-another', 'button-secondary' );
		if ( empty( $field['add_more_label'] ) ) {
			$field['add_more_label'] = $field['is_group'] ? __( 'Add group', 'voicewp' ) : __( 'Add field', 'voicewp' );
		}

		$out = '<div class="voicewp-add-another-wrapper">';
		$out .= sprintf(
			'<input type="button" class="%s" value="%s" name="%s" data-related-element="%s" data-limit="%d" />',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $field['add_more_label'] ),
			esc_attr( 'fm_add_another_' . $name ),
			esc_attr( $name ),
			intval( $field['limit'] )
		);

		$out .= '</div>';
		return $out;
	}

	/**
	 * Get all of the field attributes.
	 *
	 * @param array $attributes The field attributes.
	 * @return string $html The field attributes HTML.
	 */
	public function add_attributes( $attributes ) {
		if ( empty( $attributes ) ) {
			return '';
		}

		$html = '';

		foreach ( (array) $attributes as $key => $content ) {
			$html .= esc_attr( $key ) . '="' . esc_attr( $content ) . '" ';
		}

		return $html;
	}

	/**
	 * Get all fields.
	 *
	 * @return array The field array.
	 */
	public function get_fields() {
		return $this->_fields;
	}

	/**
	 * Get the entire field data.
	 *
	 * @return mixed The field data.
	 */
	public function get_data() {
		if ( null === $this->_retrieved_data ) {
			switch ( $this->_context ) {
				case 'options':
					$this->_retrieved_data = get_option( $this->_name );
					break;
			}
		}
	}

	/**
	 * Get the field name.
	 *
	 * @param string $name  The field name.
	 * @param array  $field The field.
	 * @return mixed The field name.
	 */
	public function get_field_name( $name, $field ) {
		// No name.
		if ( empty( $name ) && empty( $field['name'] ) ) {
			return null;
		}

		return $name . "[{$field['name']}]";
	}

	/**
	 * Get the field value.
	 *
	 * @param array $field       The field.
	 * @param array $search_data The data to search through.
	 * @return mixed The field value.
	 */
	public function get_field_value( $field, $search_data = null ) {
		// No name.
		if ( empty( $field['name'] ) ) {
			return null;
		}

		if ( null === $search_data ) {
			$search_data = $this->_retrieved_data;
		}

		$value = $search_data[ $field['name'] ] ?? null;

		// If empty use the default value.
		if ( empty( $value ) && ! empty( $field['default_value'] ) ) {
			$value = $field['default_value'];
		}

		return $value;
	}

	/**
	 * Get the field group name used when registering the settings.
	 *
	 * @return string The field group name.
	 */
	public function get_options_group_name() {
		return $this->_name . '-group';
	}

	/**
	 * Get the field section name used when registering the settings.
	 *
	 * @return string The field section name.
	 */
	public function get_options_section_name() {
		return $this->_name . '-section';
	}
}
