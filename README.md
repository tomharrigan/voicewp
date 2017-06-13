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

- 3 different types of skills can be created out of the box
	- __Flash Briefings__: Deliver original content to users as part of their flash briefing. For more on Flash Briefings, [see here](https://developer.amazon.com/alexa-skills-kit/flash-briefing).
	- __News__: Allow users to listen to your content/posts
		- Fully editable 'Welcome', 'Help', and 'Stop' messaging
		- Presents users with a list of posts to select from
		- Selected content/post is read in full
		- Users can ask for content from a specific category/taxonomy
		- Full SSML support within post content
		- Full utterances/intent schema provided as starting point to easily copy/paste into theh Amazon Developer console
		- Does your site use acronyms or other abbreviations? Settings screen provides an interface for defining items Alexa should pronounce a certain way (For example, a site may want all instances of 'DoT' within text to be read as 'Department of transportation' )
	- __Facts/Quotes__: Create simple skills for serving facts or quotes on your favorite topics. For example, 'Cat Facts', or 'Developer Quotes'.
		- Ability to include custom images as part of the app cards.
		- Ability to include this as a subset of functionality within the main News skill.
- Developers can create completely new types of skills or customize the existing functionality of the plugin via provided hooks, filters and functions. Documentation [outlined here](#documentation).

## Contribute

All development of VoiceWP happens transparently on Github. [Github Issues](https://github.com/alleyinteractive/voicewp/issues) are used for identifying and discussing bugs and features. Code contributions, whether fixes or enhancements, should be submitted as Pull Requests.

Join us on [Gitter](https://gitter.im/voicewp/Lobby) for general discussions or questions.

## Documentation

- [Skills](#skills)
- [Amazon Developer Console](#amazon-developer-console)
- [Settings](#settings)
- [Testing](#testing)
- [Creating your own custom skills](#creating-your-own-custom-skills)
- [Hook/Filter reference](#hookfilter-reference)
- [Removing unused components](#removing-unused-components)

### Skills

#### Flash Briefing Skill

A Briefings post type is created which is intended to be used for the Flash Briefing skill.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/briefing`

#### Fact/Quote Skills

A Skills post type is created for generic skill creation. Out of the box, Fact/Quote skills can be created.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/(post_id)`

#### News from your posts Skill

This news/content skill will currently read the 5 latest headlines from your WordPress posts and allows the user to choose a post to be read in full.

The endpoint for this will be at:
`https://yourdomain.com/wp-json/voicewp/v1/skill/news`

### Amazon Developer Console

### Settings

### Testing

### Creating your own custom skills

To create your own completely custom types of skills, there are two parts. First, register an endpoint. Second, have a function that gets called when the endpoint is reached.

Below is a simple Hello World skill to demonstrate how this can be accomplished.

```php
function hello_world_register_routes() {
	register_rest_route( 'voicewp/v1', '/hello-world', array(
		'callback' => 'hello_world_skill',
		'methods' => array( 'POST', 'GET' ),
	) );
}
add_action( 'rest_api_init', 'hello_world_register_routes' );
```

This registers the REST endpoint, defining the URL that our skill lives at. Our `hello_world_register_routes` function is hooked on the `rest_api_init` action.

- The URL of this skill would be `https://{yourdomain.com}/wp-json/voicewp/v1/hello-world`
- When a request is made by Alexa to this URL, a function named `hello_world_skill` is called.

Now let's define our 'hello_world_skill' function:

```php
function hello_world_skill( WP_REST_Request $request ) {
	// Begin boilerplate
	// Allows us to leverage the core plugin to handle the grunt work
	$voicewp_instance = Voicewp::get_instance();

	// Prevents people from being able to hit the skill directly in browser. Only requests from Alexa are allowed.
	$voicewp_instance->voicewp_maybe_display_notice();

	// Gets the Alexa Request and Response objects for us to use in our skill
	list( $request, $response ) = $voicewp_instance->voicewp_get_request_response_objects( $request, 'add_your_amazon_app_ID_here' );
	// End boilerplate

	// This is the main functionality of your skill,
	// it formats the response that will be sent.
	$response
		// Alexa will read this out
		->respond( __( 'Hello world!', 'voicewp' ) )
		// The app card will say 'Hello from {your site name}'
		->with_card( sprintf( __( 'Hello from %s', 'voicewp' ), get_bloginfo( 'name' ) ) )
		// End the session since a user response is not being waited on.
		// Just say hello and then exit the skill.
		->end_session();

	// Send the result to the user
	return new WP_REST_Response( $response->render() );
}
```

Our `hello_world_skill` function accepts one parameter, which we get automatically. We then have a few lines of boilerplate, each line above is commented to describe what it is doing. Don't forget to replace `add_your_amazon_app_ID_here` with your own app ID.

After the boilerplate is the main functionality of our skill. In this simple example, it is included directly here, but you may wish to break this out into a separate function in your own code.

Then, the response is sent back to the user. Alexa will say 'Hello World!', in the users' app, there will be an app card that says 'Hello from {your site name}'. That's it, simple!

The complete above sample skill, formatted nicely into a class, is included here.

### Hook/Filter reference

### Removing unused components

## Credits

See [credits.txt](/credits.txt)