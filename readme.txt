=== Plugin Name ===
Contributors: tomharrigan, dlh
Tags: Alexa, Amazon, fieldmanager
Requires at least: 4.4
Tested up to: 4.7.5
Stable tag: 1.0.0
License: MIT

WordPress + Amazon Alexa integration

== Description ==

VoiceWP is a WordPress plugin that integrates with Amazon Alexa to create and enable the creation of Alexa skills.

After setting up the plugin on your site, set up your Alexa skills in the (Amazon developer portal)[https://developer.amazon.com] as you would for any other skill.

Out of the box this plugin supports the creation of three types of skills:

**Flash Briefing Skill**

A Briefings post type is created which is intended to be used for a Flash Briefing skill.

The endpoint for this will be at: `https://yourdomain.com/wp-json/voicewp/v1/skill/briefing`

**Fact/Quote Skills**

A Skills post type is created for generic skill creation. Out of the box Fact/Quote skills can be created.

The endpoint for this will be at: `https://yourdomain.com/wp-json/voicewp/v1/skill/(post_id)`

**News from your posts Skill**

This news/content skill will currently read the 5 latest headlines from your regular WordPress posts and allows the listener to choose a post to be read in full.

The endpoint for this will be at: `https://yourdomain.com/wp-json/voicewp/v1/skill/news`

== Installation ==

1. Install the Fieldmanager plugin
2. Have a valid SSL certificate installed
3. Upload and/or activate the VoiceWP plugin

== Changelog ==

= 1.0.0 =
* Renamed to VoiceWP
* Renames all option, post type, meta prefixes with voicewp
* Adds an update routine to migrate all legacy data
* Moves some code to their own files/classes
* Adds more caching logic for clearing/resaving values to cache

= 0.2 =
* Cache data around briefings and news
* Remove hardcoded messages and add editable fields for different messages (for launch intent, help, stop/cancel)
* Adds intent schema and custom slots within settings screen
* Adds improved utterances
* Adds support for Standard Cards
* Support for PHP 5.3

= 0.1 =
* Initial commit