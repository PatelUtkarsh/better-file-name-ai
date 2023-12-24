<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Image {

	private array $temp_files = [];

	public function is_image( $file ): bool {
		if ( ! function_exists( 'finfo_open' ) ) {
			throw new \Exception( esc_html__( 'Fileinfo extension not installed', 'better-file-name' ) );
		}

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$type  = finfo_file( $finfo, $file );
		finfo_close( $finfo );

		return str_starts_with( $type, 'image/' );
	}

	public function resize_image( $file, $size = 512 ) {
		// Resize image to thumbnail size.
		$editor = wp_get_image_editor( $file );
		// Create temp file.
		if ( ! is_wp_error( $editor ) ) {
			$temp_file  = wp_tempnam( basename( $file ) );
			$is_succeed = $editor->resize( $size, null, false );
			if ( is_wp_error( $is_succeed ) ) {
				throw new \Exception( esc_html( $is_succeed->get_error_messages() ) );
			}
			$data = $editor->save( $temp_file );
			if ( is_wp_error( $data ) || empty( $data['path'] ) ) {
				throw new \Exception( esc_html( $data->get_error_message() ) );
			}
			$this->temp_files[] = $temp_file;
			$this->temp_files[] = $data['path'];

			return $data['path'];
		}
	}

	public function __destruct() {
		foreach ( $this->temp_files as $temp_file ) {
			wp_delete_file( $temp_file );
		}
	}
}
