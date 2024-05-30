(function () {
    var module_routes = [
    {
        "uri": "whmcs\/api\/search-clients",
        "name": "whmcs.api.search_client"
    },
    {
        "uri": "whmcs\/api\/auto-connect",
        "name": "whmcs.api.auto_connect"
    },
    {
        "uri": "whmcs\/api\/manual-connect",
        "name": "whmcs.api.manual-connect"
    },
    {
        "uri": "whmcs\/api\/disconnect",
        "name": "whmcs.api.disconnect"
    },
    {
        "uri": "whmcs\/api\/login-as-client",
        "name": "whmcs.api.login_as_client"
    },
    {
        "uri": "whmcs\/api\/get-backend-url",
        "name": "whmcs.api.get-backend-url"
    },
    {
        "uri": "whmcs\/api\/get-invoices",
        "name": "whmcs.api.get-invoices"
    },
    {
        "uri": "whmcs\/api\/get-products-services",
        "name": "whmcs.api.get-services-domains"
    },
    {
        "uri": "whmcs\/api\/get-client-details",
        "name": "whmcs.api.get-client-details"
    },
    {
        "uri": "whmcs\/api\/get-tickets",
        "name": "whmcs.api.get-tickets"
    },
    {
        "uri": "whmcs\/api\/get-emails",
        "name": "whmcs.api.get-emails"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();