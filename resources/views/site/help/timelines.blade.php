@extends('site.help.partial.template', ['breadcrumb'=> __("helpcenter.timelines") ])

@section('section')

	<div class="title">
		<h3 class="font-weight-bold">{{ __("helpcenter.timelines")  }}</h3>
	</div>
	<hr>
	<p class="lead">{{ __('helpcenter.timelineSubTitle')  }}</p>

	<ul class="list-unstyled">
		<li class="lead mb-2">
			<span class="font-weight-bold"><i class="fas fa-home mr-2"></i> Home</span>
			<span class="px-2">&mdash;</span>
			<span class="font-weight-light">{{ __('helpcenter.timelineHome')  }}</span>
		</li>
		<li class="lead mb-2">
			<span class="font-weight-bold"><i class="fas fa-stream mr-2"></i> Public</span>
			<span class="px-2">&mdash;</span>
			<span class="font-weight-light">{{ __('helpcenter.timelinePublic') }}</span>
		</li>
		<li class="lead">
			<span class="font-weight-bold"><i class="fas fa-globe mr-2"></i> Network</span>
			<span class="px-2">&mdash;</span>
			<span class="font-weight-light">{{ __('helpcenter.timelineNetwork') }}</span>
		</li>
	</ul>
	<div class="py-3"></div>
	<div class="card bg-primary border-primary" style="box-shadow: none !important;border: 3px solid #08d!important;">
		<div class="card-header text-light font-weight-bold h4 p-4 bg-primary">{{ __('helpcenter.timelineTips') }}</div>
		<div class="card-body bg-white p-3">
			{!! __('helpcenter.timelineTipsContent') !!}
		</div>
	</div>
@endsection
