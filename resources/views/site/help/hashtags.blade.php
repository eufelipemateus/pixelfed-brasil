@extends('site.help.partial.template', ['breadcrumb'=>'Hashtags'])

@section('section')
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [{
        "@type": "Question",
        "name": "{{ __('helpcenter.howUseHashtagAsk')}}",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "{!! __('helpcenter.howUseHashtagAnswer') !!}"
        }
      }, {
        "@type": "Question",
        "name": " {{ __('helpcenter.howFollowHashtagAsk')}}",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "{!! __('helpcenter.howFollowHashtagAnswer') !!}"
        }
      }]
    }
    </script>

  <div class="title">
    <h3 class="font-weight-bold">Hashtags</h3>
  </div>
  <hr>
  <p class="lead">{{ __('helpcenter.hashtagLead')}}</p>
  <div class="py-4">
    {!! __('helpcenter.hashtagInfo') !!}
  </div>
  <div class="py-4">
  <p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
    <a itemprop="name" class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse0" role="button" aria-expanded="false" aria-controls="collapse0">
        <i class="fas fa-chevron-down mr-2"></i>
        {{ __('helpcenter.howUseHashtagAsk')}}
    </a>
    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse0">
        <div itemprop="text">
            {!! __('helpcenter.howUseHashtagAnswer') !!}
        </div>
    </div>
  </p>
  <p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
    <a itemprop="name" class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
        <i class="fas fa-chevron-down mr-2"></i>
        {{ __('helpcenter.howFollowHashtagAsk')}}
    </a>
    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse1">
        <div itemprop="text">
            {!! __('helpcenter.howFollowHashtagAnswer') !!}
        </div>
    </div>
  </p>
  </div>
  <hr>
  <div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
    <div class="card-header text-light font-weight-bold h4 p-4 bg-primary">{{ __('helpcenter.hashtagTipsTitle')}}</div>
    <div class="card-body bg-white p-3">
      {!! __('helpcenter.hashtagTips') !!}
    </div>
  </div>
@endsection
