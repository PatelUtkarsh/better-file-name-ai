# Better File Name

## Description

This WordPress plugin renames files to be more readable using an open API (gpt-4-vision) upon upload and generates accessible alt text for images.

Rename file idea for WordPress was originally purposed by [Pascal Birchler](https://github.com/swissspidy) in following tweet.

<div align="center">
	<a href="https://twitter.com/swissspidy/status/1723610429081973133">
		<img height="350" src="https://github.com/PatelUtkarsh/better-file-name-ai/assets/5015489/6d7dd824-c80a-4c14-90f1-06abf5331e79"/>
	</a>
</div>

### Additional features:

-   Allows generating featured image using dall-e-2 or dall-e-3 API.

> Note: GPT-4 Vision is in preview, It is not recommended to use this plugin on a production site.

## Demo:

https://github.com/PatelUtkarsh/better-file-name-ai/assets/5015489/1f0ce636-ceeb-4e6e-918b-872d3069d40f

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
