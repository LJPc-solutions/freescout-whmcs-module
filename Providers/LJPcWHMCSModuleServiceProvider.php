<?php

namespace Modules\LJPcWHMCSModule\Providers;

use App\Customer;
use Config;
use Eventy;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\ServiceProvider;
use Log;
use Module;
use Modules\LJPcWHMCSModule\Console\Test;
use Modules\LJPcWHMCSModule\Http\Helpers\WHMCS;
use Option;
use View;

class LJPcWHMCSModuleServiceProvider extends ServiceProvider {
		/**
		 * Indicates if loading of the provider is deferred.
		 *
		 * @var bool
		 */
		protected $defer = false;

		/**
		 * Boot the application events.
		 *
		 * @return void
		 */
		public function boot() {
				$this->registerConfig();
				$this->registerViews();
				$this->loadMigrationsFrom( __DIR__ . '/../Database/Migrations' );
				$this->hooks();
				$this->registerSettings();
		}

		/**
		 * Register the settings for the WHMCS module.
		 *
		 * @return void
		 */
		private function registerSettings() {

				Eventy::addFilter( 'settings.section_params', function ( $params, $section ) {
						if ( $section !== 'whmcs' ) {
								return $params;
						}

						$params['settings'] = [
								'whmcs_api_identifier' => [
										'encrypt' => true,
								],
								'whmcs_api_secret'     => [
										'encrypt' => true,
								],
						];

						$params['validator_rules'] = [
								'settings.whmcs_base_url'       => 'required|url',
								'settings.whmcs_api_identifier' => 'required|string',
								'settings.whmcs_api_secret'     => 'required|string',
						];

						$currentData = [
								'whmcs_base_url'       => Option::get( 'whmcs_base_url' ),
								'whmcs_api_identifier' => $this->decryptSetting( 'whmcs_api_identifier' ),
								'whmcs_api_secret'     => $this->decryptSetting( 'whmcs_api_secret' ),
						];
						$connected   = false;
						//check if one of them is empty
						$empty = false;
						foreach ( $currentData as $key => $value ) {
								if ( empty( $value ) ) {
										$empty = true;
										break;
								}
						}
						$whmcsDetails = null;
						if ( $empty === false ) {
								$whmcsDetails = WHMCS::instance()->getWHMCSDetails();
								if ( $whmcsDetails !== null ) {
										$connected = true;
								}
						}
						Option::set( 'whmcs_connected', $connected );

						$params['template_vars'] = [
								'whmcs_details' => $whmcsDetails,
								'connected'     => $connected,
						];

						return $params;
				}, 20, 2 );

				// Add item to settings sections.
				Eventy::addFilter( 'settings.sections', function ( $sections ) {
						$sections['whmcs'] = [ 'title' => __( 'WHMCS' ), 'icon' => 'th-large', 'order' => 200 ];

						return $sections;
				}, 15 );

				// Settings view name
				Eventy::addFilter( 'settings.view', function ( $view, $section ) {
						if ( $section !== 'whmcs' ) {
								return $view;
						}

						return 'ljpcwhmcsmodule::settings';
				}, 20, 2 );

				Eventy::addFilter( 'settings.section_settings', function ( $settings, $section ) {
						if ( $section !== 'whmcs' ) {
								return $settings;
						}

						return [
								'whmcs_base_url'       => Option::get( 'whmcs_base_url' ),
								'whmcs_api_identifier' => $this->decryptSetting( 'whmcs_api_identifier' ),
								'whmcs_api_secret'     => $this->decryptSetting( 'whmcs_api_secret' ),
						];
				}, 20, 2 );

		}

		/**
		 * Decrypt a setting value.
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		public function decryptSetting( $key ): string {
				try {
						$value = Option::get( $key );

						return $value ? decrypt( $value ) : '';
				} catch ( DecryptException $e ) {
						Log::error( 'Failed to decrypt setting: ' . $key );

						return '';
				}
		}

		/**
		 * Module hooks.
		 */
		public function hooks() {
				$whmcsConnected = (bool) Option::get( 'whmcs_connected' );
				if ( $whmcsConnected !== false ) {
						Eventy::addAction( 'conversation.after_prev_convs', function ( Customer $customer, $conversation, $mailbox ) {
								echo View::make( 'ljpcwhmcsmodule::sidebar', [
										'customer'     => $customer,
										'conversation' => $conversation,
										'mailbox'      => $mailbox,
										'loader_url'   => Module::getPublicPath( 'ljpcwhmcsmodule' ) . '/images/loading.svg',
								] )->render();
						}, - 1, 3 );
				}

				Eventy::addFilter( 'javascripts', function ( $javascripts ) {
						$javascripts[] = Module::getPublicPath( 'ljpcwhmcsmodule' ) . '/js/laroute.js';
						$javascripts[] = Module::getPublicPath( 'ljpcwhmcsmodule' ) . '/js/general.js';

						return $javascripts;
				} );

				Eventy::addFilter( 'stylesheets', function ( $styles ) {
						$styles[] = Module::getPublicPath( 'ljpcwhmcsmodule' ) . '/css/general.css';

						return $styles;
				} );

				Eventy::addAction( 'js.lang.messages', function () {
						?>
            "whmcs_no_results_found": "<?php echo __( "No results found" ) ?>",
            "whmcs_confirm_customer_link": "<?php echo __( "Are you sure you want to link this customer to :customer_name?" ) ?>",
            "whmcs_confirm_whmcs_disconnect": "<?php echo __( "Are you sure you want to disconnect the link with WHMCS?" ) ?>",
            "whmcs_error": "<?php echo __( "WHMCS error: :error" ) ?>",
            "whmcs_no_invoices_found": "<?php echo __( "No invoices found" ) ?>",
            "whmcs_no_services_or_domains_found": "<?php echo __( "No services or domains found" ) ?>",
            "whmcs_email": "<?php echo __( "Email" ) ?>",
            "whmcs_company": "<?php echo __( "Company" ) ?>",
            "whmcs_phone": "<?php echo __( "Phone" ) ?>",
            "whmcs_address": "<?php echo __( "Address" ) ?>",
            "whmcs_status": "<?php echo __( "Status" ) ?>",
            "whmcs_last_update": "<?php echo __( "Last update" ) ?>",
            "whmcs_no_tickets_found": "<?php echo __( "No tickets found" ) ?>",
            "whmcs_no_emails_found": "<?php echo __( "No emails found" ) ?>",
						<?php
				} );
		}

		/**
		 * Register the service provider.
		 *
		 * @return void
		 */
		public function register() {
				$this->registerTranslations();
		}

		/**
		 * Register config.
		 *
		 * @return void
		 */
		protected function registerConfig() {
				$this->publishes( [
						__DIR__ . '/../Config/config.php' => config_path( 'ljpcwhmcsmodule.php' ),
				], 'config' );
				$this->mergeConfigFrom(
						__DIR__ . '/../Config/config.php',
						'ljpcwhmcsmodule'
				);
		}

		/**
		 * Register views.
		 *
		 * @return void
		 */
		public function registerViews() {
				$viewPath = resource_path( 'views/modules/ljpcwhmcsmodule' );

				$sourcePath = __DIR__ . '/../Resources/views';

				$this->publishes( [
						$sourcePath => $viewPath,
				], 'views' );

				$this->loadViewsFrom( array_merge( array_map( function ( $path ) {
						return $path . '/modules/ljpcwhmcsmodule';
				}, Config::get( 'view.paths' ) ), [ $sourcePath ] ), 'ljpcwhmcsmodule' );
		}

		/**
		 * Register translations.
		 *
		 * @return void
		 */
		public function registerTranslations() {
				$this->loadJsonTranslationsFrom( __DIR__ . '/../Resources/lang' );
		}


		/**
		 * Get the services provided by the provider.
		 *
		 * @return array
		 */
		public function provides() {
				return [];
		}

}
