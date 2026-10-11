<?php

use Directorist_WPML_Integration\Controller\Hook\Filter_Permalinks;
use Directorist_WPML_Integration\Controller\Hook\Query_Filtering;

class DirectoristWpmlNativeBehaviorTest extends WP_UnitTestCase {
	public function test_native_factory_is_singleton_and_translation_hooks_remain(): void {
		$integration = Directorist_WPML_Integration();
		$this->assertSame( $integration, Directorist_WPML_Integration() );
		foreach ( [ 'directorist_all_listings_query_arguments', 'atbdp_listing_search_query_argument', 'directorist_dashboard_query_arguments', 'atbdp_author_listings_query_arguments', 'directorist_page_id', 'wpml_ls_language_url' ] as $hook ) {
			$this->assertNotFalse( has_filter( $hook ) );
		}
	}

	public function test_native_query_arguments_preserve_constraints_and_enable_provider_filters(): void {
		$args = [ 'post_type' => ATBDP_POST_TYPE, 'post__in' => [ 42 ], 'suppress_filters' => true, 'orderby' => 'title' ];
		$service = new Query_Filtering();
		$this->assertSame( $args, $service->ensure_wpml_filtering( $args ) );
		$current = static function () { return 'en'; };
		add_filter( 'wpml_current_language', $current );
		$actual = $service->ensure_wpml_filtering( $args );
		remove_filter( 'wpml_current_language', $current );
		$this->assertFalse( $actual['suppress_filters'] );
		unset( $actual['suppress_filters'], $args['suppress_filters'] );
		$this->assertSame( $args, $actual );
		remove_filter( 'pre_get_posts', [ $service, 'filter_listing_queries' ] );
		foreach ( [ 'directorist_all_listings_query_arguments', 'atbdp_listing_search_query_argument', 'directorist_dashboard_query_arguments', 'atbdp_author_listings_query_arguments' ] as $hook ) {
			remove_filter( $hook, [ $service, 'ensure_wpml_filtering' ] );
		}
		remove_filter( 'directorist_listings_query_results', [ $service, 'filter_query_results' ], 5 );
	}

	public function test_native_page_translation_follows_current_language_on_each_call(): void {
		$language = 'en';
		$current = static function () use ( &$language ) { return $language; };
		$trid = static function () { return 77; };
		$translations = static function () { return [ 'en' => (object) [ 'element_id' => 101 ], 'nl' => (object) [ 'element_id' => 202 ] ]; };
		add_filter( 'wpml_current_language', $current );
		add_filter( 'wpml_element_trid', $trid );
		add_filter( 'wpml_get_element_translations', $translations );
		$service = ( new ReflectionClass( Filter_Permalinks::class ) )->newInstanceWithoutConstructor();
		try {
			$this->assertSame( 101, $service->page_id( 101 ) );
			$language = 'nl';
			$this->assertSame( 202, $service->page_id( 101 ) );
			$language = 'fr';
			$this->assertSame( 101, $service->page_id( 101 ) );
		} finally {
			remove_filter( 'wpml_current_language', $current );
			remove_filter( 'wpml_element_trid', $trid );
			remove_filter( 'wpml_get_element_translations', $translations );
		}
	}

	public function test_native_owned_public_assets_keep_extension_filters_and_handles(): void {
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_styles'] = new WP_Styles();
		$asset = new \Directorist_WPML_Integration\Controller\Asset\PublicAsset();
		try {
			$asset->enqueue_scripts();
			$this->assertTrue( wp_script_is( 'directorist-wpml-integration-public-script', 'enqueued' ) );
			$this->assertTrue( wp_style_is( 'directorist-wpml-integration-public-main-style', 'enqueued' ) );
		} finally { remove_action( 'wp_enqueue_scripts', [ $asset, 'enqueue_scripts' ] ); }
	}
}
