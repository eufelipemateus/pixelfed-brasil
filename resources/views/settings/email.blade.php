@extends('settings.template')

@section('section')

<div class="d-flex justify-content-between align-items-center">
    <div class="title d-flex align-items-center" style="gap: 1rem;">
        <p class="mb-0"><a href="/settings/home"><i class="far fa-chevron-left fa-lg"></i></a></p>
        <h3 class="font-weight-bold mb-0">{{__('settings.email.email_settings')}}</h3>
    </div>
</div>

<hr>
<form method="post" action="{{route('settings.email')}}">
    @csrf
    <input type="hidden" class="form-control" name="name" value="{{Auth::user()->profile->name}}">
    <input type="hidden" class="form-control" name="username" value="{{Auth::user()->profile->username}}">
    <input type="hidden" class="form-control" name="website" value="{{Auth::user()->profile->website}}">

    <div class="form-group">
        <label for="email" class="font-weight-bold">{{__('settings.email.email_address')}}</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" value="{{Auth::user()->email}}">
        <p class="help-text small text-muted font-weight-bold">
            @if(Auth::user()->email_verified_at)
            <span class="text-success">{{__('settings.email.verified')}}</span> {{Auth::user()->email_verified_at->diffForHumans()}}
            @else
            <span class="text-danger">{{__('settings.email.unverified')}}</span> {{__('settings.email.you_need_to')}} <a href="/i/verify-email">{{__('settings.email.verify_your_email')}}</a>.
            @endif
        </p>
    </div>
    <div class="form-group row">
        <div class="col-12 text-right">
            <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.change')}}</button>
        </div>
    </div>
</form>
<form method="post" action="{{route('settings.email_config')}}">
    @csrf
    <div class="form-group">

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="send_email_new_follower" id="send_email_new_follower" {{$settings['send_email_new_follower'] ? 'checked=""':''}}>
            <label class="form-check-label font-weight-bold" for="send_email_new_follower">
                {{__('settings.email.send_email_new_follower')}}
            </label>
        </div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="send_email_new_follower_request" id="send_email_new_follower_request" {{$settings['send_email_new_follower_request'] ? 'checked=""':''}}>
            <label class="form-check-label font-weight-bold" for="send_email_new_follower_request">
                {{__('settings.email.send_email_new_follower_request')}}
            </label>
        </div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="send_email_on_share" id="send_email_on_share" {{$settings['send_email_on_share'] ? 'checked=""':''}}>
            <label class="form-check-label font-weight-bold" for="send_email_on_share">
                {{__('settings.email.send_email_on_share')}}
            </label>
        </div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="send_email_on_like" id="send_email_on_like" {{$settings['send_email_on_like'] ? 'checked=""':''}}>
            <label class="form-check-label font-weight-bold" for="send_email_on_like">
                {{__('settings.email.send_email_on_like')}}
            </label>
        </div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="send_email_on_mention" id="send_email_on_mention" {{$settings['send_email_on_mention'] ? 'checked=""':''}}>
            <label class="form-check-label font-weight-bold" for="send_email_on_mention">
                {{__('settings.email.send_email_on_mention')}}
            </label>
        </div>

        <div class="form-check pb-3">
            <input class="form-check-input" type="checkbox" name="felipemateus_wants_updates" id="felipemateus_wants_updates" {{$settings['felipemateus_wants_updates'] ? 'checked=""':''}}>
            <label class="form-check-label font-weight-bold" for="felipemateus_wants_updates">
                Receba atualizações sobre produtos Felipe Mateus.
            </label>
        </div>


    </div>
    <div class="form-group row">
        <div class="col-12 text-right">
            <button type="submit" class="btn btn-primary font-weight-bold py-0 px-5">{{__('settings.submit')}}</button>
        </div>
    </div>
</form>
@endsection
