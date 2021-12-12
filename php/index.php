<?php

define( 'SITE_NAME', 'mycosmos' );
define( 'SITE_URL', sprintf( '%s://%s/', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'] ) );

foreach ( glob( __DIR__  . '/*' ) as $file ) {
	if ( $file !== __FILE__ )
		require_once( $file );
}

function success( array $array = [] ): void {
	header( 'content-type: application/json' );
	exit( json_encode( $array ) );
}

function site_href( string $url = '', array $parameters = [] ): string {
	if ( !empty( $parameters ) )
		$url .= '?' . http_build_query( $parameters );
	return SITE_URL . $url;
}

function redirect( string $url = SITE_URL ): void {
	header( 'location: ' . $url );
	exit;
}

$mycosmos = new MycosmosSession();
