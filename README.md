# VoiceWP

VoiceWP is a WordPress plugin that integrates with Amazon Alexa to create and enable the creation of Alexa skills.

It allows you to use WordPress as an endpoint.

[Fieldmanager](http://fieldmanager.org) is a dependency and must be installed and activated for VoiceWP to function fully as intended.

## Installation

- The minimum required PHP version for VoiceWP is PHP 5.3.

- The minimum required WordPress version for VoiceWP is WordPress Version 4.4.

- The [Fieldmanager](http://fieldmanager.org) plugin is required for VoiceWP to function properly. It is used for custom field, meta, and settings screens.

- An SSL certificate is required on your site. You can use [Let's Encrypt](https://letsencrypt.org/).

- Download the .zip file of this repo and upload to your WordPress site by navigating to WP Admin and navigating to **Plugins -> Add New**. Select the 'Upload Plugin' button near the top of the top of the screen to upload the .zip file.

### Flash Briefing Skill

A Briefings post type is created which is intended to be used for a Flash Briefing skill.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/briefing`

### Fact/Quote Skills

A Skills post type is created for generic skill creation. Out of the box Fact/Quote skills can be created.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/(post_id)`

### News from your posts Skill

This news/content skill will currently read the 5 latest headlines from your regular WordPress posts and allows the listener to choose a post to be read in full.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/news`

## Credit

See credits.txt