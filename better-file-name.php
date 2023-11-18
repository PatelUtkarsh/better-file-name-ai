<?php
/**
 * Plugin Name:     Better File Name
 * Plugin URI:      github.com/patelutkarsh/better-file-name
 * Description:     MVP plugin to rename uploaded file to more meaningful name using OpenAI.
 * Author:          Utkarsh
 * Author URI:      github.com/patelutkarsh
 * Text Domain:     better-file-name
 * Version:         1.1.1
 * Requires PHP:    8.1
 *
 * @package         Better_File_Name_Ai
 */

declare( strict_types=1 );

use Better_File_Name_Ai\Admin;
use Better_File_Name_Ai\Settings;

require_once __DIR__ . '/vendor/autoload.php';

$better_file_name_settings = new Settings();
$better_file_name_admin    = new Admin( $better_file_name_settings );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'better-file-name generate-alt-text', Better_File_Name_Ai\Generate_Alt_Text_Cli::class );
}
