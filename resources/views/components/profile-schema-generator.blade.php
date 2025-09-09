@section('schema')
@if($settings['crawlable'])
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ProfilePage",
  "dateCreated": "{{ $profile->created_at->format(DateTime::ATOM) }}",
  "dateModified": "{{ $profile->updated_at->format(DateTime::ATOM) }}",
  "mainEntity": {
    "@@type": "Person",
    "name": "{{ $profile->name }}",
    "alternateName": "{{ $profile->username }}",
    "identifier": "{{ $profile->id }}",
    "interactionStatistic": [
      {
        "@@type": "InteractionCounter",
        "interactionType": "https://schema.org/FollowAction",
        "userInteractionCount": {{ $settings['followers']['count'] ? $profile->followers_count : 0 }}
      },
    ],
    "agentInteractionStatistic": {
      "@@type": "InteractionCounter",
      "interactionType": "https://schema.org/WriteAction",
      "userInteractionCount": {{ $profile->status_count }}
    },
    "description": "{{ $profile->bio }}",
    "image": "{{ $profile->avatar_url }}",
    "sameAs": [
      "{{$profile->website}}"
    ]
  }
}
</script>
@endif
@endsection
