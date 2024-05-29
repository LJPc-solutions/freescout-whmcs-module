<div class="conv-sidebar-block">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-conv-whmcs">{{__('WHMCS')}}
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-conv-whmcs panel-collapse collapse in">
                <div class="panel-body" style="padding-top:5px;">
                    @include('ljpcwhmcsmodule::partials.loading')
                    @include('ljpcwhmcsmodule::partials.auto-connecting')
                    @include('ljpcwhmcsmodule::partials.manual-connect')
                    @include('ljpcwhmcsmodule::partials.connected')
                </div>
            </div>
        </div>
    </div>
</div>

<div class="conv-sidebar-block hidden" id="whmcs-module__invoices">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-conv-whmcs-invoices">{{__('Invoices')}}
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-conv-whmcs-invoices panel-collapse collapse in">
                <div class="panel-body" style="padding-top:5px;">
                    @include('ljpcwhmcsmodule::partials.invoices')
                </div>
            </div>
        </div>
    </div>
</div>

<div class="conv-sidebar-block hidden" id="whmcs-module__services">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-conv-whmcs-services">{{__('Services and domains')}}
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-conv-whmcs-services panel-collapse collapse in">
                <div class="panel-body" style="padding-top:5px;">
                    @include('ljpcwhmcsmodule::partials.services')
                </div>
            </div>
        </div>
    </div>
</div>


<script {!! \Helper::cspNonceAttr() !!}>
    window.ljpcwhmcsmodule = {
        csrf: '{{ csrf_token() }}',
        whmcs: {
            id: {{$customer->getMeta('whmcs_id','null')}},
            customer_connection: '{{$customer->getMeta('whmcs_connection_status','not tried')}}',
        },
        customer_id: {{$customer->id}},
        conversation_id: {{$conversation->id}}
    };
</script>
