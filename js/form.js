$( function() {

$( '.form-ajax' ).submit( function() {
	var btn = $( this ).find( 'button[type="submit"]' );
	var fa = btn.find( '.fas' );
	if ( fa.hasClass( 'fa-pulse' ) )
		return false;
	fa.data( 'class', fa.prop( 'class' ) ).prop( 'class', 'fas fa-fw fa-spinner fa-pulse' );
	btn.prop( 'disabled', true );
	$.post( $( this ).prop( 'action' ), $( this ).serialize() ).done( function( data, textStatus, jqXHR ) {
		if ( typeof( data ) === 'object' ) {
			if ( data.hasOwnProperty( 'quota' ) )
				$( '#quota' ).html( data.quota );
			if ( data.hasOwnProperty( 'clear' ) && data.clear )
				$( '#clear' ).click();
			if ( data.hasOwnProperty( 'redirect' ) )
				location.href = data.redirect;
		} else {
			alert( data );
		}
	} ).fail( function( jqXHR, textStatus, errorThrown ) {
		alert( jqXHR.statusText  + ' ' + jqXHR.status );
	} ).always( function() {
		btn.prop( 'disabled', false );
		fa.prop( 'class', fa.data( 'class' ) );
	} );
	return false;
} );

} );
