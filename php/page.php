<?php

class page {

	private $title;
	private $action_list;

	public function __construct( string $title = '' ) {
		$this->title = $title;
		$this->action_list = [];
		// https://www.w3schools.com/w3css/w3css_downloads.asp
		$this->add_action( 'head_tag', [ 'page', 'echo_style_tag' ], 'https://www.w3schools.com/w3css/4/w3.css' );
		$this->add_action( 'head_tag', [ 'page', 'echo_style_tag' ], 'https://www.w3schools.com/lib/w3-theme-lime.css' );
		$this->add_action( 'head_tag', [ 'page', 'echo_style_tag' ], site_href( 'flex.css' ) );
		// https://fontawesome.com/
		$this->add_action( 'head_tag', [ 'page', 'echo_style_tag' ], 'https://use.fontawesome.com/releases/v5.0.8/css/all.css' );
		// https://jquery.com/download/
		$this->add_action( 'head_tag', [ 'page', 'echo_script_tag' ], 'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js' );
		$this->add_action( 'head_tag', [ 'page', 'echo_script_tag' ], site_href( 'ajax.js' ) );
	}

	// action

	public function add_action( string $key, callable $fn, ...$args ): void {
		if ( !array_key_exists( $key, $this->action_list ) )
			$this->action_list[$key] = [];
		$action = [
			'fn' => $fn,
			'args' => $args,
		];
		$this->action_list[$key][] = $action;
	}

	function do_action( string $key ): void {
		if ( !array_key_exists( $key, $this->action_list ) )
			return;
		foreach ( $this->action_list[$key] as $action )
			$action['fn']( ...$action['args'] );
	}

	public static function echo_style_tag( string $href ): void {
		echo sprintf( '<link rel="stylesheet" type="text/css" href="%s" />', $href ) . "\n";
	}

	public static function echo_script_tag( string $src ): void {
		echo sprintf( '<script src="%s"></script>', $src ) . "\n";
	}

	public static function echo_button( string $href, string $text, string $icon, array|string|null $class = NULL ): void {
		if ( is_null( $class ) )
			$class = [];
		elseif ( is_string( $class ) )
			$class = explode( ' ', $class );
		$class[] = 'w3-button';
		echo sprintf( '<a href="%s" class="%s">', $href, implode( ' ', $class ) ) . "\n";
		echo sprintf( '<span class="%s"></span>', $icon ) . "\n";
		echo sprintf( '<span>%s</span>', $text ) . "\n";
		echo '</a>' . "\n";
	}

	// html

	public function html(): void {
		echo '<html lang="en">' . "\n";
		echo '<head>' . "\n";
		echo '<meta charset="UTF-8" />' . "\n";
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" />' . "\n";
		echo '<meta name="author" content="constracti" />' . "\n";
		if ( $this->title !== '' )
			echo sprintf( '<title>%s | %s</title>', $this->title, SITE_NAME ) . "\n";
		else
			echo sprintf( '<title>%s</title>', SITE_NAME ) . "\n";
		echo '<link rel="shortcut icon" type="image/png" href="/favicon.png" />' . "\n";
		$this->do_action( 'head_tag' );
		echo '</head>' . "\n";
		echo '<body class="flex-col w3-theme-l5">' . "\n";
		if ( $GLOBALS['mycosmos']->has_login() ) {
			echo '<div class="flex-row flex-justify-between flex-align-center w3-theme">' . "\n";
			echo '<div class="flex-row">' . "\n";
			self::echo_button( site_href(), 'home', 'fas fa-fw fa-home' );
			self::echo_button( site_href( 'history.php' ), 'history', 'fas fa-fw fa-history' );
			echo '</div>' . "\n";
			echo '<div class="flex-row">' . "\n";
			self::echo_button( site_href( 'logout.php' ), 'logout', 'fas fa-fw fa-sign-out-alt' );
			echo '</div>' . "\n";
			echo '</div>' . "\n";
		}
		echo '<div class="root flex-col">' . "\n";
		echo '<div class="flex-row flex-justify-center">' . "\n";
		echo sprintf( '<h1 class="leaf w3-text-theme">%s</h1>', $this->title !== '' ? $this->title : SITE_NAME ) . "\n";
		echo '</div>' . "\n";
		$this->do_action( 'body_tag' );
		echo '</div>' . "\n";
		echo '</body>' . "\n";
		echo '</html>' . "\n";
		exit;
	}
}
