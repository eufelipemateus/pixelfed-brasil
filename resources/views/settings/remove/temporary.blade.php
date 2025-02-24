@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{ __("settings.remove.temporary.title")  }}</h3>
  </div>
  <hr>
  <div class="mt-3">
  	{!! __("settings.remove.temporary.body")  !!}
  	<p>
  		<form method="post">
        @csrf
  		  <button type="submit" class="btn btn-primary font-weight-bold py-0">{{ __("settings.remove.temporary.button")}}</button>
  		</form>
  	</p>
  </div>


@endsection
