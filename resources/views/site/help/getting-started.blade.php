@extends('site.help.partial.template', ['breadcrumb'=>__('helpcenter.gettingStarted')])


@section('section')
<script type="application/ld+json">
{!! json_encode([
    "@context" => "https://schema.org",
    "@type" => "FAQPage",
    "mainEntity" => [
        [
            "@type" => "Question",
            "name" => __('helpcenter.howCreateAccountAask'),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => __('helpcenter.howCreateAccountAnswer')
            ]
        ],
        [
            "@type" => "Question",
            "name" => __('helpcenter.howUpdateProfileAsk'),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => __('helpcenter.howUpdateProfileAnswer')
            ]
        ],
        [
            "@type" => "Question",
            "name" => __('helpcenter.howInactiveUserAsk'),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => __('helpcenter.howInactiveUserAnswer')
            ]
        ],
        [
            "@type" => "Question",
            "name" => __('helpcenter.whyChantUserAsk'),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => __('helpcenter.whyChantUserAnswer')
            ]
        ],
        [
            "@type" => "Question",
            "name" => __('helpcenter.whyReceiveEmaillAsk'),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => __('helpcenter.whyReceiveEmaillAnswer')
            ]
        ],
        [
            "@type" => "Question",
            "name" => __('helpcenter.whyExistsEmailAsk'),
            "acceptedAnswer" => [
                "@type" => "Answer",
                "text" => __('helpcenter.whyExistsEmailAnswer')
            ]
        ]
    ]
]) !!}
</script>
</script>
<div class="title">
	<h3 class="font-weight-bold">{{__('helpcenter.gettingStarted')}}</h3>
</div>
<hr>
<p class="lead ">{{ __('helpcenter.welcomePiexelfed') }}</p>
<hr>
<p  itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
	<a  itemprop="name"  class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
		<i class="fas fa-chevron-down mr-2"></i>
		{{ __('helpcenter.howCreateAccountAask')}}
	</a>
	<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse1">
		<div itemprop="text">
			{!! __('helpcenter.howCreateAccountAnswer') !!}
		</div>
	</div>
</p>
<p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
	<a  itemprop="name"  class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
		<i class="fas fa-chevron-down mr-2"></i>
		{{ __('helpcenter.howUpdateProfileAsk')}}
	</a>
	<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse2">
		<div itemprop="text">
			{!! __('helpcenter.howUpdateProfileAnswer') !!}
		</div>
	</div>
</p>
<p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
	<a  itemprop="name" class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
		<i class="fas fa-chevron-down mr-2"></i>
		{{ __('helpcenter.howInactiveUserAsk')}}
	</a>
	<div  itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse3">
		<div itemprop="text" class="mt-2">
			{{ __('helpcenter.howInactiveUserAnswer')}}
		</div>
	</div>
</p>
<p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
	<a  itemprop="name" class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse4" role="button" aria-expanded="false" aria-controls="collapse4">
		<i class="fas fa-chevron-down mr-2"></i>
		{{ __('helpcenter.whyChantUserAsk') }}
	</a>
	<div  itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse4">
		<div itemprop="text" class="mt-2">
			{!! __('helpcenter.whyChantUserAnswer') !!}
		</div>
	</div>
</p>
<p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
	<a  itemprop="name" class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse5" role="button" aria-expanded="false" aria-controls="collapse5">
		<i class="fas fa-chevron-down mr-2"></i>
		{{ __('helpcenter.whyReceiveEmaillAsk')}}
	</a>
	<div  itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse5">
		<div itemprop="text" class="mt-2">
			{{ __('helpcenter.whyReceiveEmaillAnswer')}}
		</div>
	</div>
</p>
<p itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
	<a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse6" role="button" aria-expanded="false" aria-controls="collapse6">
		<i class="fas fa-chevron-down mr-2"></i>
		{{ __('helpcenter.whyExistsEmailAsk')}}
	</a>
	<div  itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer" class="collapse" id="collapse6">
		<div itemprop="text" class="mt-2">
		{{ __('helpcenter.whyExistsEmailAnswer')}}
		</div>
	</div>
</p>

@endsection
