# VoiceWP

WordPress plugin for creating Alexa skills using your WP site.

[![Gitter chat](https://badges.gitter.im/gitterHQ/gitter.png)](https://gitter.im/voicewp/Lobby)

## How it Works

VoiceWP creates REST endpoints using the WordPress REST API. These endpoints are used by Alexa to take in Requests and deliver Responses for your skill.

## Pre-requisites

- [Fieldmanager](http://fieldmanager.org) is a required plugin for VoiceWP to function properly. It is used for custom field, meta, and settings screens.

- An SSL certificate is required on your site.

- Minimum required PHP version for VoiceWP is PHP 5.3.

- Minimum required WordPress version for VoiceWP is WordPress Version 4.4.

## Installation

- Install and activate [Fieldmanager](https://github.com/alleyinteractive/wordpress-fieldmanager/archive/1.0.0.zip)

- Download the .zip file of this repo and upload to your WordPress site by navigating to WP Admin and navigating to **Plugins -> Add New**. Select the 'Upload Plugin' button near the top of the top of the screen to upload the .zip file.

### Flash Briefing Skill

A Briefings post type is created which is intended to be used for a Flash Briefing skill.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/briefing`

### Fact/Quote Skills

A Skills post type is created for generic skill creation. Out of the box, Fact/Quote skills can be created.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/(post_id)`

### News from your posts Skill

This news/content skill will currently read the 5 latest headlines from your regular WordPress posts and allows the listener to choose a post to be read in full.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/news`

## Contribute

All development of VoiceWP happens transparently on Github. [Github Issues](https://github.com/alleyinteractive/voicewp/issues) are used for identifying and discussing bugs and features. Code contributions, whether fixes or enhancements, should be submitted as Pull Requests.

The VoiceWP project has a [Gitter](https://gitter.im/voicewp/Lobby) for general discussions or questions.

## Credit

See [credits.txt]