/* global betterFileName */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	const buttonCallback = async function _buttonCallback( event ) {
		const button = event.target;
		const mediaId = button.getAttribute( 'data-media-id' );
		const input = document.querySelector(
			'#attachment-details-two-column-alt-text'
		);
		button.style.display = 'none';
		const spinner = button.parentNode.querySelector( '.spinner' );
		const generateAltText = button.parentNode.querySelector(
			'.generate-alt-text__loading'
		);
		spinner.style.visibility = 'visible';
		generateAltText.classList.remove( 'hidden' );

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
					const textChangeEvent = new Event( 'change', {
						bubbles: true,
						cancelable: true,
					} );
					input.dispatchEvent( textChangeEvent );
				}
			} catch ( error ) {
				console.error( 'Error:', error ); // eslint-disable-line no-console
			} finally {
				generateAltText.classList.add( 'hidden' );
				spinner.style.visibility = 'hidden';
				button.style.display = 'inline-block';
			}
		}
	};

	function addButtonHandler() {
		const button = document.querySelector( '.generate-alt-text' );
		if ( button ) {
			button.addEventListener( 'click', buttonCallback );
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
