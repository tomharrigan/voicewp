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
	 * The setting type.
	 *
	 * @var string
	 */
	private $_type = '';

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
	 * @param string $type   The settings type.
	 * @param string $name   The settings name.
	 * @param string $title  The settings title.
	 * @param array  $fields The settings fields.
	 * @param array  $args   The settings args.
	 */
	public function __construct( $type, $name, $title, $fields, $args = array() ) {
		$this->_type   = $type;
		$this->_name   = $name;
		$this->_title  = $title;
		$this->_fields = $fields;
		$this->_args   = $args;

		// Prime the cache.
		$this->get_data();

		if ( 'options' === $this->_type ) {
			add_action( 'admin_menu', array( $this, 'add_options_page' ) );
			add_action( 'admin_init', array( $this, 'add_options_fields' ) );
		}
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

		$this->add_options_field( $this->_name, $this->_fields );
	}

	/**
	 * Add an options field.
	 *
	 * @param string $option_name The option name.
	 * @param array  $fields      The array of fields.
	 */
	public function add_options_field( $option_name, $fields ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return;
		}

		foreach ( $fields as $name => $field ) {
			// Ensure we have the default strucuture.
			$field = wp_parse_args( $field, array(
				'name' => $name,
				'type' => 'text',
			) );

			// Group field.
			if (
				'group' === $field['type']
				&& ! empty( $field['children'] )
				&& is_array( $field['children'] )
			) {
				$this->add_options_field( $this->get_field_name( $option_name, $field ), $field['children'] );
				continue;
			}

			add_settings_field(
				$name,
				$field['label'] ?? '',
				function () use ( $option_name, $field ) {
					$this->render_field( $this->get_field_name( $option_name, $field ), $field );
				},
				$this->_name,
				$this->get_options_section_name()
			);
		}
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

		// Render the correct field type.
		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea name="%1$s" id="%1$s" rows="5" cols="20" %3$s>%2$s</textarea>',
					esc_attr( $name ),
					esc_html( $this->get_field_value( $field ) ),
					! empty( $field['attributes'] ) ? $this->add_attributes( $field['attributes'] ) : '' // Escaped internally.
				); // WPCS XSS okay.
				break;
			case 'text':
			default:
				printf(
					'<input type="text" name="%1$s" id="%1$s" value="%2$s" %3$s />',
					esc_attr( $name ),
					esc_attr( $this->get_field_value( $field ) ),
					! empty( $field['attributes'] ) ? $this->add_attributes( $field['attributes'] ) : '' // Escaped internally.
				); // WPCS XSS okay.
				break;
		}

		// The field description.
		if ( ! empty( $field['description'] ) ) {
			printf(
				'<p class="description">%1$s</p>',
				esc_html( $field['description'] )
			);
		}
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
			switch ( $this->_type ) {
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
	 * @param array $field The field.
	 * @return mixed The field value.
	 */
	public function get_field_value( $field ) {
		// No name.
		if ( empty( $field['name'] ) ) {
			return null;
		}

		$value = $this->_retrieved_data[ $field['name'] ] ?? null;

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
