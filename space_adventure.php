<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$page_title = 'Space Adventure - OSRG Connect';
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
        max-width: 650px;
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
        font-family: "Press Start 2P", cursive;
    }
    .game-container {
        text-align: center;
        width: 100%;
        position: relative;
        margin: 0 auto;
    }
    #gameCanvas {
        background: radial-gradient(circle at center, #001122, #000000);
        border: 3px solid #3b82f6;
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.6), inset 0 0 20px rgba(0,0,0,0.3);
        border-radius: 12px;
        width: 100%;
        max-width: 500px;
        height: 400px;
        display: block;
        margin: 0 auto;
    }
    #controls {
        margin-top: 25px;
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    #startButton, #muteButton {
        padding: 12px 24px;
        font-size: 14px;
        cursor: pointer;
        color: white;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        border: none;
        border-radius: 25px;
        font-family: "Press Start 2P", cursive;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        font-weight: 400;
    }
    #startButton:hover, #muteButton:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    }
    #message {
        position: absolute;
        color: white;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 16px;
        text-align: center;
        background: rgba(0, 0, 0, 0.9);
        padding: 25px;
        border-radius: 15px;
        display: none;
        z-index: 100;
        max-width: 350px;
        font-family: "Press Start 2P", cursive;
        border: 2px solid #3b82f6;
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
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
    @media (max-width: 768px) {
        body { padding-top: 60px; }
        .game-wrapper { padding: 25px 20px; margin: 15px; }
        h1 { font-size: 1.8em; margin-bottom: 20px; }
        #gameCanvas { height: 300px; max-width: 100%; }
        #startButton, #muteButton { 
            font-size: 12px; 
            padding: 10px 20px;
            margin: 5px;
        }
        #message { 
            font-size: 12px; 
            padding: 20px;
            max-width: 280px;
        }
        .instructions { font-size: 12px; padding: 15px; }
        #controls { gap: 10px; }
    }
    @media (max-width: 480px) {
        .game-wrapper { padding: 20px 15px; }
        h1 { font-size: 1.5em; }
        #gameCanvas { height: 250px; }
        #startButton, #muteButton { font-size: 10px; padding: 8px 16px; }
    }
';

require_once 'header.php';
?>

<div class="game-wrapper">
    <a href="games.php" class="back-button">← Back to Games</a>
    
    <h1>🚀 Space Adventure</h1>
    
    <div class="instructions">
        <strong>How to Play:</strong><br>
        • Use arrow keys to move your spaceship<br>
        • Press SPACEBAR to shoot<br>
        • Destroy enemies to earn points<br>
        • Don't let enemies reach the bottom!
    </div>
    
    <div class="game-container">
        <canvas id="gameCanvas"></canvas>
        <div id="controls">
            <button id="startButton">Start Game</button>
            <button id="muteButton">Toggle Mute</button>
        </div>
        <div id="message"></div>
    </div>
</div>

<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tone@14.7.77/build/Tone.min.js"></script>
<script>
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const startButton = document.getElementById('startButton');
const muteButton = document.getElementById('muteButton');
const messageElement = document.getElementById('message');

// Fixed canvas dimensions
const dimensions = { width: 500, height: 400 };
canvas.width = dimensions.width;
canvas.height = dimensions.height;

let player;
let bullets = [];
let enemies = [];
let score = 0;
let gameRunning = false;
let animationId;
let muted = false;
let gameOver = false;

// Sound Effects
const shooterSound = new Tone.Synth({
    oscillator: { type: 'triangle' },
    envelope: { attack: 0.01, decay: 0.1, sustain: 0.3, release: 0.5 }
}).toDestination();

const explosionSound = new Tone.Synth({
    oscillator: { type: 'square' },
    envelope: { attack: 0.001, decay: 0.1, sustain: 0.01, release: 0.1 }
}).toDestination();

const gameOverSound = new Tone.MembraneSynth({
    pitchDecay: 0.05,
    octaves: 10,
    oscillator: { type: 'sine' },
    envelope: { attack: 0.001, decay: 0.4, sustain: 0.01, release: 1.4, attackCurve: 'exponential' }
}).toDestination();

// Helper Functions
function random(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function showMessage(msg) {
    messageElement.textContent = msg;
    messageElement.style.display = 'block';
}

function hideMessage() {
    messageElement.style.display = 'none';
}

// Game Object Creation
function createPlayer() {
    return {
        x: dimensions.width / 2 - 25,
        y: dimensions.height - 60,
        width: 50,
        height: 40,
        speed: 5,
        color: '#3b82f6'
    };
}

function createBullet(x, y, isPlayer = true) {
    return {
        x: x,
        y: y,
        width: 8,
        height: 15,
        speed: isPlayer ? -7 : 4,
        color: isPlayer ? '#f59e0b' : '#ef4444',
        isPlayer: isPlayer,
    };
}

function createEnemy(x, y, type = 'basic') {
    let width, height, color;
    switch (type) {
        case 'basic':
        default:
            width = 30;
            height = 20;
            color = '#84cc16';
            break;
        case 'fast':
            width = 20;
            height = 15;
            color = '#f43f5e';
            break;
        case 'tough':
            width = 40;
            height = 30;
            color = '#a855f7';
            break;
    }
    return {
        x: x,
        y: y,
        width: width,
        height: height,
        speed: 2,
        color: color,
        type: type
    };
}

// Enemy Spawning
function spawnEnemies() {
    if (enemies.length < 5) {
        let enemyX = random(30, dimensions.width - 30);
        let enemyY = random(50, dimensions.height / 2);
        let type = 'basic';
        let rand = Math.random();
        if (rand < 0.2) {
            type = 'fast';
        } else if (rand < 0.4) {
            type = 'tough';
        }
        enemies.push(createEnemy(enemyX, enemyY, type));
    }
}

// Drawing Functions
function drawPlayer() {
    ctx.fillStyle = player.color;
    ctx.beginPath();
    ctx.moveTo(player.x + player.width / 2, player.y);
    ctx.lineTo(player.x, player.y + player.height);
    ctx.lineTo(player.x + player.width, player.y + player.height);
    ctx.closePath();
    ctx.fill();
}

function drawBullet(bullet) {
    ctx.fillStyle = bullet.color;
    ctx.fillRect(bullet.x, bullet.y, bullet.width, bullet.height);
}

function drawEnemy(enemy) {
    ctx.fillStyle = enemy.color;
    ctx.fillRect(enemy.x, enemy.y, enemy.width, enemy.height);
}

function drawScore() {
    ctx.fillStyle = '#ffffff';
    ctx.font = '14px "Press Start 2P"';
    ctx.shadowColor = '#ffffff';
    ctx.shadowBlur = 8;
    ctx.fillText(`Score: ${score}`, 15, 35);
    ctx.shadowBlur = 0;
}

// Update Functions
function updatePlayer() {
    if (keys['ArrowLeft'] && player.x > 0) {
        player.x -= player.speed;
    }
    if (keys['ArrowRight'] && player.x < dimensions.width - player.width) {
        player.x += player.speed;
    }
    if (keys['Space']) {
        if (Date.now() - lastFireTime > 300) {
            bullets.push(createBullet(player.x + player.width / 2 - 4, player.y));
            if (!muted) {
                shooterSound.triggerAttackRelease('C4', '16n');
            }
            lastFireTime = Date.now();
        }
    }
}

function updateBullets() {
    bullets.forEach((bullet, index) => {
        bullet.y += bullet.speed / 2;
        if (bullet.y + bullet.height < 0 || bullet.y > dimensions.height) {
            bullets.splice(index, 1);
        }
    });
}

function updateEnemies() {
    enemies.forEach((enemy, index) => {
        enemy.y += enemy.speed / 4;
        if (enemy.y + enemy.height >= dimensions.height) {
            endGame();
        }
    });
}

function checkBulletCollisions() {
    for (let i = bullets.length - 1; i >= 0; i--) {
        const bullet = bullets[i];
        if (bullet.isPlayer) {
            for (let j = enemies.length - 1; j >= 0; j--) {
                const enemy = enemies[j];
                if (
                    bullet.x < enemy.x + enemy.width &&
                    bullet.x + bullet.width > enemy.x &&
                    bullet.y < enemy.y + enemy.height &&
                    bullet.y + bullet.height > enemy.y
                ) {
                    bullets.splice(i, 1);
                    if (enemy.type === 'tough') {
                        enemies[j] = createEnemy(enemy.x, enemy.y, 'basic');
                    } else {
                        enemies.splice(j, 1);
                        score += (enemy.type === 'fast') ? 20 : 10;
                        if (!muted) {
                            explosionSound.triggerAttackRelease('C4', '8n');
                        }
                    }
                    break;
                }
            }
        } else {
            if (
                bullet.x < player.x + player.width &&
                bullet.x + bullet.width > player.x &&
                bullet.y < player.y + player.height &&
                bullet.y + bullet.height > player.y
            ) {
                endGame();
            }
        }
    }
}

// Input Handling
let keys = {};
let lastFireTime = 0;

document.addEventListener('keydown', (e) => {
    keys[e.code] = true;
});

document.addEventListener('keyup', (e) => {
    keys[e.code] = false;
});

// Game Loop
function gameLoop() {
    if (!gameRunning) return;
    
    ctx.clearRect(0, 0, dimensions.width, dimensions.height);
    
    updatePlayer();
    updateBullets();
    updateEnemies();
    checkBulletCollisions();
    spawnEnemies();
    
    drawPlayer();
    bullets.forEach(drawBullet);
    enemies.forEach(drawEnemy);
    drawScore();
    
    animationId = requestAnimationFrame(gameLoop);
}

// Game Start/End
function startGame() {
    if (gameRunning) return;
    player = createPlayer();
    bullets = [];
    enemies = [];
    score = 0;
    gameRunning = true;
    gameOver = false;
    hideMessage();
    gameLoop();
    document.getElementById("startButton").textContent = "Restart Game";
}

function endGame() {
    gameRunning = false;
    gameOver = true;
    cancelAnimationFrame(animationId);
    if (!muted) {
        gameOverSound.triggerAttackRelease("C2", "2n");
    }
    showMessage(`Game Over! Your Score: ${score} Press Start to Restart`);
    document.getElementById("startButton").textContent = "Restart Game";
}

// Event Listeners
startButton.addEventListener('click', startGame);
muteButton.addEventListener('click', () => {
    muted = !muted;
    muteButton.textContent = muted ? 'Unmute' : 'Mute';
});
</script>

</body>
</html>
