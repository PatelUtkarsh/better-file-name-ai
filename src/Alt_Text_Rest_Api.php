<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

use WP_REST_Response;

class Alt_Text_Rest_Api {

	public Settings $setting;

	public function __construct( Settings $setting ) {
		$this->setting = $setting;
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			'better-file-name/v1',
			'/alt-text-generator',
			[
				'methods'             => 'POST',
				'callback'            => $this->generate_image_alt_text( ... ),
				'permission_callback' => function () {
					return current_user_can( 'upload_files' );
				},
				'args'                => [
					'mediaId' => [
						'required'          => true,
						'validate_callback' => $this->validate_attachment_id( ... ),
					],
				],
			]
		);
	}

	public function validate_attachment_id( $param, $_request, $_key ): bool {
		unset( $_request, $_key );
		if ( ! is_numeric( $param ) ) {
			return false;
		}

		return get_post_type( $param ) === 'attachment';
	}

	public function generate_image_alt_text( $request ): WP_REST_Response {

		if ( ! $this->setting->get_openai_api_key() ) {
			return new WP_REST_Response( [ 'error' => 'OpenAI API key not found' ], 404 );
		}

		$open_ai_wrapper = new Openai_Wrapper( $this->setting->get_openai_api_key(), $this->setting->get_dell_e_version() );

		$post_id = $request->get_param( 'mediaId' );

		$uploads  = wp_get_upload_dir();
		$base_dir = $uploads['basedir'];

		if ( wp_get_environment_type() !== 'production' ) {
			$attachment_data = wp_get_attachment_metadata( $post_id );
			if ( ! isset( $attachment_data['file'] ) ) {
				return new WP_REST_Response( [ 'error' => 'Attachment file not found' ], 404 );
			}
			$file_path = str_replace( basename( $attachment_data['file'] ), '', $attachment_data['file'] );
			if ( isset( $attachment_data['sizes']['large'] ) ) {
				$file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path . $attachment_data['sizes']['large']['file'];
			} else {
				$file_path = $base_dir . DIRECTORY_SEPARATOR . $attachment_data['file'];
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
		try {
			if ( ! empty( $file_path ) ) {
				$text = $open_ai_wrapper->get_alt_text( $file_path );
				return new WP_REST_Response( [ 'alt_text' => $text ], 200 );
			} else {
				return new WP_REST_Response( [ 'error' => 'File path not found.' ], 404 );
			}
		} catch ( \Exception $e ) {
			return new WP_REST_Response( [ 'error' => $e->getMessage() ], 500 );
		}
	}
}
