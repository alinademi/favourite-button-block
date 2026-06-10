const shell = `
	<div
		class="wp-block-fav-btn-favourite-button"
		data-post-id="42"
		data-show-count="1"
		data-rest-url="/wp-json/fav-btn/v1"
	>
		<button
			class="fav-btn-button"
			data-label="Add to favourites"
			data-favorited-label="Remove from favourites"
			data-added-label="Added to favourites"
			data-removed-label="Removed from favourites"
			data-error-label="Could not update favourites"
			data-count-label="Favourite count:"
			data-pending="true"
			disabled
		>
			<span class="fav-btn-label">Add to favourites</span>
			<span class="fav-btn-count" hidden></span>
		</button>
		<a class="fav-btn-login fav-btn-button" hidden>
			<span class="fav-btn-count" hidden></span>
		</a>
		<span class="fav-btn-status"></span>
	</div>
`;

const response = ( data ) =>
	Promise.resolve( {
		ok: true,
		status: 200,
		json: () => Promise.resolve( data ),
	} );

const settle = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'hydrates and toggles a favourite', async () => {
	document.body.innerHTML = shell;
	global.fetch = jest
		.fn()
		.mockImplementationOnce( () =>
			response( {
				loggedIn: true,
				favorited: false,
				count: 2,
				nonce: 'rest-nonce',
			} )
		)
		.mockImplementationOnce( () =>
			response( { favorited: true, count: 3 } )
		);

	require( '../view' );
	await settle();

	const button = document.querySelector( 'button' );
	expect( button.disabled ).toBe( false );
	expect( button.dataset.nonce ).toBe( 'rest-nonce' );
	expect( document.querySelector( '.fav-btn-count' ).textContent ).toBe(
		'2'
	);

	button.click();
	await settle();

	expect( fetch ).toHaveBeenLastCalledWith(
		'/wp-json/fav-btn/v1/toggle',
		expect.objectContaining( {
			method: 'POST',
			headers: expect.objectContaining( { 'X-WP-Nonce': 'rest-nonce' } ),
			body: '{"post_id":42}',
		} )
	);
	expect( button.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
	expect( button.textContent ).toContain( 'Remove from favourites' );
	expect( document.querySelector( '.fav-btn-count' ).textContent ).toBe(
		'3'
	);
} );
