<div class="loading">
    <img src="{{$loader_url}}" width="16" height="16"/> {{__('Loading')}}...
</div>
<div class="loaded hidden">
    <ul id="whmcs-module__invoices-list" class="list-group mt-2"></ul>
    <small class="text-muted">{{__('Last update')}}: <span id="whmcs-module__invoices-last-update"></span></small>
    <a href="#" target="_blank" id="whmcs-module__invoices-show-all" class="btn btn-primary btn-xs btn-block" style="margin: 5px 0;">{{__('View all invoices')}}</a>
</div>
