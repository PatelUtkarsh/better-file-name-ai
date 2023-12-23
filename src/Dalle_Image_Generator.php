<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

use WP_REST_Response;

class Dalle_Image_Generator {
	public Settings $setting;

	public function __construct( Settings $setting ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		$this->setting = $setting;
	}

	public function register_routes() {
		register_rest_route(
			'better-file-name/v1',
			'/dalle-generate-image',
			[
				'methods'             => 'POST',
				'callback'            => $this->generate_image( ... ),
				'permission_callback' => function () {
					return current_user_can( 'upload_files' );
				},
				'args'                => [
					'postTitle'   => [
						'required'          => true,
						'validate_callback' => $this->validate_string( ... ),
					],
					'postContent' => [
						'required'          => true,
						'validate_callback' => $this->validate_string( ... ),
					],
					'prompt'      => [
						'required'          => true,
						'validate_callback' => $this->validate_string( ... ),
					],
				],
			]
		);
	}

	public function validate_string( $param, $_request, $_key ) {
		unset( $_request, $_key );
		return is_string( $param );
	}

	public function generate_image( $request ) {
		$post_title   = $request->get_param( 'postTitle' );
		$post_content = $request->get_param( 'postContent' );
		$prompt       = $request->get_param( 'prompt' );

		try {
			$wrapper       = new Openai_Wrapper( $this->setting->get_openai_api_key(), $this->setting->get_dell_e_version() );
			$url           = $wrapper->generate_image( $prompt, $post_title, $post_content );
			$attachment_id = $this->save_image_as_attachment( $url );
		} catch ( \Exception $e ) {
			return new WP_REST_Response( [ 'error' => $e->getMessage() ], 500 );
		}

		return new WP_REST_Response( [ 'attachment_id' => $attachment_id ], 200 );
	}

	private function save_image_as_attachment( $image_url ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			throw new \Exception( esc_html( implode( ', ', $tmp->get_error_messages() ) ) );
		}

		$file_array = [
			'name'     => basename( wp_parse_url( $image_url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		];

		$id = media_handle_sideload( $file_array, 0 );

		if ( $id instanceof \WP_Error ) {
			@unlink( $file_array['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged
			throw new \Exception( esc_html( implode( ', ', $id->get_error_messages() ) ) );
		}

		return $id;
	}
}
