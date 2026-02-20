<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Openai_Wrapper {

	public string $dall_e_version;
	private string $openai_api_key;

	public function __construct( $openai_api_key, $dall_e_version ) {
		$this->openai_api_key = $openai_api_key;
		$this->dall_e_version = $dall_e_version;
	}

	public function get_filename( string $path ): string {
		$schema = [
			'type'        => 'json_schema',
			'json_schema' => [
				'name'   => 'filename_response',
				'strict' => true,
				'schema' => [
					'type'                 => 'object',
					'properties'           => [
						'filename' => [
							'type'        => 'string',
							'description' => 'A short, descriptive, dash-separated filename without extension.',
						],
					],
					'required'             => [ 'filename' ],
					'additionalProperties' => false,
				],
			],
		];

		$response = $this->request(
			$path,
			__( 'What would a good, short, dash-separated filename be for this image? Return only the filename without any file extension.', 'better-file-name' ),
			$schema
		);

		$decoded = json_decode( $response, true );
		if ( $decoded && isset( $decoded['filename'] ) ) {
			return sanitize_file_name( $decoded['filename'] );
		}

		return sanitize_file_name( $response );
	}

	public function get_alt_text( string $path ): string {
		$schema = [
			'type'        => 'json_schema',
			'json_schema' => [
				'name'   => 'alt_text_response',
				'strict' => true,
				'schema' => [
					'type'                 => 'object',
					'properties'           => [
						'alt_text' => [
							'type'        => 'string',
							'description' => 'Descriptive alt text for the image for accessibility.',
						],
					],
					'required'             => [ 'alt_text' ],
					'additionalProperties' => false,
				],
			],
		];

		$response = $this->request(
			$path,
			__( 'Please provide the alt text for this image, ensuring it describes the content comprehensively for individuals who cannot see it.', 'better-file-name' ),
			$schema
		);

		$decoded = json_decode( $response, true );
		if ( $decoded && isset( $decoded['alt_text'] ) ) {
			return $decoded['alt_text'];
		}

		// Fallback: strip prefix if model didn't use structured output.
		if ( str_starts_with( $response, 'Alt text: ' ) ) {
			return str_replace( 'Alt text: ', '', $response );
		}

		return $response;
	}

	public function request( string $path, string $prompt, ?array $response_format = null ): string {

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
			'model'                 => 'gpt-4.1-mini',
			'messages'              => [
				[
					'role'    => 'user',
					'content' => [
						[
							'type' => 'text',
							'text' => $prompt,
						],
						[
							'type'      => 'image_url',
							'image_url' => [
								'url'    => $image_url,
								'detail' => 'low',
							],
						],
					],
				],
			],
			'max_completion_tokens' => 1024,
		];

		if ( $response_format ) {
			$data['response_format'] = $response_format;
		}

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
				'timeout' => defined( 'WP_CLI' ) && WP_CLI ? 30 : 15,
			]
		);

		if ( $request instanceof \WP_Error ) {
			throw new \Exception( esc_html( implode( ', ', $request->get_error_messages() ) ) );
		}

		$response = wp_remote_retrieve_body( $request );
		$result   = json_decode( $response, true );

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			$message = isset( $result['error']['message'] )
				? esc_html( $result['error']['message'] )
				: esc_html__( 'Unable to get response from OpenAI', 'better-file-name' );
			throw new \Exception( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- $message is already escaped above.
		}

		if ( $result && isset( $result['choices'][0]['message']['content'] ) ) {
			return $result['choices'][0]['message']['content'];
		} else {
			throw new \Exception( esc_html__( 'Unable to get filename from OpenAI', 'better-file-name' ) );
		}
	}

	private function base64( $filename ): string {
		if ( ! function_exists( 'finfo_open' ) ) {
			throw new \Exception( esc_html__( 'Fileinfo extension not installed', 'better-file-name' ) );
		}

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$type  = finfo_file( $finfo, $filename );
		finfo_close( $finfo );

		if ( strpos( $type, 'image/' ) !== 0 ) {
			throw new \Exception( esc_html__( 'File is not an image', 'better-file-name' ) );
		}

		$data        = file_get_contents( $filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$base64_data = base64_encode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return "data:$type;base64," . $base64_data;
	}

	public function generate_image( $prompt, $title = '', $content = '' ) {
		$parameters = [
			'dall-e-2' => [
				'length' => 1000 - 100,
				'size'   => '1024x1024',
			],
			'dall-e-3' => [
				'length' => 4000 - 100,
				'size'   => '1792x1024',  // 7:4 aspect ratio is highest quality supported.
			],
		];

		$current_parameters = $parameters[ $this->dall_e_version ];

		$content_without_tag = wp_strip_all_tags( $content );

		if ( $this->dall_e_version === 'dall-e-3' ) {
			$prompt_data = wp_json_encode(
				[
					'user_prompt' => $prompt,
					'title'       => $title,
					'content'     => $content_without_tag,
				]
			);
		} else {
			$prompt_data = wp_json_encode(
				[
					'user_prompt' => $prompt,
					'title'       => $title,
				]
			);
		}

		if ( strlen( $prompt ) > $current_parameters['length'] && $this->dall_e_version === 'dall-e-3' ) {
			$excess_length            = strlen( $prompt ) - $current_parameters['length'];
			$truncated_content_length = strlen( $content_without_tag ) - $excess_length;
			$truncated_content_length = max( $truncated_content_length, 0 );
			$truncated_content        = substr( $content_without_tag, 0, $truncated_content_length );
			$prompt_data              = wp_json_encode(
				[
					'user_prompt' => $prompt,
					'title'       => $title,
					'content'     => $truncated_content,
				]
			);
		}

		$body = wp_json_encode(
			[
				'model'   => $this->dall_e_version,
				'prompt'  => wp_json_encode( $prompt_data ),
				'quality' => 'hd',
				'n'       => 1,
				'size'    => $current_parameters['size'],
			]
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/images/generations',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->openai_api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => $body,
				'timeout' => '120',
			]
		);

		if ( ! is_wp_error( $response ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $data['data'][0]['url'] ) ) {
				return $data['data'][0]['url'];
			} elseif ( isset( $data['error']['message'] ) ) {
				throw new \Exception( esc_html( $data['error']['message'] ) );
			}
		}

		throw new \Exception( esc_html__( 'Unable to generate image from OpenAI', 'better-file-name' ) );
	}
}
