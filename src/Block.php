<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Block {

	public $path;

	public function __construct( $path ) {
		// Enqueue block editor scripts.
		add_action( 'enqueue_block_editor_assets', $this->enqueue_scripts( ... ) );
		$this->path = $path;
	}

	public function enqueue_scripts() {
		$version_file = __DIR__ . '/../build/index.build.asset.php';
		if ( ! file_exists( $version_file ) ) {
			return;
		}
		$version = include $version_file;
		wp_enqueue_script(
			'better-file-name-ai',
			$this->path . '/index.build.js',
			$version['dependencies'],
			$version['version'],
			[
				'in_footer' => true,
			]
		);
	}
}
