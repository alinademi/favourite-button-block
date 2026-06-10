# Technical Decisions

## Summary

This is a Gutenberg block where logged-in users can favourite posts. Everyone sees the count.

Only you see your own state.

The tricky part is caching. If user data is in the page HTML, the cache serves it to the wrong person. So the block renders an empty shell.

JavaScript loads the real state after the page is already cached and served.

I split the code into three parts: one file renders HTML, one handles the API requests, one talks to the database.

Each does one job. Nothing overlaps.

For storage, each favourite is a separate database row.

If you use one row with all favourites packed together, concurrent clicks corrupt the data.

The count lives in post meta so reading it is one fast lookup. Count updates go straight to SQL so two requests hitting at the same time don't overwrite each other.

If a login nonce expires mid-session, the frontend quietly fetches a new one and retries. The button is disabled until state loads and while a request is running.

## Tradeoffs

- Storing a derived count makes reads fast, but the count must stay in sync
  with user metadata.
- Loading state after render adds a REST request, but keeps cached HTML safe. The request is lightweight and happens after paint, so the user doesn't feel it.
- Separate controller and repository classes add structure, but keep request
  handling and storage independently testable.
- Direct SQL gives atomic count changes, but requires manual metadata cache
  clearing.

## Safety and cleanup

- Both REST endpoints accept published posts only.
- `/toggle` requires cookie authentication and a valid REST nonce.
- SQL values use prepared statements.
- Rendered values and URLs are escaped.
- Deleting a user decreases counts for that user's favourites.
- Permanently deleting a post removes its favourite rows.

## Main files

This has a full stack of code, and testing coverage, but it's all focused on the main workflow. The key files to look at are:

- `src/favourite-button/render.php`: initial block HTML.
- `src/favourite-button/view.js`: state loading and toggle requests.
- `includes/class-fav-btn-rest-controller.php`: REST routes and responses.
- `includes/class-fav-btn-favorites-repository.php`: metadata and counts.

## Roadmap

This sample stays focused on the main favourite workflow and remains under
500 lines of handwritten application code. The following features were left
out for brevity:

- An admin analytics dashboard showing the most-favourited posts.
- Tools to inspect and rebuild stored favourite counts.
- Filters for date range, post type, and post status.
- Exporting favourite data for reporting.
