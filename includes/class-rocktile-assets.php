<?php
/**
 * Rocktile Calculator Assets Handler.
 *
 * Manages loading of scripts and styles for Development (Vite HMR)
 * and Production (Vite Manifest Build) environments.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Assets {

	/**
	 * Main entry point file in Vite frontend source.
	 */
	const ENTRY_FILE = 'src/main.js';

	/**
	 * Default Vite Dev Server URL.
	 */
	const DEFAULT_DEV_SERVER = 'http://localhost:5173';

	/**
	 * Flag indicating if assets should be enqueued on this request.
	 *
	 * @var bool
	 */
	private $should_enqueue = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 100 );
		add_filter( 'script_loader_tag', array( $this, 'add_module_attribute' ), 10, 3 );
	}

	/**
	 * Mark that the calculator assets should be loaded for current request.
	 */
	public function request_assets() {
		$this->should_enqueue = true;
	}

	/**
	 * Check whether the plugin is running in Development mode.
	 *
	 * @return bool
	 */
	public static function is_dev_mode() {
		return defined( 'ROCKTILE_CALCULATOR_DEV' ) && true === ROCKTILE_CALCULATOR_DEV;
	}

	/**
	 * Get the Vite dev server URL.
	 *
	 * @return string
	 */
	public static function get_dev_server_url() {
		$url = defined( 'ROCKTILE_CALCULATOR_DEV_SERVER' ) ? ROCKTILE_CALCULATOR_DEV_SERVER : self::DEFAULT_DEV_SERVER;
		return untrailingslashit( apply_filters( 'rocktile_calculator_dev_server', $url ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_enqueue ) {
			return;
		}

		if ( self::is_dev_mode() ) {
			$this->enqueue_dev_assets();
		} else {
			$this->enqueue_prod_assets();
		}
	}

	/**
	 * Enqueue assets from Vite Dev Server (HMR mode).
	 */
	private function enqueue_dev_assets() {
		$dev_server = self::get_dev_server_url();

		$vite_client_url = $dev_server . '/@vite/client';
		$main_entry_url  = $dev_server . '/' . self::ENTRY_FILE;

		// Use wp_enqueue_script with script_loader_tag filter for type="module"
		wp_enqueue_script(
			'rocktile-vite-client',
			$vite_client_url,
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'rocktile-calculator-app',
			$main_entry_url,
			array( 'rocktile-vite-client' ),
			null,
			true
		);
	}

	/**
	 * Enqueue assets from production build using Vite manifest.
	 */
	private function enqueue_prod_assets() {
		$manifest_path = $this->get_manifest_path();

		if ( ! $manifest_path || ! file_exists( $manifest_path ) ) {
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
				add_action(
					'wp_footer',
					function () {
						echo '<!-- Rocktile Calculator: Production build manifest not found. Please run "npm run build" in frontend directory. -->';
					}
				);
			}
			return;
		}

		$manifest_content = file_get_contents( $manifest_path );
		$manifest         = json_decode( $manifest_content, true );

		if ( ! is_array( $manifest ) || empty( $manifest[ self::ENTRY_FILE ] ) ) {
			return;
		}

		$entry    = $manifest[ self::ENTRY_FILE ];
		$dist_url = ROCKTILE_CALCULATOR_URL . 'dist/';

		// Enqueue JS entry point
		if ( ! empty( $entry['file'] ) ) {
			$js_url = $dist_url . $entry['file'];

			wp_enqueue_script(
				'rocktile-calculator-app',
				$js_url,
				array(),
				ROCKTILE_CALCULATOR_VERSION,
				true
			);
		}

		// Enqueue associated CSS
		if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
			foreach ( $entry['css'] as $index => $css_file ) {
				wp_enqueue_style(
					'rocktile-calculator-style-' . $index,
					$dist_url . $css_file,
					array(),
					ROCKTILE_CALCULATOR_VERSION
				);
			}
		}
	}

	/**
	 * Locate the manifest.json file.
	 * Vite 5+ uses dist/.vite/manifest.json, earlier Vite used dist/manifest.json.
	 *
	 * @return string|false
	 */
	private function get_manifest_path() {
		$vite5_manifest = ROCKTILE_CALCULATOR_PATH . 'dist/.vite/manifest.json';
		if ( file_exists( $vite5_manifest ) ) {
			return $vite5_manifest;
		}

		$legacy_manifest = ROCKTILE_CALCULATOR_PATH . 'dist/manifest.json';
		if ( file_exists( $legacy_manifest ) ) {
			return $legacy_manifest;
		}

		return false;
	}

	/**
	 * Add type="module" attribute to script tags.
	 *
	 * @param string $tag
	 * @param string $handle
	 * @param string $src
	 * @return string
	 */
	public function add_module_attribute( $tag, $handle, $src ) {
		if ( in_array( $handle, array( 'rocktile-vite-client', 'rocktile-calculator-app' ), true ) ) {
			if ( false === strpos( $tag, 'type=' ) ) {
				$tag = str_replace( '<script ', '<script type="module" ', $tag );
			}
		}
		return $tag;
	}
}
