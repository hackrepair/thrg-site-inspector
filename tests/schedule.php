<?php
// Monthly email: settings clean-up, next due date, recipients, "only when issues", status line, email wording.
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
function esc_html( $t ) { return htmlspecialchars( (string) $t, ENT_QUOTES ); }
require __DIR__ . '/../includes/class-thrg-site-inspector.php';

function check( $cond, $name ) {
    if ( ! $cond ) {
        fwrite( STDERR, "FAIL $name\n" );
        exit( 1 );
    }
    echo "PASS $name\n";
}

// Settings are clamped to safe values.
$d = THRG_SI_Schedule::normalize( null );
check( 0 === $d['enabled'] && 1 === $d['day'] && 'all' === $d['who'] && 0 === $d['only_issues'], 'off by default, day 1, all admins, every month' );
$n = THRG_SI_Schedule::normalize( array( 'enabled' => '1', 'day' => 31, 'who' => 'evil', 'users' => array( '3', 3, -2, 'x', array( 9 ), 7 ), 'note' => str_repeat( 'a', 1500 ), 'only_issues' => 'yes', 'owner' => '-5' ) );
check( 28 === $n['day'] && 'all' === $n['who'] && array( 3, 7 ) === $n['users'] && 1000 === strlen( $n['note'] ) && 1 === $n['only_issues'] && 0 === $n['owner'], 'day capped at 28, unknown audience becomes all, bad user IDs dropped, note trimmed' );
check( 1 === THRG_SI_Schedule::normalize( array( 'day' => 0 ) )['day'], 'day below 1 becomes 1' );
check( 25 === count( THRG_SI_Schedule::normalize( array( 'users' => range( 1, 40 ) ) )['users'] ), 'at most 25 chosen administrators' );

// Next due time: chosen day at 8:00 site time, strictly in the future.
$la  = new DateTimeZone( 'America/Los_Angeles' );
$fmt = static function ( $ts ) use ( $la ) {
    return ( new DateTimeImmutable( '@' . $ts ) )->setTimezone( $la )->format( 'Y-m-d H:i T' );
};
$now = ( new DateTimeImmutable( '2026-10-26 13:40', $la ) )->getTimestamp();
check( '2026-11-01 08:00 PST' === $fmt( THRG_SI_Schedule::next_run( $now, 1, $la ) ), 'day 1 after the 26th means next month' );
check( '2026-10-28 08:00 PDT' === $fmt( THRG_SI_Schedule::next_run( $now, 28, $la ) ), 'day later this month stays this month' );
check( '2026-11-26 08:00 PST' === $fmt( THRG_SI_Schedule::next_run( $now, 26, $la ) ), 'today after 8 am moves to next month, and daylight saving is followed' );
$early = ( new DateTimeImmutable( '2026-10-26 07:59', $la ) )->getTimestamp();
check( '2026-10-26 08:00 PDT' === $fmt( THRG_SI_Schedule::next_run( $early, 26, $la ) ), 'today before 8 am is still today' );
$at = ( new DateTimeImmutable( '2026-10-26 08:00', $la ) )->getTimestamp();
check( '2026-11-26 08:00 PST' === $fmt( THRG_SI_Schedule::next_run( $at, 26, $la ) ), 'exact due time counts as done' );
$dec = ( new DateTimeImmutable( '2026-12-20 09:00', $la ) )->getTimestamp();
check( '2027-01-15 08:00 PST' === $fmt( THRG_SI_Schedule::next_run( $dec, 15, $la ) ), 'December rolls into January' );
$feb = ( new DateTimeImmutable( '2027-01-29 09:00', new DateTimeZone( 'UTC' ) ) )->getTimestamp();
check( '2027-02-28 08:00 UTC' === ( new DateTimeImmutable( '@' . THRG_SI_Schedule::next_run( $feb, 28, new DateTimeZone( 'UTC' ) ) ) )->format( 'Y-m-d H:i' ) . ' UTC', 'day 28 exists in February' );

// Recipients come only from the current administrator list.
$admins = array(
    array( 'id' => 1, 'name' => 'Jim', 'email' => 'jim@example.com' ),
    array( 'id' => 4, 'name' => 'Pat', 'email' => 'pat@example.com' ),
);
check( $admins === THRG_SI_Schedule::pick( $admins, THRG_SI_Schedule::normalize( array( 'who' => 'all' ) ) ), 'all administrators' );
check( array( $admins[1] ) === THRG_SI_Schedule::pick( $admins, THRG_SI_Schedule::normalize( array( 'who' => 'chosen', 'users' => array( 4, 99 ) ) ) ), 'chosen administrators; a removed or demoted one drops out' );
check( array() === THRG_SI_Schedule::pick( $admins, THRG_SI_Schedule::normalize( array( 'who' => 'chosen', 'users' => array( 99 ) ) ) ), 'nobody left to email' );

// "Only when something needs fixing or a second look".
$row   = static function ( $status ) {
    return array( 'id' => 'x', 'title' => 'X', 'status' => $status, 'why' => '', 'evidence' => array(), 'limits' => '', 'next' => '', 'table' => null, 'actions' => array() );
};
$clean = array( $row( 'No issue observed' ) );
check( THRG_SI_Schedule::worth_sending( $clean, false ), 'every month: clean site still emails' );
check( ! THRG_SI_Schedule::worth_sending( $clean, true ), 'only issues: clean site is skipped' );
check( THRG_SI_Schedule::worth_sending( array( $row( 'Review' ) ), true ) && THRG_SI_Schedule::worth_sending( array( $row( 'Potential weakness' ) ), true ) && THRG_SI_Schedule::worth_sending( array( $row( 'Partial inspection' ) ), true ), 'only issues: weakness, review or partial inspection sends' );

// Status line on the screen.
check( 'Not sent yet.' === THRG_SI_Schedule::describe( null, '' ), 'status before the first run' );
check( 'Last sent Nov 1 to 2 administrators.' === THRG_SI_Schedule::describe( array( 'result' => 'sent', 'sent' => 2 ), 'Nov 1' ), 'status after a send' );
check( false !== strpos( THRG_SI_Schedule::describe( array( 'result' => 'skipped' ), 'Nov 1' ), 'nothing needed attention' ), 'status after a skipped month' );
check( false !== strpos( THRG_SI_Schedule::describe( array( 'result' => 'partial', 'sent' => 1, 'failed' => 1 ), 'Nov 1' ), '1 sent, 1 failed' ), 'status after a partial failure' );
check( false !== strpos( THRG_SI_Schedule::describe( array( 'result' => 'no-recipients' ), 'Nov 1' ), 'none of the chosen administrators' ), 'status when nobody could be emailed' );

// Email wording for scheduled runs.
$args = array( 'rows' => array( $row( 'Review' ) ), 'site' => 'example.com', 'when' => 'Nov 1', 'change' => null, 'older_when' => '', 'note' => '', 'sender' => 'Jim', 'recipient' => 'Pat', 'view_url' => 'http://x/wp-admin/tools.php?page=thrg-site-inspector' );
$plain = THRG_SI_Email::html( $args ) . THRG_SI_Email::text( $args );
check( false === strpos( $plain, 'monthly security statement' ) && false === strpos( $plain, 'scheduled it' ), 'one-off emails do not mention a schedule' );
$args['scheduled'] = true;
$html = THRG_SI_Email::html( $args );
$text = THRG_SI_Email::text( $args );
check( false !== strpos( $html, 'This is your monthly security statement from Site Inspector.' ) && false !== strpos( $text, 'This is your monthly security statement from Site Inspector.' ), 'scheduled email says what it is' );
check( false !== strpos( $html, 'Tools &gt; Site Inspector, where it can be changed or turned off.' ) && false !== strpos( $text, 'Tools > Site Inspector, where it can be changed or turned off.' ), 'scheduled email says how to change or stop it' );

// Scheduled emails say "Monthly" in the subject; one-off emails do not.
$rows1 = array( $row( 'Potential weakness' ) );
check( '[example.com] [Site Inspector] Monthly Security Check: 1 issue needs attention' === THRG_SI_Email::subject( $rows1, 'example.com', null, true ), 'monthly subject' );
check( '[example.com] [Site Inspector] Security Check: 1 issue needs attention' === THRG_SI_Email::subject( $rows1, 'example.com' ), 'one-off subject unchanged' );
check( false !== strpos( $html, '<title>[example.com] [Site Inspector] Monthly Security Check:' ), 'monthly subject in the HTML title' );
