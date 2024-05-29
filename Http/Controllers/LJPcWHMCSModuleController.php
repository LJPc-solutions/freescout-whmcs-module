<?php

namespace Modules\LJPcWHMCSModule\Http\Controllers;

use App\Customer;
use DateTimeImmutable;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Modules\LJPcWHMCSModule\Entities\WHMCSClient;
use Modules\LJPcWHMCSModule\Http\Helpers\WHMCS;
use Option;

class LJPcWHMCSModuleController extends Controller {
		/**
		 * Search for WHMCS clients based on the provided search term and limit.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function searchClients( Request $request ): JsonResponse {
				$validator = Validator::make( $request->all(), [
						'search' => 'required|string',
						'limit'  => 'int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'error' => 'Invalid request' ], 400 );
				}

				$search = $request->input( 'search' );
				$limit  = $request->input( 'limit', 10 );

				$cacheKey = 'whmcs-clients:' . md5( $search . '-' . $limit );

				// Retrieve the cached response if it exists
				$cachedResponse = Cache::get( $cacheKey );

				if ( $cachedResponse ) {
						return response()->json( $cachedResponse );
				}

				$params = [
						'search'   => $search,
						'limitnum' => $limit,
				];

				$response = WHMCS::instance()->getClients( $params );

				if ( ! $response || ! isset( $response['clients']['client'] ) ) {
						return response()->json( [] );
				}

				$clients = [];

				foreach ( $response['clients']['client'] as $client ) {
						$clientObj = new WHMCSClient();
						$clientObj->fill( $client );
						$clients[] = $clientObj;
				}

				Cache::put( $cacheKey, $clients, now()->addMinutes( 15 ) );

				return response()->json( $clients );
		}

		/**
		 * Automatically connect a customer to a WHMCS client based on their email.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function autoConnect( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				if ( $customer->getMeta( 'whmcs_connection_status' ) === 'connected' ) {
						$whmcsId = $customer->getMeta( 'whmcs_client_id' );

						return response()->json(
								[ 'success' => true, 'whmcs_id' => $whmcsId, 'message' => 'WHMCS client successfully connected to the customer' ]
						);
				}

				$email = $customer->getMainEmail();

				if ( ! $email ) {
						$customer->setMeta( 'whmcs_connection_status', 'not found' );
						$customer->save();

						return response()->json( [ 'success' => false, 'error' => 'Customer does not have an email address' ], 400 );
				}

				$response = WHMCS::instance()->getClients( [
						'search'   => $email,
						'limitnum' => 1,
				] );

				if ( ! $response || ! isset( $response['clients']['client'] ) ) {
						//get domain from email
						$domain  = explode( '@', $email )[1];
						$domains = WHMCS::instance()->getClientsDomains( [
								'domain'   => $domain,
								'limitnum' => 1,
						] );

						if ( ! is_array( $domains ) || count( $domains ) === 0 ) {
								$customer->setMeta( 'whmcs_connection_status', 'not found' );
								$customer->save();

								return response()->json( [ 'success' => false, 'error' => 'No matching WHMCS client found' ] );
						}

						$userId = $domain['userid'];
						$customer->setMeta( 'whmcs_client_id', $userId );
						$customer->setMeta( 'whmcs_connection_status', 'connected' );
						$customer->save();

						return response()->json(
								[ 'success' => true, 'whmcs_id' => $userId, 'message' => 'WHMCS client successfully connected to the customer' ]
						);
				}

				$clients = $response['clients']['client'];
				if ( count( $clients ) === 0 ) {
						$customer->setMeta( 'whmcs_connection_status', 'not found' );
						$customer->save();

						return response()->json( [ 'success' => false, 'error' => 'No matching WHMCS client found' ] );
				}

				if ( $clients[0]['email'] !== $email ) {
						$customer->setMeta( 'whmcs_connection_status', 'not found' );
						$customer->save();

						return response()->json( [ 'success' => false, 'error' => 'No matching WHMCS client found' ] );
				}

				$whmcsClient = new WHMCSClient();
				$whmcsClient->fill( $clients[0] );

				// Save the WHMCS client ID to the customer
				$customer->setMeta( 'whmcs_client_id', $whmcsClient->id );
				$customer->setMeta( 'whmcs_connection_status', 'connected' );
				$customer->save();

				return response()->json(
						[ 'success' => true, 'whmcs_id' => $whmcsClient->id, 'message' => 'WHMCS client successfully connected to the customer' ]
				);
		}

		/**
		 * Manually connect a customer to a WHMCS client using their IDs.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function manualConnect( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
						'whmcs_id'    => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$whmcsId = $request->input( 'whmcs_id' );

				// Save the WHMCS client ID to the customer
				$customer->setMeta( 'whmcs_client_id', $whmcsId );
				$customer->setMeta( 'whmcs_connection_status', 'connected' );
				$customer->save();

				return response()->json(
						[ 'success' => true, 'whmcs_id' => $whmcsId, 'message' => 'WHMCS client successfully connected to the customer' ]
				);

		}

		/**
		 * Disconnect a customer from a WHMCS client.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function disconnect( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$customer->setMeta( 'whmcs_client_id', null );
				$customer->setMeta( 'whmcs_connection_status', 'not found' );
				$customer->save();

				return response()->json( [ 'success' => true, 'message' => 'WHMCS client successfully disconnected from the customer' ] );

		}

		/**
		 * Log in as a WHMCS client.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function loginAsClient( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$whmcsId = $customer->getMeta( 'whmcs_client_id' );
				if ( ! $whmcsId ) {
						return response()->json( [ 'success' => false, 'error' => 'Client not connected' ] );
				}

				$url = WHMCS::instance()->createSsoToken( [ 'client_id' => $whmcsId ] );

				if ( ! $url ) {
						return response()->json( [ 'success' => false, 'error' => 'Failed to create SSO token' ] );
				}

				return response()->json( [ 'success' => true, 'url' => $url ] );
		}

		/**
		 * Get the backend URL for a WHMCS client.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function getBackendUrl( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$whmcsId = $customer->getMeta( 'whmcs_client_id' );
				if ( ! $whmcsId ) {
						return response()->json( [ 'success' => false, 'error' => 'Client not connected' ] );
				}

				$baseUrl = Option::get( 'whmcs_base_url' );

				$url = $baseUrl . '/admin/clientssummary.php?userid=' . $whmcsId;
				$url = preg_replace( '/([^:])(\/{2,})/', '$1/', $url );

				return response()->json( [ 'success' => true, 'url' => $url ] );
		}

		/**
		 * Get the invoices for a WHMCS client.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 * @throws Exception
		 */
		public function getInvoices( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$whmcsId = $customer->getMeta( 'whmcs_client_id' );
				if ( ! $whmcsId ) {
						return response()->json( [ 'success' => false, 'error' => 'Client not connected' ] );
				}

				$cacheKey       = 'whmcs-invoices:' . $whmcsId;
				$cachedResponse = Cache::get( $cacheKey );

				if ( $cachedResponse ) {
						return response()->json( $cachedResponse );
				}

				$baseUrl = Option::get( 'whmcs_base_url' );

				$invoices   = WHMCS::instance()->getInvoices( [ 'userid' => $whmcsId, 'limitnum' => 10, 'orderby' => 'duedate', 'order' => 'desc' ] );
				$backendUrl = $baseUrl . '/admin/clientsinvoices.php?userid=' . $whmcsId;
				$backendUrl = preg_replace( '/([^:])(\/{2,})/', '$1/', $backendUrl );

				$statuses = [
						'Paid'      => __( 'Paid' ),
						'Unpaid'    => __( 'Unpaid' ),
						'Cancelled' => __( 'Cancelled' ),
						'Refunded'  => __( 'Refunded' ),
				];

				$sanitizedInvoices = [];
				$locale            = localeconv();
				foreach ( $invoices as $invoice ) {
						$invoiceUrl = $baseUrl . '/admin/billing/invoice/' . $invoice['id'];
						$invoiceUrl = preg_replace( '/([^:])(\/{2,})/', '$1/', $invoiceUrl );

						$sanitizedInvoices[] = [
								'id'          => $invoice['id'],
								'invoice_num' => $invoice['invoicenum'],
								'date'        => ( new DateTimeImmutable( $invoice['date'] ) )->format( DATE_ATOM ),
								'due_date'    => ( new DateTimeImmutable( $invoice['duedate'] ) )->format( DATE_ATOM ),
								'amount'      => $invoice['currencyprefix'] . number_format( (float) $invoice['total'], 2, $locale['decimal_point'], $locale['thousands_sep'] ),
								'status'      => $statuses[ $invoice['status'] ],
								'raw_status'  => $invoice['status'], // 'Paid', 'Unpaid', 'Cancelled', 'Refunded
								'url'         => $invoiceUrl,
						];
				}

				$response = [ 'success' => true, 'invoices' => $sanitizedInvoices, 'url' => $backendUrl, 'last_update' => now()->format( DATE_ATOM ) ];

				Cache::put( $cacheKey, $response, now()->addHours( 6 ) );

				return response()->json( $response );
		}

		/**
		 * Get the products and services for a WHMCS client.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function getProductsServices( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$whmcsId = $customer->getMeta( 'whmcs_client_id' );
				if ( ! $whmcsId ) {
						return response()->json( [ 'success' => false, 'error' => 'Client not connected' ] );
				}

				$cacheKey       = 'whmcs-products:' . $whmcsId;
				$cachedResponse = Cache::get( $cacheKey );

				if ( $cachedResponse ) {
						return response()->json( $cachedResponse );
				}

				$baseUrl = Option::get( 'whmcs_base_url' );

				$services = WHMCS::instance()->getClientsProducts( [ 'clientid' => $whmcsId, 'limitnum' => 5 ] );
				$domains  = WHMCS::instance()->getClientsDomains( [ 'clientid' => $whmcsId, 'limitnum' => 5 ] );

				$servicesUrl = $baseUrl . '/admin/clientsservices.php?userid=' . $whmcsId;
				$servicesUrl = preg_replace( '/([^:])(\/{2,})/', '$1/', $servicesUrl );

				$domainsUrl = $baseUrl . '/admin/clientsdomains.php?userid=' . $whmcsId;
				$domainsUrl = preg_replace( '/([^:])(\/{2,})/', '$1/', $domainsUrl );

				foreach ( $services as &$service ) {
						$service['url'] = $baseUrl . '/admin/clientsservices.php?userid=' . $whmcsId . '&id=' . $service['id'];
						$service['url'] = preg_replace( '/([^:])(\/{2,})/', '$1/', $service['url'] );
				}
				unset( $service );

				foreach ( $domains as &$domain ) {
						$domain['url'] = $baseUrl . '/admin/clientsdomains.php?userid=' . $whmcsId . '&id=' . $domain['id'];
						$domain['url'] = preg_replace( '/([^:])(\/{2,})/', '$1/', $domain['url'] );
				}
				unset( $domain );

				$response = [
						'success'      => true,
						'services'     => $services,
						'domains'      => $domains,
						'services_url' => $servicesUrl,
						'domains_url'  => $domainsUrl,
						'last_update'  => now()->format( DATE_ATOM ),
				];

				Cache::put( $cacheKey, $response, now()->addHours( 6 ) );

				return response()->json( $response );
		}

		/**
		 * Get the details for a WHMCS client.
		 *
		 * @param Request $request
		 *
		 * @return JsonResponse
		 */
		public function getClientDetails( Request $request ) {
				$validator = Validator::make( $request->all(), [
						'customer_id' => 'required|int',
				] );

				if ( $validator->fails() ) {
						return response()->json( [ 'success' => false, 'error' => 'Invalid request' ] );
				}

				$customer = Customer::find( $request->input( 'customer_id' ) );
				if ( ! $customer ) {
						return response()->json( [ 'success' => false, 'error' => 'Customer not found' ] );
				}

				$whmcsId = $customer->getMeta( 'whmcs_client_id' );
				if ( ! $whmcsId ) {
						return response()->json( [ 'success' => false, 'error' => 'Client not connected' ] );
				}

				$cacheKey       = 'whmcs-client-details:' . $whmcsId;
				$cachedResponse = Cache::get( $cacheKey );

				if ( $cachedResponse ) {
						return response()->json( $cachedResponse );
				}

				$clientDetails = WHMCS::instance()->getClientDetails( [ 'clientid' => $whmcsId ] );

				$response = [
						'success'     => true,
						'client'      => $clientDetails['client'],
						'last_update' => now()->format( DATE_ATOM ),
				];

				Cache::put( $cacheKey, $response, now()->addHours( 6 ) );

				return response()->json( $response );
		}
}
