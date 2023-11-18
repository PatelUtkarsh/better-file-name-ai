# Better File Name

## Description

This WordPress plugin renames files to be more readable using an open API (gpt-4-vision) upon upload and generates accessible alt text for images.

## Installation

### Manual Installation

1. Download the zip file from the [GitHub release](https://github.com/PatelUtkarsh/better-file-name-ai/releases)
1. Upload the plugin folder to /wp-content/plugins/.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Set up the open API key from the settings page under the 'Tools' menu: /wp-admin/tools.php?page=better-file-name-settings

### Composer Installation (if your setup supports composer autoloading)

1. Run `composer require patelutkarsh/better-file-name` command.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Set up the open API key from the settings page under the 'Tools' menu: `/wp-admin/tools.php?page=better-file-name-settings`

## Generate Missing Alt Text

Run following command to generate alt text for all images that do not have any.

	wp better-file-name generate-alt-text --dry-run

This can take a while depending on the number of images on your site.
