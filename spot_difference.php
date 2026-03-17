<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$page_title = 'Spot the Difference - OSRG Connect';
$additional_css = '
    body {
        margin: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        font-family: Arial, sans-serif;
        overflow-x: hidden;
        padding-top: 80px;
    }
    .game-wrapper {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 40px;
        margin: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        text-align: center;
        max-width: 900px;
        width: calc(100% - 40px);
        box-sizing: border-box;
    }
    .back-button {
        background: linear-gradient(135deg, #1877f2, #42a5f5);
        color: white;
        padding: 12px 30px;
        text-decoration: none;
        border-radius: 25px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(24,119,242,0.3);
        transition: all 0.3s ease;
        font-family: Arial, sans-serif;
        font-size: 14px;
    }
    .back-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(24,119,242,0.4);
        color: white;
    }
    h1 {
        color: #1877f2;
        margin-bottom: 30px;
        font-size: 2.5em;
        font-weight: 700;
    }
    .game-container {
        text-align: center;
        width: 100%;
        position: relative;
        margin: 0 auto;
    }
    .game-images {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    .game-image {
        position: relative;
        width: 400px;
        height: 300px;
        border: 3px solid #1877f2;
        border-radius: 12px;
        overflow: hidden;
    }
    .game-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: white;
    }
    #gameCanvas1, #gameCanvas2 {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        cursor: crosshair;
        background: transparent;
    }
    #score {
        position: absolute;
        top: 15px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 24px;
        color: #1877f2;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(255,255,255,0.8);
        z-index: 10;
    }
    .instructions {
        background: rgba(24,119,242,0.1);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        color: #333;
        line-height: 1.6;
        font-family: Arial, sans-serif;
        font-size: 14px;
    }
    .game-over {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.9);
        color: white;
        padding: 30px;
        border-radius: 15px;
        text-align: center;
        z-index: 100;
        display: none;
        border: 2px solid #1877f2;
    }
    .restart-btn {
        background: linear-gradient(135deg, #1877f2, #42a5f5);
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        margin-top: 15px;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    .restart-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(24,119,242,0.4);
    }
    #newGameBtn {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        margin-top: 20px;
        font-size: 16px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    #newGameBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    @media (max-width: 768px) {
        body { padding-top: 60px; }
        .game-wrapper { padding: 25px 20px; margin: 15px; }
        h1 { font-size: 1.8em; margin-bottom: 20px; }
        #gameCanvas1, #gameCanvas2 { height: 300px; max-width: 100%; }
        #score { font-size: 20px; }
        .instructions { font-size: 12px; padding: 15px; }
    }
    @media (max-width: 480px) {
        .game-wrapper { padding: 20px 15px; }
        h1 { font-size: 1.5em; }
        #gameCanvas1, #gameCanvas2 { height: 250px; }
        #score { font-size: 18px; }
    }
';

require_once 'header.php';
?>

<div class="game-wrapper">
    <a href="games.php" class="back-button">← Back to Games</a>
    
    <h1>🔍 Spot the Difference</h1>
    
    <div class="instructions">
        <strong>How to Play:</strong><br>
        • Look carefully at both images and find the 5 differences between them<br>
        • Click directly on a difference in either image to mark it as found<br>
        • Find all 5 differences to win the game<br>
        • Each difference gives you 10 points
    </div>
    
    <div class="game-container">
        <div class="game-images">
            <div class="game-image">
                <img src="sp_assets/spot_the_difference/scene1.svg" id="image1" alt="Spot the Difference Image 1">
                <canvas id="gameCanvas1" width="400" height="300"></canvas>
            </div>
            <div class="game-image">
                <img src="sp_assets/spot_the_difference/scene2.svg" id="image2" alt="Spot the Difference Image 2">
                <canvas id="gameCanvas2" width="400" height="300"></canvas>
            </div>
        </div>
        <div id="score">Score: 0 / 5</div>
        <button id="newGameBtn" onclick="newGame()">🎮 New Game</button>
        <div class="game-over" id="gameOver">
            <h2>🎉 Congratulations!</h2>
            <p id="finalScore">You found all differences!</p>
            <button class="restart-btn" onclick="newGame()">Play Again</button>
        </div>
    </div>
</div>

<script>
const canvas1 = document.getElementById('gameCanvas1');
const canvas2 = document.getElementById('gameCanvas2');
const ctx1 = canvas1.getContext('2d');
const ctx2 = canvas2.getContext('2d');
const scoreDisplay = document.getElementById('score');
const gameOverDiv = document.getElementById('gameOver');

let differences = [];
let score = 0;
let gameRunning = true;

// Hardcoded actual difference positions matching the two SVG images.
// x1 = position in scene1, x2 = position in scene2, y is the same on both.
// Differences:
//   1. Cloud 2 shifted right in scene2
//   2. Beach ball shifted right in scene2
//   3. Extra palm-tree leaf added in scene2
//   4. Extra bird added in scene2
//   5. Fish added in scene2's ocean
function getDifferences() {
    return [
        { name: 'Sun color change', x1: 50, x2: 50, y: 50, radius: 30, found: false },
        { name: 'Cloud moved', x1: 290, x2: 300, y: 35, radius: 25, found: false },
        { name: 'Beach ball moved', x1: 100, x2: 120, y: 250, radius: 25, found: false },
        { name: 'Extra palm leaf', x1: 290, x2: 290, y: 147, radius: 25, found: false },
        { name: 'Added bird', x1: 200, x2: 200, y: 60, radius: 25, found: false }
    ];
    return differences;
}

function drawGame() {
    ctx1.clearRect(0, 0, canvas1.width, canvas1.height);
    ctx2.clearRect(0, 0, canvas2.width, canvas2.height);

    differences.forEach((diff) => {
        if (diff.found) {
            // Draw green confirmation circle on both canvases
            [{ ctx: ctx1, x: diff.x1 }, { ctx: ctx2, x: diff.x2 }].forEach(({ ctx, x }) => {
                ctx.save();
                ctx.fillStyle = 'rgba(40, 167, 69, 0.35)';
                ctx.strokeStyle = '#28a745';
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.arc(x, diff.y, diff.radius, 0, Math.PI * 2);
                ctx.fill();
                ctx.stroke();

                // Checkmark
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.moveTo(x - 8, diff.y);
                ctx.lineTo(x - 2, diff.y + 6);
                ctx.lineTo(x + 8, diff.y - 6);
                ctx.stroke();
                ctx.restore();
            });
        }
        // Unfound differences are intentionally invisible – players must spot them naturally.
    });
}

function checkClick(mouseX, mouseY, isFirstCanvas) {
    if (!gameRunning) return;

    differences.forEach((diff) => {
        if (!diff.found) {
            const x  = isFirstCanvas ? diff.x1 : diff.x2;
            const dx = mouseX - x;
            const dy = mouseY - diff.y;
            if (Math.sqrt(dx * dx + dy * dy) < diff.radius) {
                diff.found = true;
                score++;
                scoreDisplay.textContent = `Score: ${score} / 5`;

                if (score === 5) {
                    gameRunning = false;
                    setTimeout(() => { gameOverDiv.style.display = 'block'; }, 500);
                }

                drawGame();
            }
        }
    });
}

function newGame() {
    score = 0;
    gameRunning = true;
    scoreDisplay.textContent = 'Score: 0 / 5';
    gameOverDiv.style.display = 'none';
    differences = getDifferences();
    drawGame();
}

['gameCanvas1', 'gameCanvas2'].forEach(canvasId => {
    const canvas = document.getElementById(canvasId);
    const isFirst = canvasId === 'gameCanvas1';

    canvas.addEventListener('click', (e) => {
        const rect = canvas.getBoundingClientRect();
        const mouseX = (e.clientX - rect.left) * (canvas.width / rect.width);
        const mouseY = (e.clientY - rect.top)  * (canvas.height / rect.height);
        checkClick(mouseX, mouseY, isFirst);
    });

    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        const rect  = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        const mouseX = (touch.clientX - rect.left) * (canvas.width / rect.width);
        const mouseY = (touch.clientY - rect.top)  * (canvas.height / rect.height);
        checkClick(mouseX, mouseY, isFirst);
    });
});

// Initialize game
newGame();
</script>

</body>
</html>
