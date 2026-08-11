<?php
/**
 * WordPress test bootstrap for Directorist WPML Integration.
 */

$directorist_core_dir = getenv( 'DIRECTORIST_CORE_TEST_DIR' );

if ( ! $directorist_core_dir || ! is_dir( $directorist_core_dir ) ) {
	echo 'Set DIRECTORIST_CORE_TEST_DIR to a Directorist checkout with its test dependencies installed.' . PHP_EOL;
	exit( 1 );
}

$directorist_core_dir = rtrim( $directorist_core_dir, '/\\' );
$extension_dir        = dirname( __DIR__ );
$composer_autoload    = $directorist_core_dir . '/vendor/autoload.php';
$wp_phpunit_dir       = $directorist_core_dir . '/vendor/wp-phpunit/wp-phpunit';

require_once $composer_autoload;

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $directorist_core_dir . '/vendor/yoast/phpunit-polyfills' );
}

if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . $directorist_core_dir . '/tests/wp-tests-config.php' );
}

require_once $wp_phpunit_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $directorist_core_dir, $extension_dir ) {
		require_once $directorist_core_dir . '/directorist-base.php';
		require_once $extension_dir . '/directorist-wpml-integration.php';
	}
);

require_once $wp_phpunit_dir . '/includes/bootstrap.php';
