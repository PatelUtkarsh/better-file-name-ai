<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

use WP_REST_Response;

class Dalle_Image_Generator {
	public Settings $setting;

	const JOB_TRANSIENT_PREFIX = 'bfn_image_job_';
	const ACTION_HOOK          = 'better_file_name_generate_image';

	public function __construct( Settings $setting ) {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( self::ACTION_HOOK, [ $this, 'process_image_generation' ] );
		$this->setting = $setting;
	}

	public function register_routes(): void {
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

		register_rest_route(
			'better-file-name/v1',
			'/image-job-status/(?P<job_id>[a-zA-Z0-9]+)',
			[
				'methods'             => 'GET',
				'callback'            => $this->get_job_status( ... ),
				'permission_callback' => function () {
					return current_user_can( 'upload_files' );
				},
				'args'                => [
					'job_id' => [
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && preg_match( '/^[a-zA-Z0-9]+$/', $param );
						},
					],
				],
			]
		);
	}

	public function validate_string( $param, $_request, $_key ): bool {
		unset( $_request, $_key );
		return is_string( $param );
	}

	public function generate_image( $request ): WP_REST_Response {
		$post_title   = $request->get_param( 'postTitle' );
		$post_content = $request->get_param( 'postContent' );
		$prompt       = $request->get_param( 'prompt' );

		if ( ! $this->setting->get_openai_api_key() ) {
			return new WP_REST_Response( [ 'error' => esc_html__( 'OpenAI API key not found', 'better-file-name' ) ], 404 );
		}

		$job_id = wp_generate_password( 16, false, false );

		set_transient(
			self::JOB_TRANSIENT_PREFIX . $job_id,
			[
				'status' => 'pending',
			],
			HOUR_IN_SECONDS
		);

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				self::ACTION_HOOK,
				[
					[
						'job_id'       => $job_id,
						'prompt'       => $prompt,
						'post_title'   => $post_title,
						'post_content' => $post_content,
						'api_key'      => $this->setting->get_openai_api_key(),
						'model'        => $this->setting->get_image_model(),
						'quality'      => $this->setting->get_image_quality(),
						'vision_model' => $this->setting->get_vision_model(),
					],
				],
				'better-file-name'
			);
		} else {
			// Fallback: run synchronously if Action Scheduler is not available.
			$this->process_image_generation(
				[
					'job_id'       => $job_id,
					'prompt'       => $prompt,
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'api_key'      => $this->setting->get_openai_api_key(),
					'model'        => $this->setting->get_image_model(),
					'quality'      => $this->setting->get_image_quality(),
					'vision_model' => $this->setting->get_vision_model(),
				]
			);
		}

		return new WP_REST_Response( [ 'job_id' => $job_id ], 202 );
	}

	public function get_job_status( $request ): WP_REST_Response {
		$job_id = $request->get_param( 'job_id' );
		$job    = get_transient( self::JOB_TRANSIENT_PREFIX . $job_id );

		if ( false === $job ) {
			return new WP_REST_Response( [ 'error' => esc_html__( 'Job not found or expired', 'better-file-name' ) ], 404 );
		}

		return new WP_REST_Response( $job, 200 );
	}

	public function process_image_generation( array $args ): void {
		$job_id = $args['job_id'];

		set_transient(
			self::JOB_TRANSIENT_PREFIX . $job_id,
			[
				'status' => 'processing',
			],
			HOUR_IN_SECONDS
		);

		try {
			$wrapper    = new Openai_Wrapper( $args['api_key'], $args['vision_model'] );
			$b64_json   = $wrapper->generate_image( $args['prompt'], $args['post_title'], $args['post_content'], $args['model'], $args['quality'] );
			$image_data = base64_decode( $b64_json, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( ! is_string( $image_data ) || '' === $image_data ) {
				throw new \Exception( esc_html__( 'Failed to decode image data', 'better-file-name' ) );
			}

			$attachment_id = $this->save_image_data_as_attachment( $image_data );

			set_transient(
				self::JOB_TRANSIENT_PREFIX . $job_id,
				[
					'status'        => 'completed',
					'attachment_id' => $attachment_id,
				],
				HOUR_IN_SECONDS
			);
		} catch ( \Exception $e ) {
			set_transient(
				self::JOB_TRANSIENT_PREFIX . $job_id,
				[
					'status' => 'failed',
					'error'  => $e->getMessage(),
				],
				HOUR_IN_SECONDS
			);
		}
	}

	private function save_image_data_as_attachment( string $image_data ): int {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$upload_dir = wp_upload_dir();
		$filename   = 'generated-image-' . wp_generate_password( 8, false, false ) . '.jpeg';
		$file_path  = $upload_dir['path'] . '/' . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$bytes_written = file_put_contents( $file_path, $image_data );
		if ( false === $bytes_written ) {
			throw new \Exception( esc_html__( 'Failed to write image to uploads directory', 'better-file-name' ) );
		}

		$attachment = [
			'post_mime_type' => 'image/jpeg',
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $file_path );

		// @phpstan-ignore-next-line -- wp_insert_attachment can return WP_Error despite PHPDoc stubs.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged
			throw new \Exception( esc_html( implode( ', ', $attachment_id->get_error_messages() ) ) );
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return $attachment_id;
	}
}
