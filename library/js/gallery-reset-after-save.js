/**
 * After Gutenberg save, reset gallery ACF fields that the server
 * clears via update_field() so the UI matches the database state.
 */
( function() {
    if ( ! wp || ! wp.data ) {
        return;
    }

    var wasSaving = false;

    wp.data.subscribe( function() {
        var isSaving = wp.data.select( 'core/editor' ).isSavingPost();
        var isAutosave = wp.data.select( 'core/editor' ).isAutosavingPost();

        if ( wasSaving && ! isSaving && ! isAutosave ) {
            setTimeout( function() {
                var fieldsToUncheck = [
                    'order_just_the_new_added_pictures',
                    'order_media_attachments',
                    're-attach_images_from_post_editor',
                    'select_all_vertical_images',
                    'select_all_horizontal_images'
                ];

                fieldsToUncheck.forEach( function( fieldName ) {
                    var wrapper = document.querySelector( '[data-name="' + fieldName + '"]' );
                    if ( ! wrapper ) {
                        return;
                    }

                    // Handle checkbox / true_false fields
                    var checkbox = wrapper.querySelector( 'input[type="checkbox"]' );
                    if ( checkbox && checkbox.checked ) {
                        checkbox.checked = false;
                        checkbox.dispatchEvent( new Event( 'change', { bubbles: true } ) );
                    }

                    // Handle radio fields — select the "empty" or first option (which is the default/off state)
                    var radios = wrapper.querySelectorAll( 'input[type="radio"]' );
                    if ( radios.length ) {
                        radios.forEach( function( radio ) {
                            if ( radio.checked ) {
                                radio.checked = false;
                                radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
                            }
                        } );
                    }

                    // Handle select fields
                    var select = wrapper.querySelector( 'select' );
                    if ( select && select.value ) {
                        select.value = '';
                        select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
                    }
                } );
            }, 500 );
        }

        wasSaving = isSaving && ! isAutosave;
    } );
} )();
