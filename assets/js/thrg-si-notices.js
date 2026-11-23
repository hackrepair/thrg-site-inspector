/** Keep other notices recoverable, including notices inserted after page load. */
( function () {
    'use strict';

    var cfg = window.thrgSiNotices || {};
    var body = document.body;
    var own = ':not(.thrg-si-own):not(.settings-error)';
    var selector = '#wpbody-content > .notice' + own + ', #wpbody-content > .update-nag, .wrap.thrg-inspector > .notice' + own + ', .wrap.thrg-inspector > .updated' + own + ', .wrap.thrg-inspector > .error' + own;
    var readyClass = 'thrg-si-notices-ready';
    var shownClass = 'thrg-si-notices-shown';

    if ( ! body || ! body.classList.contains( 'thrg-si-screen' ) || ! window.MutationObserver ) {
        return;
    }

    function initialize() {
        var anchor = document.querySelector( '.wrap.thrg-inspector > .wp-header-end' );
        var region = document.getElementById( 'wpbody-content' );
        if ( ! anchor || ! region ) {
            return;
        }
        var bar = document.createElement( 'div' );
        var message = document.createElement( 'span' );
        var toggle = document.createElement( 'button' );
        var pending = false;
        bar.className = 'thrg-si-notice-bar';
        bar.hidden = true;
        toggle.type = 'button';
        toggle.className = 'button-link';
        bar.appendChild( message );
        bar.appendChild( toggle );

        function refresh() {
            pending = false;
            try {
                var count = 0;
                Array.prototype.forEach.call( document.querySelectorAll( selector ), function ( node ) {
                    if ( ! node.hasAttribute( 'hidden' ) && ! node.classList.contains( 'hidden' ) && 'none' !== node.style.display ) {
                        count++;
                    }
                } );
                var shown = body.classList.contains( shownClass );
                message.textContent = 1 === count ? ( cfg.noticesOne || '1 notice is hidden on this page.' ) : ( cfg.noticesMany || '%d notices are hidden on this page.' ).replace( '%d', count );
                toggle.textContent = shown ? ( cfg.hide || 'Hide' ) : ( cfg.show || 'Show' );
                toggle.setAttribute( 'aria-expanded', shown ? 'true' : 'false' );
                bar.hidden = 0 === count;
                body.classList.toggle( readyClass, count > 0 );
            } catch ( error ) {
                body.classList.remove( readyClass );
                bar.hidden = true;
            }
        }
        toggle.addEventListener( 'click', function () {
            body.classList.toggle( shownClass );
            refresh();
        } );
        anchor.parentNode.insertBefore( bar, anchor.nextSibling );
        var observer = new window.MutationObserver( function ( mutations ) {
            var relevant = mutations.some( function ( mutation ) {
                return mutation.target !== bar && ! bar.contains( mutation.target );
            } );
            if ( relevant && ! pending ) {
                pending = true;
                window.setTimeout( refresh, 0 );
            }
        } );
        // Observe only the admin content; ignore the control's own updates above.
        observer.observe( region, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class', 'style', 'hidden' ] } );
        refresh();
    }

    if ( 'loading' === document.readyState ) {
        document.addEventListener( 'DOMContentLoaded', initialize );
    } else {
        initialize();
    }
}() );
