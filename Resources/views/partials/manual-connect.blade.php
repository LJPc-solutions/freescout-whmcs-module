<div class="hidden" id="whmcs-module__manual-connect">
    <div>
        <p>{{__('Automatic linking failed. No customer found in WHMCS with the same email address.')}}</p>
        <a href="#" class="btn btn-primary" id="whmcs-module__retry-autoconnect">{{__('Try again')}}</a>
    </div>
    <hr>
    <div>
        <p>{{__('Manual linking')}}</p>
        <div id="whmcs-module__manual-connect-form">
            <div class="form-group row">
                <div class="col-xs-12">
                    <input type="text" class="form-control" id="whmcs-module__manual-connect-search" required placeholder="{{__('Search...')}}">
                    <img src="{{$loader_url}}" class="hidden loading" width="16" height="16"/>
                    <ul id="whmcs-module__manual-connect-results" class="list-group mt-2"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
