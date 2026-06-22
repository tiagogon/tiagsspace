/**
 * Trash-with-media choice modal.
 *
 * One shared modal wired into three entry points:
 *   - the list-table row "Trash" link (a.submitdelete in .row-actions),
 *   - the bulk "Move to Trash" action (form#posts-filter),
 *   - the Gutenberg editor "Move to trash" button (.editor-post-trash).
 *
 * Choices:
 *   trashOnly  → native trash (post only).
 *   trashMedia → server route that flags the post so its media is deleted on
 *                permanent deletion.
 *   deleteNow  → server route that deletes the post + media immediately.
 *
 * Per-post nonced URLs come from data-attributes on the row link, or from
 * localized tiagsspaceTrash.editor* values in the editor. All copy is localized
 * via tiagsspaceTrash.
 */
( function( $ ) {
	'use strict';

	var L = window.tiagsspaceTrash || {};
	var stylesInjected = false;

	function injectStyles() {
		if ( stylesInjected ) {
			return;
		}
		stylesInjected = true;

		var css =
			'.tiagsspace-trash-overlay{position:fixed;inset:0;z-index:100100;' +
			'background:rgba(0,0,0,.55);display:flex;align-items:center;' +
			'justify-content:center;}' +
			'.tiagsspace-trash-modal{background:#fff;border-radius:4px;max-width:460px;' +
			'width:calc(100% - 40px);padding:24px;box-shadow:0 6px 24px rgba(0,0,0,.3);' +
			'font-size:13px;line-height:1.5;}' +
			'.tiagsspace-trash-modal h2{margin:0 0 8px;font-size:18px;}' +
			'.tiagsspace-trash-modal p{margin:0 0 16px;color:#50575e;}' +
			'.tiagsspace-trash-modal .tiagsspace-trash-buttons{display:flex;' +
			'flex-direction:column;gap:8px;}' +
			'.tiagsspace-trash-modal .button{width:100%;text-align:center;' +
			'justify-content:center;}' +
			'.tiagsspace-trash-modal .tiagsspace-trash-cancel{margin-top:8px;' +
			'background:none;border:none;color:#2271b1;cursor:pointer;' +
			'text-decoration:underline;padding:6px;}';

		$( '<style id="tiagsspace-trash-styles">' ).text( css ).appendTo( document.head );
	}

	/**
	 * Show the modal. Calls onChoose with one of:
	 * 'trashOnly' | 'trashMedia' | 'deleteNow' | null (cancelled).
	 *
	 * @param {Object}   opts    { bulk: bool }
	 * @param {Function} onChoose
	 */
	function showModal( opts, onChoose ) {
		injectStyles();
		opts = opts || {};

		var $overlay = $( '<div class="tiagsspace-trash-overlay" role="dialog" aria-modal="true">' );
		var $modal   = $( '<div class="tiagsspace-trash-modal">' ).appendTo( $overlay );

		$( '<h2>' ).text( L.title || 'Move to Trash' ).appendTo( $modal );
		$( '<p>' ).text( ( opts.bulk ? L.introBulk : L.intro ) || '' ).appendTo( $modal );

		var $buttons = $( '<div class="tiagsspace-trash-buttons">' ).appendTo( $modal );

		function addButton( label, choice, extraClass ) {
			$( '<button type="button" class="button">' )
				.addClass( extraClass || '' )
				.text( label || choice )
				.on( 'click', function() {
					done( choice );
				} )
				.appendTo( $buttons );
		}

		addButton( L.trashOnly, 'trashOnly', 'button-primary' );
		addButton( L.trashMedia, 'trashMedia' );
		addButton( L.deleteNow, 'deleteNow', 'button-link-delete' );

		$( '<button type="button" class="tiagsspace-trash-cancel">' )
			.text( L.cancel || 'Cancel' )
			.on( 'click', function() {
				done( null );
			} )
			.appendTo( $modal );

		var settled = false;

		function done( choice ) {
			if ( settled ) {
				return;
			}

			// Extra guard on the irreversible path.
			if ( 'deleteNow' === choice && L.confirmNow && ! window.confirm( L.confirmNow ) ) {
				return;
			}

			settled = true;
			$( document ).off( 'keydown.tiagsspaceTrash' );
			$overlay.remove();
			onChoose( choice );
		}

		// Click outside the modal or press Escape to cancel.
		$overlay.on( 'click', function( e ) {
			if ( e.target === $overlay[ 0 ] ) {
				done( null );
			}
		} );
		$( document ).on( 'keydown.tiagsspaceTrash', function( e ) {
			if ( 27 === e.keyCode ) {
				done( null );
			}
		} );

		$overlay.appendTo( document.body );
	}

	/* -----------------------------------------------------------------
	 * 1. List-table row "Trash" link.
	 * -------------------------------------------------------------- */
	$( document ).on( 'click', '.row-actions .trash a.submitdelete', function( e ) {
		var $link       = $( this );
		var nativeUrl   = $link.attr( 'href' );
		var trashMedia  = $link.data( 'tiagsspaceTrashMedia' );
		var deleteNow   = $link.data( 'tiagsspaceDeleteNow' );

		// No custom URLs (e.g. capability filtered them out): let WP handle it.
		if ( ! trashMedia && ! deleteNow ) {
			return;
		}

		e.preventDefault();

		showModal( {}, function( choice ) {
			if ( 'trashOnly' === choice ) {
				window.location = nativeUrl;
			} else if ( 'trashMedia' === choice ) {
				window.location = trashMedia;
			} else if ( 'deleteNow' === choice ) {
				window.location = deleteNow;
			}
		} );
	} );

	/* -----------------------------------------------------------------
	 * 2. Bulk "Move to Trash".
	 * -------------------------------------------------------------- */
	var $bulkForm = $( '#posts-filter' );

	if ( $bulkForm.length ) {
		$bulkForm.on( 'submit', function( e ) {
			var form = this;

			if ( form.dataset.tiagsspaceProceed ) {
				delete form.dataset.tiagsspaceProceed;
				return;
			}

			var $trashSelects = $( 'select[name="action"], select[name="action2"]', form )
				.filter( function() {
					return 'trash' === this.value;
				} );

			if ( ! $trashSelects.length ) {
				return; // Not a bulk trash; ignore.
			}

			e.preventDefault();

			showModal( { bulk: true }, function( choice ) {
				if ( ! choice ) {
					return;
				}

				if ( 'trashOnly' !== choice ) {
					var value = 'deleteNow' === choice ? L.bulkDelete : L.bulkTrash;

					$trashSelects.each( function() {
						if ( ! $( this ).find( 'option[value="' + value + '"]' ).length ) {
							$( '<option>' ).val( value ).appendTo( this );
						}
						this.value = value;
					} );
				}

				form.dataset.tiagsspaceProceed = '1';
				if ( typeof form.requestSubmit === 'function' ) {
					form.requestSubmit();
				} else {
					form.submit();
				}
			} );
		} );
	}

	/* -----------------------------------------------------------------
	 * 3. Gutenberg editor "Move to trash" button.
	 * -------------------------------------------------------------- */
	if ( window.wp && wp.data && ( L.editorTrashUrl || L.editorDeleteUrl ) ) {
		document.addEventListener(
			'click',
			function( e ) {
				var btn = e.target.closest && e.target.closest( '.editor-post-trash' );

				if ( ! btn || btn.dataset.tiagsspaceProceed ) {
					return;
				}

				e.preventDefault();
				e.stopImmediatePropagation();

				showModal( {}, function( choice ) {
					if ( 'trashOnly' === choice ) {
						var editor = wp.data.dispatch( 'core/editor' );
						if ( editor && typeof editor.trashPost === 'function' ) {
							editor.trashPost();
						} else {
							// Fallback: re-fire the native click.
							btn.dataset.tiagsspaceProceed = '1';
							btn.click();
						}
					} else if ( 'trashMedia' === choice ) {
						window.location = L.editorTrashUrl;
					} else if ( 'deleteNow' === choice ) {
						window.location = L.editorDeleteUrl;
					}
				} );
			},
			true // capture, so we beat React's own handler
		);
	}
} )( jQuery );
