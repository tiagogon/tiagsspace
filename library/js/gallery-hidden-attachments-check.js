/**
 * Before publishing / scheduling / setting private, check for hidden
 * gallery attachments and prompt the user to delete or keep them.
 *
 * Strategy: wp.data.subscribe fires synchronously after each dispatch.
 * When user clicks "Publish", Gutenberg dispatches editPost({status:'publish'})
 * then savePost(). Our callback fires between the two, locks saving, so
 * savePost() finds the lock and returns early. We then run our AJAX check
 * and either unlock + savePost() ourselves, or revert the status.
 */
( function() {
    if ( ! wp || ! wp.data ) {
        return;
    }

    var LOCK_NAME  = 'hidden-attachments-check';
    var checking   = false;
    var lastStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );

    // Statuses a post can be in *before* the user makes it public. A real
    // publish/schedule/private action transitions FROM one of these. On initial
    // page load of an already-public post the status hydrates straight to
    // 'publish' (from null/undefined), which must NOT be treated as a transition.
    var DRAFT_STATUSES = [ 'draft', 'auto-draft', 'pending' ];

    wp.data.subscribe( function() {
        if ( checking ) {
            return;
        }

        var newStatus  = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
        var isAutosave = wp.data.select( 'core/editor' ).isAutosavingPost();

        if ( isAutosave ) {
            lastStatus = newStatus;
            return;
        }

        // Detect transition to a public-facing status (don't require isSaving).
        // Require the previous status to be a draft-like one so that initial
        // hydration of an already-public post (null/undefined -> 'publish') and
        // re-saving an existing public post ('publish' -> 'publish') don't fire.
        var isPublishTransition = (
            newStatus !== lastStatus &&
            DRAFT_STATUSES.indexOf( lastStatus ) !== -1 &&
            ( newStatus === 'publish' || newStatus === 'future' || newStatus === 'private' )
        );

        if ( ! isPublishTransition ) {
            lastStatus = newStatus;
            return;
        }

        var savedPrevStatus = lastStatus;
        lastStatus = newStatus;
        checking   = true;

        // Lock immediately — this prevents the upcoming savePost() call
        wp.data.dispatch( 'core/editor' ).lockPostSaving( LOCK_NAME );

        var postId = wp.data.select( 'core/editor' ).getCurrentPostId();

        jQuery.ajax( {
            url: galleryHiddenCheck.ajaxurl,
            type: 'POST',
            data: {
                action: 'gallery_count_hidden_attachments',
                post_id: postId,
                _nonce: galleryHiddenCheck.nonce
            },
            success: function( response ) {
                if ( ! response.success || response.data.count === 0 ) {
                    // No hidden attachments — unlock and trigger save
                    wp.data.dispatch( 'core/editor' ).unlockPostSaving( LOCK_NAME );
                    checking = false;
                    wp.data.dispatch( 'core/editor' ).savePost();
                    return;
                }

                var count = response.data.count;
                var wantDelete = confirm(
                    'This post has ' + count + ' hidden attachment' + ( count > 1 ? 's' : '' ) + '.\n\n' +
                    'Click OK to permanently delete them, or Cancel to keep them.'
                );

                if ( wantDelete ) {
                    // Delete hidden attachments then proceed with publish
                    jQuery.ajax( {
                        url: galleryHiddenCheck.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'gallery_delete_hidden_attachments',
                            post_id: postId,
                            _nonce: galleryHiddenCheck.nonce
                        },
                        complete: function() {
                            wp.data.dispatch( 'core/editor' ).unlockPostSaving( LOCK_NAME );
                            checking = false;
                            wp.data.dispatch( 'core/editor' ).savePost();
                        }
                    } );
                } else {
                    // User chose not to delete — ask whether to proceed or cancel
                    var proceedAnyway = confirm(
                        'Proceed without deleting the hidden attachments?'
                    );

                    if ( proceedAnyway ) {
                        wp.data.dispatch( 'core/editor' ).unlockPostSaving( LOCK_NAME );
                        checking = false;
                        wp.data.dispatch( 'core/editor' ).savePost();
                    } else {
                        // Cancel the publish — revert to previous status
                        wp.data.dispatch( 'core/editor' ).editPost( { status: savedPrevStatus } );
                        wp.data.dispatch( 'core/editor' ).unlockPostSaving( LOCK_NAME );
                        wp.data.dispatch( 'core/notices' ).createNotice(
                            'info',
                            'Publish cancelled.',
                            { isDismissible: true }
                        );
                        lastStatus = savedPrevStatus;
                        checking = false;
                    }
                }
            },
            error: function() {
                // On AJAX error, don't block the save
                wp.data.dispatch( 'core/editor' ).unlockPostSaving( LOCK_NAME );
                checking = false;
                wp.data.dispatch( 'core/editor' ).savePost();
            }
        } );
    } );
} )();
