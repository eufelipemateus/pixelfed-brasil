@extends('site.help.partial.template', ['breadcrumb'=>'Discover'])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{ __('helpcenter.discorverTitle')}}</h3>
  </div>
  <hr>
  <p class="lead">{{__('helpcenter.discoversubTitle')}}</p>
  <div class="py-4">
  {!! __('helpcenter.howUseDiscover') !!}
  </div>
  <div class="py-4">
    {!! __('helpcenter.discoverCategories') !!}
  </div>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    {!! __('helpcenter.discoverTips') !!}
  </div>
@endsection
