# OAuth Setup Guide

To enable real OAuth connections, you need to set up applications with each provider and update the credentials in `oauth.php`.

## 1. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Go to "APIs & Services" → "Credentials"
4. Click "Create Credentials" → "OAuth 2.0 Client IDs"
5. Set application type to "Web application"
6. Add authorized redirect URI: `http://localhost/serieslist/oauth.php?provider=google`
7. Copy the Client ID and Client Secret

## 2. GitHub OAuth Setup

1. Go to [GitHub Settings](https://github.com/settings/developers)
2. Click "OAuth Apps" → "New OAuth App"
3. Fill in:
   - Application name: `SeriesList`
   - Homepage URL: `http://localhost/serieslist`
   - Authorization callback URL: `http://localhost/serieslist/oauth.php?provider=github`
4. Click "Register application"
5. Copy the Client ID and generate a Client Secret

## 3. Spotify OAuth Setup

1. Go to [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
2. Create a new app
3. In app settings, add redirect URI: `http://localhost/serieslist/oauth.php?provider=spotify`
4. Copy the Client ID and Client Secret

## 4. Update oauth.php

Replace the placeholder values in `oauth.php`:

```php
'google' => [
    'client_id' => 'YOUR_ACTUAL_GOOGLE_CLIENT_ID',
    'client_secret' => 'YOUR_ACTUAL_GOOGLE_CLIENT_SECRET',
    // ... rest stays the same
],
'github' => [
    'client_id' => 'YOUR_ACTUAL_GITHUB_CLIENT_ID', 
    'client_secret' => 'YOUR_ACTUAL_GITHUB_CLIENT_SECRET',
    // ... rest stays the same
],
'spotify' => [
    'client_id' => 'YOUR_ACTUAL_SPOTIFY_CLIENT_ID',
    'client_secret' => 'YOUR_ACTUAL_SPOTIFY_CLIENT_SECRET', 
    // ... rest stays the same
]
```

## 5. Update Redirect URIs for Production

When deploying to production, update all redirect URIs in both:
- The OAuth provider settings (Google/Twitter/Spotify dashboards)
- The `oauth.php` file redirect_uri values

Change from:
`http://localhost/serieslist/oauth.php?provider=PROVIDER`

To your actual domain:
`https://yourdomain.com/oauth.php?provider=PROVIDER`

## Security Notes

- Keep your client secrets secure and never commit them to version control
- Consider using environment variables for production credentials
- Enable HTTPS in production for secure OAuth flows
- Regularly rotate your OAuth credentials