@extends('site.help.partial.template', ['breadcrumb'=>'Sharing Photos & Videos'])


@section('section')
    <script type="application/ld+json">
    {!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howCreatePostAask"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howCreatePostAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howAddMultiplePhotosAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howAddMultiplePhotosAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howCaptionBeforeSharePhotoAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howCaptionBeforeSharePhotoAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howAddFilterAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howAddFilterAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howAddDescriptionPhotoAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howAddDescriptionPhotoAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howMediaTypesCanUploadAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howMediaTypesCanUploadAnswer") . ' ' . implode(', ', explode(',', config_cache('pixelfed.media_types'))),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howDisablecommentsAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howDisablecommentsAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howManyTagMentionAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howManyTagMentionAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.whatArchiveMeanAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.whatArchiveMeanAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howArchivePostAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howArchivePostAnswer"),
                ],
            ],
            [
                '@type' => 'Question',
                'name' => __("helpcenter.howUnarchivePostAsk"),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => __("helpcenter.howUnarchivePostAnswer"),
                ],
            ],
        ]
    ]) !!}
    </script>

	<div class="title">
		<h3 class="font-weight-bold">{{ __("helpcenter.sharingMediaTitle")  }}</h3>
	</div>
	<hr>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __("helpcenter.howCreatePostAask") }}
		</a>
		<div class="collapse" id="collapse1">
			{!! __("helpcenter.howCreatePostAnswer") !!}
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __("helpcenter.howAddMultiplePhotosAsk") }}
		</a>
		<div class="collapse" id="collapse2">
			<div>
                {{ __("helpcenter.howAddMultiplePhotosAnswer") }}
            </div>
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __("helpcenter.howCaptionBeforeSharePhotoAsk") }}
		</a>
		<div class="collapse" id="collapse3">
			{!! __("helpcenter.howCaptionBeforeSharePhotoAnswer") !!}
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __("helpcenter.howAddFilterAsk") }}
		</a>
		<div class="collapse" id="collapse4">
			{!! __("helpcenter.howAddFilterAnswer") !!}
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __("helpcenter.howAddDescriptionPhotoAsk") }}
		</a>
		<div class="collapse" id="collapse5">
			{!! __("helpcenter.howAddDescriptionPhotoAnswer") !!}
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __("helpcenter.howMediaTypesCanUploadAsk") }}
		</a>
		<div class="collapse" id="collapse6">
			<div>
				{{ __("helpcenter.howMediaTypesCanUploadAnswer") }}
				<ul>
					@foreach(explode(',', config_cache('pixelfed.media_types')) as $type)
					<li class="font-weight-bold">{{$type}}</li>
					@endforeach
				</ul>
			</div>
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse10" role="button" aria-expanded="false" aria-controls="collapse10">
			<i class="fas fa-chevron-down mr-2"></i>
			{{  __('helpcenter.howDisablecommentsAsk') }}
		</a>
		<div class="collapse" id="collapse10">
            {!! __('helpcenter.howDisablecommentsAnswer') !!}
		</div>
	</p>
	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse11" role="button" aria-expanded="false" aria-controls="collapse11">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __('helpcenter.howManyTagMentionAsk') }}
		</a>
		<div class="collapse" id="collapse11">
			<div>
				{{ __('helpcenter.howManyTagMentionAnswer')  }}
			</div>
		</div>
	</p>

	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse12" role="button" aria-expanded="false" aria-controls="collapse11">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __('helpcenter.whatArchiveMeanAsk') }}
		</a>
		<div class="collapse" id="collapse12">
			{!! __('helpcenter.whatArchiveMeanAnswer') !!}
		</div>
	</p>

	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse13" role="button" aria-expanded="false" aria-controls="collapse11">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __('helpcenter.howArchivePostAsk') }}
		</a>
		<div class="collapse" id="collapse13">
			{!! __('helpcenter.howArchivePostAnswer') !!}
		</div>
	</p>

	<p>
		<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse14" role="button" aria-expanded="false" aria-controls="collapse11">
			<i class="fas fa-chevron-down mr-2"></i>
			{{ __('helpcenter.howUnarchivePostAsk') }}
		</a>
		<div class="collapse" id="collapse14">
			{!! __('helpcenter.howUnarchivePostAnswer') !!}
		</div>
	</p>

@endsection
