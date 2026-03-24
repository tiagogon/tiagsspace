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

    function masonryRelayout() {
        if ( typeof jQuery !== 'undefined' && jQuery.fn.masonry ) {
            var $grid = jQuery( container );
            if ( $grid.data( 'masonry' ) ) {
                $grid.masonry( 'reloadItems' ).masonry( 'layout' );
            }
        }
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
        flashItem( getItems(), focusIdx );
    } );

    // ── Public reset for Sortable sync ───────────────────────

    window.galleryKeyboardReorderReset = function () {
        container.removeAttribute( 'data-kb-focus' );
        container.removeAttribute( 'data-kb-tracked-id' );
    };

})();
