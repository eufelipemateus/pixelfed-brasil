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
<meta property="og:description" content="Fediverse {{__('site.is_a_portmanteau_of_federation_and_universe_etc')}}">
@endpush
