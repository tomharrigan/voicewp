# VoiceWP

Create Alexa Skills through WordPress

[![Gitter chat](https://badges.gitter.im/gitterHQ/gitter.png)](https://gitter.im/voicewp/Lobby)

VoiceWP is a WordPress plugin that integrates with Amazon Alexa to create and enable the creation of Alexa skills.

## How it Works

VoiceWP creates REST endpoints using the WordPress REST API. These endpoints handle all of the logic of your Alexa skills. When a user interacts with your skill, it sends a Request to an endpoint, which is processed by this plugin, and sends the Response back to the user.

This plugin provides a settings screen for configuring your skills within the WordPress admin dashboard.

For more on how Alexa skills work in general, [see here](https://developer.amazon.com/alexa-skills-kit).

## Requirements

- [Fieldmanager](http://fieldmanager.org).
- SSL certificate.
- Minimum version of PHP: 5.3.
- Minimum version of WordPress: 4.4.

## Installation

- Install and activate [Fieldmanager](https://github.com/alleyinteractive/wordpress-fieldmanager/archive/1.0.0.zip).
- Download the .zip file of this repo and upload to your WordPress site by navigating to WP Admin and navigating to **Plugins -> Add New**. Select the 'Upload Plugin' button near the top of the top of the screen to upload the .zip file.

## Features

Out of the box, a few different types of skills can be created:

- Flash Briefings, which deliver original content to users as part of their flash briefing. For more on Flash Briefings, [see here](https://developer.amazon.com/alexa-skills-kit/flash-briefing).
- News. This makes the content/posts from your site accessible to users for consumption via Alexa.
- Facts/Quotes - Create simple skills for delivering facts or quotes on your favorite topics. For example, 'Cat Facts', or 'Developer Quotes'.

-list
-list
  -sublist
  -sublist
-list

In addition, the plugin allows developers to create completely new types of skills via provided hooks, filters and functions. Documentation for this is outlined here.[link]

### Flash Briefing Skill

A Briefings post type is created which is intended to be used for the Flash Briefing skill.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/briefing`

### Fact/Quote Skills

A Skills post type is created for generic skill creation. Out of the box, Fact/Quote skills can be created.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/(post_id)`

### News from your posts Skill

This news/content skill will currently read the 5 latest headlines from your WordPress posts and allows the user to choose a post to be read in full.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/news`

## Contribute

All development of VoiceWP happens transparently on Github. [Github Issues](https://github.com/alleyinteractive/voicewp/issues) are used for identifying and discussing bugs and features. Code contributions, whether fixes or enhancements, should be submitted as Pull Requests.

Join us on [Gitter](https://gitter.im/voicewp/Lobby) for general discussions or questions.

## Documentation

## Credits

See [credits.txt](/credits.txt)