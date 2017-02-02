# AlexaWP

AlexaWP is a WordPress plugin that integrates with Amazon Alexa to create and enable the creation of Alexa skills.

It allows you to use WordPress as an endpoint.

[Fieldmanager](http://fieldmanager.org) is a dependency and must be installed and activated for AlexaWP to function fully as intended.

## Giving credit where it's due

This project is built on top of [Amazon Alexa PHP](https://github.com/jakubsuchy/amazon-alexa-php) and the work of its authors as well as this [Drupal project](https://www.drupal.org/project/alexa). Authors of those projects include: Jakub Suchy, Emanuele Corradini, Mathias Hansen, Chris Hamper

## Functionality and setup

- Install the [Fieldmanager](http://fieldmanager.org) plugin. It is used for custom field, meta, and settings screens.

- Download the zip of this repo and upload/install it manually in Wordpress

- An SSL certificate is required on your site. You can use LetsEncrypt

### Flash Briefing Skill

A Briefings post type is created which is intended to be used for a Flash Briefing skill.

The endpoint for this will be at:
`https://yourdomaim.com/wp-json/alexawp/v1/skill/briefing`

### Fact/Quote Skills

A Skills post type is created for generic skill creation. Out of the box Fact/Quote skills can be created.

The endpoint for this will be at:
`https://yourdomaim.com/wp-json/alexawp/v1/skill/(post_id)`

### News from your posts Skill

This news/content skill will currently read the 5 latest headlines from your regular WordPress posts and allows the listener to choose a post to be read in full.

The endpoint for this will be at:
`https://yourdomaim.com/wp-json/alexawp/v1/skill/news`

----

## Contribute

I very much welcome pull requests and collaboration on this project.

## To Do

- More caching is needed around the endpoint content

- News skill can become more useful by allowing category selection without any heavy lifting

- Utterance generation can be further flushed out along with more helpful guidance within the wp-admin context

