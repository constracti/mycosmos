<?php

require_once( 'php/core.php' );

if ( !is_null( $mycosmos ) )
	$mycosmos->logout();

redirect();
