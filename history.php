<?php

require_once( 'php/core.php' );

if ( is_null( $mycosmos ) )
	failure( 'history: login required' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$task = request_var( 'task' );
	switch ( $task ) {
		case 'cache-clear':
			session_start();
			if ( array_key_exists( 'history', $_SESSION ) )
				unset( $_SESSION['history'] );
			session_write_close();
			success( [
				'redirect' => site_href(),
			] );
		case 'cache-refresh':
			session_start();
			if ( array_key_exists( 'history', $_SESSION ) )
				unset( $_SESSION['history'] );
			session_write_close();
			$mycosmos->history();
			success( [
				'redirect' => site_href( 'history.php' ),
			] );
		default:
			failure( 'task not valid' );
	}
}

$history = $mycosmos->history();

if ( array_key_exists( 'nav', $_GET ) ) {
	$cnav = $_GET['nav'];
	if ( !array_key_exists( $cnav, $history['navs'] ) )
		failure( 'history: nav not valid' );
} elseif ( !empty( $history['navs'] ) ) {
	$cnav = array_keys( $history['navs'] )[0];
} else {
	failure( 'history: empty' );
}

$years = [];
foreach ( array_keys( $history['navs'] ) as $nav ) {
	$year = intval( substr( $nav, 0, 4 ) );
	if ( array_key_exists( $year, $years ) )
		$years[$year][] = $nav;
	else
		$years[$year] = [ $nav ];
}

$page = new page( 'history' );

$page->add_css( 'css/table.css' );

$page->add_js( 'js/link.js' );

$page->add_body( function() {
?>
<div class="w3-panel w3-content">
	<h3>cache</h3>
	<a class="link-ajax w3-button w3-round w3-theme" href="?task=cache-refresh">
		<span class="fas fa-fw fa-sync"></span>
		<span>refresh</span>
	</a>
	<a class="link-ajax w3-button w3-round w3-theme" href="?task=cache-clear">
		<span class="fas fa-fw fa-ban"></span>
		<span>clear</span>
	</a>
</div>
<?php
} );

$page->add_body( function( array $years, string $cnav ) {
	$cyear = intval( substr( $cnav, 0, 4 ) );
	echo '<div class="w3-bar w3-section w3-theme">' . "\n";
	foreach ( $years as $year => $navs ) {
		echo '<div class="w3-dropdown-hover">' . "\n";
		echo sprintf( '<button class="w3-button %s">%d</button>', $year === $cyear ? 'w3-black' : 'w3-theme', $year ) . "\n";
		echo '<div class="w3-dropdown-content w3-bar-block">' . "\n";
		foreach ( $navs as $nav )
			echo sprintf( '<a href="%s" class="w3-bar-item w3-button %s">%s</a>',
				site_href( 'history.php', [ 'nav' => $nav ] ),
				$nav === $cnav ? 'w3-black' : 'w3-theme-l2',
				DateTime::createFromFormat( 'Y-m', $nav )->format( 'F' )
			) . "\n";
		echo '</div>' . "\n";
		echo '</div>' . "\n";
	}
	echo '</div>' . "\n";
}, $years, $cnav );

$page->add_body( function( array $history, string $cnav ) {
	$cols = [
		Mycosmos::COL_CONTENT,
		Mycosmos::COL_RECIPIENT,
		Mycosmos::COL_STATUS,
		Mycosmos::COL_TIME,
	];
	echo '<table class="w3-striped w3-hoverable w3-section">' . "\n";
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
						echo '<span class="fas fa-question"></span>' . "\n";
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
	echo '<tfoot class="w3-theme">' . "\n";
	echo '<tr>' . "\n";
	foreach ( $cols as $col )
		echo sprintf( '<th>%s</th>', $history['cols'][$col] ) . "\n";
	echo '</tr>' . "\n";
	echo '</tfoot>' . "\n";
	echo '</table>' . "\n";
}, $history, $cnav );

$page->html();
