<?php

if ( !class_exists( 'Mycosmos' ) )
	require_once( __DIR__ . '/mycosmos.php' );

final class MycosmosSession extends Mycosmos {

	public function __construct() {
		$username = $this->get_value( 'username' );
		$password = $this->get_value( 'password' );
		parent::__construct( $username, $password );
	}

	public function do_logout(): void {
		session_start();
		session_destroy();
		parent::do_logout();
	}

	public function get_value( string $key ) {
		session_start();
		$value = array_key_exists( $key, $_SESSION ) ? $_SESSION[$key] : NULL;
		session_abort();
		return $value;
	}

	public function set_value( string $key, $value = NULL ): void {
		session_start();
		if ( !is_null( $value ) )
			$_SESSION[$key] = $value;
		else
			unset( $_SESSION[$key] );
		session_commit();
	}
}
