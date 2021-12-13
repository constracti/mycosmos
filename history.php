<?php

require_once( 'php/index.php' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$task = MCR::get_str( 'task' );
	if ( $task === 'cache-clear' ) {
		$mycosmos->set_value( 'history' );
		success( [
			'redirect' => site_href(),
		] );
	}
	if ( $task === 'cache-refresh' ) {
		session_start();
		$mycosmos->set_value( 'history' );
		$mycosmos->history();
		success( [
			'redirect' => NULL,
		] );
	}
	exit( 'task' );
}

if ( !$mycosmos->has_login() )
	mycosmos::page_login();

$page = new page();

$history = $mycosmos->history();

$cnav = MCR::get_str( 'nav', TRUE );
if ( !is_null( $cnav ) ) {
	if ( !array_key_exists( $cnav, $history['navs'] ) )
		exit( 'nav' );
} elseif ( !empty( $history['navs'] ) ) {
	$cnav = array_key_first( $history['navs'] );
} else {
	exit( 'history' );
}

$years = [];
foreach ( array_keys( $history['navs'] ) as $nav ) {
	$year = intval( substr( $nav, 0, 4 ) );
	if ( !array_key_exists( $year, $years ) )
		$years[$year] = [];
	$years[$year][] = $nav;
}

$page = new page( 'history' );
$page->add_action( 'head_tag', [ 'page', 'echo_style_tag' ], site_href( 'table.css' ) );

$page->add_action( 'body_tag', function(): void {
	echo '<div class="flex-row flex-align-center">' . "\n";
	echo '<h3 class="leaf">cache</h3>' . "\n";
	page::echo_button( '?task=cache-refresh', 'refresh', 'fas fa-fw fa-sync', 'ajax-link leaf w3-round' );
	page::echo_button( '?task=cache-clear', 'clear', 'fas fa-fw fa-ban', 'ajax-link leaf w3-round' );
	echo '</div>' . "\n";
} );

$page->add_action( 'body_tag', function( array $years, string $cnav ): void {
	$cyear = intval( substr( $cnav, 0, 4 ) );
	echo '<div class="leaf flex-row w3-theme">' . "\n";
	foreach ( $years as $year => $navs ) {
		echo '<div class="w3-dropdown-hover">' . "\n";
		echo sprintf( '<button class="w3-button %s">%d</button>', $year === $cyear ? 'w3-black' : 'w3-theme', $year ) . "\n";
		echo '<div class="w3-dropdown-content w3-bar-block">' . "\n";
		foreach ( $navs as $nav ) {
			echo sprintf( '<a href="%s" class="w3-bar-item w3-button %s">%s</a>',
				site_href( 'history.php', [ 'nav' => $nav ] ),
				$nav === $cnav ? 'w3-black' : 'w3-theme-l2',
				DateTime::createFromFormat( 'Y-m', $nav )->format( 'F' )
			) . "\n";
		}
		echo '</div>' . "\n";
		echo '</div>' . "\n";
	}
	echo '</div>' . "\n";
}, $years, $cnav );

$page->add_action( 'body_tag', function( array $history, string $cnav ): void {
	$cols = [
		Mycosmos::COL_CONTENT,
		Mycosmos::COL_RECIPIENT,
		Mycosmos::COL_STATUS,
		Mycosmos::COL_TIME,
	];
	echo '<div class="leaf">' . "\n";
	echo '<table class="w3-striped w3-hoverable">' . "\n";
	echo '<thead class="w3-theme">' . "\n";
	echo '<tr>' . "\n";
	foreach ( $cols as $col )
		echo sprintf( '<th>%s</th>', $history['cols'][$col] ) . "\n";
	echo '</tr>' . "\n";
	echo '</thead>' . "\n";
	echo '<tbody>' . "\n";
	for ( $i = $history['navs'][$cnav]; $i < count( $history['rows'] ); $i++ ) {
		$row = $history['rows'][$i];
		if ( $row[Mycosmos::COL_TIME] !== '' ) {
			$dt = new DateTime( $row[Mycosmos::COL_TIME] );
			$curr = $dt->format( 'Y-m' );
			if ( $curr !== $cnav )
				break;
		}
		echo '<tr>' . "\n";
		foreach ( $cols as $col ) {
			echo sprintf( '<td data-col="%s">', $history['cols'][$col] ) . "\n";
			switch ( $col ) {
				case Mycosmos::COL_CONTENT:
					echo nl2br( $row[$col] ) . "\n";
					break;
				case Mycosmos::COL_RECIPIENT:
					echo sprintf( '<a href="tel:%s">%s</a>', $row[$col], $row[$col] ) . "\n";
					break;
				case Mycosmos::COL_TIME:
					if ( $row[$col] === '' )
						echo '<span>&mdash;</span>' . "\n";
					else
						foreach ( explode( ' ', $row[$col] ) as $dt )
							echo sprintf( '<span style="white-space: nowrap;">%s</span>', $dt ) . "\n";
					break;
				default:
					echo $row[$col];
					break;
			}
			echo '</td>' . "\n";
		}
		echo '</tr>' . "\n";
	}
	echo '</tbody>' . "\n";
	echo '</table>' . "\n";
	echo '</div>' . "\n";
}, $history, $cnav );

$page->html();
