<?php

class DirectoristWpmlBootstrapBehaviorTest extends WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();
		$this->reset_integration();
	}

	public function tear_down(): void {
		$this->reset_integration();
		parent::tear_down();
	}

	public function test_provider_absence_registers_no_integration_controller_callbacks(): void {
		$this->assertFalse( defined( 'ICL_SITEPRESS_VERSION' ) );
		$this->assertFalse( class_exists( 'SitePress', false ) );

		Directorist_WPML_Integration();

		$this->assertSame( 0, $this->integration_controller_callback_count() );
	}

	public function test_public_factory_remains_available_without_wpml(): void {
		$this->assertTrue( function_exists( 'Directorist_WPML_Integration' ) );
		$this->assertInstanceOf( Directorist_WPML_Integration::class, Directorist_WPML_Integration() );
	}

	public function test_provider_loaded_after_directorist_is_detected_at_deferred_boot(): void {
		global $wp_actions;

		$plugins_loaded_count          = $wp_actions['plugins_loaded'] ?? 0;
		$wp_actions['plugins_loaded'] = 0;
		$integration                  = Directorist_WPML_Integration();

		$this->assertSame( 0, $this->integration_controller_callback_count() );
		$this->assertNotFalse( has_action( 'plugins_loaded', array( $integration, 'boot' ) ) );

		add_filter( 'directorist_wpml_integration_should_boot', '__return_true' );
		$wp_actions['plugins_loaded'] = $plugins_loaded_count;
		$integration->boot();

		$this->assertGreaterThan( 0, $this->integration_controller_callback_count() );
		remove_filter( 'directorist_wpml_integration_should_boot', '__return_true' );
	}

	private function integration_controller_callback_count(): int {
		global $wp_filter;

		$count = 0;

		foreach ( $wp_filter as $hook ) {
			if ( ! $hook instanceof WP_Hook ) {
				continue;
			}

			foreach ( $hook->callbacks as $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'] ?? null;

					if ( is_array( $function ) && is_object( $function[0] ) && 0 === strpos( get_class( $function[0] ), 'Directorist_WPML_Integration\\Controller\\' ) ) {
						++$count;
					}
				}
			}
		}

		return $count;
	}

	private function reset_integration(): void {
		global $wp_filter;

		foreach ( $wp_filter as $hook_name => $hook ) {
			if ( ! $hook instanceof WP_Hook ) {
				continue;
			}

			foreach ( $hook->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'] ?? null;
					if ( ! is_array( $function ) || ! is_object( $function[0] ) ) {
						continue;
					}

					$class = get_class( $function[0] );
					if ( $function[0] instanceof Directorist_WPML_Integration || 0 === strpos( $class, 'Directorist_WPML_Integration\\Controller\\' ) ) {
						remove_filter( $hook_name, $function, $priority );
					}
				}
			}
		}

		$property = new ReflectionProperty( Directorist_WPML_Integration::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}
}
