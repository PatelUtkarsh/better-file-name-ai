import { Button, Spinner, TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState, useRef, useCallback, useEffect } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const POLL_INTERVAL_MS = 3000;

const STATUS_MESSAGES = {
	pending: __( 'Job queued, waiting to start…', 'better-file-name' ),
	processing: __( 'Generating image…', 'better-file-name' ),
};

const ImageGenerationIntegration = () => {
	const [ prompt, setPrompt ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ statusMessage, setStatusMessage ] = useState( null );
	const [ errorMessage, setErrorMessage ] = useState( null );
	const pollTimerRef = useRef( null );

	const { postTitle, postContent } = useSelect( ( select ) => ( {
		postTitle: select( 'core/editor' ).getEditedPostAttribute( 'title' ),
		postContent:
			select( 'core/editor' ).getEditedPostAttribute( 'content' ),
	} ) );

	const { editPost } = useDispatch( 'core/editor' );

	const stopPolling = useCallback( () => {
		if ( pollTimerRef.current ) {
			clearTimeout( pollTimerRef.current );
			pollTimerRef.current = null;
		}
	}, [] );

	// Clean up polling on unmount.
	useEffect( () => {
		return () => stopPolling();
	}, [ stopPolling ] );

	const pollJobStatus = useCallback(
		async ( jobId ) => {
			try {
				const data = await apiFetch( {
					path: `/better-file-name/v1/image-job-status/${ jobId }`,
					method: 'GET',
				} );

				if ( data?.status === 'completed' && data?.attachment_id ) {
					stopPolling();
					editPost( { featured_media: data.attachment_id } );
					setStatusMessage( null );
					setIsLoading( false );
					return;
				}

				if ( data?.status === 'failed' ) {
					stopPolling();
					setErrorMessage(
						data?.error ||
							__( 'Image generation failed.', 'better-file-name' )
					);
					setStatusMessage( null );
					setIsLoading( false );
					return;
				}

				// Still pending or processing — update status and continue polling.
				setStatusMessage(
					STATUS_MESSAGES[ data?.status ] ||
						__( 'Working…', 'better-file-name' )
				);
				pollTimerRef.current = setTimeout(
					() => pollJobStatus( jobId ),
					POLL_INTERVAL_MS
				);
			} catch ( error ) {
				stopPolling();
				setErrorMessage(
					error?.message ||
						__( 'Failed to check job status.', 'better-file-name' )
				);
				setStatusMessage( null );
				setIsLoading( false );
			}
		},
		[ editPost, stopPolling ]
	);

	const generateImage = async () => {
		setIsLoading( true );
		setErrorMessage( null );
		setStatusMessage(
			__( 'Submitting generation request…', 'better-file-name' )
		);

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

			if ( data?.job_id ) {
				setStatusMessage(
					__( 'Job queued, waiting to start…', 'better-file-name' )
				);
				pollTimerRef.current = setTimeout(
					() => pollJobStatus( data.job_id ),
					POLL_INTERVAL_MS
				);
			} else {
				throw new Error(
					__( 'No job ID returned from server.', 'better-file-name' )
				);
			}
		} catch ( error ) {
			setErrorMessage(
				error?.message ||
					error?.error ||
					__(
						'Failed to start image generation.',
						'better-file-name'
					)
			);
			setStatusMessage( null );
			setIsLoading( false );
		}
	};

	return (
		<div style={ { marginBlockStart: '16px' } }>
			<TextareaControl
				label={ __( 'Enter Additional Prompt', 'better-file-name' ) }
				value={ prompt }
				help={ __(
					'To generate a better image, you can enter additional prompt here in addition to title and content.',
					'better-file-name'
				) }
				onChange={ ( value ) => setPrompt( value ) }
			/>
			<Button
				variant="primary"
				onClick={ generateImage }
				disabled={ isLoading }
			>
				{ isLoading ? (
					<>
						<Spinner />
						{ statusMessage && (
							<span style={ { marginInlineStart: '8px' } }>
								{ statusMessage }
							</span>
						) }
					</>
				) : (
					__( 'Generate Image', 'better-file-name' )
				) }
			</Button>
			{ errorMessage && (
				<div style={ { color: '#cc1818', marginBlockStart: '10px' } }>
					{ errorMessage }
				</div>
			) }
		</div>
	);
};

const ImageGenerationWrapper = ( FilteredComponent ) => {
	return ( props ) => {
		return (
			<>
				<FilteredComponent
					{ ...props }
					hint="Override by featured video."
				/>
				<ImageGenerationIntegration />
			</>
		);
	};
};

addFilter(
	'editor.PostFeaturedImage',
	'better-file-name/image-generation-integration',
	ImageGenerationWrapper,
	10
);
