<?php
/**
 * Email a saved report to the site's administrators as a "state of security" briefing.
 *
 * Recipients can only be administrator accounts on this site: addresses are
 * looked up server-side from the chosen user IDs, never typed in. The email is
 * built by pure functions (subject, html, text) so it can be tested without WordPress.
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

final class THRG_SI_Email {

    const MAX_RECIPIENTS = 25;
    const NOTE_LENGTH    = 1000;
    const TABLE_ROWS     = 8;
    const WAIT_SECONDS   = 60;

    /* ---------- Pure builders ---------- */

    /** Group findings by what the reader should do with them. */
    public static function groups( array $rows ) {
        $out = array(
			'fix'  => array(),
			'look' => array(),
			'good' => array(),
		);
        foreach ( $rows as $r ) {
            if ( 'Potential weakness' === $r['status'] ) {
                $out['fix'][] = $r;
            } elseif ( 'No issue observed' === $r['status'] ) {
                $out['good'][] = $r;
            } else {
                $out['look'][] = $r;
            }
        }
        return $out;
    }

    private static function plural( $n, $one, $many ) {
        return $n . ' ' . ( 1 === (int) $n ? $one : $many );
    }

    /** Report acceptance separately from confirmed inbox delivery. */
    public static function feedback( $accepted, $failed ) {
        return sprintf(
            'Email accepted for sending: %d. Failed: %d. Acceptance does not confirm delivery.',
            max( 0, (int) $accepted ),
            max( 0, (int) $failed )
        );
    }

    /** Site identity for subjects and previews, including local ports. */
    public static function site_domain() {
        $url  = home_url( '/' );
        $host = wp_parse_url( $url, PHP_URL_HOST );
        $port = wp_parse_url( $url, PHP_URL_PORT );
        if ( ! $host ) {
            $url  = site_url( '/' );
            $host = wp_parse_url( $url, PHP_URL_HOST );
            $port = wp_parse_url( $url, PHP_URL_PORT );
        }
        return strtolower( (string) $host ) . ( $port ? ':' . (int) $port : '' );
    }

    /**
     * A subject line that says where things stand before the email is opened.
     *
     * @param array[]    $rows   Findings.
     * @param string     $site   Site host.
     * @param array|null $change Output of THRG_SI_History::change(), or null.
     */
    public static function subject( array $rows, $site, $change = null ) {
        $g    = self::groups( $rows );
        $fix  = count( $g['fix'] );
        $look = count( $g['look'] );
        $new  = is_array( $change ) && empty( $change['first'] ) ? (int) $change['new'] : 0;
        $done = is_array( $change ) && empty( $change['first'] ) ? (int) $change['resolved'] : 0;

        if ( $fix ) {
            $s = sprintf( 'Security Check: %s %s attention', self::plural( $fix, 'issue', 'issues' ), 1 === $fix ? 'needs' : 'need' );
            if ( $new ) {
                $s .= sprintf( ' (%d new since last check)', $new );
            }
        } elseif ( $look ) {
            $s = sprintf( 'Security Check: nothing urgent, %s worth a look', self::plural( $look, 'item', 'items' ) );
        } else {
            $s = sprintf( 'Security Check: all clear on %s', self::plural( count( $rows ), 'check', 'checks' ) );
        }
        if ( ! $fix && $done ) {
            $s .= sprintf( ' (%d fixed since last check)', $done );
        }
        return '[' . $site . '] [Site Inspector] ' . $s;
    }

    /** The opening "state of security" paragraph. */
    public static function narrative( array $rows, $site ) {
        $g    = self::groups( $rows );
        $fix  = count( $g['fix'] );
        $look = count( $g['look'] );
        if ( $fix ) {
            return sprintf(
                "Today's check found %s that could make %s easier to break into%s. None of this means the site has been hacked. These are the open doors attackers try first, and most of them take only minutes to close.",
                self::plural( $fix, 'thing', 'things' ),
                $site,
                $look ? sprintf( ', plus %s worth a second look', self::plural( $look, 'item', 'items' ) ) : ''
            );
        }
        if ( $look ) {
            return sprintf(
                'Nothing urgent turned up on %s. %s a second look from someone who knows the site, because only a person can say whether %s expected.',
                $site,
                self::plural( $look, 'item deserves', 'items deserve' ),
                1 === $look ? 'it is' : 'they are'
            );
        }
        return sprintf(
            'Good news: every check on %s came back clean. No automated check can promise a site is safe, but the most common ways in are closed right now.',
            $site
        );
    }

    /** One line about what changed since the previous saved report. */
    public static function change_line( $change, $older_when = '' ) {
        if ( ! is_array( $change ) || ! empty( $change['first'] ) ) {
            return 'This is the first saved report for this site, so future emails will show what changed.';
        }
        $since = '' !== $older_when ? ' on ' . $older_when : '';
        if ( ! $change['new'] && ! $change['resolved'] ) {
            return 'Nothing has changed since the previous check' . $since . '.';
        }
        $parts = array();
        if ( $change['resolved'] ) {
            $parts[] = self::plural( $change['resolved'], 'issue fixed', 'issues fixed' );
        }
        if ( $change['new'] ) {
            $parts[] = self::plural( $change['new'], 'new issue', 'new issues' );
        }
        return 'Since the previous check' . $since . ': ' . implode( ', ', $parts ) . '.';
    }

    private static function e( $text ) {
        return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
    }

    private static function clean( $text ) {
        return THRG_Site_Inspector::strip_links( str_replace( ' [[update-core.php?force-check=1|Check again]]', '', (string) $text ) );
    }

    /** Table cells as plain strings, usernames masked, at most TABLE_ROWS rows. */
    private static function table_rows( $table ) {
        $mask = isset( $table['mask'] ) ? (int) $table['mask'] : -1;
        $out  = array();
        foreach ( array_slice( $table['rows'], 0, self::TABLE_ROWS ) as $cells ) {
            $row = array();
            foreach ( array_values( $cells ) as $i => $c ) {
                $c     = is_array( $c ) ? (string) $c[0] : (string) $c;
                $row[] = $i === $mask ? THRG_Site_Inspector::mask( $c ) : $c;
            }
            $out[] = $row;
        }
        return $out;
    }

    private static function finding_html( array $r, $accent, $full ) {
        $h  = '<tr><td style="padding:0 0 18px 0;">';
        $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #ead9cc;border-left:4px solid ' . $accent . ';border-radius:6px;background:#ffffff;">';
        $h .= '<tr><td style="padding:14px 16px;">';
        $h .= '<div style="font-size:16px;font-weight:700;color:#1d2327;margin:0 0 6px;">' . self::e( $r['title'] ) . '</div>';
        $h .= '<div style="font-size:14px;line-height:1.55;color:#3c434a;margin:0 0 10px;">' . self::e( self::clean( $r['why'] ) ) . '</div>';
        if ( $full && $r['evidence'] ) {
            $h .= '<div style="background:#faf6f2;border-radius:4px;padding:8px 12px;margin:0 0 10px;font-size:13px;line-height:1.5;color:#50575e;">';
            foreach ( array_slice( $r['evidence'], 0, 3 ) as $line ) {
                $h .= '<div>' . self::e( self::clean( $line ) ) . '</div>';
            }
            $h .= '</div>';
        }
        if ( $full && ! empty( $r['table']['rows'] ) ) {
            $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:12px;margin:0 0 10px;">';
            $h .= '<tr>';
            foreach ( $r['table']['head'] as $head ) {
                $h .= '<th align="left" style="padding:5px 6px;background:#fff1e6;color:#1d2327;border-bottom:1px solid #ead9cc;">' . self::e( $head ) . '</th>';
            }
            $h .= '</tr>';
            foreach ( self::table_rows( $r['table'] ) as $cells ) {
                $h .= '<tr>';
                foreach ( $cells as $c ) {
                    $h .= '<td style="padding:5px 6px;border-bottom:1px solid #f0e6de;color:#3c434a;word-break:break-word;">' . self::e( $c ) . '</td>';
                }
                $h .= '</tr>';
            }
            $more = count( $r['table']['rows'] ) - self::TABLE_ROWS;
            if ( $more > 0 ) {
                $h .= '<tr><td colspan="' . count( $r['table']['head'] ) . '" style="padding:5px 6px;color:#646970;">…and ' . (int) $more . ' more in the full report.</td></tr>';
            }
            $h .= '</table>';
        }
        if ( '' !== (string) $r['next'] ) {
            $h .= '<div style="font-size:14px;line-height:1.55;color:#1d2327;border-left:3px solid #b34700;padding:2px 0 2px 10px;"><strong>What to do:</strong> ' . self::e( self::clean( $r['next'] ) ) . '</div>';
        }
        $h .= '</td></tr></table></td></tr>';
        return $h;
    }

    /**
     * The HTML email.
     *
     * @param array $a {rows, site, when, change, older_when, note, sender, recipient, view_url}
     */
    public static function html( array $a ) {
        $rows = $a['rows'];
        $g    = self::groups( $rows );
        $site = (string) $a['site'];

        $h  = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . self::e( self::subject( $rows, $site, $a['change'] ) ) . '</title></head>';
        $h .= '<body style="margin:0;padding:0;background:#f3eee9;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">';
        $h .= '<div style="display:none;max-height:0;overflow:hidden;">' . self::e( self::narrative( $rows, $site ) ) . '</div>';
        $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3eee9;"><tr><td align="center" style="padding:24px 12px;">';
        $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">';

        // Header.
        $h .= '<tr><td style="background:#2b170d;border-radius:8px 8px 0 0;padding:22px 24px;">';
        $h .= '<div style="font-size:12px;font-weight:700;letter-spacing:1.5px;color:#ff9933;text-transform:uppercase;">The Hack Repair Guy&rsquo;s</div>';
        $h .= '<div style="font-size:26px;font-weight:700;color:#ffffff;margin:2px 0 6px;">Site Inspector</div>';
        $h .= '<div style="font-size:14px;color:#e8d8cc;">State of security for <strong style="color:#ffffff;">' . self::e( $site ) . '</strong> &middot; ' . self::e( $a['when'] ) . '</div>';
        $h .= '</td></tr>';

        $h    .= '<tr><td style="background:#ffffff;padding:24px;border:1px solid #ead9cc;border-top:0;">';
        $greet = '' !== (string) $a['recipient'] ? 'Hi ' . self::e( $a['recipient'] ) . ',' : 'Hi,';
        $h    .= '<p style="font-size:15px;line-height:1.6;color:#1d2327;margin:0 0 14px;">' . $greet . '</p>';

        if ( '' !== trim( (string) $a['note'] ) ) {
            $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;"><tr><td style="background:#fff8f2;border-left:4px solid #b34700;border-radius:4px;padding:12px 14px;font-size:14px;line-height:1.55;color:#1d2327;">';
            $h .= '<div style="font-size:12px;font-weight:700;color:#8f3a00;margin:0 0 4px;">A note from ' . self::e( $a['sender'] ) . '</div>' . nl2br( self::e( trim( $a['note'] ) ) );
            $h .= '</td></tr></table>';
        }

        $h .= '<p style="font-size:15px;line-height:1.6;color:#1d2327;margin:0 0 10px;">' . self::e( self::narrative( $rows, $site ) ) . '</p>';
        $h .= '<p style="font-size:14px;line-height:1.6;color:#50575e;margin:0 0 18px;">' . self::e( self::change_line( $a['change'], $a['older_when'] ) ) . '</p>';

        // Counts.
        $tiles = array(
            array( count( $g['fix'] ), 'need attention', '#fdecea', '#c62828' ),
            array( count( $g['look'] ), 'worth a look', '#fff4d6', '#8a5a00' ),
            array( count( $g['good'] ), 'looking good', '#e6f3ec', '#2e6b4f' ),
        );
        $h    .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:6px 0;margin:0 -6px 22px;"><tr>';
        foreach ( $tiles as $t ) {
            $h .= '<td width="33%" style="background:' . $t[2] . ';border-radius:6px;padding:12px 14px;color:' . $t[3] . ';"><div style="font-size:26px;font-weight:700;line-height:1.1;">' . (int) $t[0] . '</div><div style="font-size:13px;font-weight:600;">' . $t[1] . '</div></td>';
        }
        $h .= '</tr></table>';

        $sections = array(
            array( 'fix', 'Fix these first', '#e53935', true ),
            array( 'look', 'Worth a second look', '#d39b1f', true ),
        );
        foreach ( $sections as $s ) {
            if ( ! $g[ $s[0] ] ) {
                continue;
            }
            $h .= '<div style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8f3a00;margin:0 0 10px;">' . $s[1] . '</div>';
            $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">';
            foreach ( $g[ $s[0] ] as $r ) {
                $h .= self::finding_html( $r, $s[2], $s[3] );
            }
            $h .= '</table>';
        }

        if ( $g['good'] ) {
            $h .= '<div style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#2e6b4f;margin:4px 0 8px;">Looking good</div>';
            $h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">';
            foreach ( $g['good'] as $r ) {
                $h .= '<tr><td style="padding:5px 0;font-size:14px;line-height:1.5;color:#3c434a;"><span style="color:#2e6b4f;font-weight:700;">&#10003;</span> <strong style="color:#1d2327;">' . self::e( $r['title'] ) . '</strong> &mdash; ' . self::e( self::clean( $r['why'] ) ) . '</td></tr>';
            }
            $h .= '</table>';
        }

        // Real-world context.
        $h .= '<div style="font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#8f3a00;margin:0 0 8px;">Why this matters</div>';
        $h .= '<p style="font-size:14px;line-height:1.6;color:#3c434a;margin:0 0 20px;">Most hacked WordPress sites aren&rsquo;t broken into by anything clever. Attackers run automated tools that look for the same handful of openings on thousands of sites a day: an out-of-date plugin, an admin account nobody remembers, a backup left where anyone can download it. Checking regularly and closing the easy ones quickly is the most effective thing a site owner can do.</p>';

        if ( '' !== (string) $a['view_url'] ) {
            $h .= '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 6px;"><tr><td style="background:#b34700;border-radius:4px;"><a href="' . self::e( $a['view_url'] ) . '" style="display:inline-block;padding:11px 20px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Open the full report</a></td></tr></table>';
            $h .= '<p style="font-size:12px;color:#646970;margin:0 0 4px;">You&rsquo;ll need to be logged in as an administrator.</p>';
        }
        $h .= '</td></tr>';

        // Footer.
        $h .= '<tr><td style="padding:16px 24px;font-size:12px;line-height:1.6;color:#646970;">';
        $h .= 'Sent by ' . self::e( $a['sender'] ) . ' from ' . self::e( $site ) . ' with The Hack Repair Guy&rsquo;s Site Inspector. Usernames are partly hidden in this email. ';
        $h .= 'This is a read-only look at the site&rsquo;s own files and settings. It does not test what the public can reach, match known vulnerabilities, or scan for malware. ';
        $h .= 'Need a deeper look? Contact <a href="https://hackrepair.com/" title="The Hack Repair Guy: WordPress security and hack repair" style="color:#8f3a00;">The Hack Repair Guy</a>.';
        $h .= '</td></tr></table></td></tr></table></body></html>';
        return $h;
    }

    /** Plain-text version for mail clients that don't show HTML. */
    public static function text( array $a ) {
        $rows = $a['rows'];
        $g    = self::groups( $rows );
        $site = (string) $a['site'];
        $out  = ( '' !== (string) $a['recipient'] ? 'Hi ' . $a['recipient'] . ',' : 'Hi,' ) . "\n\n";
        if ( '' !== trim( (string) $a['note'] ) ) {
            $out .= 'A note from ' . $a['sender'] . ":\n" . trim( $a['note'] ) . "\n\n";
        }
        $out .= 'STATE OF SECURITY: ' . $site . ' (' . $a['when'] . ")\n\n";
        $out .= self::narrative( $rows, $site ) . "\n" . self::change_line( $a['change'], $a['older_when'] ) . "\n\n";
        $out .= count( $g['fix'] ) . ' need attention · ' . count( $g['look'] ) . ' worth a look · ' . count( $g['good'] ) . " looking good\n";
        foreach ( array(
			'fix'  => 'FIX THESE FIRST',
			'look' => 'WORTH A SECOND LOOK',
		) as $k => $label ) {
            if ( ! $g[ $k ] ) {
                continue;
            }
            $out .= "\n" . $label . "\n";
            foreach ( $g[ $k ] as $r ) {
                $out .= "\n- " . $r['title'] . "\n  " . self::clean( $r['why'] ) . "\n";
                foreach ( array_slice( $r['evidence'], 0, 3 ) as $line ) {
                    $out .= '  · ' . self::clean( $line ) . "\n";
                }
                if ( ! empty( $r['table']['rows'] ) ) {
                    foreach ( self::table_rows( $r['table'] ) as $cells ) {
                        $out .= '  * ' . implode( ' | ', array_filter( $cells, 'strlen' ) ) . "\n";
                    }
                }
                if ( '' !== (string) $r['next'] ) {
                    $out .= '  What to do: ' . self::clean( $r['next'] ) . "\n";
                }
            }
        }
        if ( $g['good'] ) {
            $out .= "\nLOOKING GOOD\n";
            foreach ( $g['good'] as $r ) {
                $out .= '✓ ' . $r['title'] . "\n";
            }
        }
        $out .= "\nWHY THIS MATTERS\nMost hacked WordPress sites aren't broken into by anything clever. Attackers run automated tools that look for the same handful of openings on thousands of sites a day. Checking regularly and closing the easy ones quickly is the most effective thing a site owner can do.\n";
        if ( '' !== (string) $a['view_url'] ) {
            $out .= "\nOpen the full report (administrator login required):\n" . $a['view_url'] . "\n";
        }
        $out .= "\n--\nSent by " . $a['sender'] . ' from ' . $site . " with The Hack Repair Guy's Site Inspector. Usernames are partly hidden. This is a read-only look at the site's own files and settings; it does not test what the public can reach, match known vulnerabilities, or scan for malware. Need a deeper look? https://hackrepair.com/\n";
        return $out;
    }

    /* ---------- WordPress side ---------- */

    /**
     * Administrators of this site with a valid email address.
     *
     * @return array[] Each: id, name, email.
     */
    public static function recipients() {
        $users = get_users(
            array(
                'role'    => 'administrator',
                'number'  => self::MAX_RECIPIENTS,
                'orderby' => 'display_name',
                'fields'  => array( 'ID', 'display_name', 'user_email' ),
            )
        );
        $out   = array();
        foreach ( $users as $u ) {
            if ( is_email( $u->user_email ) ) {
                $out[] = array(
					'id'    => (int) $u->ID,
					'name'  => (string) $u->display_name,
					'email' => (string) $u->user_email,
				);
            }
        }
        return $out;
    }

    /**
     * Send a saved report to the chosen administrators, one personal email each.
     *
     * @param array  $entry    Saved report from THRG_SI_History.
     * @param array  $older    Next older report, or null.
     * @param int[]  $user_ids Chosen user IDs (validated against recipients()).
     * @param string $note     Optional note from the sender.
     * @return array{sent:int,failed:int}
     */
    public static function send( array $entry, $older, array $user_ids, $note ) {
        $wanted  = array_map( 'intval', $user_ids );
        $to      = array_values(
            array_filter(
                self::recipients(),
                static function ( $r ) use ( $wanted ) {
                    return in_array( $r['id'], $wanted, true );
                }
            )
        );
        $sender  = wp_get_current_user();
        $fmt     = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        $change  = THRG_SI_History::change( $entry, $older );
        $base    = array(
            'rows'       => $entry['rows'],
            'site'       => self::site_domain(),
            'when'       => wp_date( $fmt, (int) $entry['time'] ),
            'change'     => $change,
            'older_when' => is_array( $older ) ? wp_date( get_option( 'date_format' ), (int) $older['time'] ) : '',
            'note'       => $note,
            'sender'     => $sender->display_name ? $sender->display_name : $sender->user_login,
            'view_url'   => add_query_arg( 'thrg_report', $entry['id'], admin_url( THRG_Site_Inspector::PAGE_URL ) ),
        );
        $subject = self::subject( $entry['rows'], $base['site'], $change );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if ( is_email( $sender->user_email ) ) {
            $headers[] = 'Reply-To: ' . $base['sender'] . ' <' . $sender->user_email . '>';
        }

        $sent = 0;
        foreach ( $to as $r ) {
            $args = $base + array( 'recipient' => $r['name'] );
            $alt  = self::text( $args );
            $hook = static function ( $mailer ) use ( $alt ) {
                $mailer->AltBody = $alt; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            };
            add_action( 'phpmailer_init', $hook );
            if ( wp_mail( $r['email'], $subject, self::html( $args ), $headers ) ) {
                ++$sent;
            }
            remove_action( 'phpmailer_init', $hook );
        }
        return array(
			'sent'   => $sent,
			'failed' => count( $to ) - $sent,
		);
    }
}
