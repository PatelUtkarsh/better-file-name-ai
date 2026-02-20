=== Better File Name Ai ===
Contributors: utkarshpatel
Donate link:
Tags: file name generator, alt text, alt text generator, featured image generator, dall-e
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
This WordPress plugin renames files to be more readable using OpenAI (gpt-4.1-mini) upon upload and generates accessible alt text for images.

Plugin allows generating featured image using dall-e-2 or dall-e-3 API.

Note: This plugin relies on the OpenAI API. Read [terms of service from OpenAI](https://openai.com/policies/terms-of-use) before using this plugin. The plugin sends a low-resolution image to the OpenAI API to generate alt text and file names.

You must have an OpenAI account and generate an API key to use this plugin. You can sign up for an account [here](https://platform.openai.com/account/api-keys/).

== Installation ==

1. Download zip file from GitHub release.
2. Upload the plugin folder to the `/wp-content/plugins/` directory or upload the zip file from WordPress admin panel.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Setup open api key from settings page from under tools menu. `/wp-admin/tools.php?page=better-file-name-settings`

If your setup supports composer autoload then you can install plugin using composer.

1. Run `composer require patelutkarsh/better-file-name` command.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Setup open api key from settings page from under tools menu. `/wp-admin/tools.php?page=better-file-name-settings`

== Frequently Asked Questions ==
= How to get openapi key? =
Sign up for [open api](https://openai.com/) and get generate key from [here](https://platform.openai.com/account/api-keys).

= How do I generate alt text for images? =
Run following command to generate alt text for all images that do not have any.

	wp better-file-name generate-alt-text --dry-run

This can take a while depending on the number of images on your site.

== Demo ==

[Demo of featured image generation using Dall-E 2](https://p.utkarsh.workers.dev/demo-TfVNE.mp4)

== Changelog ==
See [GitHub releases](https://github.com/PatelUtkarsh/better-file-name-ai/releases) for changelog.
