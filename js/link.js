$( function() {

$( '.link-ajax' ).click( function() {
	var fa = $( this ).find( '.fas' );
	if ( fa.hasClass( 'fa-pulse' ) )
		return false;
	fa.data( 'class', fa.prop( 'class' ) ).prop( 'class', 'fas fa-fw fa-spinner fa-pulse' );
	$.post( $( this ).prop( 'href' ) ).done( function( data, textStatus, jqXHR ) {
		if ( typeof( data ) === 'object' ) {
			if ( data.hasOwnProperty( 'redirect' ) )
				location.href = data.redirect;
		} else {
			alert( data );
		}
	} ).fail( function( jqXHR, textStatus, errorThrown ) {
		alert( jqXHR.statusText  + ' ' + jqXHR.status );
	} ).always( function() {
		fa.prop( 'class', fa.data( 'class' ) );
	} );
	return false;
} );

} );
