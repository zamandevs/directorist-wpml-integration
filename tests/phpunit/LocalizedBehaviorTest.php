<?php

class DirectoristWpmlLocalizedBehaviorTest extends WP_UnitTestCase {
	public function test_passive_declaration_preserves_and_deduplicates_identities(): void {
		$slug = 'directorist-wpml-integration';
		$this->assertSame( [ $slug ], apply_filters( 'directorist_localized_data_aware_extensions', [] ) );
		$this->assertSame( [ 'unknown-extension', $slug ], apply_filters( 'directorist_localized_data_aware_extensions', [ 'unknown-extension', $slug ] ) );
	}

	public function test_all_native_scripts_use_owned_builder_data_not_core_aggregate(): void {
		$js = '';
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( dirname( __DIR__, 2 ) . '/assets', FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file ) {
			if ( 'js' === $file->getExtension() ) { $js .= file_get_contents( $file->getPathname() ); }
		}
		$this->assertStringContainsString( 'directory_builder_script_data.directorist_nonce', $js );
		$this->assertDoesNotMatchRegularExpression( '/\b(?:atbdp_public_data|directorist_data)\b|\bdirectorist(?:\s*\.|\s*\[)/', $js );
	}

	public function test_unknown_consumer_fallback_stays_authoritative(): void {
		$policy = \Directorist\Asset_Loader\Asset_Compatibility::class;
		if ( function_exists( 'directorist_require_style' ) ) { $this->assertTrue( is_callable( [ $policy, 'should_use_legacy_localized_data' ] ) ); }
		if ( ! is_callable( [ $policy, 'should_use_legacy_localized_data' ] ) ) { $this->markTestSkipped( 'Older core has no modular data policy.' ); }
		$policy::reset();
		$policy::handle_render_context( [ 'source' => 'extension', 'extension' => 'directorist-wpml-integration' ] );
		$this->assertFalse( $policy::should_use_legacy_localized_data() );
		$policy::handle_render_context( [ 'source' => 'extension', 'extension' => 'unknown-extension' ] );
		$this->assertTrue( $policy::should_use_legacy_localized_data() );
		$policy::reset();
	}
}
