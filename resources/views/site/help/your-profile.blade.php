@extends('site.help.partial.template', ['breadcrumb'=>__("helpcenter.profieleTitle")  ])

@php
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];

    // Lista de perguntas/respostas (use __() ou {!! !!} conforme precisar)
    $questions = [
        [ __("helpcenter.profileEditBioAsk"), __("helpcenter.profileEditBioAnswer") ],
        [ __("helpcenter.profileEditUpdateUsernameAsk"), __("helpcenter.profileEditUpdateUsernameAnswer") ],
        [ __('helpcenter.profilePrivacyAsk'), __('helpcenter.profilePrivacyAnswer') ],
        [ __("helpcenter.profileSecurityHowSecureAsk"), __("helpcenter.profileSecurityHowSecureAnswer") ],
        [ __("helpcenter.profileSecurityHowAddSecurityAsk"), __("helpcenter.profileSecurityHowAddSecurityAnswer") ],
        [ __("helpcenter.profileSecurityHowReportUnauthorizedAsk"), __("helpcenter.profileSecurityHowReportUnauthorizedAnswer") ],
        [ __("helpcenter.profileMigrarionHowMigrateAsk"), __("helpcenter.profileMigrarionHowMigrateAnswer") ],
        [ __("helpcenter.profileMigrationHowLongAsk"), __("helpcenter.profileMigrationHowLongAnswer") ],
        [ __("helpcenter.profileMigrationWhyPostNotAsk"), __("helpcenter.profileMigrationWhyPostNotAnswer") ],
        [ __('helpcenter.profileDeleteTemporaryAsk'), __('helpcenter.profileDeleteTemporaryAnswer') ],
    ];

    foreach ($questions as $qa) {
        $q = $qa[0];
        $a = $qa[1];
        // remove tags HTML das respostas para evitar "<" dentro do array fonte
        $data['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => is_string($a) ? strip_tags($a) : '',
            ],
        ];
    }

    // Se a exclusão de conta está ativada, adiciona a pergunta extra (texto sem HTML)
    if (config('pixelfed.account_deletion')) {
        $deleteText = 'When you delete your account, your profile, photos, videos, comments, likes and followers will be permanently removed.';
        if (config('pixelfed.account_delete_after')) {
            $deleteText .= ' Deletion will occur after ' . config('pixelfed.account_delete_after') . ' days unless you log in to cancel.';
        }
        $deleteText .= ' To permanently delete your account: 1) Go to the Delete Your Account page; 2) Navigate to Security Settings; 3) Confirm your account password; 4) Click Delete; 5) Follow the instructions.';

        $data['mainEntity'][] = [
            '@type' => 'Question',
            'name' => 'How do I delete my account?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $deleteText,
            ],
        ];
    }
@endphp

@section('section')
    <script type="application/ld+json">
    {!! json_encode($data) !!}
    </script>

  <div class="title">
    <h3 class="font-weight-bold">{{ __("helpcenter.profieleTitle")  }}</h3>
  </div>
  <hr>
  <p class="h5 text-muted ">{{ __("helpcenter.profileEdit")  }}</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse1" role="button" aria-expanded="false" aria-controls="collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
     {{ __("helpcenter.profileEditBioAsk")  }}
    </a>
    <div class="collapse" id="collapse1">
        {!! __("helpcenter.profileEditBioAnswer")  !!}
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse2" role="button" aria-expanded="false" aria-controls="collapse2">
      <i class="fas fa-chevron-down mr-2"></i>
     {{ __("helpcenter.profileEditUpdateUsernameAsk")  }}
    </a>
    <div class="collapse" id="collapse2">
        {!! __("helpcenter.profileEditUpdateUsernameAnswer")  !!}
    </div>
  </p>
  <hr>
  <p class="h5 text-muted ">{{ __('helpcenter.profilePrivacyTitle')}}</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#collapse3" role="button" aria-expanded="false" aria-controls="collapse3">
      <i class="fas fa-chevron-down mr-2"></i>
        {{ __('helpcenter.profilePrivacyAsk')}}
    </a>
    <div class="collapse" id="collapse3">
      {!! __('helpcenter.profilePrivacyAnswer') !!}
    </div>
  </p>

  <hr>
  <p class="h5 text-muted " id="security">{{ __("helpcenter.profileSecurityTitle")}}</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse8" role="button" aria-expanded="false" aria-controls="sec-collapse8">
      <i class="fas fa-chevron-down mr-2"></i>
      {{ __("helpcenter.profileSecurityHowSecureAsk")}}
    </a>
    <div class="collapse" id="sec-collapse8">
       {!! __("helpcenter.profileSecurityHowSecureAnswer") !!}
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse9" role="button" aria-expanded="false" aria-controls="sec-collapse9">
      <i class="fas fa-chevron-down mr-2"></i>
     {{ __("helpcenter.profileSecurityHowAddSecurityAsk")}}
    </a>
    <div class="collapse" id="sec-collapse9">
      {!! __("helpcenter.profileSecurityHowAddSecurityAnswer") !!}
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#sec-collapse10" role="button" aria-expanded="false" aria-controls="sec-collapse10">
      <i class="fas fa-chevron-down mr-2"></i>
      {{ __("helpcenter.profileSecurityHowReportUnauthorizedAsk")}}
    </a>
    <div class="collapse" id="sec-collapse10">
      <div>
       {{ __('helpcenter.profileSecurityHowReportUnauthorizedAnswer') }}
      </div>
    </div>
  </p>
  <hr>
  <p class="h5 text-muted " id="migration">{{ __("helpcenter.profileMigrarionTitle") }}</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#migrate-collapse1" role="button" aria-expanded="false" aria-controls="migrate-collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
     {{ __("helpcenter.profileMigrarionHowMigrateAsk") }}
    </a>
    <div class="collapse" id="migrate-collapse1">
      {!! __("helpcenter.profileMigrarionHowMigrateAnswer") !!}
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#migrate-collapse2" role="button" aria-expanded="false" aria-controls="migrate-collapse2">
      <i class="fas fa-chevron-down mr-2"></i>
      {{ __("helpcenter.profileMigrationHowLongAsk") }}
    </a>
    <div class="collapse" id="migrate-collapse2">
      <div>
        {!! __("helpcenter.profileMigrationHowLongAnswer") !!}
      </div>
    </div>
  </p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#migrate-collapse3" role="button" aria-expanded="false" aria-controls="migrate-collapse3">
      <i class="fas fa-chevron-down mr-2"></i>
     {{__("helpcenter.profileMigrationWhyPostNotAsk")  }}
    </a>
    <div class="collapse" id="migrate-collapse3">
            {!! __("helpcenter.profileMigrationWhyPostNotAnswer") !!}
    </div>
  </p>
  <hr>
  <p class="h5 text-muted " id="delete-your-account">{{ __('helpcenter.profileDeleteTitle')  }}</p>
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#del-collapse1" role="button" aria-expanded="false" aria-controls="del-collapse1">
      <i class="fas fa-chevron-down mr-2"></i>
     {{ __('helpcenter.profileDeleteTemporaryAsk')  }}
    </a>
    <div class="collapse" id="del-collapse1">
        {!! __('helpcenter.profileDeleteTemporaryAnswer') !!}
    </div>
  </p>
  @if(config('pixelfed.account_deletion'))
  <p>
    <a class="text-dark font-weight-bold" data-toggle="collapse" href="#del-collapse2" role="button" aria-expanded="false" aria-controls="del-collapse2">
      <i class="fas fa-chevron-down mr-2"></i>
      How do I delete my account?
    </a>
    <div class="collapse" id="del-collapse2">
      <div>
        @if(config('pixelfed.account_delete_after') == false)
        <div class="bg-light p-3 mb-4">
          <p class="mb-0">When you delete your account, your profile, photos, videos, comments, likes and followers will be <b>permanently removed</b>. If you'd just like to take a break, you can <a href="{{route('settings.remove.temporary')}}">temporarily disable</a> your account instead.</p>
        </div>
        @else
        <div class="bg-light p-3 mb-4">
          <p class="mb-0">When you delete your account, your profile, photos, videos, comments, likes and followers will be <b>permanently removed</b> after {{config('pixelfed.account_delete_after')}} days. You can log in during that period to prevent your account from permanent deletion. If you'd just like to take a break, you can <a href="{{route('settings.remove.temporary')}}">temporarily disable</a> your account instead.</p>
        </div>
        @endif
        <p>After you delete your account, you can't sign up again with the same username on this instance or add that username to another account on this instance, and we can't reactivate deleted accounts.</p>
        <p>To permanently delete your account:</p>
        <ol class="">
          <li>Go to <a href="{{route('settings.remove.permanent')}}">the <span class="font-weight-bold">Delete Your Account</span> page</a>.  If you're not logged into pixelfed on the web, you'll be asked to log in first. You can't delete your account from within a mobile app.</li>
          <li>Navigate to the <a href="{{route('settings.security')}}">Security Settings</a></li>
          <li>Confirm your account password.</li>
          <li>Scroll down to the Danger Zone section and click on the <span class="btn btn-sm btn-outline-danger py-1 font-weight-bold">Delete</span> button.</li>
          <li>Follow the instructions on the next page.</li>
        </ol>
      </div>
    </div>
  </p>
  @endif
@endsection
