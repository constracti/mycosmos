<?php

define( 'SITE_NAME', 'mycosmos' );
define( 'SITE_URL', 'https://mycosmos.raktivan.gr/' );
define( 'SITE_DIR', '/var/www/vhosts/raktivan.gr/mycosmos/' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' )
	require_once( SITE_DIR . 'php/ajax.php' );
else
	require_once( SITE_DIR . 'php/page.php' );

function site_href( string $url = '', array $parameters = [] ): string {
	if ( !empty( $parameters ) )
		$url .= '?' . http_build_query( $parameters );
	return SITE_URL . $url;
}


/***********
 * filters *
 ***********/

function filter_int( string $var ) {
	$var = filter_var( $var, FILTER_VALIDATE_INT );
	if ( $var === FALSE )
		return NULL;
	return $var;
}

function filter_text( string $var ) {
	$var = strip_tags( $var );
	$var = preg_replace( '/\s+/', ' ', $var );
	$var = trim( $var );
	if ( $var === '' )
		return NULL;
	return $var;
}

function filter_email( string $var ) {
	$var = filter_var( $var, FILTER_VALIDATE_EMAIL );
	if ( $var === FALSE )
		return NULL;
	return $var;
}

function filter_regexp( string $var, string $regexp ) {
	$var = filter_var( $var, FILTER_VALIDATE_REGEXP, [
		'options' => [
			'regexp' => '/^' . $regexp . '$/',
		],
	] );
	if ( $var === FALSE )
		return NULL;
	return $var;
}


/***********
 * request *
 ***********/

function request_bool( string $key ): bool {
	if ( !array_key_exists( $key, $_REQUEST ) )
		return FALSE;
	$var = $_REQUEST[ $key ];
	if ( is_null( $var ) || $var === '' )
		return FALSE;
	return TRUE;
}

function request_var( string $key, bool $nullable = FALSE ) {
	if ( request_bool( $key ) )
		return $_REQUEST[ $key ];
	if ( $nullable )
		return NULL;
	failure( 'argument %s not defined', $key );
}

function request_int( string $key, bool $nullable = FALSE ) {
	$var = request_var( $key, $nullable );
	if ( is_null( $var ) )
		return NULL;
	$var = filter_int( $var );
	if ( !is_null( $var ) )
		return $var;
	failure( 'argument %s not valid', $key );
}


/***********
 * session *
 ***********/

require_once( SITE_DIR . 'php/mycosmos-session.php' );

try {
	$mycosmos = new MycosmosSession();
} catch ( MycosmosSessionException $e ) {
	$mycosmos = NULL;
}
