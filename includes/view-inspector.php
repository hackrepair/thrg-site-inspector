<?php
/**
 * Site Inspector screen. Expects $rows (null before an inspection).
 *
 * @package THRG_Site_Inspector
 */

defined( 'ABSPATH' ) || exit;

$thrg_si_tone = static function ( $status ) {
    if ( 'Potential weakness' === $status ) {
        return 'attention';
    }
    return 'No issue observed' === $status ? 'observed' : 'review';
};

$thrg_si_table = static function ( $table ) {
    if ( ! $table ) {
        return;
    }
    $mask = isset( $table['mask'] ) ? (int) $table['mask'] : -1;
    echo '<div class="si-table-wrap"><table class="widefat striped si-table"><thead><tr>';
    foreach ( $table['head'] as $cell ) {
        echo '<th scope="col">' . esc_html( $cell ) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ( $table['rows'] as $cells ) {
        echo '<tr>';
        foreach ( array_values( $cells ) as $i => $cell ) {
            $target = is_array( $cell ) ? THRG_Site_Inspector::href( $cell[1] ) : null;
            $text   = is_array( $cell ) ? (string) $cell[0] : (string) $cell;
            echo '<td data-label="' . esc_attr( $table['head'][ $i ] ) . '">';
            if ( $target ) {
                echo '<a href="' . esc_url( $target['url'] ) . '" target="_blank" rel="noopener noreferrer">';
            }
            if ( $i === $mask ) {
                echo '<span class="si-full">' . esc_html( $text ) . '</span><span class="si-masked">' . esc_html( THRG_Site_Inspector::mask( $text ) ) . '</span>';
            } else {
                echo esc_html( $text );
            }
            if ( $target ) {
                echo wp_kses_post( THRG_Site_Inspector::NEW_TAB_HINT ) . '</a>';
            }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};

$thrg_si_actions = static function ( $actions ) {
    if ( ! $actions ) {
        return;
    }
    echo '<p class="si-actions">';
    foreach ( $actions as $action ) {
        $target = THRG_Site_Inspector::href( $action[1] );
        if ( $target ) {
            echo '<a class="button" href="' . esc_url( $target['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $action[0] ) . ( $target['external'] ? ' ↗' : '' ) . wp_kses_post( THRG_Site_Inspector::NEW_TAB_HINT ) . '</a> ';
        }
    }
    echo '</p>';
};
?>
<div class="wrap thrg-inspector">
    <div class="si-head">
        <img class="si-mascot thrg-brand-heart" src="<?php echo esc_url( plugins_url( '../assets/brand/thrg-heart-128.png', __FILE__ ) ); ?>" alt="" width="56" height="56" />
        <div>
            <p class="si-eyebrow">The Hack Repair Guy&rsquo;s</p>
            <h1>Site Inspector <span class="si-version"><?php echo esc_html( 'v' . THRG_Site_Inspector::version() ); ?></span></h1>
            <p class="si-byline">A quick, friendly site security inspection to identify what might need your attention today.</p>
        </div>
    </div>

    <hr class="wp-header-end" />

    <section class="si-block si-start">
        <h2 class="si-section"><?php echo $saved ? 'Saved report' : 'Inspect this site'; ?></h2>
        <div class="si-card si-toolbar">
            <form method="post" onsubmit="var b=this.querySelector('button');b.disabled=true;b.textContent='Inspecting…';">
                <?php wp_nonce_field( 'thrg_si_inspect' ); ?>
                <button class="button button-primary" name="inspect" type="submit"><?php echo null === $rows ? 'Inspect this site' : ( $saved ? 'Run new inspection' : 'Run inspection again' ); ?></button>
            </form>
            <span class="si-muted">Just a look around. Nothing on your site is changed, and your reports stay right here unless you choose to email one.</span>
            <a href="<?php echo esc_url( admin_url( 'options-general.php?page=thrg-site-health-controls' ) ); ?>">Site Health Controls</a>
        </div>
    </section>

    <?php
    $thrg_si_notices = array(
        'cleared'      => 'Report history cleared.',
        'deleted'      => 'Report deleted.',
        'missing'      => 'That report is no longer available.',
        'paused'       => 'History is paused. New inspections will not be saved.',
        'resumed'      => 'History resumed. New inspections will be saved.',
        'emailed'      => THRG_SI_Email::feedback( $sent, $mail_failed ),
        'mail-partial' => THRG_SI_Email::feedback( $sent, $mail_failed ) . ' Check your site\'s email setup for failed messages.',
        'mail-failed'  => THRG_SI_Email::feedback( $sent, $mail_failed ) . ' Check your site\'s email setup for failed messages.',
        'mail-none'    => 'Choose at least one administrator to email.',
        'mail-wait'    => 'An email was just sent. Please wait a minute before sending another.',
    );
    $thrg_si_warn    = array( 'missing', 'mail-partial', 'mail-failed', 'mail-none', 'mail-wait' );
    if ( isset( $thrg_si_notices[ $flag ] ) && ! in_array( $flag, array( 'emailed', 'mail-partial', 'mail-failed' ), true ) ) :
        ?>
        <div class="si-callout<?php echo in_array( $flag, $thrg_si_warn, true ) ? ' si-callout--warning' : ''; ?>" role="status"><p><?php echo esc_html( $thrg_si_notices[ $flag ] ); ?></p></div>
    <?php endif; ?>

    <?php if ( null === $rows ) : ?>
        <section class="si-block si-welcome">
            <h2 class="si-section">What gets checked</h2>
            <div class="si-card">
            <ul class="si-checklist">
                <li><?php echo wp_kses_post( THRG_Site_Inspector::linkify( '[[update-core.php|Updates]] waiting for WordPress, plugins, and themes' ) ); ?></li>
                <li><?php echo wp_kses_post( THRG_Site_Inspector::linkify( '[[users.php?role=administrator|Administrator accounts]], including recent and unusual ones' ) ); ?></li>
                <li>PHP files hiding in uploads</li>
                <li>Backups, logs, and config copies left in the web folder</li>
                <li><?php echo wp_kses_post( THRG_Site_Inspector::linkify( '[[plugins.php?plugin_status=mustuse|Must-use plugins]] and [[plugins.php?plugin_status=dropins|drop-ins]]' ) ); ?></li>
                <li><?php echo wp_kses_post( THRG_Site_Inspector::linkify( 'Unused [[plugins.php?plugin_status=inactive|plugins]] and [[themes.php|themes]]' ) ); ?></li>
                <li><?php echo wp_kses_post( THRG_Site_Inspector::linkify( 'PHP version, security keys, code editor, error display' ) ); ?></li>
                <li><?php echo wp_kses_post( THRG_Site_Inspector::linkify( 'Registration, HTTPS settings ([[options-general.php|General Settings]]), wp-config.php permissions' ) ); ?></li>
            </ul>
            </div>
        </section>
    <?php else : ?>
        <?php
        $thrg_si_counts = array(
            'attention' => 0,
            'review'    => 0,
            'observed'  => 0,
        );
        foreach ( $rows as $thrg_si_row ) {
            ++$thrg_si_counts[ $thrg_si_tone( $thrg_si_row['status'] ) ];
        }
        $thrg_si_issues = array_values(
            array_filter(
                $rows,
                static function ( $r ) {
					return 'No issue observed' !== $r['status'];
				}
            )
        );
        $thrg_si_passed = array_values(
            array_filter(
                $rows,
                static function ( $r ) {
					return 'No issue observed' === $r['status'];
				}
            )
        );
        $thrg_si_first  = array();
        foreach ( $rows as $thrg_si_row ) {
            $thrg_si_first += array( $thrg_si_tone( $thrg_si_row['status'] ) => $thrg_si_row['id'] );
        }
        $thrg_si_when = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $saved ? (int) $saved['time'] : null );
        $thrg_si_site = $saved ? (string) $saved['site'] : wp_parse_url( home_url(), PHP_URL_HOST );
        ?>
        <?php if ( $saved ) : ?>
            <div class="si-callout si-callout--warning" role="status"><strong>Saved report</strong> from <?php echo esc_html( $thrg_si_when ); ?><?php echo '' !== (string) $saved['name'] ? ' by ' . esc_html( $saved['name'] ) : ''; ?>. This is a snapshot, so what you see may have changed since. <a href="<?php echo esc_url( admin_url( THRG_Site_Inspector::PAGE_URL ) ); ?>">Back to Site Inspector</a></div>
        <?php endif; ?>
        <div class="si-summary" role="status">
            <?php
            foreach ( array(
				'attention' => 'need attention',
				'review'    => 'to review',
				'observed'  => 'nothing found',
			) as $thrg_si_k => $thrg_si_label ) :
				?>
                <?php $thrg_si_anchor = 'observed' === $thrg_si_k ? 'si-passed' : ( isset( $thrg_si_first[ $thrg_si_k ] ) ? $thrg_si_first[ $thrg_si_k ] : '' ); ?>
                <?php if ( $thrg_si_anchor && $thrg_si_counts[ $thrg_si_k ] ) : ?>
                    <a class="si-sum si-sum-<?php echo esc_attr( $thrg_si_k ); ?>" href="#<?php echo esc_attr( $thrg_si_anchor ); ?>"><strong><?php echo (int) $thrg_si_counts[ $thrg_si_k ]; ?></strong> <?php echo esc_html( $thrg_si_label ); ?></a>
                <?php else : ?>
                    <div class="si-sum si-sum-<?php echo esc_attr( $thrg_si_k ); ?>"><strong><?php echo (int) $thrg_si_counts[ $thrg_si_k ]; ?></strong> <?php echo esc_html( $thrg_si_label ); ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="si-meta"><?php echo esc_html( $thrg_si_site ); ?><br /><?php echo esc_html( $thrg_si_when ); ?><br /><a class="si-sharelink" href="#si-share">Share this report &darr;</a></div>
        </div>

        <div class="si-report">
            <aside class="si-index">
                <nav aria-label="Inspection findings">
                    <?php foreach ( $thrg_si_issues as $thrg_si_row ) : ?>
                        <a href="#<?php echo esc_attr( $thrg_si_row['id'] ); ?>" class="si-dot-<?php echo esc_attr( $thrg_si_tone( $thrg_si_row['status'] ) ); ?>"><?php echo esc_html( $thrg_si_row['title'] ); ?></a>
                    <?php endforeach; ?>
                    <?php if ( $thrg_si_passed ) : ?>
                        <a href="#si-passed" class="si-dot-observed">Nothing found (<?php echo count( $thrg_si_passed ); ?>)</a>
                    <?php endif; ?>
                </nav>
                <a class="si-create" href="#si-share">Copy report</a>
            </aside>

            <section class="si-findings" aria-label="Findings">
                <?php if ( ! $thrg_si_issues ) : ?>
                    <div class="si-callout" role="status"><p><strong>Nothing needs attention in these checks.</strong> That covers only what is listed below. It is not a guarantee that the site is clean or secure.</p></div>
                <?php endif; ?>

                <?php foreach ( $thrg_si_issues as $thrg_si_row ) : ?>
                    <?php $thrg_si_t = $thrg_si_tone( $thrg_si_row['status'] ); ?>
                    <article id="<?php echo esc_attr( $thrg_si_row['id'] ); ?>" class="si-block si-finding si-<?php echo esc_attr( $thrg_si_t ); ?>">
                        <h2 class="si-section"><span><?php echo esc_html( $thrg_si_row['title'] ); ?></span> <span class="si-chip si-chip--<?php echo esc_attr( $thrg_si_t ); ?>"><?php echo esc_html( $thrg_si_row['status'] ); ?></span></h2>
                        <div class="si-card">
                        <p class="si-why"><?php echo wp_kses_post( THRG_Site_Inspector::linkify( $thrg_si_row['why'] ) ); ?></p>
                        <div class="si-evidence">
                            <?php foreach ( $thrg_si_row['evidence'] as $thrg_si_line ) : ?>
                                <p><?php echo wp_kses_post( THRG_Site_Inspector::linkify( $thrg_si_line ) ); ?></p>
                            <?php endforeach; ?>
                            <?php $thrg_si_table( $thrg_si_row['table'] ); ?>
                        </div>
                        <?php if ( '' !== $thrg_si_row['next'] ) : ?>
                            <div class="si-next"><strong>What to do:</strong> <?php echo wp_kses_post( THRG_Site_Inspector::linkify( $thrg_si_row['next'] ) ); ?></div>
                        <?php endif; ?>
                        <?php $thrg_si_actions( $thrg_si_row['actions'] ); ?>
                        <?php if ( '' !== $thrg_si_row['limits'] ) : ?>
                            <details class="si-limits"><summary>What this check can't tell you</summary><p><?php echo esc_html( $thrg_si_row['limits'] ); ?></p></details>
                        <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if ( $thrg_si_passed ) : ?>
                    <section id="si-passed" class="si-block si-passed">
                        <h2 class="si-section"><span>Nothing found</span> <span class="si-chip si-chip--observed"><?php echo count( $thrg_si_passed ); ?> checks</span></h2>
                        <div class="si-card">
                        <?php foreach ( $thrg_si_passed as $thrg_si_row ) : ?>
                            <details id="<?php echo esc_attr( $thrg_si_row['id'] ); ?>">
                                <summary><span class="si-check" aria-hidden="true">✓</span> <strong><?php echo esc_html( $thrg_si_row['title'] ); ?></strong> <span><?php echo wp_kses_post( THRG_Site_Inspector::linkify( $thrg_si_row['why'] ) ); ?></span></summary>
                                <div class="si-passed-body">
                                    <?php foreach ( $thrg_si_row['evidence'] as $thrg_si_line ) : ?>
                                        <p><?php echo wp_kses_post( THRG_Site_Inspector::linkify( $thrg_si_line ) ); ?></p>
                                    <?php endforeach; ?>
                                    <?php $thrg_si_table( $thrg_si_row['table'] ); ?>
                                    <?php if ( '' !== $thrg_si_row['limits'] ) : ?>
                                        <p class="si-note"><?php echo esc_html( $thrg_si_row['limits'] ); ?></p>
                                    <?php endif; ?>
                                    <?php $thrg_si_actions( $thrg_si_row['actions'] ); ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section id="si-share" class="si-block si-share" aria-labelledby="si-share-title">
                    <h2 id="si-share-title" class="si-section">Send this report to your designer or developer</h2>
                    <div class="si-card">
                    <p>Copy it into an email or ticket, save it as a text file, or email a &ldquo;state of security&rdquo; summary to this site&rsquo;s administrators. It lists every finding, what was observed, and what to do next.</p>
                    <div class="si-share-buttons">
                        <button type="button" class="button button-primary" id="si-copy">Copy report</button>
                        <button type="button" class="button" id="si-download" data-filename="<?php echo esc_attr( 'site-inspector-' . sanitize_file_name( $thrg_si_site ) . '-' . wp_date( 'Y-m-d' ) . '.txt' ); ?>">Download as text file</button>
                        <button type="button" class="button" id="si-print">Print or save as PDF</button>
                        <button type="button" class="button" id="si-email-toggle" aria-expanded="false" aria-controls="si-email"<?php disabled( ! $report_id || ! $recipients ); ?>>Email to administrators</button>
                        <span class="si-share-status" id="si-share-status" role="status" aria-live="polite"></span>
                    </div>
                    <?php if ( ! $report_id ) : ?>
                        <p class="si-note">Emailing needs a saved report. Turn report history back on and run a new inspection to email it.</p>
                    <?php elseif ( ! $recipients ) : ?>
                        <p class="si-note">No administrator accounts with a valid email address were found.</p>
                    <?php else : ?>
                        <?php
                        $thrg_si_entry = null;
                        $thrg_si_prev  = null;
                        foreach ( $history as $thrg_si_k => $thrg_si_h ) {
                            if ( $thrg_si_h['id'] === $report_id ) {
                                $thrg_si_entry = $thrg_si_h;
                                $thrg_si_prev  = isset( $history[ $thrg_si_k + 1 ] ) ? $history[ $thrg_si_k + 1 ] : null;
                                break;
                            }
                        }
                        ?>
                        <form id="si-email" class="si-email" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
                            <input type="hidden" name="action" value="thrg_si_email_report" />
                            <input type="hidden" name="report" value="<?php echo esc_attr( $report_id ); ?>" />
                            <?php wp_nonce_field( 'thrg_si_email_report' ); ?>
                            <fieldset>
                                <legend>Send a &ldquo;state of security&rdquo; email to:</legend>
                                <p class="si-email-all"><label><input type="checkbox" id="si-email-all" checked /> All administrators (<?php echo count( $recipients ); ?>)</label></p>
                                <div class="si-email-list">
                                <?php foreach ( $recipients as $thrg_si_r ) : ?>
                                    <label><input type="checkbox" name="recipients[]" value="<?php echo (int) $thrg_si_r['id']; ?>" checked /> <?php echo esc_html( $thrg_si_r['name'] ); ?> <span class="si-muted">&lt;<?php echo esc_html( $thrg_si_r['email'] ); ?>&gt;</span></label>
                                <?php endforeach; ?>
                                </div>
                            </fieldset>
                            <p>
                                <label for="si-email-note">Add a personal note (optional)</label><br />
                                <textarea id="si-email-note" name="note" rows="3" maxlength="<?php echo (int) THRG_SI_Email::NOTE_LENGTH; ?>" class="large-text" placeholder="For example: Here's this week's check. Can we fix the first two items by Friday?"></textarea>
                            </p>
                            <?php if ( $thrg_si_entry ) : ?>
                                <p class="si-email-subject"><span class="si-muted">Subject:</span> <?php echo esc_html( THRG_SI_Email::subject( $thrg_si_entry['rows'], THRG_SI_Email::site_domain(), THRG_SI_History::change( $thrg_si_entry, $thrg_si_prev ) ) ); ?></p>
                            <?php endif; ?>
                            <p class="si-email-actions">
                                <button type="submit" class="button button-primary">Send email</button>                            </p>
                        </form>
                    <?php endif; ?>
                    <?php if ( in_array( $flag, array( 'emailed', 'mail-partial', 'mail-failed' ), true ) ) : ?>
                        <div class="si-callout<?php echo 'emailed' === $flag ? '' : ' si-callout--warning'; ?>" role="status"><p><?php echo esc_html( $thrg_si_notices[ $flag ] ); ?></p></div>
                    <?php endif; ?>
                    <textarea id="si-report-text" class="large-text si-report" readonly rows="12" aria-label="Plain-text report"><?php echo esc_textarea( THRG_Site_Inspector::plain_text( $rows, $thrg_si_site, $thrg_si_when ) ); ?></textarea>
                    <p class="si-note">Includes usernames, file names and update details for this site. Share it only with people you trust.</p>
                    </div>
                </section>
                <script>
                (function () {
                    var box = document.getElementById('si-report-text'), status = document.getElementById('si-share-status');
                    function say(t) { status.textContent = t; setTimeout(function () { status.textContent = ''; }, 3000); }
                    document.getElementById('si-copy').addEventListener('click', function () {
                        box.select();
                        var done = function () { say('Copied to clipboard'); };
                        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(box.value).then(done, function () { document.execCommand('copy'); done(); }); }
                        else { try { document.execCommand('copy'); done(); } catch (e) { say('Press Ctrl+C or Cmd+C to copy'); } }
                    });
                    document.getElementById('si-download').addEventListener('click', function () {
                        var a = document.createElement('a'), url = URL.createObjectURL(new Blob([box.value], { type: 'text/plain' }));
                        a.href = url; a.download = this.getAttribute('data-filename'); document.body.appendChild(a); a.click();
                        setTimeout(function () { URL.revokeObjectURL(url); a.remove(); }, 500); say('Download started');
                    });
                    document.getElementById('si-print').addEventListener('click', function () { window.print(); });
                    var toggle = document.getElementById('si-email-toggle'), form = document.getElementById('si-email');
                    if (toggle && form) {
                        toggle.addEventListener('click', function () {
                            form.hidden = !form.hidden;
                            toggle.setAttribute('aria-expanded', form.hidden ? 'false' : 'true');
                            if (!form.hidden) { form.querySelector('input[name="recipients[]"]').focus(); }
                        });
                        var all = document.getElementById('si-email-all'), boxes = form.querySelectorAll('input[name="recipients[]"]');
                        all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); });
                        boxes.forEach(function (b) { b.addEventListener('change', function () { var n = form.querySelectorAll('input[name="recipients[]"]:checked').length; all.checked = n === boxes.length; all.indeterminate = n > 0 && n < boxes.length; }); });
                        form.addEventListener('submit', function (e) {
                            if (!form.querySelector('input[name="recipients[]"]:checked')) { e.preventDefault(); say('Choose at least one administrator'); return; }
                            var b = form.querySelector('button[type="submit"]'); b.disabled = true; b.textContent = 'Sending…';
                        });
                        if (/[?&]thrg_si_done=mail-/.test(location.search)) { form.hidden = false; toggle.setAttribute('aria-expanded', 'true'); }
                    }
                })();
                </script>
            </section>
        </div>
    <?php endif; ?>

    <?php
    $thrg_si_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
    $thrg_si_cur = $saved ? $saved['id'] : '';
    ?>
    <section id="si-history" class="si-block si-history" aria-labelledby="si-history-title">
        <h2 id="si-history-title" class="si-section">Your reports history and changes over time</h2>
        <div class="si-card">
        <?php if ( ! $history ) : ?>
            <p class="si-note">Nothing saved yet. Each inspection you run is saved here so you can review it later and see what changed.</p>
        <?php else : ?>
            <div class="si-table-wrap">
                <table class="widefat striped si-table si-history-table">
                    <thead><tr><th scope="col">When</th><th scope="col">Run by</th><th scope="col">Findings</th><th scope="col">Changes</th><th scope="col"><span class="screen-reader-text">Actions</span></th></tr></thead>
                    <tbody>
                    <?php foreach ( $history as $thrg_si_i => $thrg_si_e ) : ?>
                        <?php
                        $thrg_si_older  = isset( $history[ $thrg_si_i + 1 ] ) ? $history[ $thrg_si_i + 1 ] : null;
                        $thrg_si_change = THRG_SI_History::change( $thrg_si_e, $thrg_si_older );
                        ?>
                        <tr<?php echo $thrg_si_cur === $thrg_si_e['id'] ? ' class="si-current"' : ''; ?>>
                            <td data-label="When"><?php echo esc_html( wp_date( $thrg_si_fmt, (int) $thrg_si_e['time'] ) ); ?></td>
                            <td data-label="Run by"><?php echo esc_html( (string) ( isset( $thrg_si_e['name'] ) ? $thrg_si_e['name'] : '' ) ); ?></td>
                            <td data-label="Findings">
                                <span class="si-chip si-chip--attention"><?php echo (int) $thrg_si_e['attention']; ?><span class="screen-reader-text"> need attention</span></span>
                                <span class="si-chip si-chip--review"><?php echo (int) $thrg_si_e['review']; ?><span class="screen-reader-text"> to review</span></span>
                                <span class="si-chip si-chip--observed"><?php echo (int) $thrg_si_e['observed']; ?><span class="screen-reader-text"> nothing found</span></span>
                            </td>
                            <td data-label="Changes">
                                <?php
                                if ( $thrg_si_change['first'] ) {
                                    echo 'First report';
                                } elseif ( ! $thrg_si_change['new'] && ! $thrg_si_change['resolved'] ) {
                                    echo 'No change';
                                } else {
                                    $thrg_si_parts = array();
                                    if ( $thrg_si_change['new'] ) {
                                        $thrg_si_parts[] = '<span class="si-worse">' . (int) $thrg_si_change['new'] . ' new</span>';
                                    }
                                    if ( $thrg_si_change['resolved'] ) {
                                        $thrg_si_parts[] = '<span class="si-better">' . (int) $thrg_si_change['resolved'] . ' resolved</span>';
                                    }
                                    echo wp_kses( implode( ', ', $thrg_si_parts ), array( 'span' => array( 'class' => true ) ) );
                                }
                                ?>
                            </td>
                            <td class="si-history-actions">
                                <?php if ( $thrg_si_cur === $thrg_si_e['id'] ) : ?>
                                    <strong>Viewing</strong>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( admin_url( THRG_Site_Inspector::PAGE_URL . '&thrg_report=' . $thrg_si_e['id'] ) ); ?>" target="_blank" rel="noopener noreferrer">View<?php echo wp_kses_post( THRG_Site_Inspector::NEW_TAB_HINT ); ?></a>
                                <?php endif; ?>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="si-inline">
                                    <input type="hidden" name="action" value="thrg_si_delete_report" />
                                    <input type="hidden" name="report" value="<?php echo esc_attr( $thrg_si_e['id'] ); ?>" />
                                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'thrg_si_delete_report' ) ); ?>" />
                                    <button type="submit" class="button-link si-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="si-note">Up to <?php echo (int) THRG_SI_History::MAX_ENTRIES; ?> reports are kept, newest first, in this site's database. They include usernames and file names. Nothing is sent unless you email a report.</p>
        <div class="si-history-controls">
            <?php if ( $history ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="si-inline" onsubmit="return window.confirm('Delete every saved report? This cannot be undone.');">
                    <input type="hidden" name="action" value="thrg_si_clear_history" />
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'thrg_si_clear_history' ) ); ?>" />
                    <button type="submit" class="button-link si-delete">Clear history</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="si-inline">
                <input type="hidden" name="action" value="thrg_si_toggle_history" />
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'thrg_si_toggle_history' ) ); ?>" />
                <button type="submit" class="button-link"><?php echo THRG_SI_History::enabled() ? 'Pause history' : 'Resume history'; ?></button>
            </form>
        </div>
        </div>
    </section>

    <section id="support" class="si-block si-support" aria-labelledby="si-support-title">
        <h2 id="si-support-title" class="si-section"><?php esc_html_e( 'Support', 'thrg-site-inspector' ); ?></h2>
        <div class="si-card">
            <p>&#9749; <?php echo wp_kses_post( __( 'If you found this plugin helpful, please <a href="https://hackrepair.com/donations/buy-jim-a-coffee" target="_blank" rel="noopener">buy Jim a cup of coffee?</a>', 'thrg-site-inspector' ) ); ?></p>
            <p><span class="dashicons dashicons-editor-help" aria-hidden="true"></span> <?php echo wp_kses_post( __( 'Questions and Feedback: For questions, ideas, or help with the plugin, <a href="https://github.com/hackrepair/thrg-site-inspector/discussions" target="_blank" rel="noopener">visit GitHub Discussions</a>.', 'thrg-site-inspector' ) ); ?></p>
        </div>
    </section>

    <footer class="si-coverage">
        <div class="si-coverage-col">
            <p><strong>Limits:</strong> This is a read-only look at your site's own files and settings. It does not test what the public can reach, match known vulnerabilities, or scan for malware.</p>
        </div>
        <div class="si-coverage-col si-help">
            <img class="si-mascot" src="<?php echo esc_url( plugins_url( '../assets/brand/thrg-heart-128.png', __FILE__ ) ); ?>" alt="" width="44" height="44" />
            <p>Need a deeper look?<br />Contact <a href="https://hackrepair.com/" title="The Hack Repair Guy: WordPress security and hack repair" target="_blank" rel="noopener noreferrer">The Hack Repair Guy<?php echo wp_kses_post( THRG_Site_Inspector::NEW_TAB_HINT ); ?></a>.</p>
        </div>
    </footer>
</div>
