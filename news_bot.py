import html
import os
import re
import datetime
from pathlib import Path
from zoneinfo import ZoneInfo
import google.generativeai as genai

CONFIG_FILE = Path(__file__).with_name("mine_config.php")

def _load_key_from_config():
    if not CONFIG_FILE.exists():
        return None
    try:
        content = CONFIG_FILE.read_text(encoding="utf-8")
    except Exception:
        return None
    match = re.search(r"define\(\s*['\"]GEMINI_API_KEY['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)", content)
    if match:
        candidate = match.group(1).strip()
        if candidate and candidate != "your_actual_api_key_here":
            return candidate
    return None

API_KEY = os.getenv("GEMINI_API_KEY") or _load_key_from_config()

lisbon_tz = ZoneInfo("Europe/Lisbon")
now_in_lisbon = datetime.datetime.now(lisbon_tz)
current_date = now_in_lisbon.strftime("%B %d, %Y")
current_timestamp = now_in_lisbon.strftime("%Y-%m-%d %H:%M %Z")

def build_model():
    genai.configure(api_key=API_KEY)
    return genai.GenerativeModel(
        model_name="gemini-1.5-flash",
        tools=[{"google_search_retrieval": {}}],
    )

def safe_response_text(response):
    candidate_text = getattr(response, "text", None)
    if candidate_text:
        return candidate_text
    candidates = getattr(response, "candidates", None)
    if candidates:
        first = candidates[0]
        return getattr(first, "content", None) or getattr(first, "text", None)
    return ""

def format_output(body_html, status_statement):
    page = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Global News</title>
    <style>
        body {{ font-family: "Segoe UI", system-ui, sans-serif; background: #f3f5f9; margin: 0; padding: 0; }}
        .wrapper {{ max-width: 960px; margin: 0 auto; padding: 48px 24px; }}
        .card {{ background: white; border-radius: 16px; padding: 32px; box-shadow: 0 12px 36px rgba(15, 23, 42, 0.12); }}
        h1 {{ margin-bottom: 12px; color: #1e293b; }}
        p {{ color: #475569; line-height: 1.7; }}
        .meta {{ margin-top: 32px; font-size: 0.9rem; color: #0f172a; }}
        .status {{ color: #1d4ed8; font-weight: 600; margin-top: 6px; }}
        a {{ color: #2563eb; }}
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1>Global News Digest</h1>
            <div class="status">{status_statement}</div>
            <div class="meta">Summary prepared for Lisbon on {current_date} · {current_timestamp}</div>
            <div class="content">{body_html}</div>
        </div>
    </div>
</body>
</html>
"""
    return page

def write_page(content):
    with open("global_news.html", "w", encoding="utf-8") as f:
        f.write(content)

def fetch_news():
    if not API_KEY:
        reason = "Missing GEMINI_API_KEY environment variable."
        print("ERROR:", reason)
        write_page(format_output(
            "<p>Please set the <strong>GEMINI_API_KEY</strong> secret before running the news runner.</p>",
            "Runner paused: no API key."
        ))
        return

    model = build_model()
    prompt = (
        f"Today is {current_date}. Provide a summary of the top 5 global news "
        f"stories from the last 24 hours. Format the output into clean HTML with <h2> titles, "
        f"<p> bodies, and include source links. Keep the HTML snippet focused and free of scripts."
    )

    try:
        response = model.generate_text(prompt=prompt, temperature=0.2, top_p=0.9)
        raw_html = safe_response_text(response)
        if not raw_html:
            raw_html = "<p>No stories returned; try rerunning the bot or checking the log.</p>"
        sanitized = raw_html
        final_page = format_output(sanitized, "News generated automatically via Gemini.")
        write_page(final_page)
        print("Success: News updated on global_news.html")
    except Exception as exc:
        error_text = html.escape(str(exc))
        write_page(format_output(f"<p>Failed to generate news: {error_text}</p>", "Runner encountered an error."))
        with open("news_bot_error.log", "a", encoding="utf-8") as log:
            log.write(f"{datetime.datetime.now().isoformat()} - {exc}\n")

if __name__ == "__main__":
    fetch_news()
