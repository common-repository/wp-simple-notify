<?php


namespace SimpleNotify;

require_once( ABSPATH . WPINC . '/class-phpmailer.php' );

class Email {

	/**
	 * @var \PHPMailer
	 */
	private $mailer;

	/**
	 * Email constructor.
	 *
	 * @param array $config
	 */
	public function __construct( array $config ) {
		$this->mailer          = new \PHPMailer();
		$this->mailer->CharSet = 'UTF-8';
		$this->mailer->IsHTML( true );
		$this->mailer->IsSMTP();

		$this->mailer->SMTPAuth   = ! empty( $config['smtp_user'] );
		$this->mailer->From       = $config['email_from'];
		$this->mailer->FromName   = $config['sender'];
		$this->mailer->Host       = $config['host'];
		$this->mailer->Port       = $config['port'];
		$this->mailer->SMTPSecure = $config['secure'];
		$this->mailer->Username   = $config['smtp_user'] ?: $config['email_from'];
		$this->mailer->Password   = $config[ Settings::DEFINED_PWD_VAR ] ? WSN_EMAIL_PWD : $config['smtp_pwd'] ?: $config['email_pwd'];
	}

	/**
	 * @param string $address
	 * @param string $subject
	 * @param string $body
	 *
	 * @return bool
	 */
	public function send( string $address, string $subject, string $body ) {
		$this->mailer->AddAddress( $address );
		$this->mailer->Subject = $subject;
		$this->mailer->Body    = $body;

		try {
			return $this->mailer->Send();
		} catch ( \Exception $ex ) {
			return false;
		}
	}

	/**
	 * @param int $debug_level
	 *
	 * @return $this
	 */
	public function setDebug( int $debug_level ) {
		$this->mailer->SMTPDebug = $debug_level;

		return $this;
	}

	public function setHTML( bool $html ) {
		$this->mailer->isHTML( $html );

		return $this;
	}
}