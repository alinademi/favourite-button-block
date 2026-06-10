# Technical Decisions

## Server-rendered block

The plugin uses a dynamic block. `render.php` returns the initial HTML, while
`view.js` loads the current user's state and handles button clicks.

The initial HTML contains no user state, favourite count, or REST nonce. This
keeps cached pages from showing one user's data to another user.

REST responses include a `Cache-Control: no-store` header. The block's
`save.js` returns `null` because WordPress renders the block on the server.

## REST endpoints

The plugin provides two endpoints:

- `/state` returns the public count. For a same-origin logged-in request, it
  also returns the user's favourite state and a REST nonce.
- `/toggle` requires a logged-in user and adds or removes that user's
  favourite.

Both endpoints accept published posts only. If a nonce expires, the frontend
loads a new state and retries the toggle once.

## Data storage

Each favourite is stored as a separate `fav_btn_favourite` user-meta row. The
row value is the post ID.

The public count is stored in `_fav_btn_count` post meta. Reading one count
from post meta is cheaper than searching all user metadata on every request.

Separate user-meta rows also avoid rewriting one large serialized array.

## Count updates

Counts are changed with one SQL update instead of reading the old value into
PHP and writing a new value. This prevents count updates from overwriting each
other when requests happen at the same time.

The SQL update keeps the count at zero or above. Because the update bypasses
WordPress metadata functions, the plugin clears the post-meta cache afterward.

Deleting a user decreases the counts for that user's favourites. Permanently
deleting a post removes its favourite rows from all users.

## Request and output safety

- REST post IDs are sanitized and must belong to published posts.
- The toggle endpoint requires WordPress cookie authentication and a valid
  REST nonce.
- SQL values use prepared statements.
- Rendered values and URLs are escaped.
- The button stays disabled until its state loads and while a request is
  running.

## Main files

- `src/favourite-button/render.php`: initial block HTML.
- `src/favourite-button/view.js`: state loading and toggle requests.
- `includes/class-fav-btn-rest-controller.php`: REST routes and responses.
- `includes/class-fav-btn-favorites-repository.php`: metadata and count
  updates.

## Roadmap

This sample stays focused on the main favourite workflow and was kept under
500 lines of handwritten application code. The following features were left
out for brevity:

- An admin analytics dashboard showing the most-favourited posts.
- Tools to inspect and rebuild stored favourite counts.
- Filters for date range, post type, and post status.
- Exporting favourite data for reporting.

These features can be added without changing the current storage or REST
contracts.
