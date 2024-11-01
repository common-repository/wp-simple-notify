<?php

namespace SimpleNotify;

/**
 * Class Controller
 * @package SimpleNotify
 */
class Controller {
	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * Controller constructor.
	 *
	 * @param Settings $settings
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Put everything to work now
	 */
	public function run() {
		foreach ( array_keys( Settings::PLUGIN_ACTIONS ) as $action_key ) {
			if ( ! $this->is_active( $action_key ) ) {
				continue;
			}

			switch ( $action_key ) {
				case 'comment_for_author':
					add_action( 'comment_post', [ $this, 'notify_comment_author' ], 10, 3 );
					break;
				case 'comment_for_user':
					add_action( 'comment_post', [ $this, 'notify_comment_user' ], 10, 3 );
					break;
			}
		}
	}

	/**
	 * @param string $action
	 *
	 * @return bool
	 */
	private function is_active( string $action ): bool {
		$finder = array_filter( $this->settings->get_actions(), function ( $item ) use ( $action ) {
			return $item['key'] === $action && $item['active'];
		} );

		return ! empty( $finder );
	}

	/**
	 * @param $comment_ID
	 * @param $comment_approved
	 * @param $commentdata
	 *
	 */
	public function notify_comment_author( $comment_ID, $comment_approved, $commentdata ) {
		$post = get_post( $commentdata['comment_post_ID'] );
		if ( ! $post || $post->post_author == $commentdata['user_id'] ) {
			return;
		}

		$author = get_userdata( $post->post_author );
		if ( ! $author->user_email ) {
			return;
		}

		$link    = get_permalink( $post->ID );
		$subject = 'New comment for: ' . $post->post_title;
		$message = '<strong>Comment:</strong><p>' . $commentdata['comment_content'] . '</p>';
		$this->send_email( $author->user_email, $subject, $message, $link );
	}

	/**
	 * @param $comment_ID
	 * @param $comment_approved
	 * @param $commentdata
	 *
	 */
	public function notify_comment_user( $comment_ID, $comment_approved, $commentdata ) {
		if ( ! $commentdata['user_id'] || ! $commentdata['comment_parent'] ) {
			return;
		}

		$comment_parent = get_comment( $commentdata['comment_parent'] );
		if ( ! $comment_parent ) {
			return;
		}

		$post = get_post( $comment_parent->comment_post_ID );
		if ( ! $post || $post->post_author == $comment_parent->user_id || ! $comment_parent->comment_author_email ) {
			return;
		}

		$link    = get_permalink( $post->ID );
		$subject = 'New reply in: ' . $post->post_title;
		$message = '<strong>Comment:</strong><p>' . $commentdata['comment_content'] . '</p>';
		$this->send_email( $comment_parent->comment_author_email, $subject, $message, $link );
	}

	/**
	 * @param string $address
	 * @param string $subject
	 * @param string $message
	 * @param string $post_link
	 *
	 * @return bool
	 */
	public function send_email( string $address, string $subject, string $message, string $post_link ) {
		$email = new Email( $this->settings->get_config() );

		$subject = get_bloginfo() . ' - ' . $subject;
		$message = $message . '<p>Post link: <a href = "' . $post_link . '">' . $post_link . '</a></p>';

		try {
			return $email->send( $address, $subject, $message );
		} catch ( \Exception $ex ) {
			return false;
		}
	}
}