<?php
// Report history: storage, bounds, deduplication, deletion, pausing, and change tracking.
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['opts'] = array();
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['opts'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['opts'][ $k ] ); return true; }
require __DIR__ . '/../includes/class-thrg-si-history.php';

function check( $cond, $name ) {
    if ( ! $cond ) { fwrite( STDERR, "FAIL $name\n" ); exit( 1 ); }
    echo "PASS $name\n";
}
function rows( array $status_by_id ) {
    $out = array();
    foreach ( $status_by_id as $id => $status ) {
        $out[] = array( 'id' => $id, 'title' => $id, 'status' => $status, 'why' => '', 'evidence' => array(), 'limits' => '', 'next' => '', 'table' => null, 'actions' => array() );
    }
    return $out;
}

$a = rows( array( 'updates' => 'Potential weakness', 'inactive' => 'Review', 'keys' => 'No issue observed' ) );
$b = rows( array( 'updates' => 'No issue observed', 'inactive' => 'Review', 'keys' => 'No issue observed', 'uploads' => 'Potential weakness' ) );

$s = THRG_SI_History::summarize( $a );
check( 1 === $s['attention'] && 1 === $s['review'] && 1 === $s['observed'] && array( 'updates', 'inactive' ) === $s['issues'], 'summary counts and issue IDs' );

$id1 = THRG_SI_History::add( $a, 'example.com', '2.0.0', 1, 'owner', 1000 );
check( THRG_SI_History::valid_id( $id1 ) && 1 === count( THRG_SI_History::all() ), 'first report saved with a valid ID' );
check( $id1 === THRG_SI_History::add( $a, 'example.com', '2.0.0', 1, 'owner', 1100 ) && 1 === count( THRG_SI_History::all() ), 'identical repeat within five minutes is not saved twice (refresh safe)' );
$id2 = THRG_SI_History::add( $a, 'example.com', '2.0.0', 1, 'owner', 1000 + 400 );
check( $id2 !== $id1 && 2 === count( THRG_SI_History::all() ), 'identical repeat later is saved' );
$id3 = THRG_SI_History::add( $b, 'example.com', '2.0.0', 2, 'dev', 2000 );
$all = THRG_SI_History::all();
check( $id3 === $all[0]['id'] && 3 === count( $all ), 'newest first' );

$ch = THRG_SI_History::change( $all[0], $all[1] );
check( ! $ch['first'] && 1 === $ch['new'] && 1 === $ch['resolved'], 'change vs previous: 1 new, 1 resolved' );
check( THRG_SI_History::change( $all[2], null )['first'], 'oldest report is first' );
check( 0 === THRG_SI_History::change( $all[1], $all[2] )['new'] + THRG_SI_History::change( $all[1], $all[2] )['resolved'], 'no change between identical reports' );

check( null !== THRG_SI_History::find( $id1 ) && null === THRG_SI_History::find( 'zzzzzzzzzzzz' ) && null === THRG_SI_History::find( '../../etc' ) && null === THRG_SI_History::find( array() ), 'find validates IDs' );

check( THRG_SI_History::delete( $id2 ) && 2 === count( THRG_SI_History::all() ) && ! THRG_SI_History::delete( $id2 ) && ! THRG_SI_History::delete( 'bad' ), 'delete one report' );

for ( $i = 0; $i < 40; $i++ ) { THRG_SI_History::add( rows( array( 'x' . $i => 'Review' ) ), 'example.com', '2.0.0', 1, 'owner', 5000 + $i * 1000 ); }
check( THRG_SI_History::MAX_ENTRIES === count( THRG_SI_History::all() ), 'count is capped' );

// Size cap: huge reports are trimmed oldest-first but the newest always survives.
$big = rows( array( 'big' => 'Review' ) );
$big[0]['evidence'] = array( str_repeat( 'x', 100000 ) );
for ( $i = 0; $i < 12; $i++ ) { $big[0]['why'] = (string) $i; THRG_SI_History::add( $big, 'example.com', '2.0.0', 1, 'owner', 100000 + $i * 1000 ); }
$all = THRG_SI_History::all();
check( count( $all ) >= 1 && strlen( serialize( $all ) ) <= THRG_SI_History::MAX_BYTES + 120000 && '11' === $all[0]['rows'][0]['why'], 'size is bounded and newest survives' );

THRG_SI_History::set_enabled( false );
$before = count( THRG_SI_History::all() );
check( null === THRG_SI_History::add( $a, 'example.com', '2.0.0', 1, 'owner', 900000 ) && $before === count( THRG_SI_History::all() ) && ! THRG_SI_History::enabled(), 'paused history saves nothing' );
THRG_SI_History::set_enabled( true );
check( THRG_SI_History::enabled(), 'history can resume' );

THRG_SI_History::clear();
check( array() === THRG_SI_History::all(), 'clear removes everything' );
$GLOBALS['opts'][ THRG_SI_History::OPTION ] = 'corrupt';
check( array() === THRG_SI_History::all(), 'corrupt storage reads as empty' );
$GLOBALS['opts'][ THRG_SI_History::OPTION ] = array( 'junk', array( 'id' => 'x' ) );
check( array() === THRG_SI_History::all(), 'malformed entries are ignored' );
