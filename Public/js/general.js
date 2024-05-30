$(document).ready(function () {
    if (!window.hasOwnProperty('ljpcwhmcsmodule')) return;

    const debounce = (callback, wait) => {
        let timeoutId = null;
        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => {
                callback.apply(null, args);
            }, wait);
        };
    }

    const loadData = () => {
        loadInvoices();
        loadServicesAndDomains();
        loadClientDetails();
        loadTickets();
        loadEmails();
    }

    const autoConnect = () => {
        const url = laroute.route('whmcs.api.auto_connect');
        // Send a post call to the url, containing the customer_id and the csrf token
        $.post(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                $('#whmcs-module__auto-connecting').addClass('hidden');
                $('#whmcs-module__connected').removeClass('hidden');
                $('#whmcs-module__invoices').removeClass('hidden');
                $('#whmcs-module__services').removeClass('hidden');
                $('#whmcs-module__tickets').removeClass('hidden');
                $('#whmcs-module__emails').removeClass('hidden');
                window.ljpcwhmcsmodule.whmcs.customer_connection = 'connected';
                loadData();
            } else {
                $('#whmcs-module__auto-connecting').addClass('hidden');
                $('#whmcs-module__manual-connect').removeClass('hidden');
            }
        });
    }

    /**
     * Manual connect
     */
    $('#whmcs-module__retry-autoconnect').on('click', function (e) {
        e.preventDefault();
        $('#whmcs-module__manual-connect').addClass('hidden');
        $('#whmcs-module__auto-connecting').removeClass('hidden');
        autoConnect();
    });

    $('#whmcs-module__manual-connect-search').on('keyup', debounce(function () {
        const ul = $('#whmcs-module__manual-connect-results');
        ul.empty();
        $('#whmcs-module__manual-connect-form .loading').removeClass('hidden');
        const search = $('#whmcs-module__manual-connect-search').val();
        const url = laroute.route('whmcs.api.search_client');
        $.get(url, {search: search}, function (data) {
            $('#whmcs-module__manual-connect-form .loading').addClass('hidden');
            //Show results in ul#whmcs-module__manual-connect-results

            if (data.length === 0) {
                const li = $('<li class="list-group-item text-muted"></li>');
                li.text(Lang.get('messages.whmcs_no_results_found'));
                ul.append(li);
            } else {
                data.forEach(function (client) {
                    const li = $('<li class="list-group-item list-group-item-action" data-whmcs-id="' + client.id + '"></li>');

                    let displayText = '';
                    if (client.firstname && client.lastname) {
                        displayText += client.firstname + ' ' + client.lastname;
                    }
                    if (client.companyname) {
                        if (displayText) {
                            displayText += ' (' + client.companyname + ')';
                        } else {
                            displayText = client.companyname;
                        }
                    }

                    li.text(displayText);
                    ul.append(li);
                });
            }
        });
    }, 500));

    $('body').on('click', '#whmcs-module__manual-connect-results li', function () {
        if (!confirm(Lang.get('messages.whmcs_confirm_customer_link', {customer_name: $(this).text()}))) return;
        $('#whmcs-module__manual-connect').addClass('hidden');
        $('#whmcs-module__auto-connecting').removeClass('hidden');
        window.ljpcwhmcsmodule.whmcs.id = $(this).data('whmcs-id');
        $.post(laroute.route('whmcs.api.manual-connect'), {customer_id: window.ljpcwhmcsmodule.customer_id, whmcs_id: window.ljpcwhmcsmodule.whmcs.id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                autoConnect()
            } else {
                $('#whmcs-module__auto-connecting').addClass('hidden');
                $('#whmcs-module__manual-connect').removeClass('hidden');
            }
        });
    });

    /**
     * Connected
     */
    $('#whmcs-module__disconnect').on('click', function (e) {
        e.preventDefault();
        if (confirm(Lang.get('messages.whmcs_confirm_whmcs_disconnect')) === false) return;
        $.post(laroute.route('whmcs.api.disconnect'), {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                $('#whmcs-module__connected').addClass('hidden');
                $('#whmcs-module__invoices').addClass('hidden');
                $('#whmcs-module__services').addClass('hidden');
                $('#whmcs-module__tickets').addClass('hidden');
                $('#whmcs-module__emails').addClass('hidden');
                $('#whmcs-module__manual-connect').removeClass('hidden');
                window.ljpcwhmcsmodule.whmcs.id = null;
                window.ljpcwhmcsmodule.whmcs.customer_connection = 'not found';
            }
        });
    });

    $('#whmcs-module__login-as-client').on('click', function (e) {
        e.preventDefault();

        const url = laroute.route('whmcs.api.login_as_client');
        $.get(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                //open url in new tab
                window.open(data.url, '_blank');
            } else {
                showFloatingAlert('error', Lang.get('messages.whmcs_error', {error: data.error}));
            }
        });
    });

    $('#whmcs-module__navigate-to-backend').on('click', function (e) {
        e.preventDefault();
        const url = laroute.route('whmcs.api.get-backend-url');

        $.get(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                //open url in new tab
                window.open(data.url, '_blank');
            } else {
                showFloatingAlert('error', 'WHMCS error: ' + data.error);
            }
        });
    });

    const loadClientDetails = () => {
        $.get(laroute.route('whmcs.api.get-client-details'), {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                const clientInfo = $('#whmcs-module__client-info');
                const clientDetails = data.client;

                const lastUpdate = new Date(data.last_update);

                let clientInfoHtml = `
  <div class="client-info">
    <h2>${clientDetails.fullname || '-'}</h2>
    <p><strong>${Lang.get('messages.whmcs_email')}:</strong> ${clientDetails.email || '-'}</p>
    <p><strong>${Lang.get('messages.whmcs_company')}:</strong> ${clientDetails.companyname || '-'}</p>
    <p><strong>${Lang.get('messages.whmcs_phone')}:</strong> ${clientDetails.phonenumberformatted || '-'}</p>
    <p><strong>${Lang.get('messages.whmcs_address')}:</strong><br>
      ${clientDetails.address1 || ''}<br>
      ${clientDetails.address2 ? clientDetails.address2 + '<br>' : ''}
      ${clientDetails.city || ''}, ${clientDetails.state || ''} ${clientDetails.postcode || ''}<br>
      ${clientDetails.countryname || ''}
    </p>
    <p>
        <strong>${Lang.get('messages.whmcs_status')}:</strong> ${clientDetails.status || '-'}<br />
        <small class="text-muted">${Lang.get('messages.whmcs_last_update')}: ${lastUpdate.toLocaleDateString() + ' ' + lastUpdate.toLocaleTimeString()}</small>
    </p>
  </div>
`;

                clientInfo.html(clientInfoHtml);

            }
        });
    }

    /**
     * Invoices
     */
    const loadInvoices = () => {
        const loadingIndicator = $('#whmcs-module__invoices .loading');
        const loadedIndicator = $('#whmcs-module__invoices .loaded');

        loadingIndicator.removeClass('hidden');
        loadedIndicator.addClass('hidden');
        const url = laroute.route('whmcs.api.get-invoices');
        $.get(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                const lastUpdate = new Date(data.last_update);
                $('#whmcs-module__invoices-last-update').html(lastUpdate.toLocaleDateString() + ' ' + lastUpdate.toLocaleTimeString());

                $('#whmcs-module__invoices-show-all').attr('href', data.url);

                const ul = $('#whmcs-module__invoices-list');
                ul.empty();

                if (data.invoices.length === 0) {
                    const li = $('<li class="list-group-item text-muted"></li>');
                    li.text(Lang.get('messages.whmcs_no_invoices_found'));
                    ul.append(li);
                } else {
                    data.invoices.forEach(function (invoice) {
                        const li = $('<li class="list-group-item list-group-item-action" data-invoice-id="' + invoice.id + '" data-invoice-url="' + invoice.url + '" title="' + invoice.status + '"></li>');
                        const statusIndicator = '<span class="invoice-status-indicator ' + invoice.raw_status.toLowerCase() + '"></span>';
                        li.html(statusIndicator + invoice.invoice_num + ' - ' + invoice.amount);
                        ul.append(li);
                    });
                }

                loadingIndicator.addClass('hidden');
                loadedIndicator.removeClass('hidden');
            }
        });
    }

    $('body').on('click', '[data-invoice-url]', function () {
        window.open($(this).data('invoice-url'), '_blank');
    });

    /**
     * Tickets
     */
    const loadTickets = () => {
        const loadingIndicator = $('#whmcs-module__tickets .loading');
        const loadedIndicator = $('#whmcs-module__tickets .loaded');

        loadingIndicator.removeClass('hidden');
        loadedIndicator.addClass('hidden');
        const url = laroute.route('whmcs.api.get-tickets');
        $.get(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                const lastUpdate = new Date(data.last_update);
                $('#whmcs-module__tickets-last-update').html(lastUpdate.toLocaleDateString() + ' ' + lastUpdate.toLocaleTimeString());

                $('#whmcs-module__tickets-show-all').attr('href', data.url);

                const ul = $('#whmcs-module__tickets-list');
                ul.empty();

                if (data.tickets.length === 0) {
                    const li = $('<li class="list-group-item text-muted"></li>');
                    li.text(Lang.get('messages.whmcs_no_tickets_found'));
                    ul.append(li);
                } else {
                    data.tickets.forEach(function (ticket) {
                        const li = $('<li class="list-group-item list-group-item-action" data-ticket-id="' + ticket.id + '" data-ticket-url="' + ticket.url + '" title="' + ticket.status + '"></li>');
                        li.html('#' + ticket.tid + ' - ' + ticket.subject);
                        ul.append(li);
                    });
                }

                loadingIndicator.addClass('hidden');
                loadedIndicator.removeClass('hidden');
            }
        });
    }

    $('body').on('click', '[data-ticket-url]', function () {
        window.open($(this).data('ticket-url'), '_blank');
    });

    /**
     * Emails
     */
    const loadEmails = () => {
        const loadingIndicator = $('#whmcs-module__emails .loading');
        const loadedIndicator = $('#whmcs-module__emails .loaded');

        loadingIndicator.removeClass('hidden');
        loadedIndicator.addClass('hidden');
        const url = laroute.route('whmcs.api.get-emails');
        $.get(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                const lastUpdate = new Date(data.last_update);
                $('#whmcs-module__emails-last-update').html(lastUpdate.toLocaleDateString() + ' ' + lastUpdate.toLocaleTimeString());

                $('#whmcs-module__emails-show-all').attr('href', data.url);

                const ul = $('#whmcs-module__emails-list');
                ul.empty();

                if (data.emails.length === 0) {
                    const li = $('<li class="list-group-item text-muted"></li>');
                    li.text(Lang.get('messages.whmcs_no_emails_found'));
                    ul.append(li);
                } else {
                    data.emails.forEach(function (email) {
                        const li = $('<li class="list-group-item list-group-item-action" data-email-id="' + email.id + '" data-email-url="' + email.url + '" title="' + email.status + '"></li>');
                        li.html(email.subject);
                        ul.append(li);
                    });
                }

                loadingIndicator.addClass('hidden');
                loadedIndicator.removeClass('hidden');
            }
        });
    }

    $('body').on('click', '[data-email-url]', function () {
        window.open($(this).data('email-url'), '_blank');
    });

    /**
     * Services and domains
     */
    const loadServicesAndDomains = () => {
        const loadingIndicator = $('#whmcs-module__services .loading');
        const loadedIndicator = $('#whmcs-module__services .loaded');

        loadingIndicator.removeClass('hidden');
        loadedIndicator.addClass('hidden');
        const url = laroute.route('whmcs.api.get-services-domains');
        $.get(url, {customer_id: window.ljpcwhmcsmodule.customer_id, _token: window.ljpcwhmcsmodule.csrf}, function (data) {
            if (data.success) {
                const lastUpdate = new Date(data.last_update);
                $('#whmcs-module__services-last-update').html(lastUpdate.toLocaleDateString() + ' ' + lastUpdate.toLocaleTimeString());

                $('#whmcs-module__services-show-all').attr('href', data.services_url);
                $('#whmcs-module__domains-show-all').attr('href', data.domains_url);

                const ul = $('#whmcs-module__services-list');
                ul.empty();

                if (data.services.length === 0 && data.domains.length === 0) {
                    const li = $('<li class="list-group-item text-muted"></li>');
                    li.text(Lang.get('messages.whmcs_no_services_or_domains_found'));
                    ul.append(li);
                } else {
                    data.services.forEach(function (service) {
                        const li = $('<li class="list-group-item list-group-item-action" data-service-id="' + service.id + '" data-service-url="' + service.url + '" title="' + service.status + '"></li>');
                        const statusIndicator = '<span class="invoice-status-indicator ' + service.status.toLowerCase() + '"></span>';
                        let name = statusIndicator + service.name;
                        if (service.hasOwnProperty('domain') && service.domain.length > 0) {
                            name += ' (' + service.domain + ')';
                        }
                        li.html(name);
                        ul.append(li);
                    });

                    data.domains.forEach(function (domain) {
                        const li = $('<li class="list-group-item list-group-item-action" data-domain-id="' + domain.id + '" data-domain-url="' + domain.url + '" title="' + domain.status + '"></li>');
                        const statusIndicator = '<span class="invoice-status-indicator ' + domain.status.toLowerCase() + '"></span>';
                        li.html(statusIndicator + domain.domainname);
                        ul.append(li);
                    });
                }

                loadingIndicator.addClass('hidden');
                loadedIndicator.removeClass('hidden');
            }
        });
    }

    $('body').on('click', '[data-domain-url]', function () {
        window.open($(this).data('domain-url'), '_blank');
    }).on('click', '[data-service-url]', function () {
        window.open($(this).data('service-url'), '_blank');
    });

    /**
     * General
     */
    if (window.ljpcwhmcsmodule.whmcs.customer_connection === 'connected') {
        $('#whmcs-module__loading').addClass('hidden');
        $('#whmcs-module__connected').removeClass('hidden');
        $('#whmcs-module__invoices').removeClass('hidden');
        $('#whmcs-module__services').removeClass('hidden');
        $('#whmcs-module__tickets').removeClass('hidden');
        $('#whmcs-module__emails').removeClass('hidden');
        loadData();
    } else if (window.ljpcwhmcsmodule.whmcs.customer_connection === 'not found') {
        $('#whmcs-module__loading').addClass('hidden');
        $('#whmcs-module__manual-connect').removeClass('hidden');
    } else {
        $('#whmcs-module__loading').addClass('hidden');
        $('#whmcs-module__auto-connecting').removeClass('hidden');
        autoConnect();
    }
});
