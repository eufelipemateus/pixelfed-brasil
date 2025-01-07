@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{__('helpcenter.helpcenter')}}</h3>
  </div>
  <hr>
  {{-- <div class="row pb-5">
    <div class="col-12">
      <p class="font-weight-bold">{{__('helpcenter.whatsnew')}}</p>
      <ul class="small">
        <li>
          <a href="#">Stories</a>
        </li>
        <li>
          <a href="#">Mobile Web UI updates</a>
        </li>
      </ul>
    </div>
  </div> --}}
  <div class="row">
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.getting-started')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-life-ring text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.gettingStarted')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.howAccount')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.howBio')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.hashtags')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-hashtag text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.hashtags')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.whatHahstag')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.hashtagsTips')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.sharing-media')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-plus-square text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.sharingMedia')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.howPost') }}</p>
                <p class="small text-dark font-weight-bold mb-0">{{__('helpcenter.howFilter')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.discover')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-compass text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.discover')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.whatDiscover')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.whatDiscoverCat')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.your-profile')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-user text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.profile')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.howPrivate')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.howSecure')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.dm')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-comment-dots text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.directMessages')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.howDirect')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.hoUnsend')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    {{-- <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.stories')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-pause-circle text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.stories')}}</p>
            <div class="text-center pt-3">
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
            </div>
          </div>
        </div>
      </a>
    </div> --}}
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.timelines')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-home text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.timelines')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.personal')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.public') }}</p>
            </div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.community-guidelines')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-user-shield text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.communityGuidelines')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.contentRemoved')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.contentExplicitly')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    {{-- <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.blocking-accounts')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-ban text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.blockingAccounts')}}</p>
            <div class="text-center pt-3">
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
            </div>
          </div>
        </div>
      </a>
    </div> --}}
    {{-- <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.what-is-fediverse')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-network-wired text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.whatIsTheFediverse')}}</p>
            <div class="text-center pt-3">
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
            </div>
          </div>
        </div>
      </a>
    </div> --}}
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.safety-tips')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="fas fa-shield-alt text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.safetyTips')}}</p>
            <div class="text-center pt-3">
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.knowRules')}}</p>
                <p class="small text-dark font-weight-bold mb-0">{{ __('helpcenter.make3Post')}}</p>
            </div>
          </div>
        </div>
      </a>
    </div>
    {{-- <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.controlling-visibility')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-eye-slash text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.controllingVisibility')}}</p>
            <div class="text-center pt-3">
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
            </div>
          </div>
        </div>
      </a>
    </div> --}}
    {{-- <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.report-something')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-flag text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.reportSomething')}}</p>
            <div class="text-center pt-3">
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
              <p class="small text-dark font-weight-bold mb-0">&nbsp;</p>
            </div>
          </div>
        </div>
      </a>
    </div> --}}
    {{-- <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.data-policy')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="text-center text-muted font-weight-bold h4 mb-0">{{__('helpcenter.dataPolicy')}}</p>
          </div>
        </div>
      </a>
    </div> --}}
    <div class="col-12 col-md-6 mb-3">
      <a href="{{route('help.import')}}" class="text-decoration-none">
        <div class="card">
          <div class="card-body">
            <p class="py-1 text-center">
              <i class="far fa-file-import text-lighter fa-2x"></i>
            </p>
            <p class="text-center text-muted font-weight-bold h4 mb-0">Import</p>
            <div class="text-center pt-3">
              <p class="small text-dark font-weight-bold mb-0">How to Import from Instagram</p>
              <p class="small text-dark font-weight-bold mb-0">Troubleshooting Imports</p>
            </div>
          </div>
        </div>
      </a>
    </div>
  </div>
@endsection

@push('meta')
<meta property="og:description" content="Help">
@endpush
