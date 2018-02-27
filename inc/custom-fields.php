<?php

add_action( 'admin_menu', function() {
	add_submenu_page(
		'tools.php',
		__( 'Voice WP', 'voicewp' ),
		__( 'Voice WP', 'voicewp' ),
		'manage_options',
		'voicewp-settings-new',
		function () {
			?>
			<div class="wrap">
				<h2>Voice WP Settings</h2>

				<form method="post" action="options.php">
					<?php settings_fields( 'voicewp-settings-group' ); ?>
					<?php do_settings_sections( 'voicewp-settings-group' ); ?>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}
	);
} );

add_action( 'admin_init', function () {
	// Add the section to reading settings so we can add our
	// fields to it
	add_settings_section(
		'voicewp-settings-section',
		'Example settings section in reading',
		function () {
			return 'Section';
		},
		'voicewp-settings-group'
	);

	// Add the field with the names and function to use for our new
	// settings, put it in our new section
	add_settings_field(
		'eg_setting_name',
		'Example setting Name',
		function () {
			return 'Setting';
		},
		'voicewp-settings-group',
		'voicewp-settings-section'
	);

	register_setting(
		'voicewp-settings-group', 'voicewp_test', array(
			'type'         => 'string',
			'description'  => __( 'Site title.' ),
		)
	);
} );
