<?php

require_once( SITE_DIR . 'php/mycosmos.php' );

class MycosmosSessionException extends Exception {}

class MycosmosSession extends Mycosmos {

	public function __construct() {
		session_start();
		if ( !array_key_exists( 'username', $_SESSION ) )
			throw new MycosmosSessionException();
		$username = $_SESSION['username'];
		if ( !array_key_exists( 'password', $_SESSION ) )
			throw new MycosmosSessionException();
		$password = $_SESSION['password'];
		session_write_close();
		parent::__construct( $username, $password );
	}

	public function logout() {
		session_start();
		$_SESSION = [];
		session_write_close();
	}

	protected function load( string $key ) {
		session_start();
		if ( array_key_exists( $key, $_SESSION ) && is_array( $_SESSION[$key] ) )
			$value = $_SESSION[$key];
		else
			$value = NULL;
		session_write_close();
		return $value;
	}

	protected function save( string $key, $value ) {
		session_start();
		$_SESSION[$key] = $value;
		session_write_close();
	}
}
