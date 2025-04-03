@foreach($users as $profile)
    <div style="max-width: 400px; margin: 20px auto; border: 1px solid #ddd; padding: 20px; text-align: center; font-family: Arial, sans-serif; border-radius: 10px;">
        <a href="{!! $profile->permalink() !!}" style="text-decoration: none; color: #333;">
            <img src="{{ $profile->avatarUrl() }}" alt="Avatar - {{$profile->username }} " style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 10px;">
            <h2 style="margin: 0; font-size: 20px;">{{  '@'.$profile->username }}</h2>
            <div style="display: flex; justify-content: center; align-items: center; margin-top: 10px; text-align: center;">
                <div style="text-align: center; margin: 0 auto;">
                    <strong style="display: block; font-size: 16px;">{{ $profile->statusCount() }}</strong>
                    <span style="font-size: 12px; color: #666;">Publicações</span>
                </div>
                <div style="text-align: center; margin: 0 auto;">
                    <strong style="display: block; font-size: 16px;">{{ $profile->followerCount(true) }}</strong>
                    <span style="font-size: 12px; color: #666;">Seguidores</span>
                </div>
                <div style="text-align: center; margin: 0 auto;">
                    <strong style="display: block; font-size: 16px;">{{ $profile->followingCount(true)}}</strong>
                    <span style="font-size: 12px; color: #666;">Seguindo</span>
                </div>
            </div>
        </a>
    </div>
@endforeach
