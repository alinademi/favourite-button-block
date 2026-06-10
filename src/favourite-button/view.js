/**
 * The server ships a neutral shell, this
 * script fetches per-user state + nonce from the uncached /state endpoint
 * and hydrates, then handles click → /toggle.
 */

const SELECTOR = '.wp-block-fav-btn-favourite-button';

function setStatus( root, message ) {
	root.querySelector( '.fav-btn-status' ).textContent = message;
}

function applyState( root, state ) {
	const button = root.querySelector( '.fav-btn-button' );
	const login = root.querySelector( '.fav-btn-login' );

	if ( root.dataset.showCount === '1' ) {
		root.querySelectorAll( '.fav-btn-count' ).forEach( ( count ) => {
			count.textContent = String( state.count );
			count.hidden = false;
		} );
	}

	if ( ! state.loggedIn ) {
		button.hidden = true;
		login.hidden = false;
		return;
	}

	button.hidden = false;
	login.hidden = true;
	const label = root.querySelector( '.fav-btn-label' );
	button.dataset.nonce = state.nonce || button.dataset.nonce;
	button.setAttribute( 'aria-pressed', String( state.favorited ) );
	button.classList.toggle( 'is-favorited', state.favorited );
	label.textContent = state.favorited
		? button.dataset.favoritedLabel
		: button.dataset.label;

	button.disabled = false;
	delete button.dataset.pending;
}

// On plain permalinks rest_url() is `index.php?rest_route=/...`, so extra
// params must join with `&`, not a second `?`.
function endpoint( root, path, params = '' ) {
	const base = `${ root.dataset.restUrl }${ path }`;
	if ( ! params ) {
		return base;
	}
	return base + ( base.includes( '?' ) ? '&' : '?' ) + params;
}

async function fetchState( root ) {
	const response = await fetch(
		endpoint( root, '/state', `post_id=${ root.dataset.postId }` ),
		{ credentials: 'same-origin' }
	);
	if ( ! response.ok ) {
		throw new Error( `state ${ response.status }` );
	}
	return response.json();
}

function postToggle( root, nonce ) {
	return fetch( endpoint( root, '/toggle' ), {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': nonce,
		},
		body: JSON.stringify( { post_id: Number( root.dataset.postId ) } ),
	} );
}

async function handleClick( root ) {
	const button = root.querySelector( '.fav-btn-button' );

	if ( button.dataset.pending ) {
		return;
	}
	button.dataset.pending = 'true';
	button.disabled = true;
	button.setAttribute( 'aria-busy', 'true' );

	try {
		let response = await postToggle( root, button.dataset.nonce );

		// 403 = expired nonce (they live ~12-24h). Refresh once, retry once.
		if ( response.status === 403 ) {
			const state = await fetchState( root );
			button.dataset.nonce = state.nonce;
			response = await postToggle( root, button.dataset.nonce );
		}

		if ( ! response.ok ) {
			throw new Error( `toggle ${ response.status }` );
		}

		const result = await response.json();
		applyState( root, {
			loggedIn: true,
			nonce: button.dataset.nonce,
			...result,
		} );
		setStatus(
			root,
			`${
				result.favorited
					? button.dataset.addedLabel
					: button.dataset.removedLabel
			}. ${ button.dataset.countLabel } ${ result.count }`
		);
	} catch {
		setStatus( root, button.dataset.errorLabel );
	} finally {
		delete button.dataset.pending;
		button.disabled = false;
		button.removeAttribute( 'aria-busy' );
	}
}

async function hydrate( root ) {
	const button = root.querySelector( '.fav-btn-button' );

	try {
		applyState( root, await fetchState( root ) );
	} catch {
		// Keep the button disabled and announce the error to screen readers.
		setStatus( root, button.dataset.unavailableLabel );
		return;
	}

	button.addEventListener( 'click', () => handleClick( root ) );
}

document.querySelectorAll( SELECTOR ).forEach( hydrate );
