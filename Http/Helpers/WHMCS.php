<?php

namespace Modules\LJPcWHMCSModule\Http\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class WHMCS {
		protected string $baseUrl;
		protected string $identifier;
		protected string $secret;

		private static ?self $instance = null;

		public static function instance(): self {
				if ( self::$instance === null ) {
						self::$instance = new self();
				}

				return self::$instance;
		}

		private function __construct() {
				$this->baseUrl    = \Option::get( 'whmcs_base_url' );
				$this->identifier = decrypt( \Option::get( 'whmcs_api_identifier' ) );
				$this->secret     = decrypt( \Option::get( 'whmcs_api_secret' ) );
		}

		public function call( string $action, array $params = [] ): ?array {
				$client = new Client( [
						'base_uri'    => $this->baseUrl,
						'http_errors' => false,
						'verify'      => true,
				] );

				$params = array_merge( $params, [
						'identifier'   => $this->identifier,
						'secret'       => $this->secret,
						'action'       => $action,
						'responsetype' => 'json',
				] );

				try {
						$response = $client->post( 'includes/api.php', [
								'form_params' => $params,
								'timeout'     => 30,
						] );

						$statusCode   = $response->getStatusCode();
						$responseBody = $response->getBody()->getContents();

						if ( $statusCode === 200 ) {
								return json_decode( $responseBody, true );
						} else {
								Log::error( 'WHMCS API Error: ' . $responseBody );

								return null;
						}
				} catch ( GuzzleException $e ) {
						Log::error( 'WHMCS API Exception: ' . $e->getMessage() );

						return null;
				}
		}

		public function getClients( array $params = [] ): ?array {
				return $this->call( 'GetClients', $params );
		}

		public function createSsoToken( array $params = [] ): ?string {
				$response = $this->call( 'CreateSsoToken', $params );

				if ( $response !== null && isset( $response['redirect_url'] ) ) {
						return $response['redirect_url'];
				}

				return null;
		}

		public function getInvoices( array $params = [] ): ?array {
				$response = $this->call( 'GetInvoices', $params );

				if ( $response !== null && $response['result'] === 'success' ) {
						return $response['invoices']['invoice'] ?? [];
				}

				return null;
		}

		public function getClientsProducts( array $params = [] ): ?array {
				$response = $this->call( 'GetClientsProducts', $params );

				if ( $response !== null && $response['result'] === 'success' ) {
						return $response['products']['product'] ?? [];
				}

				return null;
		}

		public function getClientsDomains( array $params = [] ): ?array {
				$response = $this->call( 'GetClientsDomains', $params );

				if ( $response !== null && $response['result'] === 'success' ) {
						return $response['domains']['domain'] ?? [];
				}

				return null;
		}

		public function getTickets( array $params = [] ): ?array {
				$response = $this->call( 'GetTickets', $params );

				if ( $response !== null && $response['result'] === 'success' ) {
						return $response['tickets']['ticket'] ?? [];
				}

				return null;
		}

		public function getEmails( array $params = [], bool $raw = false ): ?array {
				$response = $this->call( 'GetEmails', $params );

				if ( $response !== null && $response['result'] === 'success' ) {
						if ( $raw ) {
								return $response;
						}

						return $response['emails']['email'] ?? [];
				}

				return null;
		}

		public function getClientDetails( array $params = [] ): ?array {
				$response = $this->call( 'GetClientsDetails', $params );

				if ( $response !== null && $response['result'] === 'success' ) {
						return [
								'client' => $response['client'] ?? [],
								'stats'  => $response['stats'] ?? [],
						];
				}

				return null;
		}

		public function getWHMCSDetails(): ?array {
				$response = $this->call( 'WhmcsDetails' );

				if ( $response !== null && $response['result'] === 'success' && isset( $response['whmcs'] ) ) {
						return $response['whmcs'];
				}

				return null;
		}
}
