<?php
/**
 * WordPress test database configuration.
 */

$db_name = getenv( 'WP_TESTS_DB_NAME' );
$db_name = false !== $db_name ? $db_name : 'wordpress_test';

if ( 'db' === $db_name ) {
	throw new RuntimeException( 'Refusing to run tests against the development database.' );
}

define( 'DB_NAME', $db_name );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ? getenv( 'WP_TESTS_DB_USER' ) : 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ? getenv( 'WP_TESTS_DB_PASSWORD' ) : 'root' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ? getenv( 'WP_TESTS_DB_HOST' ) : 'db' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Favourite Button Tests' );
define( 'WP_PHP_BINARY', PHP_BINARY );
define( 'ABSPATH', getenv( 'WP_CORE_DIR' ) ? getenv( 'WP_CORE_DIR' ) : dirname( __DIR__, 4 ) . '/' );

$table_prefix = 'wptests_';
