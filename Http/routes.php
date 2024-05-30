<?php

Route::group( [ 'middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\LJPcWHMCSModule\Http\Controllers' ], function () {
		Route::get( '/', 'LJPcWHMCSModuleController@index' );

		$middleWare = [ 'auth', 'roles' ];
		$middleWare = [];
		Route::get( '/whmcs/api/search-clients', [
				'uses'       => 'LJPcWHMCSModuleController@searchClients',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.search_client' );

		Route::post( '/whmcs/api/auto-connect', [
				'uses'       => 'LJPcWHMCSModuleController@autoConnect',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.auto_connect' );

		Route::post( '/whmcs/api/manual-connect', [
				'uses'       => 'LJPcWHMCSModuleController@manualConnect',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.manual-connect' );

		Route::post( '/whmcs/api/disconnect', [
				'uses'       => 'LJPcWHMCSModuleController@disconnect',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.disconnect' );

		Route::get( '/whmcs/api/login-as-client', [
				'uses'       => 'LJPcWHMCSModuleController@loginAsClient',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.login_as_client' );

		Route::get( '/whmcs/api/get-backend-url', [
				'uses'       => 'LJPcWHMCSModuleController@getBackendUrl',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.get-backend-url' );

		Route::get( '/whmcs/api/get-invoices', [
				'uses'       => 'LJPcWHMCSModuleController@getInvoices',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.get-invoices' );

		Route::get( '/whmcs/api/get-products-services', [
				'uses'       => 'LJPcWHMCSModuleController@getProductsServices',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.get-services-domains' );

		Route::get( '/whmcs/api/get-client-details', [
				'uses'       => 'LJPcWHMCSModuleController@getClientDetails',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.get-client-details' );

		Route::get( '/whmcs/api/get-tickets', [
				'uses'       => 'LJPcWHMCSModuleController@getTickets',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.get-tickets' );

		Route::get( '/whmcs/api/get-emails', [
				'uses'       => 'LJPcWHMCSModuleController@getEmails',
				'middleware' => $middleWare,
				'laroute'    => true,
		] )->name( 'whmcs.api.get-emails' );

} );
