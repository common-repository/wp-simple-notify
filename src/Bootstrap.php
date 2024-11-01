<?php

namespace SimpleNotify;


/**
 * Class Bootstrap
 * @package SimpleNotify
 */
class Bootstrap {

	const PLUGIN_NAME = 'wp-simple-notify';

	const MENU_SLUG = 'wp-simple-notify-admin';

	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * @var Controller
	 */
	private $controller;

	/**
	 * Bootstrap constructor.
	 *
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings   = $settings;
		$this->controller = new Controller( $settings );
		$this->controller->run();
	}

	/**
	 * Starts the magic
	 */
	public static function init() {
		$obj = new self( Settings::init() );

		add_action( 'admin_enqueue_scripts', [ $obj, 'manage_assets' ] );

		add_action( 'admin_menu', [ $obj, 'set_admin_menu' ] );

		add_action( 'wp-simple-notify-settings-end', [ $obj, 'handle_main_app' ] );
	}


	/**
	 *
	 */
	public function handle_main_app() {
		wp_enqueue_script( 'main-app', SIMPLE_NOTIFY_PLUGIN_URL . '/src/js/main.js', [ 'vue-resource' ] );

		wp_localize_script( 'main-app', 'wsnConfig', $this->settings->get_config() ?: (object) [] );
		wp_localize_script( 'main-app', 'wsnActions', $this->settings->get_actions() ?: (object) [] );
		wp_localize_script( 'main-app', 'wsnEndpoint', $this->settings->get_endpoints() ?: (object) [] );
		wp_localize_script( 'main-app', 'wsnIsReady', $this->settings->is_setup() );
	}

	/**
	 *
	 */
	public function manage_assets() {
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		wp_enqueue_style( 'boostrap-css', SIMPLE_NOTIFY_PLUGIN_URL . '/src/assets/bootstrap-4.4.1.min.css' );
		wp_enqueue_script( 'bootstrap-js', SIMPLE_NOTIFY_PLUGIN_URL . '/src/assets/bootstrap-4.4.1.min.js' );
		wp_enqueue_script( 'font-awesome', SIMPLE_NOTIFY_PLUGIN_URL . '/src/assets/font-awesome-4.7.0.min.css' );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_enqueue_script( 'vue-js', SIMPLE_NOTIFY_PLUGIN_URL . '/src/assets/vue-dev.js' );
		} else {
			wp_enqueue_script( 'vue-js', SIMPLE_NOTIFY_PLUGIN_URL . '/src/assets/vue@2.6.11.js' );
		}

		wp_enqueue_script( 'vue-resource', SIMPLE_NOTIFY_PLUGIN_URL . '/src/assets/vue-resource@1.5.1.js', [ 'vue-js' ] );
	}

	/**
	 *
	 */
	public function set_admin_menu() {
		add_submenu_page(
			'options-general.php',
			'WP Simple Notify Settings',
			'WP Simple Notify',
			'manage_options',
			self::MENU_SLUG,
			function () {
				include __DIR__ . '/templates/main-settings.php';
			}
		);
	}

	/**
	 * @return bool
	 */
	public function is_plugin_page(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return strpos( $screen->id, self::MENU_SLUG ) !== false;
	}
}