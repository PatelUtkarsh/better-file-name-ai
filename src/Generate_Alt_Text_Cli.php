<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

use WP_CLI;

class Generate_Alt_Text_Cli {

	/**
	 * Add missing alt texts for images.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Perform a dry run without making any actual changes.
	 *
	 * [--use=url|data]
	 * : While sending request to API, use url or data to generate alt text. Use url only if file is accessible via url.
	 * If environment is production it defaults to url else data.
	 *
	 * ## EXAMPLES
	 *
	 *     wp better-file-name generate-alt-text --dry-run
	 *
	 * @param   array $args        Command args.
	 * @param   array $assoc_args  Command options.
	 */
	public function __invoke( array $args, array $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] );
		$use     = $assoc_args['use'] ?? '';
		$setting = new Settings();
		// We are not checking if alt text generation is enabled here because user explicitly ran this command.

		$query = new \WP_Query(
			[
				'post_type'              => 'attachment',
				'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_wp_attachment_image_alt',
						'compare' => 'NOT EXISTS',
					],
				],
				'posts_per_page'         => - 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'fields'                 => 'ids',
				'post_mime_type'         => 'image/%',
				'post_status'            => 'any',
			]
		);

		$attachment_ids = $query->posts;

		WP_CLI::log( 'Found ' . count( $attachment_ids ) . ' attachments without alt text.' );

		$attachment_ids_chunks = array_chunk( $attachment_ids, 50 );
		$uploads               = wp_get_upload_dir();
		$base_dir              = $uploads['basedir'];
		unset( $attachment_ids, $query, $uploads );

		$open_ai_wrapper = new Openai_Wrapper( $setting->get_openai_api_key() );

		$generated_alt_text_count = 0;
		foreach ( $attachment_ids_chunks as $attachment_ids ) {
			foreach ( $attachment_ids as $post_id ) {
				WP_CLI::log( 'Processing attachment ID: ' . $post_id );
				if ( wp_get_environment_type() !== 'production' || $use === 'data' ) {
					$attachment_data = wp_get_attachment_metadata( $post_id );
					if ( ! isset( $attachment_data['file'] ) ) {
						continue;
					}
					$file_path = str_replace( basename( $attachment_data['file'] ), '', $attachment_data['file'] );
					if ( isset( $attachment_data['sizes']['large'] ) ) {
						$file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path . $attachment_data['sizes']['large']['file'];
					} else {
						$file_path = $base_dir . DIRECTORY_SEPARATOR . $file_path . $attachment_data['file'];
					}
				} else {
					$file_path = wp_get_attachment_url( $post_id, 'large' );
				}
				if ( ! $dry_run && $setting->get_openai_api_key() ) {
					try {
						$text = $open_ai_wrapper->get_alt_text( $file_path );
						if ( $text ) {
							++$generated_alt_text_count;
							update_post_meta( $post_id, '_wp_attachment_image_alt', $text );
						}
					} catch ( \Exception $e ) {
						WP_CLI::warning( $e->getMessage() );
					}
				}
			}
			WP_CLI::log( 'Sleeping for 3 seconds...' );
			sleep( 3 );
		}
		WP_CLI::success( 'Generated alt text for ' . $generated_alt_text_count . ' attachments.' );
	}
}
