# OSRG Connect — Change Log & Project Summary

Project Master Record: OSRG Connect Ecosystem

1. Core Infrastructure & Stack
- Primary Domain: connect.osrg.lol/
- Hosting: Hostinger (Manual deployment via local editing and FTP/File Manager upload).
- Database: MySQL (Relational storage for users, messages, settings, notifications, and unlocked content).
- Backend: PHP (Logic in messages.php and data serving via series_api_activity.php).
- Frontend: Tailwind CSS (via Play CDN), FontAwesome, and Vanilla JavaScript.
- Security: Multi-point reCAPTCHA protection (Main page and sensitive endpoints).

2. Content & Media Modules
- Series List (/series_index): Library management, tracking, and "AI Insights" (API key required).
- Trivia Hub (/series_trivia): 10-question quizzes (The Rookie, Arrow, NYC, Technology).
- Games Library:
  - Active Internal: Wordle, Spot the Difference, Endless Runner, Space Adventure.
  - Active External: Valley Game (osrg.lol/osrg/valley_game/).
- Integrated Tools: MineMod Archive and Quiz Generator.
- Reels: Short-form video content section.

3. Role-Based Access Control (RBAC) & Coupon System
- Admin ("OSRG"):
  - Exclusive access to the Admin Panel.
  - Coupon Generation: Can trigger a "Send Coupon" action. This sends an automated message from the "Admin" user to the recipient.
- Coupon Logic & Rewards:
  - Activation: Users input the code into a dedicated field on the Settings page.
  - Dynamic UI: Upon successful activation, a new permanent page link is appended to the end of the user's Settings page as a reward/secret area.

4. UI/UX & Navigation Map
- Header Structure: Hamburger menu (Mobile) or horizontal bar (Desktop) with persistent Notification Bell and User Avatar.
- Social Feed: Rich-text posts, emoji reactions, and 10MB media uploads with a global "Disable Notifications" toggle.
- Settings Page: Profile/Avatar edits, timezone preferences, and the Coupon input/unlock area.

5. Active Tasks & Problems ⚠️
- Navigation Architecture:
  - Linking Trivia Hub and Valley Game to the Games Page.
  - Adding a direct link from Series List back to the Main Page.
- File Cleanup: series_friends.php is flagged for deletion.
- Auth 401 Error: Standalone pages (Quiz, MineMod) fail to authenticate with series_api_activity.php.
- Empty Notification Data: Bell UI is present on satellite pages but fails to display database rows.
- Console Noise: Tailwind CDN production warnings and JS polling loop errors.

---

## Change Log
- 2026-02-11T21:29:50Z — Deleted file: series_friends.php (per user request: file cleanup). Created this change log to track edits by the assistant.
- 2026-02-11T21:37:00Z — Added header link: https://osrg.lol/wiki/Global_News_Today (file: header.php).
- 2026-02-11T21:50:00Z — Added coupon generator UI and send action to admin.php (creates coupons table and sends coupon message to recipient).
- 2026-02-11T21:50:00Z — Added coupon activation handling and UI to settings.php; upon activation users get a permanent secret link. Created secret_reward.php.
- 2026-02-11T22:19:00Z — Fixed notification API authentication and DB connection in series_api_activity.php (accepts session user_id or legacy flag; robustly locates get_db/getDB).
- 2026-02-11T22:48:00Z — Reduced console noise and improved fetch resilience: wrapped Tailwind Play CDN inclusion to suppress banner logs and temporary console output in series_header.php, admin.php, mine_index.php, mine_setup.php, quiz_Quizz_Generator.html, series_privacy.php, series_register.php, series_setup_database.php, series_find_db_credentials.php, series_trivia.php. Also updated header.php and admin.php to handle non-OK fetch responses and avoid noisy console.error messages during polling.

**Location:** C:\Users\omers\Documents\Local Sites\OSRG Connect\osrg_connect_change_log.md

## Notes
- This file will be edited and appended after any further edits made by the assistant to keep a running record of actions and changes.
- 2026-02-11T22:19:00Z — Fixed notification API auth to accept session user_id so header notifications return activities (file: series_api_activity.php).
- 2026-02-11T22:48:00Z — Suppressed Tailwind Play CDN banner logs and reduced console noise during repeated polling fetches by making responses resilient and suppressing non-critical console.error output.
- 2026-02-13T11:26:46Z — Fixed login failure: ensured DB helper is included before remember-token checks and added cookie-consent handling to disable sessions/cookies when users decline; updated login.php and register.php to add a cookie consent banner and to respect the user's choice (remember-me cookie disabled when declined). Files changed: config.php, login.php, register.php.
- 2026-02-13T11:30:00Z — Fixed cookie consent default which unintentionally defaulted to 'declined' on form submit causing sessions to be disabled; changed default to 'accepted' so submitting login/register without explicit decline allows normal login flow. Files changed: login.php, register.php, osrg_connect_change_log.md.
- 2026-02-13T12:03:51Z — Added debug logging to login.php to record cookie_consent, session status, and DB lookup result to diagnose login failures; logs written to login_debug.log. Files changed: login.php.
- 2026-02-13T12:15:00Z — Prevented reCAPTCHA client interception on connect.osrg.lol by only including reCAPTCHA script and submit-hook on true production hosts; this prevents grecaptcha from blocking form submission when unavailable. Files changed: login.php, osrg_connect_change_log.md.
- 2026-02-13T11:51:24Z — Fixed JS TypeError on login/register: cookie banner event listeners could run after the banner was removed, causing "Cannot read properties of null". Added guards checking elements exist before calling addEventListener in login.php and register.php.
