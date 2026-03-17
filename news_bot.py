import os
import datetime
from zoneinfo import ZoneInfo
import google.generativeai as genai

# 1. Setup Gemini API
# On Hostinger, it's easiest to paste your key here directly 
# or use an environment variable if you know how to set them in hPanel.
API_KEY = "YOUR_GEMINI_API_KEY_HERE" 

genai.configure(api_key=API_KEY)

# 2. Get current date in Lisbon
lisbon_tz = ZoneInfo("Europe/Lisbon")
now_in_lisbon = datetime.datetime.now(lisbon_tz)
current_date = now_in_lisbon.strftime("%B %d, %Y")

# 3. Initialize Gemini
model = genai.GenerativeModel(
    model_name="gemini-1.5-flash",
    tools=[{"google_search_retrieval": {}}]
)

def fetch_news():
    prompt = (
        f"Today is {current_date}. Provide a summary of the top 5 global news "
        f"stories from the last 24 hours. Format the output in clean HTML "
        f"using <h1>, <h2>, and <p> tags. Include source links."
    )
    
    try:
        response = model.generate_content(prompt)
        
        # Create a simple HTML page
        html_content = f"""
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Real-Time News - Lisbon</title>
            <style>
                body {{ font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 40px auto; padding: 20px; background: #f4f4f4; }}
                .container {{ background: white; padding: 30px; border-radius: 8px; shadow: 0 2px 5px rgba(0,0,0,0.1); }}
                h1 {{ color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }}
                a {{ color: #007bff; text-decoration: none; }}
            </style>
        </head>
        <body>
            <div class="container">
                <p><em>Last updated: {current_date} at 08:00 (Lisbon Time)</em></p>
                {response.text}
            </div>
        </body>
        </html>
        """
        
        # Save as index.html so it shows up at connect.osrg.lol
        with open("index.html", "w", encoding="utf-8") as f:
            f.write(html_content)
        print("Success: News updated on website.")
            
    except Exception as e:
        with open("error.log", "a") as f:
            f.write(f"{datetime.datetime.now()}: {str(e)}\n")

if __name__ == "__main__":
    fetch_news()