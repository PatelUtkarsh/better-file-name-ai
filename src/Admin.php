<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Admin {

	public Settings $settings;

	public $plugin_url;


	public function __construct( Settings $settings, string $plugin_url ) {
		$this->settings   = $settings;
		$this->plugin_url = $plugin_url;

		if ( ! $this->settings->get_openai_api_key() ) {
			return;
		}

		if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
			return;
		}

		if ( $this->settings->get_rename_file() ) {
			add_filter( 'wp_handle_sideload_prefilter', [ $this, 'rename_new_file' ], 9999 );
			add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_new_file' ], 9999 );
		}

		if ( $this->settings->should_generate_alt_text() ) {
			add_filter( 'wp_update_attachment_metadata', $this->update_alt_text( ... ), 10, 2 );
			add_filter( 'attachment_fields_to_edit', $this->attachment_fields_to_edit( ... ), 10, 2 );
			add_action( 'wp_enqueue_media', $this->enqueue_media( ... ) );
		}

		if ( $this->settings->should_integrate_dall_e() ) {
			add_action( 'enqueue_block_editor_assets', $this->enqueue_scripts( ... ) );
		}
	}

	public function rename_new_file( array $file ) {
		$path = $file['tmp_name'];

		$wrapper = new Openai_Wrapper( $this->settings->get_openai_api_key(), $this->settings->get_dell_e_version() );
		try {
			$image = new Image();
			if ( $image->is_image( $path ) ) {
				$new_file     = $image->resize_image( $path );
				$new_filename = $wrapper->get_filename( $new_file );
				if ( $new_filename ) {
					// Amend extension to new file name from original file name if it doesn't exists in new filename.
					$extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
					if ( ! str_contains( $new_filename, $extension ) ) {
						$new_filename .= '.' . $extension;
					}
					$file['name'] = $new_filename;
				}
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

		$wrapper = new Openai_Wrapper( $this->settings->get_openai_api_key(), $this->settings->get_dell_e_version() );
		try {
			$image = new Image();
			if ( $image->is_image( $file ) ) {
				$new_file     = $image->resize_image( $file );
				$new_alt_text = $wrapper->get_alt_text( $new_file );
				if ( $new_alt_text ) {
					update_post_meta( $post_id, '_wp_attachment_image_alt', $new_alt_text );
				}
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return $data;
	}

	public function enqueue_scripts() {
		$version_file = __DIR__ . '/../build/index.asset.php';
		if ( ! file_exists( $version_file ) ) {
			return;
		}
		$version = include $version_file;
		wp_enqueue_script(
			'better-file-name-ai',
			$this->plugin_url . '/index.js',
			$version['dependencies'],
			$version['version'],
			[
				'in_footer' => true,
			]
		);
	}

	/**
	 * Add custom field to media attachment
	 *
	 * @param array  $form_fields Form fields.
	 * @param object $post        WP_Post object.
	 *
	 * @return array
	 */
	public function attachment_fields_to_edit( $form_fields, $post ) {
		if ( ! str_starts_with( $post->post_mime_type, 'image' ) ) {
			return $form_fields;
		}

		$form_fields['alt-text-generator'] = [
			'input' => 'html',
			'html'  => sprintf( '<button class="button generate-alt-text" data-media-id="%d">%s</button><span class="generate-alt-text__loading hidden">%s</span><span class="spinner"></span>', $post->ID, __( 'Generate alt text', 'better-file-name' ), esc_html__( 'Generating alt text...', 'better-file-name' ) ),
			'label' => '',
		];

		return $form_fields;
	}

	public function enqueue_media() {
		$version_file = __DIR__ . '/../build/media-alt-text.asset.php';
		if ( ! file_exists( $version_file ) ) {
			return;
		}
		$version = include $version_file;
		wp_enqueue_script(
			'better-file-name-ai-media',
			$this->plugin_url . '/media-alt-text.js',
			$version['dependencies'],
			$version['version'],
			[
				'in_footer' => true,
			]
		);

		wp_localize_script(
			'better-file-name-ai-media',
			'betterFileName',
			[
				'api'   => rest_url( 'better-file-name/v1/alt-text-generator' ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}
}
