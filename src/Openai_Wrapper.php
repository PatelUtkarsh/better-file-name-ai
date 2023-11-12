<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

use OpenAI;

class Openai_Wrapper {

	private string $openai_api_key;

	public function __construct( $openai_api_key ) {
		$this->openai_api_key = $openai_api_key;
	}

	public function get_renamed_filename( $file_name, $path ): string {

		if ( ! $this->openai_api_key ) {
			throw new \Exception( esc_html__( 'OpenAI API Key not set', 'better-file-name-ai' ) );
		}

		$image_url = $this->base64( $path );

		$client = OpenAI::client( $this->openai_api_key );

		$result = $client->chat()->create(
			[
				'model'    => 'gpt-4-vision-preview',
				'messages' => [
					[
						'role'    => 'user',
						'content' => [
							[
								'type' => 'text',
								'text' => __( 'What would a good, short, filename be for this image? Only reply with the filename.', 'better-file-name-ai' ),
							],
							[
								'type'      => 'image_url',
								'image_url' => [ 'url' => $image_url ],
							],
						],
					],
				],
			]
		);

		$result = $result->toArray();

		return $result['choices'][0]['message']['content'] ?? false;
	}

	private function base64( $filename ): string {
		$data        = file_get_contents( $filename ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.file_get_contents_file_get_contents
		$base64_data = base64_encode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$finfo       = finfo_open( FILEINFO_MIME_TYPE );
		$type        = finfo_file( $finfo, $filename );
		finfo_close( $finfo );

		if ( strpos( $type, 'image/' ) !== 0 ) {
			throw new \Exception( esc_html__( 'File is not an image', 'better-file-name-ai' ) );
		}

		return "data:$type;base64," . $base64_data;
	}
}
