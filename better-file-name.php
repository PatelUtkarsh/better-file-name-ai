<?php
/**
 * Plugin Name:     Better File Name
 * Plugin URI:      github.com/patelutkarsh/better-file-name
 * Description:     MVP plugin to rename uploaded file to more meaningful name using OpenAI.
 * Author:          Utkarsh
 * Author URI:      github.com/patelutkarsh
 * Text Domain:     better-file-name
 * Version:         1.4.2
 * Requires PHP:    8.1
 * License:         GPL-2.0-or-later
 *
 * @package         Better_File_Name_Ai
 */

declare( strict_types=1 );

use Better_File_Name_Ai\Admin;
use Better_File_Name_Ai\Dalle_Image_Generator;
use Better_File_Name_Ai\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( 'Better_File_Name_Ai\\Settings' ) ) {
	throw new \Exception( esc_html__( 'Could not find vendor/autoload.php, make sure you ran composer.', 'better-file-name' ) );
}

$better_file_name_settings              = new Settings();
$better_file_name_admin                 = new Admin( $better_file_name_settings, plugins_url( 'build', __FILE__ ) );
$better_file_name_dalle_image_generator = new Dalle_Image_Generator( $better_file_name_settings );
$better_file_name_alt_text_rest_api     = new Better_File_Name_Ai\Alt_Text_Rest_Api( $better_file_name_settings );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'better-file-name generate-alt-text', Better_File_Name_Ai\Generate_Alt_Text_Cli::class );
}
