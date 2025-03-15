<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse;">
                <tr>
                    <td align="center" style="padding: 10px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse;">
                            @foreach ($posts->chunk(3) as $chunk)
                                <tr>
                                    <td align="center" style="display: flex; flex-wrap: wrap; justify-content: center;">
                                        @foreach ($chunk as $photo)
                                            <a href="{!! $photo->url() !!}" style="display: inline-block; position: relative; margin: 5px;">
                                                <img src="{{ $photo->mediaUrl() }}" alt="{{ $photo->firstMedia()->caption }}" style="display: block; height: 180px; width: 180px; aspect-ratio: 1/1; object-fit: cover; border-radius: 5px;" />
                                            </a>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
