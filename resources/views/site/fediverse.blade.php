@extends('site.partial.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{ __('fediverse.title')}}</h3>
  </div>
  <hr>
  <section>
    {!! __('fediverse.body') !!}
  </section>
@endsection

@push('meta')
<meta property="og:description" content="Fediverse is a portmanteau of “federation” and “universe”. It is a common, informal name for a federation of social network servers, specializing in different types of media.">
@endpush
