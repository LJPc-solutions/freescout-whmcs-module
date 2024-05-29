<div class="container">
    <div class="row">
        <div class="col-sm-6">
            <img src="{{Module::getPublicPath( 'ljpcwhmcsmodule' ) . '/images/whmcs_logo_navy_green.png'}}" alt="WHMCS" style="width:350px; max-width:80%;">
            <p>{!! __("This module, developed by :solutions, integrates WHMCS with Freescout. It allows you to connect customers between the two systems, view customer details, invoices, services, and domains from WHMCS directly in your application. The module also provides functionality to log in as a client in WHMCS and navigate to the client's page in the WHMCS admin area.",[
										                'solutions' => '<a href="https://ljpc.solutions">LJPc solutions</a>'])!!}</p>
            <p>{{__('To set up the integration, please provide your WHMCS base URL, API identifier, and API secret in the fields below.')}}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            @if($connected === false)
                <div class="alert alert-danger">
                    <strong>{{__('WHMCS is not connected.') }}</strong><br/><br/>
                    {{__('Please note that the WHMCS API must be enabled in your WHMCS installation. To enable the API, log in to your WHMCS admin area, navigate to Setup > Staff Management > Manage API Credentials, and create a new API credential.')}}
                </div>
            @else
                <div class="alert alert-success">
                    <strong>{{__('WHMCS is connected.')}}</strong><br/><br/>
                    {{__('Details')}}:<br/>
                    <pre>{{json_encode($whmcs_details, JSON_PRETTY_PRINT)}}</pre>
                </div>
            @endif
        </div>
    </div>
</div>
<h3 class="subheader">{{__('Settings')}}</h3>
<form class="form-horizontal margin-top margin-bottom" method="POST" action="" enctype="multipart/form-data">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('settings.whmcs_base_url') ? ' has-error' : '' }}">
        <label class="col-sm-1 control-label">{{ __('Base URL') }}</label>
        <div class="col-sm-6">
            <input type="url" class="form-control input-sized-lg" name="settings[whmcs_base_url]"
                   value="{{ old('settings.whmcs_base_url', $settings['whmcs_base_url']) }}">

            @include('partials/field_error', ['field'=>'settings.whmcs_base_url'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.whmcs_api_identifier') ? ' has-error' : '' }}">
        <label class="col-sm-1 control-label">{{ __('API Identifier') }}</label>
        <div class="col-sm-6">
            <input type="text" class="form-control input-sized-lg" name="settings[whmcs_api_identifier]"
                   value="{{ old('settings.whmcs_api_identifier', $settings['whmcs_api_identifier']) }}">

            @include('partials/field_error', ['field'=>'settings.whmcs_api_identifier'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.whmcs_api_secret') ? ' has-error' : '' }}">
        <label class="col-sm-1 control-label">{{ __('API Secret') }}</label>
        <div class="col-sm-6">
            <input type="password" class="form-control input-sized-lg" name="settings[whmcs_api_secret]"
                   value="{{ old('settings.whmcs_api_secret', $settings['whmcs_api_secret']) }}">

            @include('partials/field_error', ['field'=>'settings.whmcs_api_secret'])
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-1">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>

</form>
