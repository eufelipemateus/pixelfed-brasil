<div style="background: #ffffff; border: 1px solid #f2f2f2; border-radius: 3px; width: 100%; max-width: 600px; margin: 2% auto;">
    <div style="padding: 20px; height: 40px; display: flex; align-items: center;">
      <img src="{{ $post->profile->avatarUrl()  }}" style="border-radius: 50%; width: 40px; height: 40px; vertical-align: middle; margin-right: 10px;"/>
      <a style="margin-left: 0; font-weight: bold; color: #262626; text-decoration: none;" href="{{ url('/' . $post->profile->username) }}">{{ '@'.$post->profile->username }}</a>
     {{-- <div style="margin-left: auto; color: #999;">58 min</div>--}}
    </div>

    <div>
    <a href="{{ url('i/web/post/' . $post->id) }}" target="_blank">
        <img src="{{ $post->mediaUrl() }}" width="90%" style=" margin: 0 5%; border-radius: 10px;" alt="{{ $post->firstMedia()->caption ?? 'Imagem do post.' }}" />
    </a>
    </div>

    <div style="padding: 20px;">
    <p style="font-weight: bold; text-align: left;">{{ $post->likes_count}} Curttidas &bull;  {{ $post->reblogs_count}}  Compartilhamentos   </p>
    
    @if(!empty($post->caption))
        <p style="text-align: left;"><a style="font-weight: bolder; color: #262626; text-decoration: none;" href="{{ url('/' . $post->profile->username) }}">{{ '@'.$post->profile->username }}</a> {!! $post->rendered !!}</p>
    @endif

    @if(count($post->comments))
        <p style="color: #999;">
            <a href="{{ url('i/web/post/' . $post->id) }}" style="color: #999; text-decoration: none;">Ver {{ count($post->comments) }} comentários</a>
        </p>
    @endif

     @for ($i = 0; $i <  (count($post->comments) <3?  count($post->comments) : 3   ) ; $i++)
        <p style="text-align: left;"><a style="color: #003569; text-decoration: none;  text-align: left;" href="{{ url('/' . $post->comments[$i]->profile->username) }}">{{ '@'.$post->comments[$i]->profile->username }}</a>  {!! $post->comments[$i]->rendered !!}</p>
     @endfor
    </div>
</div>
