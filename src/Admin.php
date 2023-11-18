<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Admin {

	public Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		if ( ! $this->settings->get_openai_api_key() ) {
			return;
		}
		if ( $this->settings->get_rename_file() ) {
			add_filter( 'wp_handle_sideload_prefilter', [ $this, 'rename_new_file' ], 9999 );
			add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_new_file' ], 9999 );
		}

		if ( $this->settings->should_generate_alt_text() ) {
			add_filter( 'wp_update_attachment_metadata', [ $this, 'update_alt_text' ], 10, 2 );
		}
	}

	public function rename_new_file( array $file ) {
		$path = $file['tmp_name'];

		$wrapper = new Openai_Wrapper( $this->settings->get_openai_api_key() );
		try {
			$new_filename = $wrapper->get_filename( $path );
			if ( $new_filename ) {
				// Amend extension to new file name from original file name if it doesn't exists in new filename.
				$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
				if ( ! str_contains( $new_filename, $extension ) ) {
					$new_filename .= '.' . $extension;
				}
				$file['name'] = $new_filename;
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $file;
	}

	public function update_alt_text( array $data, int $post_id ): array {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// Skip if WP-CLI is running; This should be handled via custom command.
			return $data;
		}

		$has_alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true );
		if ( ! empty( $has_alt ) || ! isset( $data['file'] ) ) {
			return $data;
		}

		$uploads = wp_get_upload_dir();
		$file    = $uploads['basedir'] . '/' . $data['file'];

		$wrapper = new Openai_Wrapper( $this->settings->get_openai_api_key() );
		try {
			$new_alt_text = $wrapper->get_alt_text( $file );
			if ( $new_alt_text ) {
				update_post_meta( $post_id, '_wp_attachment_image_alt', $new_alt_text );
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $data;
	}
}
