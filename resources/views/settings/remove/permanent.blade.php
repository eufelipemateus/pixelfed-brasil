@extends('settings.template')

@section('section')

  <div class="title">
    <h3 class="font-weight-bold">{{ __("settings.remove.permanent.title") }}</h3>
  </div>
  <hr>
  <div class="mt-3">
  	{!! __('settings.remove.permanent.body') !!}

  	<p>
      <form method="post">
        @csrf
        <div class="custom-control custom-switch mb-3">
          <input type="checkbox" class="custom-control-input" id="confirm-check">
          <label class="custom-control-label font-weight-bold" for="confirm-check">{{ __("settings.remove.permanent.confirm_check") }}</label>
        </div>
        <button type="submit" class="btn btn-danger font-weight-bold py-0 delete-btn" disabled="">{{ __("settings.remove.permanent.confirm_button") }}</button>
      </form>
  	</p>
  </div>


@endsection

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
  $('#confirm-check').on('change', function() {
    let el = $(this);
    let state = el.prop('checked');
    if(state == true) {
      $('.delete-btn').removeAttr('disabled');
    } else {
      $('.delete-btn').attr('disabled', '');
    }
  });
});
</script>
@endpush
