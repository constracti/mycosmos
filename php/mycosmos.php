<?php

function curl_safe_init( string $url = '' ) {
	$ch = curl_init( $url );
	if ( $ch === FALSE )
		exit( 'curl_init' );
	return $ch;
}

function curl_safe_setopt( $ch, int $option, $value ) {
	$ret = curl_setopt( $ch, $option, $value );
	if ( $ret === FALSE )
		exit( 'curl_setopt: ' . $option );
	return $ret;
}

function curl_safe_exec( $ch ) {
	$ret = curl_exec( $ch );
	if ( $ret === FALSE )
		exit( 'curl_exec' );
	return $ret;
}

function curl_safe_getinfo( $ch, int $opt = 0 ) {
	$ret = curl_getinfo( $ch, $opt );
	if ( $opt === 0 && $ret === FALSE )
		exit( 'curl_getinfo' );
	return $ret;
}

abstract class Mycosmos {

	const URL_HOME    = 'https://mycosmos.gr/';
	const URL_COMPOSE = 'https://www.mycosmos.gr/?_action=plugin.websms_compose';
	const URL_SEND    = 'https://www.mycosmos.gr/?_action=plugin.websms_compose_send';
	const URL_HISTORY = 'https://www.mycosmos.gr/?_action=plugin.websms_sent';

	const COL_ID        = 0;
	const COL_RECIPIENT = 1;
	const COL_CONTENT   = 2;
	const COL_STATUS    = 3;
	const COL_TIME      = 4;

	private $username;
	private $password;
	protected $cookies;

	private $has_login = FALSE;
	private $xpath = NULL;

	public function __construct( string $username, string $password ) {
		$this->username = $username;
		$this->password = $password;
		$this->cookies = $this->load( 'cookies' );
	}

	public function logout() {}

	abstract protected function load( string $key );

	abstract protected function save( string $key, $value );

	private function serialize_cookies(): string {
		$cookies = [];
		foreach ( $this->cookies ?? [] as $name => $value )
			$cookies[] = $name . '=' . $value;
		return implode( '; ', $cookies );
	}

	private function update_cookies( string $header ) {
		if ( is_null( $this->cookies ) )
			$this->cookies = [];
		$lines = explode( "\n", $header );
		foreach ( $lines as $line ) {
			if ( mb_strpos( $line, 'Set-Cookie: ' ) === 0 ) {
				$cookie = explode( '; ', mb_substr( $line, mb_strlen( 'Set-Cookie: ' ) ) )[0];
				$pos = mb_strpos( $cookie, '=' );
				$name = mb_substr( $cookie, 0, $pos );
				$value = mb_substr( $cookie, $pos + 1 );
				if ( $value !== '-del-' )
					$this->cookies[ $name ] = $value;
				else
					unset( $this->cookies[ $name ] );
			}
		}
		$this->save( 'cookies', $this->cookies );
	}

	private function curl_init( string $url ) {
		$ch = curl_safe_init( $url );
		curl_safe_setopt( $ch, CURLOPT_HEADER, TRUE );
		curl_safe_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_safe_setopt( $ch, CURLOPT_COOKIE, $this->serialize_cookies() );
		return $ch;
	}

	private function curl_exec( $ch ) {
		$this->xpath = NULL;
		$result = curl_safe_exec( $ch );
		$code = curl_safe_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( $code === 302 ) {
			$url = curl_safe_getinfo( $ch, CURLINFO_REDIRECT_URL );
			curl_close( $ch );
			$this->update_cookies( $result );
			$ch = $this->curl_init( $url );
			return $this->curl_exec( $ch );
		}
		curl_close( $ch );
		$pos = mb_strpos( $result, '<!DOCTYPE html>' );
		$header = mb_substr( $result, 0, $pos );
		$this->update_cookies( $header );
		$html = mb_substr( $result, $pos );
		$doc = new DOMDocument();
		$level = error_reporting();
		if ( $level & E_WARNING )
			error_reporting( $level - E_WARNING );
		$bool = $doc->loadHTML( $html );
		error_reporting( $level );
		if ( $bool === FALSE )
			exit( 'html' );
		$xpath = new DOMXPath( $doc );
		if ( is_null( $xpath ) )
			exit( 'xpath' );
		$this->xpath = $xpath;
		return $xpath;
	}

	private function xpath_has_login( $xpath = NULL ): bool {
		if ( is_null( $xpath ) )
			$xpath = $this->xpath;
		return $xpath->query( '//*[@id="login-area"]' )->length !== 0;
	}

	private function xpath_get_token( $xpath = NULL ): string {
		if ( is_null( $xpath ) )
			$xpath = $this->xpath;
		$nodelist = $xpath->query( '//input[@name="_token"]' );
		if ( $nodelist->length !== 1 )
			exit( 'token' );
		return $nodelist->item( 0 )->getAttribute( 'value' );
	}

	private function xpath_get_quota( $xpath = NULL ): string {
		if ( is_null( $xpath ) )
			$xpath = $this->xpath;
		$nodelist = $xpath->query( '//*[@id="sms_quota"]' );
		if ( $nodelist->length !== 1 )
			exit( 'quota' );
		return $nodelist->item( 0 )->nodeValue;
	}

	public function login() {
		if ( $this->has_login )
			return;
		// cookies login
		$ch = $this->curl_init( self::URL_HOME );
		$this->curl_exec( $ch );
		if ( $this->xpath_has_login() ) {
			$this->has_login = TRUE;
			return;
		}
		// credentials login
		$token = $this->xpath_get_token();
		$ch = $this->curl_init( self::URL_HOME );
		curl_safe_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_safe_setopt( $ch, CURLOPT_POSTFIELDS, [
			'_token'  => $token,
			'_action' => 'login',
			'_user'   => $this->username,
			'_pass'   => $this->password,
		] );
		$this->curl_exec( $ch );
		if ( $this->xpath_has_login() ) {
			$this->has_login = TRUE;
			return;
		} else
			exit( 'login' );
	}

	public function get_quota(): string {
		$this->login();
		$ch = $this->curl_init( self::URL_COMPOSE );
		$this->curl_exec( $ch );
		return $this->xpath_get_quota();
	}

	private function get_compose_token(): string {
		$ch = $this->curl_init( self::URL_COMPOSE );
		$this->curl_exec( $ch );
		return $this->xpath_get_token();
	}

	public function send( string $recipients, string $message, bool $save ) {
		$this->login();
		$token = $this->get_compose_token();
		$quota_prev = $this->xpath_get_quota();
		$ch = $this->curl_init( self::URL_SEND );
		curl_safe_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_safe_setopt( $ch, CURLOPT_POSTFIELDS, [
			'_token'    => $token,
			'_to'       => $recipients,
			'_message'  => $message,
			'_save_sms' => $save,
			'_date'     => '', # TODO date
		] );
		$this->curl_exec( $ch );
		$quota = $this->xpath_get_quota();
		if ( $quota === $prev_quota )
			exit( 'send' );
	}

	public function history(): array {
		$history = $this->load( 'history' );
		if ( !is_null( $history ) )
			return $history;
		$this->login();
		$ch = $this->curl_init( self::URL_HISTORY );
		$xpath = $this->curl_exec( $ch );
		// table
		$nodelist = $xpath->query( '//table[@id="websms-sms-list"]' );
		if ( $nodelist->length !== 1 )
			exit( 'table' );
		$table = $nodelist->item( 0 );
		// cols
		$cols = [];
		foreach ( $xpath->query( 'thead/tr/th', $table ) as $th )
			$cols[] = $th->nodeValue;
		$rows = [];
		foreach ( $xpath->query( 'tbody/tr', $table ) as $tr ) {
			$row = [];
			foreach ( $tr->getElementsByTagName( 'td' ) as $key => $td ) {
				$value = $td->nodeValue;
				if ( $key === self::COL_ID )
					$value = intval( $value );
				$row[] = $value;
			}
			$rows[] = $row;
		}
		usort( $rows, function( $a, $b ) {
			return $b[self::COL_ID] <=> $a[self::COL_ID];
		} );
		$navs = [];
		$prev = NULL;
		foreach ( $rows as $key => $row ) {
			if ( $row[self::COL_TIME] !== '' ) {
				$dt = new DateTime( $row[self::COL_TIME] );
				$curr = $dt->format( 'Y-m' );
				if ( $curr !== $prev ) {
					$navs[$curr] = $key;
					$prev = $curr;
				}
			} else if ( is_null( $prev ) ) {
				$dt = new DateTime();
				$prev = $dt->format( 'Y-m' );
				$navs[$prev] = $key;
			}
		}
		$history = [
			'navs' => $navs,
			'cols' => $cols,
			'rows' => $rows,
		];
		$this->save( 'history', $history );
		return $history;
	}
}
