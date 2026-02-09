<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$page_title = 'Games - OSRG Connect';
$additional_css = '
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .games-header { 
        background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95)); 
        backdrop-filter: blur(20px); 
        padding: 40px; 
        border-radius: 20px; 
        margin-bottom: 30px; 
        box-shadow: 0 20px 60px rgba(0,0,0,0.15); 
        text-align: center; 
        border: 1px solid rgba(255,255,255,0.3);
    }
    .games-title {
        font-size: 2.5em;
        background: linear-gradient(135deg, #1877f2, #42a5f5);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 15px;
        font-weight: 700;
    }
    .games-subtitle {
        color: #666;
        font-size: 18px;
        margin-bottom: 20px;
    }
    .games-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    .game-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95));
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.3);
        transition: all 0.3s ease;
        text-align: center;
    }
    .game-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 60px rgba(0,0,0,0.2);
    }
    .game-icon {
        font-size: 4em;
        margin-bottom: 20px;
        display: block;
    }
    .game-title {
        font-size: 1.5em;
        font-weight: 700;
        color: #1877f2;
        margin-bottom: 15px;
    }
    .game-description {
        color: #666;
        line-height: 1.6;
        margin-bottom: 20px;
    }
    .play-button {
        background: linear-gradient(135deg, #1877f2, #42a5f5);
        color: white;
        padding: 12px 30px;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        display: inline-block;
        box-shadow: 0 8px 25px rgba(24,119,242,0.3);
        transition: all 0.3s ease;
    }
    .play-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(24,119,242,0.4);
        color: white;
    }
    .coming-soon {
        background: linear-gradient(135deg, #ff6b6b, #ffa726);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 15px;
    }
    @media (max-width: 768px) {
        .container { padding: 15px; }
        .games-header { padding: 25px 20px; }
        .games-title { font-size: 2em; }
        .games-grid { grid-template-columns: 1fr; gap: 20px; }
        .game-card { padding: 25px 20px; }
    }
';

require_once 'header.php';
?>

<div class="container">
        <div class="games-header">
        <h1 class="games-title">🎮 Games</h1>
        <p class="games-subtitle">Play fun games and compete with friends!</p>
    </div>

<div class="games-grid">
    <div class="game-card">
        <span class="game-icon">📝</span>
        <h3 class="game-title">Wordle</h3>
        <p class="game-description">Guess the 5-letter word in 6 tries! A daily word puzzle challenge.</p>
        <a href="wordle.php" class="play-button">Play Now</a>
    </div>
    
    <div class="game-card">
        <span class="game-icon">🔍</span>
        <h3 class="game-title">Spot the Difference</h3>
        <p class="game-description">Find all the hidden differences in this visual puzzle game.</p>
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="spot_difference.php" class="play-button" style="flex: 1; min-width: 120px;">Play Solo</a>
            <a href="multiplayer_spot.php" class="play-button" style="flex: 1; min-width: 120px;">Multiplayer</a>
        </div>
    </div>
    
    <div class="game-card">
        <span class="game-icon">🏃</span>
        <h3 class="game-title">Endless Runner</h3>
        <p class="game-description">Run as far as you can while avoiding obstacles and collecting coins!</p>
        <a href="endless_runner.php" class="play-button">Play Now</a>
    </div>
    
    <div class="game-card">
        <div class="coming-soon">Coming Soon</div>
        <span class="game-icon">🎲</span>
        <h3 class="game-title">Dice Games</h3>
        <p class="game-description">Roll the dice and try your luck in various dice-based games.</p>
        <a href="#" class="play-button" onclick="alert('Coming soon!')">Play Now</a>
    </div>
    
    <div class="game-card">
        <div class="coming-soon">Coming Soon</div>
        <span class="game-icon">🃏</span>
        <h3 class="game-title">Card Games</h3>
        <p class="game-description">Play classic card games like Solitaire, Poker, and more!</p>
        <a href="#" class="play-button" onclick="alert('Coming soon!')">Play Now</a>
    </div>
    
    <div class="game-card">
        <span class="game-icon">💎</span>
        <h3 class="game-title">MineMod Archive</h3>
        <p class="game-description">Advanced modding intelligence and Void Sage AI for Minecraft enthusiasts.</p>
        <a href="../minemod/" class="play-button">Open Archive</a>
    </div>

    <div class="game-card">
        <span class="game-icon">❓</span>
        <h3 class="game-title">Quiz Generator</h3>
        <p class="game-description">Challenge yourself with custom generated quizzes on any topic!</p>
        <a href="../quiz/Quizz_Generator.html" class="play-button">Start Quiz</a>
    </div>

    <div class="game-card">
        <span class="game-icon">🚀</span>
        <h3 class="game-title">Space Adventure</h3>
        <p class="game-description">Explore the galaxy and battle aliens in this space shooter game.</p>
        <a href="space_adventure.php" class="play-button">Play Now</a>
    </div>
    </div>
</div>

</body>
</html>
