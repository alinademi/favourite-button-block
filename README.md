# Favourite Button Block

Adds a WordPress block that lets logged-in users favourite posts.

The block shows a favourite button and an optional public count. Logged-out visitors see a login link.

Favourite state loads after the page renders, so the block works with page caching. Deleting a user updates post counts. Permanently deleting a post removes its favourites.

https://github.com/user-attachments/assets/5cab4022-258f-4fdc-a068-54005d6f16da

## Requirements

- WordPress 6.8 or newer
- PHP 7.4 or newer

## Installation

1. Upload the plugin to `wp-content/plugins/favourite-button`.
2. Activate Favourite Button Block.
3. Add the Favourite Button block to a post or template.

## Development

The block was scaffolded with `@wordpress/create-block`.

Install dependencies:

```sh
npm install
composer install
```

Build the block:

```sh
npm run build
```

Run tests and coding standards:

```sh
npm run test:unit
composer test
composer lint
```

PHP tests use WordPress PHPUnit and a separate `wordpress_test` database.

## Uninstall

Uninstalling the plugin removes all favourite user metadata and post counts.

## License

GPL-2.0-or-later
