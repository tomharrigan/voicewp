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
	private $_settings_name = '';

	private $_settings_parent_page = '';

	private $_settings_page_title = '';

	private $_settings = [];

	/**
	 * Setup the class.
	 */
	public function __construct( $settings_name, $settings_parent_page, $settings_page_title, $settings ) {
		$this->_settings_name = $settings_name;
		$this->_settings_parent_page = $settings_parent_page;
		$this->_settings_page_title = $settings_page_title;
		$this->_settings = $settings;

		// Only get the settings once per page.
		$this->_retrieved_settings = get_option( $this->_settings_name );

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'add_settings' ] );
	}

	/**
	 * Add the settings page.
	 */
	public function add_settings_page() {
		add_submenu_page(
			$this->_settings_parent_page,
			$this->_settings_page_title,
			$this->_settings_page_title,
			'manage_options',
			$this->_settings_name,
			function () {
				?>
				<div class="wrap">
					<h2><?php echo esc_html( $this->_settings_page_title ); ?></h2>

					<form method="POST" action="options.php">
						<?php settings_fields( $this->get_settings_group_name() ); ?>
						<?php do_settings_sections( $this->_settings_name ); ?>
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
	public function add_settings() {
		if ( empty( $this->_settings ) ) {
			return;
		}

		register_setting( $this->get_settings_group_name(), $this->_settings_name );

		add_settings_section(
			$this->get_settings_section_name(),
			'',
			'',
			$this->_settings_name
		);

		foreach ( (array) $this->_settings as $name => $setting ) {
			add_settings_field(
				$name,
				$setting['label'],
				function () use ( $name ) {
					$this->render_text_field( $name );
				},
				$this->_settings_name,
				$this->get_settings_section_name()
			);
		}
	}

	public function get_field( $field_name ) {
		return $this->_settings[ $field_name ] ?? null;
	}

	public function get_field_value( $field_name ) {
		return $this->_retrieved_settings[ $field_name ] ?? null;
	}

	public function get_settings_group_name() {
		return $this->_settings_name . '-group';
	}

	public function get_settings_section_name() {
		return $this->_settings_name . '-section';
	}

	public function render_text_field( $field_name ) {
		$field = $this->get_field( $field_name );

		if ( empty( $field ) ) {
			return;
		}

		// Get the field value.
		$value = '';

		// Get the field description.
		$description = '<p class="description">Description</p>';

		return printf(
			'<input type="text" name="%1$s" id="%1$s" value="%2$s" />%3$s',
			esc_attr( $this->_settings_name . '[' . $field_name . ']' ),
			esc_attr( $this->get_field_value( $field_name ) ),
			$description
		);
	}
}
