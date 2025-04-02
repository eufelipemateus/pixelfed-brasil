@extends('site.help.partial.template', ['breadcrumb'=> __("helpcenter.safetyTips") ])

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{ __("helpcenter.safetyTips")  }}</h3>
  </div>
  <hr>
  <p class="lead py-4">{{ __('helpcenter.safetyTipsSubTitle')  }}</p>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">{{ __('helpcenter.safetyTipsKnowRules') }}</p>
      <p class="mb-0">{!! __('helpcenter.safetyTipsKnowRulesContent') !!} </p>
    </div>
  </div>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">{{  __("helpcenter.safetyTipsAage") }}</p>
      <p class="mb-0">{{ __("helpcenter.safetyTipsAageContent") }}</p>
    </div>
  </div>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">{{  __("helpcenter.safetyTipsRport")  }}</p>
      <p class="mb-0">{{ __("helpcenter.safetyTipsRportContent") }}</p>
    </div>
  </div>

  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">{{ __("helpcenter.safetyTipsVisility")  }}</p>
      <p class="mb-0">{{ __("helpcenter.safetyTipsVisilityContent")  }}</p>
    </div>
  </div>


  <div class="card border-left-blue mb-3">
    <div class="card-body">
      <p class="h5">{{ __("helpcenter.safetyTipsPostsPrivacy")  }}</p>
      <p class="mb-0">{{ __("helpcenter.safetyTipsPostsPrivacyContent") }}</p>
    </div>
  </div>
@endsection
