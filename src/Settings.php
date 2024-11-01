<?php


namespace SimpleNotify;


/**
 * Class Settings
 * @package SimpleNotify
 */
class Settings {

	const ENDPOINT_SAVE_CONFIG = '/save';

	const ENDPOINT_SET_ACTION = '/action';

	const ENDPOINT_TEST_EMAIL = '/test';

	const OPTION_CONFIG_NAME = Bootstrap::PLUGIN_NAME . '-config';

	const OPTION_ACTION_NAME = Bootstrap::PLUGIN_NAME . '-actions';

	const DEFINED_PWD_VAR = 'DEFINED_PWD';

	/**
	 *
	 */
	const PLUGIN_ACTIONS = [
		'comment_for_author' => 'Notify new comments to post author',
		'comment_for_user'   => 'Notify new replies to visitor\'s comments',
	];

	/**
	 * @var array
	 */
	private $stored_data;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		$this->stored_data = [
			'config'  => get_option( self::OPTION_CONFIG_NAME, [] ),
			'actions' => get_option( self::OPTION_ACTION_NAME, [] ),
		];
	}

	/**
	 * @return Settings
	 */
	public static function init() {
		$obj = new self();

		add_action( 'rest_api_init', [ $obj, 'register_rest_route' ] );

		return $obj;
	}

	/**
	 *
	 */
	public function register_rest_route() {
		register_rest_route( Bootstrap::PLUGIN_NAME, self::ENDPOINT_SAVE_CONFIG,
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'save' ],
			] );

		register_rest_route( Bootstrap::PLUGIN_NAME, self::ENDPOINT_SET_ACTION,
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'switch' ],
			] );

		register_rest_route( Bootstrap::PLUGIN_NAME, self::ENDPOINT_TEST_EMAIL,
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'test_email' ],
			] );
	}

	/**
	 * @return array
	 */
	public function get_endpoints(): array {
		return [
			'save'   => '/wp-json/' . trim( Bootstrap::PLUGIN_NAME, '\\/' ) . '/' . ltrim( self::ENDPOINT_SAVE_CONFIG, '/' ),
			'action' => '/wp-json/' . trim( Bootstrap::PLUGIN_NAME, '\\/' ) . '/' . ltrim( self::ENDPOINT_SET_ACTION, '/' ),
			'test'   => '/wp-json/' . trim( Bootstrap::PLUGIN_NAME, '\\/' ) . '/' . ltrim( self::ENDPOINT_TEST_EMAIL, '/' )
		];
	}

	/**
	 * @return array
	 */
	public function get_config(): array {
		return [ self::DEFINED_PWD_VAR => defined( 'WSN_EMAIL_PWD' ) && ! empty( WSN_EMAIL_PWD ) ] + $this->stored_data['config'];
	}

	/**
	 * @return array
	 */
	public function get_actions() {
		$data = [];
		foreach ( self::PLUGIN_ACTIONS as $action => $description ) {
			$data[] = [
				'key'    => $action,
				'text'   => $description,
				'active' => $this->stored_data['actions'][ $action ]['active'] ?? 0
			];
		}

		return $data;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function save( \WP_REST_Request $request ) {
		$request_data = $this->get_request_data( $request->get_body_params() );

		if ( empty( $request_data ) ) {
			return new \WP_REST_Response( 'Please check again your entries and try again.', 500 );
		}

		update_option( self::OPTION_CONFIG_NAME, $request_data );

		return new \WP_REST_Response( 'Settings successfully saved!' );
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	private function get_request_data( array $params ): array {
		$config = [
			'email_from' => sanitize_email( $params['email_from'] ),
			'email_pwd'  => sanitize_text_field( $params['email_pwd'] ),
			'sender'     => sanitize_text_field( $params['sender'] ),
			'host'       => sanitize_text_field( $params['host'] ),
			'port'       => sanitize_text_field( $params['port'] ),
			'secure'     => sanitize_text_field( $params['secure'] ),
		];

		foreach ( $config as $key => $value ) {
			if ( isset( $params[ $key ] ) && empty( $value ) ) {
				return [];
			}
		}

		$config['smtp_user'] = sanitize_text_field( $params['smtp_user'] );
		$config['smtp_pwd']  = sanitize_text_field( $params['smtp_pwd'] );

		return $config;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function switch( \WP_REST_Request $request ) {
		$action = $this->validate_action( $request->get_body_params() );

		if ( empty( $action ) ) {
			return new \WP_REST_Response( 'There was an error saving your request.', 500 );
		}

		if ( ! $this->is_setup() ) {
			return new \WP_REST_Response( 'Please setup all email config before continue', 500 );
		}

		$data = array_merge( $this->stored_data['actions'], $action );

		update_option( self::OPTION_ACTION_NAME, $data );

		return new \WP_REST_Response( 'Action updated successfully!' );
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	private function validate_action( array $params ): array {
		if ( ! isset( self::PLUGIN_ACTIONS[ $params['key'] ] ) ) {
			return [];
		}

		$status = ! (bool) $params['active'];

		return [
			$params['key'] => [ 'active' => $status ? 1 : 0 ]
		];
	}

	/**
	 * @return bool
	 */
	public function is_setup(): bool {
		return ! empty( $this->stored_data['config'] );
	}

	public function test_email() {
		if ( ! $this->is_setup() ) {
			return new \WP_REST_Response( 'Please complete the configuration before testing', 500 );
		}

		$email = new Email( $this->get_config() );
		$email->setDebug( 2 );

		$subject = 'Email testing';
		$address = $this->stored_data['config']['email_from'];
		$body    = 'This was an automatic test from ' . Bootstrap::PLUGIN_NAME . ' plugin
					<p>Site: <a href = "' . home_url() . '">' . home_url() . '</a></p>';

		if ( ! $email->send( $address, $subject, $body ) ) {
			return new \WP_REST_Response( 'Email not sent, check again your configuration', 500 );
		}

		return new \WP_REST_Response( 'Email test successful' );
	}
}