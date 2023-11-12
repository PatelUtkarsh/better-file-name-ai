<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Admin {

	public Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		if ( $this->settings->get_rename_file() && $this->settings->get_openai_api_key() ) {
			add_filter( 'wp_handle_sideload_prefilter', [ $this, 'rename_new_file' ], 9999 );
			add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_new_file' ], 9999 );
		}
	}

	public function rename_new_file( array $file ) {
		$path = $file['tmp_name'];

		$wrapper = new Openai_Wrapper( $this->settings->get_openai_api_key() );
		try {
			$new_filename = $wrapper->get_renamed_filename( $path );
			if ( $new_filename ) {
				$file['name'] = $new_filename;
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $file;
	}
}
