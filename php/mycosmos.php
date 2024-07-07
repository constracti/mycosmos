<?php

function curl_safe_init( string|null $url = NULL ): CurlHandle {
	$ch = curl_init( $url );
	if ( $ch === FALSE )
		exit( 'curl_init' );
	return $ch;
}

function curl_safe_setopt( CurlHandle $ch, int $option, $value ): void {
	if ( curl_setopt( $ch, $option, $value ) === FALSE ) {
		$errno = curl_errno( $ch );
		curl_close( $ch );
		exit( 'curl_setopt ' . $option . ': ' . curl_strerror( $errno ) );
	}
}

function curl_safe_exec( CurlHandle $ch ) {
	$ret = curl_exec( $ch );
	if ( $ret === FALSE ) {
		$errno = curl_errno( $ch );
		curl_close( $ch );
		exit( 'curl_exec: ' . curl_strerror( $errno ) );
	}
	return $ret;
}

function curl_safe_getinfo( CurlHandle $ch, int|null $opt = NULL ) {
	$ret = curl_getinfo( $ch, $opt );
	if ( ( is_null( $opt ) || $opt === 0 ) && $ret === FALSE ) {
		$errno = curl_errno( $ch );
		curl_close( $ch );
		exit( 'curl_getinfo ' . $opt . ': ' . curl_strerror( $errno ) );
	}
	return $ret;
}

abstract class Mycosmos {

	const URL_HOME    = 'https://mycosmos.gr/';
	const URL_COMPOSE = 'https://mycosmos.gr/?_task=websms&_action=plugin.websms_compose';
	const URL_SEND    = 'https://mycosmos.gr/?_task=websms&_action=plugin.websms_compose_send';
	const URL_HISTORY = 'https://mycosmos.gr/?_task=websms&_action=plugin.websms_sent';

	const COL_ID        = 0;
	const COL_RECIPIENT = 1;
	const COL_CONTENT   = 2;
	const COL_STATUS    = 3;
	const COL_TIME      = 4;

	private $username;
	private $password;
	protected $cookies;

	public $valid;

	public function __construct( string|null $username, string|null $password ) {
		$this->username = $username;
		$this->password = $password;
		$this->cookies = $this->get_value( 'cookies' );
		$this->valid = FALSE;
		$this->do_login();
	}

	public function has_login(): bool {
		return $this->valid;
	}

	public function do_logout(): void {
		$this->valid = FALSE;
	}

	abstract public function get_value( string $key );

	abstract public function set_value( string $key, $value = NULL ): void;

	private function serialize_cookies(): string {
		$cookies = [];
		foreach ( $this->cookies ?? [] as $name => $value )
			$cookies[] = $name . '=' . $value;
		return implode( '; ', $cookies );
	}

	private function update_cookies( string $header ): void {
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
		$this->set_value( 'cookies', $this->cookies );
	}

	private function curl_init( string $url ): CurlHandle {
		$ch = curl_safe_init( $url );
		curl_safe_setopt( $ch, CURLOPT_HEADER, TRUE );
		curl_safe_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_safe_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE ); // TODO update certificates
		curl_safe_setopt( $ch, CURLOPT_COOKIE, $this->serialize_cookies() );
		return $ch;
	}

	private function curl_exec( $ch ): DOMXPath {
		$result = curl_safe_exec( $ch );
		$code = curl_safe_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( $code === 302 ) {
			$url = curl_safe_getinfo( $ch, CURLINFO_REDIRECT_URL );
			curl_close( $ch );
			$this->update_cookies( $result );
			$ch = $this->curl_init( $url );
			$result = curl_safe_exec( $ch );
		}
		curl_close( $ch );
		$pos = mb_strpos( $result, '<!DOCTYPE html>' );
		$header = mb_substr( $result, 0, $pos );
		$this->update_cookies( $header );
		$html = mb_substr( $result, $pos );
		$doc = new DOMDocument();
		$level = error_reporting();
		if ( $level & E_WARNING )
			error_reporting( $level & ~E_WARNING );
		$bool = $doc->loadHTML( $html );
		error_reporting( $level );
		if ( $bool === FALSE )
			exit( 'html' );
		$xpath = new DOMXPath( $doc );
		if ( is_null( $xpath ) )
			exit( 'xpath' );
		return $xpath;
	}

	private static function xpath_has_login( DOMXPath $xpath ): bool {
		return $xpath->query( '//*[@id="taskmenu"]' )->length !== 0;
	}

	private static function xpath_get_token( DOMXPath $xpath ): string|null {
		$nodelist = $xpath->query( '//input[@name="_token"]' );
		if ( $nodelist->length !== 1 )
			return NULL;
		return $nodelist->item( 0 )->getAttribute( 'value' );
	}

	private static function xpath_get_quota( DOMXPath $xpath ): string|null {
		$nodelist = $xpath->query( '//*[@id="sms_quota"]' );
		if ( $nodelist->length !== 1 )
			return NULL;
		return $nodelist->item( 0 )->nodeValue;
	}

	public function do_login(): bool {
		// true
		if ( $this->has_login() )
			return TRUE;
		// cookies
		$ch = $this->curl_init( self::URL_HOME );
		$xpath = $this->curl_exec( $ch );
		if ( self::xpath_has_login( $xpath ) ) {
			$this->valid = TRUE;
			return TRUE;
		}
		// form
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$task = MCR::get_str( 'task', TRUE );
			if ( $task === 'login' ) {
				$this->set_value( 'username', MCR::post_str( 'username' ) );
				$this->set_value( 'password', MCR::post_str( 'password' ) );
				success( [
					'redirect' => NULL,
				] );
			}
		}
		// credentials
		if ( !is_null( $this->username ) && !is_null( $this->password ) ) {
			$token = self::xpath_get_token( $xpath );
			$ch = $this->curl_init( self::URL_HOME );
			curl_safe_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
			curl_safe_setopt( $ch, CURLOPT_POSTFIELDS, [
				'_token'  => $token,
				'_action' => 'login',
				'_user'   => $this->username,
				'_pass'   => $this->password,
			] );
			$xpath = $this->curl_exec( $ch );
			if ( self::xpath_has_login( $xpath ) ) {
				$this->valid = TRUE;
				return TRUE;
			}
		}
		// false
		return FALSE;
	}

	public static function page_login(): void {
		$page = new page();
		$page->add_action( 'body_tag', function(): void {
?>
<form class="ajax-form leaf root flex-col w3-card w3-round w3-theme-l4" method="post" action="?task=login" autocomplete="off">
	<label class="leaf">
		<span>username</span>
		<br />
		<input type="text" class="w3-input" name="username" required="required" />
	</label>
	<label class="leaf">
		<span>password</span>
		<br />
		<input type="password" class="w3-input" name="password" required="required" />
	</label>
	<div class="flex-row">
		<button class="leaf w3-button w3-round w3-theme" type="submit">
			<span class="fas fa-fw fa-sign-in-alt"></span>
			<span>login</span>
		</button>
	</div>
</form>
<?php
		} );
		$page->html();
	}

	public function get_quota(): string|null {
		if ( !$this->has_login() )
			return NULL;
		$ch = $this->curl_init( self::URL_COMPOSE );
		$xpath = $this->curl_exec( $ch );
		return self::xpath_get_quota( $xpath );
	}

	public function send( string $recipients, string $message, bool $save ): void {
		if ( !$this->has_login() )
			exit( 'session' );
		$ch = $this->curl_init( self::URL_COMPOSE );
		$xpath = $this->curl_exec( $ch );
		$token = self::xpath_get_token( $xpath );
		$quota_prev = self::xpath_get_quota( $xpath );
		$ch = $this->curl_init( self::URL_SEND );
		curl_safe_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_safe_setopt( $ch, CURLOPT_POSTFIELDS, [
			'_token'    => $token,
			'_to'       => $recipients,
			'_message'  => $message,
			'_save_sms' => $save,
			'_date'     => '', # TODO date
		] );
		$xpath = $this->curl_exec( $ch );
		$quota = self::xpath_get_quota( $xpath );
		if ( $quota === $prev_quota )
			exit( 'send' );
	}

	public function history(): array {
		if ( !$this->has_login() )
			exit( 'session' );
		$history = $this->get_value( 'history' );
		if ( !is_null( $history ) )
			return $history;
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
		usort( $rows, function( $a, $b ): int {
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
		$this->set_value( 'history', $history );
		return $history;
	}
}
