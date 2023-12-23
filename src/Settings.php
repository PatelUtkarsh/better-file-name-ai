<?php
declare( strict_types=1 );

namespace Better_File_Name_Ai;

class Settings {

	public bool $should_rename_file;

	public string $openai_api_key;

	public bool $should_generate_alt_text;

	public bool $dell_e_integration;

	public string $dell_e_version;

	const RENAME_NEW_FILE = 'rename_new_file';

	const OPENAI_API_KEY = 'better_file_name_api_key';

	const ALT_TEXT = 'better_file_name_alt_text';

	const DELL_E_INTEGRATION = 'better_file_name_dell_e_integration';

	const DELL_E_VERSION = 'better_file_name_dell_e_version';

	public function __construct() {

		$this->should_rename_file       = (bool) get_option( self::RENAME_NEW_FILE, true );
		$this->openai_api_key           = get_option( self::OPENAI_API_KEY, '' );
		$this->should_generate_alt_text = (bool) get_option( self::ALT_TEXT, true );
		$this->dell_e_integration       = (bool) get_option( self::DELL_E_INTEGRATION, true );
		$this->dell_e_version           = get_option( self::DELL_E_VERSION, 'dall-e-2' );

		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_settings_page(): void {
		add_submenu_page(
			'tools.php',
			__( 'Better File Name Settings', 'better-file-name' ),
			'Better File Name',
			'manage_options',
			'better-file-name-settings',
			[
				$this,
				'settings_page_content',
			]
		);
	}

	public function settings_page_content(): void {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Better File Name', 'better-file-name' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'better_file_name_settings_group' ); ?>
				<?php do_settings_sections( 'better_file_name_settings' ); ?>
				<?php submit_button(); ?>
			</form>
			<p class="description"><?php esc_html_e( 'Note: Plugin utilizes separate APIs request for generating file names and alt text from the uploaded images and billing for image processing is determined by the uploaded image\'s file size.', 'better-file-name' ); ?></p>
		</div>
		<?php
	}

	public function register_settings(): void {
		register_setting( 'better_file_name_settings_group', self::RENAME_NEW_FILE, [ 'sanitize_callback' => 'intval' ] );
		register_setting( 'better_file_name_settings_group', self::OPENAI_API_KEY, [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'better_file_name_settings_group', self::ALT_TEXT, [ 'sanitize_callback' => 'intval' ] );
		register_setting( 'better_file_name_settings_group', self::DELL_E_INTEGRATION, [ 'sanitize_callback' => 'intval' ] );
		register_setting(
			'better_file_name_settings_group',
			self::DELL_E_VERSION,
			[
				'sanitize_callback' => fn( $v ) => in_array(
					$v,
					[
						'dall-e-3',
						'dall-e-2',
					],
					true
				) ? $v : '',
			]
		);
		$section = 'better_file_name_section';
		add_settings_section( $section, esc_html__( 'Media', 'better-file-name' ), '__return_empty_string', 'better_file_name_settings' );
		add_settings_field(
			self::RENAME_NEW_FILE,
			esc_html__( 'Rename New File', 'better-file-name' ),
			[
				$this,
				'rename_new_file_callback',
			],
			'better_file_name_settings',
			$section,
			[
				'label_for' => self::RENAME_NEW_FILE,
			]
		);
		add_settings_field(
			self::ALT_TEXT,
			esc_html__( 'Generate Alt Text', 'better-file-name' ),
			[
				$this,
				'generate_alt_text_callback',
			],
			'better_file_name_settings',
			$section,
			[
				'label_for' => self::ALT_TEXT,
			]
		);
		add_settings_field(
			self::DELL_E_INTEGRATION,
			esc_html__( 'Dell-e integration with featured image', 'better-file-name' ),
			[
				$this,
				'generate_dall_e_callback',
			],
			'better_file_name_settings',
			$section,
			[
				'label_for' => self::DELL_E_INTEGRATION,
			]
		);
		add_settings_field(
			self::DELL_E_VERSION,
			esc_html__( 'DELL E Version', 'better-file-name' ),
			[
				$this,
				'generate_dall_e_version_dropdown_callback',
			],
			'better_file_name_settings',
			$section,
			[
				'label_for' => self::DELL_E_VERSION,
			]
		);
		$section_api = 'better_file_name_section_api';
		add_settings_section( $section_api, esc_html__( 'API', 'better-file-name' ), '__return_empty_string', 'better_file_name_settings' );
		add_settings_field(
			self::OPENAI_API_KEY,
			esc_html__( 'OpenAI API Key', 'better-file-name' ),
			[
				$this,
				'better_file_name_api_key_callback',
			],
			'better_file_name_settings',
			$section_api,
			[
				'label_for' => self::OPENAI_API_KEY,
			]
		);
	}

	public function rename_new_file_callback(): void {
		printf( '<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s />', self::RENAME_NEW_FILE, checked( $this->get_rename_file(), true, false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function better_file_name_api_key_callback(): void {
		printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" />', self::OPENAI_API_KEY, esc_attr( $this->get_openai_api_key() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$allowed_tag = [
			'a' => [
				'href'   => [],
				'target' => [],
			],
		];
		echo '<p class="description">' . wp_kses( __( 'Get your API key from your <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI account</a>.', 'better-file-name' ), $allowed_tag ) . '</p>';
	}

	public function generate_alt_text_callback(): void {
		printf( '<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />', self::ALT_TEXT, checked( $this->should_generate_alt_text(), true, false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function generate_dall_e_callback(): void {
		printf( '<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />', self::DELL_E_INTEGRATION, checked( $this->should_integrate_dall_e(), true, false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function generate_dall_e_version_dropdown_callback() {
		$versions = [
			'dall-e-2' => 'Dall-e-2',
			'dall-e-3' => 'Dall-e-3',
		];
		?>
		<select name="<?php echo esc_attr( self::DELL_E_VERSION ); ?>"
				id="<?php echo esc_attr( self::DELL_E_VERSION ); ?>">
			<?php foreach ( $versions as $key => $version ) : ?>
				<option
					value="<?php echo esc_attr( $key ); ?>" <?php selected( $this->dell_e_version, $key ); ?>><?php echo esc_html( $version ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function get_rename_file(): bool {
		return $this->should_rename_file;
	}

	public function get_openai_api_key(): string {
		return $this->openai_api_key;
	}

	public function should_generate_alt_text(): bool {
		return $this->should_generate_alt_text;
	}

	public function should_integrate_dall_e(): bool {
		return $this->dell_e_integration;
	}

	public function get_dell_e_version(): string {
		return $this->dell_e_version;
	}
}
