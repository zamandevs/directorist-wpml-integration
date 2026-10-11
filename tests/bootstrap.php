<?php

if ( in_array( getenv( 'DIRECTORIST_TEST_REQUEST_CONTEXT' ), [ 'admin', 'ajax' ], true ) ) {
	define( 'WP_ADMIN', true );
}
if ( 'ajax' === getenv( 'DIRECTORIST_TEST_REQUEST_CONTEXT' ) ) {
	define( 'DOING_AJAX', true );
}
$core = getenv( 'DIRECTORIST_CORE_TEST_DIR' );
if ( ! $core || ! is_dir( $core ) ) {
	throw new RuntimeException( 'Set DIRECTORIST_CORE_TEST_DIR to a qualified core.' );
}
require_once $core . '/vendor/autoload.php';
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $core . '/vendor/yoast/phpunit-polyfills' );
require_once $core . '/vendor/wp-phpunit/wp-phpunit/includes/functions.php';
tests_add_filter( 'muplugins_loaded', static function () use ( $core ) {
	add_filter( 'directorist_page_cache_allow_runtime_mutation', '__return_false', PHP_INT_MAX );
	update_option( 'active_plugins', [ 'directorist/directorist-base.php' ] );
	if ( 'extension-first' === getenv( 'DIRECTORIST_TEST_PLUGIN_ORDER' ) ) {
		require_once dirname( __DIR__ ) . '/directorist-wpml-integration.php';
	}
	require_once $core . '/directorist-base.php';
	require_once dirname( __DIR__ ) . '/directorist-wpml-integration.php';
} );
require_once $core . '/vendor/wp-phpunit/wp-phpunit/includes/bootstrap.php';
