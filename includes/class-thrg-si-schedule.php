<?php
/**
 * Monthly security email: runs an inspection on a chosen day and emails it to administrators.
 *
 * Off by default. Uses WordPress's own scheduler (one single event at a time, rescheduled
 * after each run), so it only fires when the site gets a visit or a server cron job calls wp-cron.php.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

final class THRG_SI_Schedule {

    const OPTION = 'thrg_si_schedule';
    const LOG    = 'thrg_si_schedule_log';
    const HOOK   = 'thrg_si_scheduled_report';
    const LOCK   = 'thrg_si_schedule_lock';

    /** Local hour (site time zone) the email is due. */
    const HOUR = 8;

    /** Days 29 to 31 do not exist in every month, so the choice stops at 28. */
    const LAST_DAY = 28;

    /** A run this many seconds past due is shown as overdue. */
    const LATE_SECONDS = 21600;

    /** Name shown as "Run by" in report history for scheduled runs. */
    const RUN_BY = 'Monthly email';

    public static function boot() {
        add_action( self::HOOK, array( __CLASS__, 'run' ) );
        add_action( 'admin_post_thrg_si_schedule', array( __CLASS__, 'handle_save' ) );
        add_action( 'admin_init', array( __CLASS__, 'heal' ) );
    }

    /* ---------- Pure helpers (no WordPress calls) ---------- */

    public static function defaults() {
        return array(
            'enabled'     => 0,
            'day'         => 1,
            'only_issues' => 0,
            'who'         => 'all',
            'users'       => array(),
            'note'        => '',
            'owner'       => 0,
        );
    }

    /**
     * Clamp stored or submitted settings to safe values.
     *
     * @param array $s Raw settings.
     * @return array
     */
    public static function normalize( $s ) {
        $s   = array_merge( self::defaults(), is_array( $s ) ? $s : array() );
        $day = (int) $s['day'];
        $ids = array();
        foreach ( (array) $s['users'] as $id ) {
            $id = is_scalar( $id ) ? (int) $id : 0;
            if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
                $ids[] = $id;
            }
        }
        $note = trim( (string) ( is_scalar( $s['note'] ) ? $s['note'] : '' ) );
        return array(
            'enabled'     => empty( $s['enabled'] ) ? 0 : 1,
            'day'         => max( 1, min( self::LAST_DAY, $day ) ),
            'only_issues' => empty( $s['only_issues'] ) ? 0 : 1,
            'who'         => 'chosen' === $s['who'] ? 'chosen' : 'all',
            'users'       => array_slice( $ids, 0, THRG_SI_Email::MAX_RECIPIENTS ),
            'note'        => function_exists( 'mb_substr' ) ? mb_substr( $note, 0, THRG_SI_Email::NOTE_LENGTH ) : substr( $note, 0, THRG_SI_Email::NOTE_LENGTH ),
            'owner'       => max( 0, (int) $s['owner'] ),
        );
    }

    /**
     * Next due time: the chosen day of the month at HOUR, site time, strictly after $now.
     *
     * @param int          $now Timestamp.
     * @param int          $day 1 to 28.
     * @param DateTimeZone $tz  Site time zone.
     * @return int Timestamp.
     */
    public static function next_run( $now, $day, DateTimeZone $tz ) {
        $day   = max( 1, min( self::LAST_DAY, (int) $day ) );
        $local = ( new DateTimeImmutable( '@' . (int) $now ) )->setTimezone( $tz );
        $due   = $local->setDate( (int) $local->format( 'Y' ), (int) $local->format( 'n' ), $day )->setTime( self::HOUR, 0, 0 );
        if ( $due->getTimestamp() <= $now ) {
            $due = $local->setDate( (int) $local->format( 'Y' ), (int) $local->format( 'n' ) + 1, $day )->setTime( self::HOUR, 0, 0 );
        }
        return $due->getTimestamp();
    }

    /**
     * Recipients for this run, taken only from the current administrator list.
     * Chosen people who are no longer administrators (or have no valid email) drop out.
     *
     * @param array[] $admins From THRG_SI_Email::recipients().
     * @param array   $s      Normalized settings.
     * @return array[]
     */
    public static function pick( array $admins, array $s ) {
        if ( 'all' === $s['who'] ) {
            return $admins;
        }
        return array_values(
            array_filter(
                $admins,
                static function ( $r ) use ( $s ) {
                    return in_array( (int) $r['id'], $s['users'], true );
                }
            )
        );
    }

    /**
     * Whether the email should go out for these findings.
     *
     * @param array[] $rows        Findings.
     * @param bool    $only_issues Send only when something needs fixing or a second look.
     */
    public static function worth_sending( array $rows, $only_issues ) {
        if ( ! $only_issues ) {
            return true;
        }
        $g = THRG_SI_Email::groups( $rows );
        return (bool) ( $g['fix'] || $g['look'] );
    }

    /**
     * One-line description of the last run, for the settings screen.
     *
     * @param array|null $log  Last run record.
     * @param string     $when Formatted date of the last run.
     */
    public static function describe( $log, $when ) {
        if ( ! is_array( $log ) || empty( $log['result'] ) ) {
            return 'Not sent yet.';
        }
        $sent   = (int) ( isset( $log['sent'] ) ? $log['sent'] : 0 );
        $failed = (int) ( isset( $log['failed'] ) ? $log['failed'] : 0 );
        switch ( $log['result'] ) {
            case 'sent':
                return sprintf( 'Last sent %s to %d administrator%s.', $when, $sent, 1 === $sent ? '' : 's' );
            case 'partial':
                return sprintf( 'Last run %s: %d sent, %d failed. Check your site\'s email setup.', $when, $sent, $failed );
            case 'failed':
                return sprintf( 'Last run %s: WordPress could not send the email. Check your site\'s email setup.', $when );
            case 'skipped':
                return sprintf( 'Last run %s: nothing needed attention, so no email was sent.', $when );
            case 'no-recipients':
                return sprintf( 'Last run %s: none of the chosen administrators could be emailed, so nothing was sent.', $when );
        }
        return 'Not sent yet.';
    }

    /* ---------- WordPress side ---------- */

    public static function settings() {
        return self::normalize( get_option( self::OPTION, array() ) );
    }

    public static function last_run() {
        $log = get_option( self::LOG, null );
        return is_array( $log ) ? $log : null;
    }

    /** Replace any pending run with one at the next due time (or none when off). */
    public static function reschedule() {
        wp_clear_scheduled_hook( self::HOOK );
        $s = self::settings();
        if ( $s['enabled'] ) {
            wp_schedule_single_event( self::next_run( time(), $s['day'], wp_timezone() ), self::HOOK );
        }
    }

    /** Put the schedule back if it was lost (for example after the plugin was deactivated and reactivated). */
    public static function heal() {
        $s = self::settings();
        if ( $s['enabled'] && ! wp_next_scheduled( self::HOOK ) ) {
            self::reschedule();
        }
    }

    /** Sender name and Reply-To: the administrator who set up the schedule, or the site name. */
    private static function sender( $user_id ) {
        $user = $user_id ? get_userdata( $user_id ) : false;
        if ( $user && user_can( $user, 'manage_options' ) ) {
            return array(
                'name'  => $user->display_name ? $user->display_name : $user->user_login,
                'email' => $user->user_email,
            );
        }
        return array(
            'name'  => wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ),
            'email' => '',
        );
    }

    /**
     * Run an inspection, save it to history, and email it.
     *
     * @return array The log record.
     */
    public static function run() {
        $s = self::settings();
        if ( ! $s['enabled'] ) {
            return array();
        }
        if ( get_transient( self::LOCK ) ) {
            return array( 'result' => 'busy' );
        }
        set_transient( self::LOCK, 1, 10 * MINUTE_IN_SECONDS );

        try {
            $now  = time();
            $rows = THRG_Site_Inspector::evaluate( THRG_Site_Inspector::collect() );
            $id   = THRG_SI_History::add( $rows, wp_parse_url( home_url(), PHP_URL_HOST ), THRG_Site_Inspector::version(), 0, self::RUN_BY, $now );
            $all  = THRG_SI_History::all();

            $entry = array(
                'id'   => '',
                'time' => $now,
                'rows' => $rows,
            );
            $older = isset( $all[0] ) ? $all[0] : null;
            foreach ( $all as $i => $e ) {
                if ( $id && $e['id'] === $id ) {
                    $entry = $e;
                    $older = isset( $all[ $i + 1 ] ) ? $all[ $i + 1 ] : null;
                    break;
                }
            }

            $log = array(
                'time'   => $now,
                'report' => (string) $id,
                'sent'   => 0,
                'failed' => 0,
            );
            $to  = self::pick( THRG_SI_Email::recipients(), $s );
            if ( ! self::worth_sending( $rows, $s['only_issues'] ) ) {
                $log['result'] = 'skipped';
            } elseif ( ! $to ) {
                $log['result'] = 'no-recipients';
            } else {
                $result        = THRG_SI_Email::send( $entry, $older, wp_list_pluck( $to, 'id' ), $s['note'], self::sender( $s['owner'] ), true );
                $log['sent']   = (int) $result['sent'];
                $log['failed'] = (int) $result['failed'];
                $log['result'] = $result['sent'] ? ( $result['failed'] ? 'partial' : 'sent' ) : 'failed';
            }
            update_option( self::LOG, $log, false );
            return $log;
        } finally {
            delete_transient( self::LOCK );
            self::reschedule();
        }
    }

    /* ---------- Settings form ---------- */

    private static function back( $flag ) {
        wp_safe_redirect( add_query_arg( 'thrg_si_done', $flag, admin_url( THRG_Site_Inspector::PAGE_URL ) ) . '#si-schedule' );
        exit;
    }

    public static function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do that.', 'thrg-site-inspector' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( 'thrg_si_schedule' );
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce checked above.
        $raw = array(
            'enabled'     => ! empty( $_POST['enabled'] ),
            'day'         => isset( $_POST['day'] ) ? absint( $_POST['day'] ) : 1,
            'only_issues' => isset( $_POST['only_issues'] ) && '1' === $_POST['only_issues'],
            'who'         => isset( $_POST['who'] ) ? sanitize_key( wp_unslash( $_POST['who'] ) ) : 'all',
            'users'       => isset( $_POST['users'] ) ? array_map( 'absint', array_filter( (array) wp_unslash( $_POST['users'] ), 'is_scalar' ) ) : array(),
            'note'        => isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '',
            'owner'       => get_current_user_id(),
        );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $s    = self::normalize( $raw );
        $flag = 'schedule-saved';
        if ( 'chosen' === $s['who'] && ! self::pick( THRG_SI_Email::recipients(), $s ) ) {
            $s['enabled'] = 0;
            $flag         = 'schedule-none';
        }
        update_option( self::OPTION, $s, false );
        self::reschedule();
        self::back( $flag );
    }
}
