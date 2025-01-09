@extends('site.help.partial.template', ['breadcrumb'=>'Direct Messages'])

@section('section')

<div class="title">
  <h3 class="font-weight-bold">{{__('helpcenter.directMessages')}}</h3>
</div>
<hr>
<p class="lead ">{{ __('helpcenter.dmSubTitle') }}</p>
<hr>
<p>
  <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
    <i class="fas fa-chevron-down mr-2"></i>
   {{ __('helpcenter.howUseDirectMessagesAsk') }}
  </a>
  <div class="collapse" id="collapse1">
    {!! __('helpcenter.howUseDirectMessagesAnswer') !!}
  </div>
</p>
<p>
  <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
    <i class="fas fa-chevron-down mr-2"></i>
   {{ __('helpcenter.howUnsedDirectMessageAsk') }}
  </a>
  <div class="collapse" id="collapse3">
    <div class="mt-2">
     {!! __('helpcenter.howUnsedDirectMessageAnswer') !!}
    </div>
  </div>
</p>
<p>
  <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
    <i class="fas fa-chevron-down mr-2"></i>
   {{ __('helpcenter.canSendDirectMessageAsk') }}
  </a>
  <div class="collapse" id="collapse4">
    <div class="mt-2">
        {{ __('helpcenter.canSendDirectMessageAnswer') }}
    </div>
  </div>
</p>
<p>
  <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
    <i class="fas fa-chevron-down mr-2"></i>
   {{ __('helpcenter.howReportDirectMessageAsk') }}
  </a>
  <div class="collapse" id="collapse5">
    <div class="mt-2">
        {!! __('helpcenter.howReportDirectMessageAnswer') !!}
    </div>
  </div>
</p>

@endsection
