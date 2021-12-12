<?php

require_once( 'php/index.php' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$task = MCR::get_str( 'task' );
	if ( $task === 'send' ) {
		if ( !$mycosmos->has_login() )
			exit( 'session' );
		$recipients = MCR::post_str( 'recipients' );
		$message = MCR::post_str( 'message' );
		$save = MCR::post_str( 'save', TRUE );
		if ( !is_null( $save ) && $save !== 'on' )
			exit( 'save' );
		$save = $save === 'on';
		$mycosmos->send( $recipients, $message, $save );
		success( [
			'redirect' => '',
		] );
	}
	exit( 'task' );
}

if ( !$mycosmos->has_login() )
	mycosmos::page_login();

$page = new page();

$quota = $mycosmos->get_quota();

$page->add_action( 'body_tag', function( string $quota ): void {
?>
<form class="ajax-form leaf root flex-col w3-card w3-round w3-theme-l4" method="post" action="?task=send" autocomplete="off">
	<label class="leaf">
		<input type="text" class="w3-input" name="recipients" required="required" />
		<span>recipients</span>
	</label>
	<label class="leaf">
		<textarea class="w3-input" id="message" name="message" required="required" rows="10" style="resize: vertical;"></textarea>
		<div class="flex-row flex-justify-between flex-align-center">
			<label for="message">message</label>
			<span>
				<span id="characters">0</span>
				<span class="fas fa-fw fa-font"></span>
			</span>
		</div>
	</label>
	<div class="flex-row flex-justify-between flex-align-center">
		<label class="leaf">
			<input type="checkbox" class="w3-check" name="save" value="on" checked="checked" />
			<span>save</span>
		</label>
		<span id="quota" class="leaf w3-tag w3-round"><?= $quota ?></span>
	</div>
	<div class="flex-row">
		<button class="leaf w3-button w3-round w3-theme" type="submit">
			<span class="fas fa-fw fa-paper-plane"></span>
			<span>send</span>
		</button>
	</div>
</form>
<script>
$(document).on('keyup', '#message', function(event) {
	$('#characters').html($(this).val().length);
});
</script>
<?php
}, $quota );

$page->html();
