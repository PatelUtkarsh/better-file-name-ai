<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Openai_Wrapper {

	private string $openai_api_key;

	public function __construct( $openai_api_key ) {
		$this->openai_api_key = $openai_api_key;
	}

	public function get_filename( string $path ): string {
		return $this->request( $path, __( 'What would a good, short, dash separator filename be for this image? Only reply with the filename.', 'better-file-name' ) );
	}

	public function get_alt_text( string $path ): string {
		$text = $this->request( $path, __( 'Please provide the alt text for this image, ensuring it describes the content comprehensively for individuals who cannot see it. Only reply output.', 'better-file-name' ) );
		if ( str_starts_with( $text, 'Alt text: ' ) ) {
			return str_replace( 'Alt text: ', '', $text );
		}

		return $text;
	}

	public function request( string $path, string $prompt ): string {

		if ( ! $this->openai_api_key ) {
			throw new \Exception( esc_html__( 'OpenAI API Key not set', 'better-file-name' ) );
		}

		if ( ! str_starts_with( $path, 'http' ) ) {
			if ( ! file_exists( $path ) ) {
				throw new \Exception( esc_html__( 'File does not exist', 'better-file-name' ) );
			}
			$image_url = $this->base64( $path );
		} else {
			$image_url = $path;
		}

		$data = [
			'model'    => 'gpt-4-vision-preview',
			'messages' => [
				[
					'role'    => 'user',
					'content' => [
						[
							'type' => 'text',
							'text' => $prompt,
						],
						[
							'type'      => 'image_url',
							'image_url' => [ 'url' => $image_url ],
						],
					],
				],
			],
		];

		$headers = [
			'Authorization' => 'Bearer ' . $this->openai_api_key,
			'Content-Type'  => 'application/json',
		];

		$request = wp_remote_request(
			'https://api.openai.com/v1/chat/completions',
			[
				'method'  => 'POST',
				'headers' => $headers,
				'body'    => wp_json_encode( $data ),
				'timeout' => 10,
			]
		);

		if ( $request instanceof \WP_Error ) {
			throw new \Exception( esc_html( implode( ', ', $request->get_error_messages() ) ) );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			throw new \Exception( esc_html__( 'Unable to get filename from OpenAI', 'better-file-name' ) );
		}

		$response = wp_remote_retrieve_body( $request );
		$result   = json_decode( $response, true );

		if ( $result && isset( $result['choices'][0]['message']['content'] ) ) {
			return $result['choices'][0]['message']['content'];
		} else {
			throw new \Exception( esc_html__( 'Unable to get filename from OpenAI', 'better-file-name' ) );
		}
	}

	private function base64( $filename ): string {
		$data        = file_get_contents( $filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$base64_data = base64_encode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$finfo       = finfo_open( FILEINFO_MIME_TYPE );
		$type        = finfo_file( $finfo, $filename );
		finfo_close( $finfo );

		if ( strpos( $type, 'image/' ) !== 0 ) {
			throw new \Exception( esc_html__( 'File is not an image', 'better-file-name' ) );
		}

		return "data:$type;base64," . $base64_data;
	}
}
