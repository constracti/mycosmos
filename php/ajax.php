<?php

function success( array $array = [] ) {
	header( 'content-type: application/json' );
	exit( json_encode( $array ) );
}

function failure( string $error = '', ...$args ) {
	if ( !empty( $args ) )
		$error = sprintf( $error, ...$args );
	$error = str_replace( '<br />', "\n", $error );
	$error = strip_tags( $error );
	exit( $error );
}
