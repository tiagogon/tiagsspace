/**
 * Keyboard arrow reorder for gallery preview mode.
 *
 * Left / Right  — move focus between items (no wrap)
 * Up / Down     — swap focused item with neighbor (wraps at edges)
 *
 * Requires: window.galleryKeyboardReorderTarget = 'gallery-{ID}'
 * set before this script loads.
 */
(function () {
    'use strict';

    var containerId = window.galleryKeyboardReorderTarget;
    if ( ! containerId ) return;

    var container = document.getElementById( containerId );
    if ( ! container ) return;

    var FLASH_CLASS = 'thumbnail--kb-flash';

    // ── Hide / undo stack ───────────────────────────────────
    // Each entry: { element, nextSiblingId, prevSiblingId, attachmentId }
    var hideStack = [];

    // ── Helpers ──────────────────────────────────────────────

    function getItems() {
        // Collect only gallery items, skip the masonry sizer element
        var children = container.children;
        var items = [];
        for ( var i = 0; i < children.length; i++ ) {
            if ( children[i].classList.contains( 'item' ) ) {
                items.push( children[i] );
            }
        }
        return items;
    }

    function flashItem( items, idx ) {
        for ( var i = 0; i < items.length; i++ ) {
            items[i].classList.remove( FLASH_CLASS );
        }
        void items[idx].offsetWidth; // reflow to restart animation
        items[idx].classList.add( FLASH_CLASS );
    }

    function scrollToFocused( items, focusIdx ) {
        // Ensure focused item + 1 neighbor before it are in the viewport
        var peekIdx = Math.max( focusIdx - 1, 0 );
        var focusRect = items[focusIdx].getBoundingClientRect();
        var peekRect  = items[peekIdx].getBoundingClientRect();

        // If the peek item's top is above viewport, scroll it into view at top
        if ( peekRect.top < 0 ) {
            items[peekIdx].scrollIntoView( { block: 'start', behavior: 'smooth' } );
        // If focused item's bottom is below viewport, scroll so it's visible
        } else if ( focusRect.bottom > window.innerHeight ) {
            items[focusIdx].scrollIntoView( { block: 'end', behavior: 'smooth' } );
        }
    }

    function masonryRelayout() {
        if ( typeof jQuery !== 'undefined' && jQuery.fn.masonry ) {
            var $grid = jQuery( container );
            if ( $grid.data( 'masonry' ) ) {
                $grid.masonry( 'reloadItems' ).masonry( 'layout' );
            }
        }
    }

    // ── AJAX helper for hide/unhide ──────────────────────────

    function ajaxToggleHide( attachmentId, hide ) {
        var ajaxUrl = window.galleryAjaxUrl;
        var nonce   = window.galleryHideNonce;
        if ( ! ajaxUrl || ! nonce ) return;

        var xhr = new XMLHttpRequest();
        xhr.open( 'POST', ajaxUrl, true );
        xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
        xhr.send(
            'action=gallery_toggle_hide_attachment' +
            '&attachment_id=' + encodeURIComponent( attachmentId ) +
            '&hide=' + ( hide ? '1' : '0' ) +
            '&_nonce=' + encodeURIComponent( nonce )
        );
    }

    // ── Hide a specific item element ────────────────────────

    function hideItemElement( el ) {
        var items = getItems();
        var idx = -1;
        for ( var i = 0; i < items.length; i++ ) {
            if ( items[i] === el ) { idx = i; break; }
        }
        if ( idx < 0 ) return;

        var attId = el.getAttribute( 'attachmentId' );

        // Record neighbours for undo positioning
        var nextSiblingId = ( idx + 1 < items.length ) ? items[ idx + 1 ].getAttribute( 'attachmentId' ) : null;
        var prevSiblingId = ( idx - 1 >= 0 ) ? items[ idx - 1 ].getAttribute( 'attachmentId' ) : null;

        hideStack.push( {
            element: el,
            attachmentId: attId,
            nextSiblingId: nextSiblingId,
            prevSiblingId: prevSiblingId
        } );

        // Remove from DOM
        container.removeChild( el );

        // Persist: set remove_from_default_gallery = 1
        ajaxToggleHide( attId, true );

        // Update focus
        var updatedItems = getItems();
        if ( updatedItems.length === 0 ) {
            container.removeAttribute( 'data-kb-focus' );
            container.removeAttribute( 'data-kb-tracked-id' );
        } else {
            var newFocus = Math.min( idx, updatedItems.length - 1 );
            var newId    = updatedItems[ newFocus ].getAttribute( 'attachmentId' );
            container.setAttribute( 'data-kb-focus', newFocus );
            container.setAttribute( 'data-kb-tracked-id', newId );
            flashItem( updatedItems, newFocus );
            scrollToFocused( updatedItems, newFocus );
        }

        masonryRelayout();
    }

    // ── Hide focused item (H) ───────────────────────────────

    function hideCurrentItem() {
        var items = getItems();
        if ( items.length === 0 ) return;

        var focusStr = container.getAttribute( 'data-kb-focus' );
        var focusIdx = focusStr !== null ? Math.min( parseInt( focusStr, 10 ) || 0, items.length - 1 ) : 0;

        hideItemElement( items[ focusIdx ] );
    }

    // ── Undo last hide (U) ──────────────────────────────────

    function undoHide() {
        if ( hideStack.length === 0 ) return;

        var entry = hideStack.pop();
        var items = getItems();

        // Find the reference sibling to insert next to
        var inserted = false;

        // 1. Try to insert before the item that was on the right
        if ( entry.nextSiblingId ) {
            for ( var i = 0; i < items.length; i++ ) {
                if ( items[i].getAttribute( 'attachmentId' ) === entry.nextSiblingId ) {
                    container.insertBefore( entry.element, items[i] );
                    inserted = true;
                    break;
                }
            }
        }

        // 2. Try to insert after the item that was on the left
        if ( ! inserted && entry.prevSiblingId ) {
            for ( var j = 0; j < items.length; j++ ) {
                if ( items[j].getAttribute( 'attachmentId' ) === entry.prevSiblingId ) {
                    // insertAfter: insert before the next sibling, or appendChild if last
                    var ref = items[j].nextElementSibling;
                    if ( ref ) {
                        container.insertBefore( entry.element, ref );
                    } else {
                        container.appendChild( entry.element );
                    }
                    inserted = true;
                    break;
                }
            }
        }

        // 3. Fallback: append to end
        if ( ! inserted ) {
            container.appendChild( entry.element );
        }

        // Persist: clear remove_from_default_gallery
        ajaxToggleHide( entry.attachmentId, false );

        // Set focus to the restored item
        var updatedItems = getItems();
        for ( var k = 0; k < updatedItems.length; k++ ) {
            if ( updatedItems[k].getAttribute( 'attachmentId' ) === entry.attachmentId ) {
                container.setAttribute( 'data-kb-focus', k );
                container.setAttribute( 'data-kb-tracked-id', entry.attachmentId );
                flashItem( updatedItems, k );
                scrollToFocused( updatedItems, k );
                break;
            }
        }

        masonryRelayout();
    }

    function applyOrder( newIds, items ) {
        var map = {};
        for ( var i = 0; i < items.length; i++ ) {
            map[ items[i].getAttribute( 'attachmentId' ) ] = items[i];
        }
        for ( var j = 0; j < newIds.length; j++ ) {
            var el = map[ String( newIds[j] ) ];
            if ( el ) container.appendChild( el );
        }
        masonryRelayout();
    }

    // ── Reorder algorithm (same as Instagram carousel kbComputeReorder) ──

    function computeReorder( ids, focusIdx, trackedIdx, direction ) {
        var arr  = ids.slice();
        var last = arr.length - 1;
        var tmp, old;

        if ( direction === 'down' ) {
            if ( trackedIdx === last ) {
                tmp = arr[focusIdx]; arr[focusIdx] = arr[trackedIdx]; arr[trackedIdx] = tmp;
                trackedIdx = focusIdx;
            } else {
                old = trackedIdx;
                tmp = arr[old]; arr[old] = arr[old + 1]; arr[old + 1] = tmp;
                trackedIdx = old + 1;
                if ( old !== focusIdx ) {
                    tmp = arr[focusIdx]; arr[focusIdx] = arr[old]; arr[old] = tmp;
                }
            }
        } else {
            if ( trackedIdx === focusIdx ) {
                tmp = arr[focusIdx]; arr[focusIdx] = arr[last]; arr[last] = tmp;
                trackedIdx = last;
            } else {
                old = trackedIdx;
                if ( old - 1 !== focusIdx ) {
                    tmp = arr[focusIdx]; arr[focusIdx] = arr[old - 1]; arr[old - 1] = tmp;
                }
                tmp = arr[old]; arr[old] = arr[old - 1]; arr[old - 1] = tmp;
                trackedIdx = old - 1;
            }
        }

        return { ids: arr, trackedIdx: trackedIdx };
    }

    // ── Keyboard handler ─────────────────────────────────────

    document.addEventListener( 'keydown', function ( e ) {
        var tag = e.target.tagName;
        if ( tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable ) return;
        if ( e.metaKey || e.ctrlKey || e.altKey ) return;

        var key = e.key;

        // S = Save Order
        if ( key === 's' || key === 'S' ) {
            if ( typeof window.orderAttachmentesOnWpDb === 'function' ) {
                e.preventDefault();
                window.orderAttachmentesOnWpDb();
            }
            return;
        }

        // H = Hide focused item
        if ( key === 'h' || key === 'H' ) {
            e.preventDefault();
            hideCurrentItem();
            return;
        }

        // U = Undo last hide
        if ( key === 'u' || key === 'U' ) {
            e.preventDefault();
            undoHide();
            return;
        }

        if ( key !== 'ArrowUp' && key !== 'ArrowDown' && key !== 'ArrowLeft' && key !== 'ArrowRight' ) return;

        var items = getItems();
        if ( items.length < 2 ) return;

        e.preventDefault();

        var last     = items.length - 1;
        var focusStr = container.getAttribute( 'data-kb-focus' );
        var focusIdx, trackedId, trackedIdx;

        if ( focusStr === null ) {
            focusIdx   = 0;
            trackedId  = items[0].getAttribute( 'attachmentId' );
            trackedIdx = 0;
        } else {
            focusIdx  = Math.min( parseInt( focusStr, 10 ) || 0, last );
            trackedId = container.getAttribute( 'data-kb-tracked-id' );
            trackedIdx = -1;
            for ( var j = 0; j < items.length; j++ ) {
                if ( items[j].getAttribute( 'attachmentId' ) === trackedId ) {
                    trackedIdx = j;
                    break;
                }
            }
            if ( trackedIdx < 0 ) {
                trackedIdx = focusIdx;
                trackedId  = items[focusIdx].getAttribute( 'attachmentId' );
            }
        }

        // Left / Right: move focus, reset tracked item (no wrap)
        if ( key === 'ArrowLeft' || key === 'ArrowRight' ) {
            var newFocusIdx = key === 'ArrowLeft' ? focusIdx - 1 : focusIdx + 1;
            if ( newFocusIdx < 0 || newFocusIdx > last ) return;
            focusIdx  = newFocusIdx;
            trackedId = items[focusIdx].getAttribute( 'attachmentId' );
            container.setAttribute( 'data-kb-focus', focusIdx );
            container.setAttribute( 'data-kb-tracked-id', trackedId );
            flashItem( items, focusIdx );
            scrollToFocused( items, focusIdx );
            return;
        }

        // Up / Down: reorder
        var ids = [];
        for ( var k = 0; k < items.length; k++ ) {
            ids.push( items[k].getAttribute( 'attachmentId' ) );
        }

        var direction = key === 'ArrowDown' ? 'down' : 'up';
        var result    = computeReorder( ids, focusIdx, trackedIdx, direction );

        trackedIdx = result.trackedIdx;
        trackedId  = String( result.ids[trackedIdx] );
        container.setAttribute( 'data-kb-focus', focusIdx );
        container.setAttribute( 'data-kb-tracked-id', trackedId );

        applyOrder( result.ids, items );
        var updatedItems = getItems();
        flashItem( updatedItems, focusIdx );
    } );

    // ── Intercept HIDE button clicks (capture phase) ────────
    // Fires before the inline onclick="removeDiv(this)" so we can
    // route the hide through hideItemElement (push to hideStack + AJAX).

    container.addEventListener( 'click', function ( e ) {
        if ( ! e.target.classList.contains( 'remove' ) ) return;

        e.stopPropagation();
        e.preventDefault();

        // Walk up to find the .item ancestor
        var item = e.target.parentNode;
        while ( item && item !== container ) {
            if ( item.classList.contains( 'item' ) ) break;
            item = item.parentNode;
        }
        if ( ! item || item === container ) return;

        hideItemElement( item );
    }, true ); // ← capture phase

    // ── Click to focus ────────────────────────────────────────

    container.addEventListener( 'click', function ( e ) {
        var target = e.target;
        // Walk up from the click target to find the .item ancestor
        while ( target && target !== container ) {
            if ( target.classList.contains( 'item' ) ) break;
            target = target.parentNode;
        }
        if ( ! target || target === container ) return;

        var items = getItems();
        for ( var i = 0; i < items.length; i++ ) {
            if ( items[i] === target ) {
                var attId = items[i].getAttribute( 'attachmentId' );
                container.setAttribute( 'data-kb-focus', i );
                container.setAttribute( 'data-kb-tracked-id', attId );
                flashItem( items, i );
                scrollToFocused( items, i );
                break;
            }
        }
    } );

    // ── Public reset for Sortable sync ───────────────────────

    window.galleryKeyboardReorderReset = function () {
        container.removeAttribute( 'data-kb-focus' );
        container.removeAttribute( 'data-kb-tracked-id' );
    };

})();
