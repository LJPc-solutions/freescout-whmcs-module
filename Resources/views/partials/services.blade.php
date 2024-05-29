<div class="loading">
    <img src="{{$loader_url}}" width="16" height="16"/> {{__('Loading')}}...
</div>
<div class="loaded hidden">
    <ul id="whmcs-module__services-list" class="list-group mt-2"></ul>
    <small class="text-muted">{{__('Last update')}}: <span id="whmcs-module__services-last-update"></span></small>
    <div style="display: flex; gap:5px;">
        <a href="#" target="_blank" id="whmcs-module__services-show-all" class="btn btn-primary btn-xs btn-block" style="margin: 5px 0;">{{__('All services')}}</a>
        <a href="#" target="_blank" id="whmcs-module__domains-show-all" class="btn btn-primary btn-xs btn-block" style="margin: 5px 0;">{{__('All domains')}}</a>
    </div>
</div>
