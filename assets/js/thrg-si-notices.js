/**
 * Other plugins' admin notices are tucked away on this plugin's screens; this
 * adds a small bar that says how many are hidden and lets the user show them.
 */
( function () {
    'use strict';

    var cfg = window.thrgSiNotices || {};
    var body = document.body;
    var own = ':not(.thrg-si-own):not(.settings-error)';
    var selector = '#wpbody-content > .notice' + own + ', #wpbody-content > .update-nag, .wrap.thrg-inspector > .notice' + own + ', .wrap.thrg-inspector > .updated' + own + ', .wrap.thrg-inspector > .error' + own;
    var shownClass = 'thrg-si-notices-shown';
    var bar = null;
    var message = null;
    var toggle = null;

    if ( ! body || ! body.classList.contains( 'thrg-si-screen' ) ) {
        return;
    }

    function hiddenCount() {
        var count = 0;
        Array.prototype.forEach.call( document.querySelectorAll( selector ), function ( node ) {
            if ( node.hasAttribute( 'hidden' ) || node.classList.contains( 'hidden' ) || ( node.style && 'none' === node.style.display ) ) {
                return;
            }
            count++;
        } );
        return count;
    }

    function refresh() {
        var count = hiddenCount();
        var shown = body.classList.contains( shownClass );
        if ( ! bar ) {
            if ( 0 === count ) {
                return;
            }
            var anchor = document.querySelector( '.wrap.thrg-inspector > .wp-header-end' );
            if ( ! anchor ) {
                return;
            }
            bar = document.createElement( 'div' );
            bar.className = 'thrg-si-notice-bar';
            message = document.createElement( 'span' );
            toggle = document.createElement( 'button' );
            toggle.type = 'button';
            toggle.className = 'button-link';
            toggle.addEventListener( 'click', function () {
                body.classList.toggle( shownClass );
                refresh();
            } );
            bar.appendChild( message );
            bar.appendChild( toggle );
            anchor.parentNode.insertBefore( bar, anchor.nextSibling );
        }
        bar.hidden = 0 === count;
        message.textContent = ( 1 === count ? ( cfg.noticesOne || '' ) : ( cfg.noticesMany || '%d' ).replace( '%d', count ) );
        toggle.textContent = shown ? ( cfg.hide || 'Hide' ) : ( cfg.show || 'Show' );
        toggle.setAttribute( 'aria-expanded', shown ? 'true' : 'false' );
    }

    document.addEventListener( 'DOMContentLoaded', function () {
        refresh();
        window.setTimeout( refresh, 1500 );
    } );
    window.addEventListener( 'load', refresh );
}() );
