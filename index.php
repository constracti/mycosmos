<?php

require_once( 'php/core.php' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$task = request_var( 'task' );
	switch ( $task ) {
		case 'login':
			$username = request_var( 'username' );
			$password = request_var( 'password' );
			session_start();
			$_SESSION['username'] = $username;
			$_SESSION['password'] = $password;
			session_write_close();
			success( [
				'redirect' => '',
			] );
		case 'send':
			if ( is_null( $mycosmos ) )
				failure( 'send: login required' );
			$recipients = request_var( 'recipients' );
			$message = request_var( 'message' );
			$save = request_bool( 'save' );
			$mycosmos->send( $recipients, $message, $save );
			success( [
				'quota' => $mycosmos->get_quota(),
				'clear' => TRUE,
			] );
		default:
			failure( 'task not valid' );
	}
}

$page = new page();

if ( is_null( $mycosmos ) ) {

$page->add_body( function() {
?>
<div class="w3-panel w3-content">
	<form class="form-ajax w3-container w3-card w3-round w3-theme-l4" method="post" autocomplete="off">
		<input type="hidden" name="task" value="login" />
		<div class="w3-section">
			<input id="username" class="w3-input" type="text" name="username" required="required" />
			<label for="username">username</label>
		</div>
		<div class="w3-section">
			<input id="password" class="w3-input" type="password" name="password" required="required" />
			<label for="password">password</label>
		</div>
		<div class="w3-section">
			<button class="w3-button w3-round w3-theme" type="submit">
				<span class="fas fa-fw fa-sign-in-alt"></span>
				<span>login</span>
			</button>
			<button id="clear" class="w3-button w3-round w3-right w3-theme" type="button">
				<span class="fas fa-fw fa-ban"></span>
				<span>clear</span>
			</button>
		</div>
	</form>
</div>
<script>
$( function() {

$( '#clear' ).click( function() {
	$( '#username, #password' ).val( '' );
} );

} );
</script>
<?php
} );

} else {

$quota = $mycosmos->get_quota();

$page->add_body( function( string $quota ) {
?>
<div class="w3-panel w3-content">
	<form class="form-ajax w3-container w3-card w3-round w3-theme-l4" method="post" autocomplete="off">
		<input type="hidden" name="task" value="send" />
		<div class="w3-section">
			<input id="recipients" class="w3-input" type="text" name="recipients" required="required" />
			<label for="recipients">recipients</label>
		</div>
		<div class="w3-section">
			<textarea id="message" class="w3-input" name="message" required="required" style="resize: vertical;"></textarea>
			<label for="message">message</label>
			<span class="w3-right">
				<span id="characters"></span>
				<span class="fas fa-font"></span>
			</span>
		</div>
		<div class="w3-section">
			<input id="save" class="w3-check" type="checkbox" name="save" checked="checked" />
			<label for="save">save</label>
			<span class="w3-right">
				<span id="quota" class="w3-tag w3-round"><?= $quota ?></span>
			</span>
		</div>
		<div class="w3-section">
			<button class="w3-button w3-round w3-theme" type="submit">
				<span class="fas fa-fw fa-paper-plane"></span>
				<span>send</span>
			</button>
			<button id="clear" class="w3-button w3-round w3-right w3-theme" type="button">
				<span class="fas fa-ban"></span>
				<span>clear</span>
			</button>
		</div>
	</form>
</div>
<script>
$( function() {

$( '#message' ).keyup( function() {
	$( '#characters' ).html( $( this ).val().length );
} ).keyup();

$( '#clear' ).click( function() {
	$( '#recipients' ).val( '' );
	$( '#message' ).val( '' ).keyup();
} );

} );
</script>
<?php
}, $quota );

}

$page->add_js( site_href( 'js/form.js' ) );

$page->html();
