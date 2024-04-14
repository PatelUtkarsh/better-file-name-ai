<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class File_Path {

	private string $base_dir;

	public function __construct( private readonly string $use_path = '' ) {
		$uploads        = wp_get_upload_dir();
		$this->base_dir = $uploads['basedir'];
	}

	public function get_image_path( $post_id ): ?string {
		if ( wp_get_environment_type() !== 'production' || $this->use_path === 'local' ) {
			$attachment_data = wp_get_attachment_metadata( $post_id );
			if ( ! isset( $attachment_data['file'] ) ) {
				return null;
			}
			$file_path = str_replace( basename( $attachment_data['file'] ), '', $attachment_data['file'] );
			if ( isset( $attachment_data['sizes']['large'] ) ) {
				$file_path = $this->base_dir . DIRECTORY_SEPARATOR . $file_path . $attachment_data['sizes']['large']['file'];
			} else {
				$file_path = $this->base_dir . DIRECTORY_SEPARATOR . $attachment_data['file'];
			}
		} else {
			[ $file_path ] = image_downsize( $post_id, 'thumbnail' );
			if ( ! $file_path ) {
				[ $file_path ] = image_downsize( $post_id, 'large' );
			}
			if ( ! $file_path ) {
				$file_path = wp_get_attachment_url( $post_id );
			}
		}
		return $file_path;
	}
}
