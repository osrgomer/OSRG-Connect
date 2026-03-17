OSRG Connect — Change Log & Project Summary
Project Master Record: OSRG Connect Ecosystem

Core Infrastructure & Stack

Primary Domain: connect.osrg.lol/

Hosting: Hostinger (Manual deployment via local editing and FTP/File Manager upload).

Database: MySQL (Relational storage for users, messages, settings, notifications, and unlocked content).

Backend: PHP (Logic in messages.php and data serving via series_api_activity.php).

Frontend: Tailwind CSS (via Play CDN), FontAwesome, and Vanilla JavaScript.

Security: Multi-point reCAPTCHA protection (Main page and sensitive endpoints).

Content & Media Modules

Series List (/series_index): Library management, tracking, and "AI Insights" (API key required).

Trivia Hub (/series_trivia): 10-question quizzes (The Rookie, Arrow, NYC, Technology).

Games Library:

Active Internal: Wordle, Spot the Difference, Endless Runner, Space Adventure.

Active External: Valley Game (osrg.lol/osrg/valley_game/).

Integrated Tools: MineMod Archive and Quiz Generator.

Reels: Short-form video content section.

Role-Based Access Control (RBAC) & Coupon System

Admin ("OSRG"):

Exclusive access to the Admin Panel.

Coupon Generation: Can trigger a "Send Coupon" action. This sends an automated message from the "Admin" user to the recipient.

Coupon Logic & Rewards:

Activation: Users input the code into a dedicated field on the Settings page.

Dynamic UI: Upon successful activation, a new permanent page link is appended to the end of the user's Settings page as a reward/secret area.

UI/UX & Navigation Map

Header Structure: Hamburger menu (Mobile) or horizontal bar (Desktop) with persistent Notification Bell and User Avatar.

Social Feed: Rich-text posts, emoji reactions, and 10MB media uploads with a global "Disable Notifications" toggle.

Settings Page: Profile/Avatar edits, timezone preferences, and the Coupon input/unlock area.

Active Tasks & Problems ⚠️

Navigation Architecture:

Linking Trivia Hub and Valley Game to the Games Page.

Adding a direct link from Series List back to the Main Page.

Auth 401 Error: Standalone pages (Quiz, MineMod) fail to authenticate with series_api_activity.php.

Empty Notification Data: Bell UI is present on satellite pages but fails to display database rows.

Console Noise: Tailwind CDN production warnings and JS polling loop errors.

Change Log
2026-03-17 — Created activity log to track actions on demand; added pending-user reject handler in admin.php to allow admins to delete unapproved accounts and notify users via email.

2026-02-17T23:21:40Z — Hardened admin delete flow to avoid HTTP 500 by validating delete IDs, using transactions where possible, and catching exceptions (file: admin.php).

2026-02-17T23:21:40Z — Fixed God Mode exit endpoint mismatch: series_header now calls series_api_admin.php?action=exit_impersonate so exit works correctly (file: series_header.php).

2026-02-17T22:57:57Z — Resolved missing session user_id in series API by mapping session user_email to DB id so users can load/save library entries (file: series_api_series.php).

2026-02-17T22:57:57Z — Added media_type column support and 'Movie' option in UI; series_api_series.php now persists media_type and series_index.php includes a media-type selector (files: series_api_series.php, series_index.php).

2026-02-17T22:57:57Z — Removed duplicate legacy series_friends references and updated header links to use canonical friends.php; removed the 'Back to Main Page' link from series_index (files: series_header.php, series_index.php, series_restore_users.php, series_check_server_file.php, series_debug_header.php).

2026-02-17T22:57:57Z — Fixed online status update flow: series_api_set_status.php now resolves legacy session data, returns clearer messages, and header JS handles success/failure reliably (files: series_api_set_status.php, series_header.php).

2026-02-17T22:57:57Z — Sanitized avatars in notifications to avoid attempting to load malformed emoji URLs and added img onerror fallback to ui-avatars to prevent 404 noise (file: series_header_nav.php).

2026-02-15T15:50:52Z — Ensured coupons table has expires_at column (ALTER if missing) and set coupon expiry to 12 hours; updated admin.php and settings.php.

2026-02-14T15:40:00Z — init_db() now ensures users table exists and will create it if missing, enabling default admin creation (file: config.php).

2026-02-14T15:20:00Z — init_db() now creates a default admin user 'admin@connect.osrg.lol' with a random password written to login_debug.log when the users table is empty (file: config.php).

2026-02-14T14:58:08Z — Removed 'localhost' from login local-detection; treating connect.osrg.lol as production and ensuring reCAPTCHA is applied; updated login.php.

2026-02-13T12:24:33Z — Added on-page debug output and server temp logging for login attempts to capture cookie consent, session status, and user lookup result; debug displayed on the login page when present and also written to system temp at osrg_login_debug.log. Files changed: login.php, osrg_connect_change_log.md.

2026-02-13T12:15:00Z — Prevented reCAPTCHA client interception on connect.osrg.lol by only including reCAPTCHA script and submit-hook on true production hosts; this prevents grecaptcha from blocking form submission when unavailable. Files changed: login.php, osrg_connect_change_log.md.

2026-02-13T12:03:51Z — Added debug logging to login.php to record cookie_consent, session status, and DB lookup result to diagnose login failures; logs written to login_debug.log. Files changed: login.php.

2026-02-13T11:51:24Z — Fixed JS TypeError on login/register: cookie banner event listeners could run after the banner was removed, causing "Cannot read properties of null". Added guards checking elements exist before calling addEventListener in login.php and register.php.

2026-02-13T11:30:00Z — Fixed cookie consent default which unintentionally defaulted to 'declined' on form submit causing sessions to be disabled; changed default to 'accepted' so submitting login/register without explicit decline allows normal login flow. Files changed: login.php, register.php, osrg_connect_change_log.md.

2026-02-13T11:26:46Z — Fixed login failure: ensured DB helper is included before remember-token checks and added cookie-consent handling to disable sessions/cookies when users decline; updated login.php and register.php to add a cookie consent banner and to respect the user's choice (remember-me cookie disabled when declined). Files changed: config.php, login.php, register.php.

2026-02-11T22:48:00Z — Reduced console noise and improved fetch resilience: wrapped Tailwind Play CDN inclusion to suppress banner logs and temporary console output in series_header.php, admin.php, mine_index.php, mine_setup.php, quiz_Quizz_Generator.html, series_privacy.php, series_register.php, series_setup_database.php, series_find_db_credentials.php, series_trivia.php. Also updated header.php and admin.php to handle non-OK fetch responses and avoid noisy console.error messages during polling.

2026-02-11T22:19:00Z — Fixed notification API authentication and DB connection in series_api_activity.php (accepts session user_id or legacy flag; robustly locates get_db/getDB).

2026-02-11T21:50:00Z — Added coupon activation handling and UI to settings.php; upon activation users get a permanent secret link. Created secret_reward.php.

2026-02-11T21:50:00Z — Added coupon generator UI and send action to admin.php (creates coupons table and sends coupon message to recipient).

2026-02-11T21:37:00Z — Added header link: https://osrg.lol/wiki/Global_News_Today (file: header.php).

2026-02-11T21:29:50Z — Deleted file: series_friends.php (per user request: file cleanup). Created this change log to track edits by the assistant.

Location: C:\Users\omers\Documents\Local Sites\OSRG Connect\osrg_connect_change_log.md

Notes
This file will be edited and appended after any further edits made by the assistant to keep a running record of actions and changes.

2026-03-17 — Implemented reject/delete functionality for pending users in admin.php to improve security and user management.

2026-03-18 — Updated the notification bell so message and friend-request events show action links and route directly to the chat or friend-approval area (files: header.php, series_api_activity.php, users.php, index.php).

2026-03-19 — Pointed the navigation's Global News button to a local `global_news.html`, added the page placeholder, and reworked `news_bot.py` plus `run_news.yml` so the Gemini-powered runner writes that digest while respecting the shared `mine_config.php` API key (files: header.php, global_news.html, news_bot.py, .github/workflows/run_news.yml).

2026-03-19T20:31 — Ensured the news runner workflow runs every day at 08:00 Lisbon time by annotating the scheduled cron job with the proper timezone (file: .github/workflows/run_news.yml).

2026-03-17T20:40 — Added a helper section on `global_news.html` so visitors can locally store their preferred API provider/key (Gemini, ChatGPT, Grok, Anthropic, etc.) even though the runner still uses the server-side secret (file: global_news.html).

2026-02-17T22:57:57Z — Streamlined navigation by removing redundant 'friends' references and prioritizing canonical links.
