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

		$file_path = new File_Path();
		$file_path = $file_path->get_image_path( $post_id );
		if ( $file_path === null ) {
			return new WP_REST_Response( [ 'error' => 'Attachment file not found.' ], 404 );
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
