import { Button, Spinner, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const DalleIntegration = () => {
	const [ prompt, setPrompt ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( null );
	const { postTitle, postContent } = useSelect( ( select ) => ( {
		postTitle: select( 'core/editor' ).getEditedPostAttribute( 'title' ),
		postContent:
			select( 'core/editor' ).getEditedPostAttribute( 'content' ),
	} ) );

	const { editPost } = useDispatch( 'core/editor' );

	const generateImage = async () => {
		setIsLoading( true );
		setErrorMessage( null );
		try {
			const data = await apiFetch( {
				path: '/better-file-name/v1/dalle-generate-image',
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				data: {
					prompt,
					postTitle,
					postContent,
				},
			} );

			if ( data?.attachment_id ) {
				editPost( { featured_media: data.attachment_id } );
			}
		} catch ( error ) {
			if ( error?.error ) {
				setErrorMessage( error.error );
			}
		} finally {
			setIsLoading( false );
		}
	};

	return (
		<div style={ { marginBlockStart: '16px' } }>
			<TextareaControl
				label="Enter Additional prompt"
				value={ prompt }
				help={ __(
					'To generate a better image, you can enter additional prompt here in addition to title and content.',
					'better-file-name'
				) }
				onChange={ ( value ) => setPrompt( value ) }
			/>
			<Button isPrimary onClick={ generateImage } disabled={ isLoading }>
				{ isLoading ? (
					<Spinner />
				) : (
					__( 'Generate Image', 'better-file-name' )
				) }
			</Button>
			{ errorMessage && (
				<div style={ { color: 'red', marginBlockStart: '10px' } }>
					{ errorMessage }
				</div>
			) }
		</div>
	);
};

const DalleWrapper = ( FilteredComponent ) => {
	return ( props ) => {
		return (
			<>
				<FilteredComponent
					{ ...props }
					hint="Override by featured video."
				/>
				<DalleIntegration />
			</>
		);
	};
};

addFilter(
	'editor.PostFeaturedImage',
	'better-file-name/dalle-integration',
	DalleWrapper,
	10
);
