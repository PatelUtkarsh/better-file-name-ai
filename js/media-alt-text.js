/* global betterFileName */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	function addButtonHandler() {
		const button = document.querySelector( '.generate-alt-text' );
		if ( button ) {
			button.addEventListener( 'click', async function () {
				const mediaId = button.getAttribute( 'data-media-id' );
				const input = document.querySelector(
					'#attachment-details-two-column-alt-text'
				);
				button.style.display = 'none';
				// Hide help.
				button.parentNode.querySelector( '.help' ).style.display =
					'none';
				const spinner = document.createElement( 'span' );
				spinner.classList.add( 'spinner' );
				button.parentNode.prepend( spinner );
				spinner.style.visibility = 'visible';
				const altText = input.value;
				if ( betterFileName?.api ) {
					try {
						const result = await fetch( betterFileName.api, {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'X-WP-Nonce': betterFileName.nonce,
							},
							body: JSON.stringify( {
								mediaId,
								altText,
							} ),
						} );
						const data = await result.json();
						if ( data.alt_text ) {
							input.value = data.alt_text;
							const event = new Event( 'change', {
								bubbles: true,
								cancelable: true,
							} );
							input.dispatchEvent( event );
						}
					} catch ( error ) {
						console.error( 'Error:', error );
					} finally {
						spinner.style.visibility = 'hidden';
						button.style.display = 'inline-block';
						button.parentNode.querySelector(
							'.help'
						).style.display = 'block';
					}
				}
			} );
		}
	}

	const ModalView = wp.media.view.Modal;
	wp.media.view.Modal = wp.media.view.Modal.extend( {
		open() {
			// Ensure that the main attachment fields are rendered.
			ModalView.prototype.open.apply( this );
			// debugger;
			addButtonHandler();
		},
	} );

	const EditAttachments = wp.media.view.MediaFrame.EditAttachments;
	wp.media.view.MediaFrame.EditAttachments =
		wp.media.view.MediaFrame.EditAttachments.extend( {
			rerender() {
				EditAttachments.prototype.rerender.apply( this, arguments );
				// debugger;
				addButtonHandler();
			},
		} );
} );
