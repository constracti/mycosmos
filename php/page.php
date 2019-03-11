<?php

function failure( string $error = '', ...$args ) {
	if ( !empty( $args ) )
		$error = sprintf( $error, ...$args );
	$page = new page( 'error' );
	$page->add_msg( $error, 'error' );
	$page->html();
}

function redirect( string $url = SITE_URL ) {
	header( 'location: ' . $url );
	exit;
}

class page {

	private $title;
	private $css = [];
	private $js = [];
	private $msg = [];
	private $body = [];

	public function __construct( string $title = '' ) {
		$this->title = $title;
		$this->add_css( 'https://www.w3schools.com/w3css/4/w3.css' );
		$this->add_css( 'https://www.w3schools.com/lib/w3-theme-lime.css' );
		$this->add_css( 'https://use.fontawesome.com/releases/v5.0.8/css/all.css' );
		$this->add_js( 'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js' );
	}

	public function add_css( string $css ) {
		$this->css[] = $css;
	}

	public function add_js( string $js ) {
		$this->js[] = $js;
	}

	public static function msg_color( string $type ): string {
		switch ( $type ) {
			case 'success':
				return 'w3-green';
			case 'info':
				return 'w3-blue';
			case 'warning':
				return 'w3-orange';
			case 'error':
				return 'w3-red';
			default:
				return 'w3-theme';
		}
	}

	public function add_msg( string $html, string $type = '' ) {
		$this->msg[] = [
			'html' => $html,
			'type' => $type,
		];
	}

	public function add_body( callable $func, ...$args ) {
		$this->body[] = [
			'func' => $func,
			'args' => $args,
		];
	}

	public function html() {
		global $mycosmos;
		echo '<html lang="en">' . "\n";
		echo '<head>' . "\n";
		echo '<meta charset="UTF-8" />' . "\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
		echo '<meta name="author" content="constracti" />' . "\n";
		if ( $this->title !== '' )
			echo sprintf( '<title>%s :: %s</title>', SITE_NAME, $this->title ) . "\n";
		else
			echo sprintf( '<title>%s</title>', SITE_NAME ) . "\n";
		echo '<link rel="shortcut icon" type="image/png" href="/favicon.png" />' . "\n";
		foreach ( $this->css as $css )
			echo sprintf( '<link rel="stylesheet" type="text/css" href="%s" />', $css ) . "\n";
		foreach ( $this->js as $js )
			echo sprintf( '<script type="application/javascript" src="%s"></script>', $js ) . "\n";
?>
<style rel="stylesheet" type="text/css">
label, input[type="checkbox"] {
	cursor: pointer;
}
</style>
<?php
		echo '</head>' . "\n";
		echo '<body class="w3-theme-l5">' . "\n";
		if ( !is_null( $mycosmos ) ) {
			echo '<div class="w3-bar w3-card w3-theme">' . "\n";
			$navbar = [
				[
					'name' => 'home',
					'href' => site_href(),
					'icon' => 'fa-home',
				],
				[
					'name' => 'history',
					'href' => site_href( 'history.php' ),
					'icon' => 'fa-history',
				],
				[
					'name' => 'logout',
					'href' => site_href( 'logout.php' ),
					'icon' => 'fa-sign-out-alt',
				],
			];
			foreach ( $navbar as $navbaritem ) {
				echo sprintf( '<a class="w3-bar-item w3-button %s" href="%s">', $navbaritem['name'] === 'logout' ? 'w3-right' : '', $navbaritem['href'] ) . "\n";
				echo sprintf( '<span class="fas %s"></span>', $navbaritem['icon'] ) . "\n";
				echo sprintf( '<span class="w3-hide-small">%s</span>', $navbaritem['name'] ) . "\n";
				echo '</a>' . "\n";
			}
			echo '</div>' . "\n";
		}
		echo '<div class="w3-panel w3-content">' . "\n";
		echo sprintf( '<h1 class="w3-text-theme w3-center" style="margin: 0px;">%s</h1>', $this->title !== '' ? $this->title : SITE_NAME ) . "\n";
		echo '</div>' . "\n";
		foreach ( $this->msg as $msg ) {
			echo '<div class="w3-panel w3-content">' . "\n";
			echo sprintf( '<div class="w3-container w3-round w3-leftbar %s">', self::msg_color( $msg['type'] ) ) . "\n";
			echo sprintf( '<p>%s</p>', $msg['html'] ) . "\n";
			echo '</div>' . "\n";
			echo '</div>' . "\n";
		}
		foreach ( $this->body as $body )
			$body['func'](...$body['args']);
		echo '</body>' . "\n";
		echo '</html>' . "\n";
		exit;
	}
}
