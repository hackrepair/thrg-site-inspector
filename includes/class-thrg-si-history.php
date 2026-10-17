<?php
/**
 * Report history: the most recent inspections, kept on this site only.
 *
 * Stored in one non-autoloaded option, newest first, bounded by count and size.
 * Reports include usernames and file names, so history can be paused, a single
 * report deleted, or everything cleared. History is never sent anywhere; a report
 * only leaves the site when an administrator emails it (THRG_SI_Email).
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

final class THRG_SI_History {

    const OPTION      = 'thrg_si_history';
    const PAUSED      = 'thrg_si_history_paused';
    const MAX_ENTRIES = 25;
    const MAX_BYTES   = 600000;
    const SCHEMA      = 1;

    /** A repeat inspection with identical results inside this window is not saved twice. */
    const DEDUPE_SECONDS = 300;

    public static function enabled() {
        return ! get_option( self::PAUSED, false );
    }

    public static function set_enabled( $enabled ) {
        if ( $enabled ) {
            delete_option( self::PAUSED );
        } else {
            update_option( self::PAUSED, 1, false );
        }
    }

    /** @return array[] Entries, newest first. */
    public static function all() {
        $entries = get_option( self::OPTION, array() );
        if ( ! is_array( $entries ) ) {
            return array();
        }
        return array_values(
            array_filter(
                $entries,
                static function ( $e ) {
                    return is_array( $e ) && isset( $e['id'], $e['time'], $e['rows'] ) && is_array( $e['rows'] );
                }
            )
        );
    }

    /** Valid report ID: twelve lowercase hex characters. */
    public static function valid_id( $id ) {
        return is_string( $id ) && 1 === preg_match( '/^[a-f0-9]{12}$/', $id );
    }

    public static function find( $id ) {
        if ( ! self::valid_id( $id ) ) {
            return null;
        }
        foreach ( self::all() as $entry ) {
            if ( $entry['id'] === $id ) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Counts by tone and the IDs of findings that need a look.
     *
     * @param array[] $rows Findings.
     * @return array{attention:int,review:int,observed:int,issues:string[]}
     */
    public static function summarize( array $rows ) {
        $out = array( 'attention' => 0, 'review' => 0, 'observed' => 0, 'issues' => array() );
        foreach ( $rows as $row ) {
            if ( 'Potential weakness' === $row['status'] ) {
                $out['attention']++;
            } elseif ( 'No issue observed' === $row['status'] ) {
                $out['observed']++;
            } else {
                $out['review']++;
            }
            if ( 'No issue observed' !== $row['status'] ) {
                $out['issues'][] = (string) $row['id'];
            }
        }
        return $out;
    }

    /**
     * Save an inspection. Returns the saved (or identical recent) report ID, or null when history is paused.
     *
     * @param array[] $rows      Findings from THRG_Site_Inspector::evaluate().
     * @param string  $site      Site host.
     * @param string  $version   Plugin version.
     * @param int     $user_id   Current user ID.
     * @param string  $user_name Current user login.
     * @param int     $now       Timestamp.
     * @return string|null
     */
    public static function add( array $rows, $site, $version, $user_id, $user_name, $now ) {
        if ( ! self::enabled() ) {
            return null;
        }
        $entries = self::all();
        $hash    = md5( serialize( $rows ) );
        if ( $entries && isset( $entries[0]['hash'] ) && $entries[0]['hash'] === $hash && ( $now - (int) $entries[0]['time'] ) < self::DEDUPE_SECONDS ) {
            return $entries[0]['id'];
        }
        $summary = self::summarize( $rows );
        $id      = bin2hex( random_bytes( 6 ) );
        array_unshift(
            $entries,
            array(
                'schema'   => self::SCHEMA,
                'id'       => $id,
                'time'     => (int) $now,
                'site'     => substr( (string) $site, 0, 190 ),
                'version'  => substr( (string) $version, 0, 20 ),
                'user'     => (int) $user_id,
                'name'     => substr( (string) $user_name, 0, 60 ),
                'attention' => $summary['attention'],
                'review'   => $summary['review'],
                'observed' => $summary['observed'],
                'issues'   => $summary['issues'],
                'hash'     => $hash,
                'rows'     => $rows,
            )
        );
        $entries = array_slice( $entries, 0, self::MAX_ENTRIES );
        while ( count( $entries ) > 1 && strlen( serialize( $entries ) ) > self::MAX_BYTES ) {
            array_pop( $entries );
        }
        update_option( self::OPTION, $entries, false );
        return $id;
    }

    public static function delete( $id ) {
        if ( ! self::valid_id( $id ) ) {
            return false;
        }
        $entries = self::all();
        $kept    = array_values(
            array_filter(
                $entries,
                static function ( $e ) use ( $id ) {
                    return $e['id'] !== $id;
                }
            )
        );
        if ( count( $kept ) === count( $entries ) ) {
            return false;
        }
        if ( $kept ) {
            update_option( self::OPTION, $kept, false );
        } else {
            delete_option( self::OPTION );
        }
        return true;
    }

    public static function clear() {
        delete_option( self::OPTION );
    }

    /**
     * What changed compared with the next older report.
     *
     * @param array      $entry Report.
     * @param array|null $older Next older report, if any.
     * @return array{first:bool,new:int,resolved:int}
     */
    public static function change( array $entry, $older ) {
        if ( ! is_array( $older ) ) {
            return array( 'first' => true, 'new' => 0, 'resolved' => 0 );
        }
        $now  = isset( $entry['issues'] ) ? (array) $entry['issues'] : array();
        $then = isset( $older['issues'] ) ? (array) $older['issues'] : array();
        return array( 'first' => false, 'new' => count( array_diff( $now, $then ) ), 'resolved' => count( array_diff( $then, $now ) ) );
    }
}
