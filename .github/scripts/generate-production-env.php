<?php

declare(strict_types=1);

$fixed = [
    'APP_NAME' => 'Pixelfed Brasil', 'APP_ENV' => getenv('DEPLOY_APP_ENV'), 'APP_DEBUG' => 'false',
    'APP_LOCALE' => 'pt', 'APP_FALLBACK_LOCALE' => 'pt', 'APP_URL' => 'https://'.getenv('DOMAIN'),
    'APP_DOMAIN' => getenv('DOMAIN'), 'ADMIN_DOMAIN' => getenv('ADMIN_DOMAIN'), 'SESSION_DOMAIN' => getenv('DOMAIN'),
    'TRUST_PROXIES' => '*', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '5432',
    'REDIS_HOST' => '127.0.0.1', 'REDIS_PORT' => '6379', 'REDIS_PASSWORD' => 'null',
    'MAIL_DRIVER' => 'smtp', 'MAIL_MAILER' => 'smtp', 'MAIL_PORT' => '587', 'MAIL_ENCRYPTION' => 'tls',
    'MAIL_FROM_ADDRESS' => 'noreply@pixelfed.com.br', 'MAIL_FROM_NAME' => 'Pixelfed Brasil',
    'OPEN_REGISTRATION' => 'true', 'PF_ALLOW_APP_REGISTRATION' => 'false', 'ENFORCE_EMAIL_VERIFICATION' => 'true',
    'PF_MAX_USERS' => '5000', 'OAUTH_ENABLED' => 'true', 'APP_REGISTER' => 'true', 'PF_USER_INVITES' => 'false',
    'INSTANCE_PUBLIC_HASHTAGS' => 'false', 'INSTANCE_DISCOVER_PUBLIC' => 'true',
    'INSTANCE_DESCRIPTION' => 'Pixelfed é uma plataforma de compartilhamento de imagens, uma alternativa ética às plataformas centralizadas.',
    'INSTANCE_PUBLIC_LOCAL_TIMELINE' => 'true', 'INSTANCE_PUBLIC_TIMELINE_CACHED' => 'false',
    'PF_NETWORK_TIMELINE' => 'true', 'INSTANCE_NETWORK_TIMELINE_CACHED' => 'false', 'ENABLE_COVID_LABEL' => 'false',
    'INSTANCE_CONTACT_FORM' => 'true', 'INSTANCE_CONTACT_EMAIL' => 'suporte@felipemateus.com',
    'INSTANCE_NOTIFY_AUTO_GC' => 'true', 'REMOTE_AVATARS' => 'false', 'PF_ENABLE_GEOLOCATION' => 'false',
    'ACCOUNT_DELETE_AFTER' => '30', 'PF_OPTIMIZE_IMAGES' => 'false', 'PF_OPTIMIZE_VIDEOS' => 'false',
    'INSTANCE_SHOW_PEERS' => 'true', 'INSTANCE_POLLS' => 'true', 'ACTIVITY_PUB' => 'true',
    'ACTIVITYPUB_DELIVERY_CONCURRENCY' => '100', 'AP_REMOTE_FOLLOW' => 'true', 'AP_INBOX' => 'true',
    'AP_OUTBOX' => 'true', 'AP_SHAREDINBOX' => 'true', 'AP_ALLOW_SHARE_ALL' => 'true',
    'INSTANCE_DISCOVER_BEAGLE_API' => 'true', 'INSTANCE_LANDING_SHOW_EXPLORE' => 'false',
    'INSTANCE_LANDING_SHOW_DIRECTORY' => 'false', 'ACCOUNT_DELETION' => 'true', 'GROUPS_ENABLED' => 'true',
    'PF_LOGIN_WITH_MASTODON_ENABLED' => 'false', 'PF_LOGIN_WITH_MASTODON_ENABLED_SKIP_CLOSED' => 'true',
    'PF_LOGIN_WITH_MASTODON_DOMAINS' => 'mastodon.com.br,masto.donte.com.br,mastodon.social,mastodon.online,mstdn.social,mas.to',
    'PF_HIDE_NSFW_ON_PUBLIC_FEEDS' => 'true', 'PF_HIDE_REMOTE_INSTANCE' => 'true',
    'CAPTCHA_ENABLED' => 'true', 'CAPTCHA_ENABLED_ON_LOGIN' => 'true', 'CAPTCHA_ENABLED_ON_REGISTER' => 'true',
    'PF_ENABLE_CLOUD' => 'true', 'FILESYSTEM_CLOUD' => 'spaces',
    'PORTFOLIO_DOMAIN' => 'portifolio.'.getenv('DOMAIN'), 'PORTFOLIO_PATH' => '',
    'TRANSLATION_ENABLED' => 'true', 'PF_LIMIT_DAILY_POSTS_ENABLED' => 'true',
    'PF_LIMIT_DAILY_POSTS_LIMIT' => '6', 'PF_LIMIT_DAILY_POSTS_USER_EXCEPTIONS' => 'false',
    'PF_DEFAULT_NO_AUTOLINK' => 'false',
];

$environment = [
    'APP_KEY', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD',
    'HORIZON_MEMORY_LIMIT', 'HORIZON_MAX_PROCESSES', 'CAPTCHA_SECRET', 'CAPTCHA_SITEKEY', 'BANNED_USERNAMES',
    'DANGEROUSLY_SET_FILESYSTEM_DRIVER', 'DO_SPACES_KEY', 'DO_SPACES_SECRET', 'DO_SPACES_REGION',
    'DO_SPACES_BUCKET', 'DO_SPACES_ENDPOINT', 'AWS_URL', 'R2_ACCESS_KEY_ID', 'R2_SECRET_ACCESS_KEY',
    'R2_BUCKET', 'R2_ENDPOINT', 'FELIPEMATEUS_SENDPORTAL_TOKEN', 'TRANSLATION_PROVIDER', 'GOOGLE_API_KEY',
    'DEEPL_API_KEY', 'PF_IMPORT_FROM_INSTAGRAM', 'VAPID_PUBLIC_KEY', 'VAPID_PRIVATE_KEY',
];
foreach ($environment as $name) {
    $value = getenv($name);
    if ($value === false || $value === '') {
        fwrite(STDERR, "Missing required deploy variable: {$name}\n");
        exit(1);
    }
    $fixed[$name] = $value;
}

$quote = static fn (string $value): string => '"'.str_replace(["\\", '"', "\r", "\n"], ['\\\\', '\\"', '\\r', '\\n'], $value).'"';
$output = [];
foreach ($fixed as $name => $value) {
    $output[] = $name.'='.$quote((string) $value);
}
file_put_contents('.env', implode(PHP_EOL, $output).PHP_EOL, LOCK_EX);
