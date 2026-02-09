<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$page_title = 'Endless Runner - OSRG Connect';
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
    #gameCanvas {
        background: linear-gradient(to bottom, #87ceeb, #98d8e8);
        border: 3px solid #1877f2;
        box-shadow: 0 0 30px rgba(24, 119, 242, 0.3);
        border-radius: 12px;
        width: 100%;
        max-width: 800px;
        height: 400px;
        display: block;
        margin: 0 auto;
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
    #restartBtn {
        position: absolute;
        top: 60px;
        right: 20px;
        padding: 8px 16px;
        font-size: 12px;
        cursor: pointer;
        color: white;
        background: linear-gradient(135deg, #ff6b6b, #e74c3c);
        border: none;
        border-radius: 20px;
        font-family: Arial, sans-serif;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        z-index: 50;
    }
    #restartBtn:hover {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
    }
    @media (max-width: 768px) {
        body { padding-top: 60px; }
        .game-wrapper { padding: 25px 20px; margin: 15px; }
        h1 { font-size: 1.8em; margin-bottom: 20px; }
        #gameCanvas { height: 300px; max-width: 100%; }
        #score { font-size: 20px; }
        .instructions { font-size: 12px; padding: 15px; }
    }
    @media (max-width: 480px) {
        .game-wrapper { padding: 20px 15px; }
        h1 { font-size: 1.5em; }
        #gameCanvas { height: 250px; }
        #score { font-size: 18px; }
    }
';

require_once 'header.php';
?>

<div class="game-wrapper">
    <a href="games.php" class="back-button">← Back to Games</a>
    
    <h1>🏃 Endless Runner</h1>
    
    <div class="instructions">
        <strong>How to Play:</strong><br>
        • Press SPACEBAR to jump over ground obstacles<br>
        • Press RIGHT ARROW to slide under floating obstacles<br>
        • Collect gold coins for 10 points each<br>
        • Avoid obstacles or the game ends<br>
        • See how far you can run!
    </div>
    
    <div class="game-container">
        <canvas id="gameCanvas" width="800" height="400"></canvas>
        <div id="score">Score: 0</div>
        <button id="restartBtn" onclick="restartGame()">🔄 Restart</button>
        <div class="game-over" id="gameOver">
            <h2>Game Over!</h2>
            <p id="finalScore">Score: 0</p>
            <button class="restart-btn" onclick="restartGame()">Play Again</button>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');

let score = 0;
let gameRunning = true;
let speed = 6; // starting speed
const scoreDisplay = document.getElementById('score');
const gameOverDiv = document.getElementById('gameOver');
const finalScoreDiv = document.getElementById('finalScore');

const player = {
    x: 50,
    y: 300,
    width: 50,
    height: 50,
    dy: 0,
    gravity: 1,
    jumpPower: -15,
    grounded: true,
    frame: 0,
    animFrame: 0,
    sliding: false,
    slideTimer: 0,
    dying: false,
    deathTimer: 0
};

const obstacles = [];
const coins = [];

let frame = 0;
const obstacleFrequency = 120;
const coinFrequency = 180;
let keys = {};

// Ninja sprite images
const ninjaSprites = {
    run: [],
    jump: [],
    dead: [],
    slide: []
};

// Load ninja sprites
function loadNinjaSprites() {
    for(let i = 0; i < 10; i++) {
        const runImg = new Image();
        runImg.src = `assets/ninja/png/Run__00${i}.png`;
        ninjaSprites.run.push(runImg);
        
        const jumpImg = new Image();
        jumpImg.src = `assets/ninja/png/Jump__00${i}.png`;
        ninjaSprites.jump.push(jumpImg);
        
        const deadImg = new Image();
        deadImg.src = `assets/ninja/png/Dead__00${i}.png`;
        ninjaSprites.dead.push(deadImg);
        
        const slideImg = new Image();
        slideImg.src = `assets/ninja/png/Slide__00${i}.png`;
        ninjaSprites.slide.push(slideImg);
    }
}

loadNinjaSprites();

function spawnObstacle() {
    const isFloating = Math.random() < 0.4; // 40% chance for floating obstacle
    
    if (isFloating) {
        obstacles.push({
            x: canvas.width,
            y: canvas.height - 100, // lower floating height for sliding
            width: 40,
            height: 20,
            type: 'floating'
        });
    } else {
        const height = Math.random() * 30 + 20;
        obstacles.push({
            x: canvas.width,
            y: canvas.height - height - 50,
            width: 30,
            height: height,
            type: 'ground'
        });
    }
}

function spawnCoin() {
    const y = Math.random() * 120 + 150; // spawn between y=150 and y=270 (perfect jump range)
    coins.push({
        x: canvas.width,
        y: y,
        width: 20,
        height: 20
    });
}

function drawPlayer() {
    let sprites, animSpeed;
    
    if (player.dying || !gameRunning) {
        sprites = ninjaSprites.dead;
        animSpeed = 8;
    } else if (player.sliding) {
        sprites = ninjaSprites.slide;
        animSpeed = 6;
    } else if (!player.grounded) {
        sprites = ninjaSprites.jump;
        animSpeed = 6;
    } else {
        sprites = ninjaSprites.run;
        animSpeed = 4;
    }
    
    const spriteIndex = Math.floor(player.animFrame / animSpeed) % sprites.length;
    const sprite = sprites[spriteIndex];
    
    if (sprite && sprite.complete) {
        ctx.drawImage(sprite, player.x, player.y, player.width, player.height);
    } else {
        // Fallback to colored rectangle if sprite not loaded
        ctx.fillStyle = '#e74c3c';
        ctx.fillRect(player.x, player.y, player.width, player.height);
    }
    
    player.animFrame++;
}

function drawObstacle(obstacle) {
    if (obstacle.type === 'floating') {
        ctx.fillStyle = '#e74c3c'; // red for floating obstacles
    } else {
        ctx.fillStyle = '#8b4513'; // brown for ground obstacles
    }
    ctx.fillRect(obstacle.x, obstacle.y, obstacle.width, obstacle.height);
}

function drawCoin(coin) {
    ctx.fillStyle = '#f1c40f';
    ctx.beginPath();
    ctx.arc(coin.x + coin.width/2, coin.y + coin.height/2, coin.width/2, 0, Math.PI * 2);
    ctx.fill();
    
    ctx.fillStyle = '#f39c12';
    ctx.beginPath();
    ctx.arc(coin.x + coin.width/2, coin.y + coin.height/2, coin.width/3, 0, Math.PI * 2);
    ctx.fill();
}

function drawGround() {
    ctx.fillStyle = '#27ae60';
    ctx.fillRect(0, canvas.height - 50, canvas.width, 50);
    
    // Grass texture
    ctx.fillStyle = '#2ecc71';
    for(let i = 0; i < canvas.width; i += 20) {
        ctx.fillRect(i, canvas.height - 50, 2, 10);
    }
}

function update() {
    if (!gameRunning && !player.dying) return;
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Handle death animation
    if (player.dying) {
        player.deathTimer++;
        if (player.deathTimer > 60) { // 1 second at 60fps
            gameOver();
            return;
        }
        drawGround();
        drawPlayer();
        requestAnimationFrame(update);
        return;
    }

    // Increase speed gradually every 300 frames (about 5 seconds)
    if (frame % 300 === 0) speed += 0.5;

    // Handle sliding
    if (player.sliding) {
        player.slideTimer++;
        if (player.slideTimer > 30) { // slide for 0.5 seconds
            player.sliding = false;
            player.slideTimer = 0;
        }
    }

    // Player physics
    if (!player.sliding) {
        player.dy += player.gravity;
        player.y += player.dy;
        if (player.y + player.height > canvas.height - 50) {
            player.y = canvas.height - 50 - player.height;
            player.dy = 0;
            player.grounded = true;
        }
    }

    drawGround();
    drawPlayer();

    // Obstacles
    for (let i = obstacles.length - 1; i >= 0; i--) {
        obstacles[i].x -= speed;
        drawObstacle(obstacles[i]);

        // Collision detection
        let playerHitbox = {
            x: player.x,
            y: player.sliding ? player.y + 30 : player.y, // lower hitbox when sliding
            width: player.width,
            height: player.sliding ? 20 : player.height // smaller height when sliding
        };

        if (playerHitbox.x < obstacles[i].x + obstacles[i].width &&
            playerHitbox.x + playerHitbox.width > obstacles[i].x &&
            playerHitbox.y < obstacles[i].y + obstacles[i].height &&
            playerHitbox.y + playerHitbox.height > obstacles[i].y) {
            player.dying = true;
            player.deathTimer = 0;
            return;
        }

        if (obstacles[i].x + obstacles[i].width < 0) {
            obstacles.splice(i, 1);
        }
    }

    // Coins
    for (let i = coins.length - 1; i >= 0; i--) {
        coins[i].x -= speed;
        drawCoin(coins[i]);

        // Collision detection
        if (player.x < coins[i].x + coins[i].width &&
            player.x + player.width > coins[i].x &&
            player.y < coins[i].y + coins[i].height &&
            player.y + player.height > coins[i].y) {
            score += 10;
            scoreDisplay.textContent = 'Score: ' + score;
            coins.splice(i, 1);
        }

        if (coins[i] && coins[i].x + coins[i].width < 0) {
            coins.splice(i, 1);
        }
    }

    frame++;
    if (frame % obstacleFrequency === 0) spawnObstacle();
    if (frame % coinFrequency === 0) spawnCoin();

    requestAnimationFrame(update);
}

function gameOver() {
    gameRunning = false;
    finalScoreDiv.textContent = 'Score: ' + score;
    gameOverDiv.style.display = 'block';
}

function restartGame() {
    gameRunning = true;
    score = 0;
    frame = 0;
    speed = 6; // reset speed
    scoreDisplay.textContent = 'Score: 0';
    gameOverDiv.style.display = 'none';
    
    // Reset player
    player.x = 50;
    player.y = 300;
    player.dy = 0;
    player.grounded = true;
    player.frame = 0;
    player.animFrame = 0;
    player.sliding = false;
    player.slideTimer = 0;
    player.dying = false;
    player.deathTimer = 0;
    
    // Clear arrays
    obstacles.length = 0;
    coins.length = 0;
    
    update();
}

document.addEventListener('keydown', (e) => {
    keys[e.code] = true;
    
    if (e.code === 'Space' && player.grounded && gameRunning && !player.sliding) {
        e.preventDefault();
        player.dy = player.jumpPower;
        player.grounded = false;
    }
    
    if (e.code === 'ArrowRight' && player.grounded && gameRunning && !player.sliding) {
        e.preventDefault();
        player.sliding = true;
        player.slideTimer = 0;
    }
});

document.addEventListener('keyup', (e) => {
    keys[e.code] = false;
});

// Touch support for mobile
canvas.addEventListener('touchstart', (e) => {
    e.preventDefault();
    if (player.grounded && gameRunning && !player.sliding) {
        player.dy = player.jumpPower;
        player.grounded = false;
    }
});

// Touch support for sliding (swipe right)
let touchStartX = 0;
canvas.addEventListener('touchstart', (e) => {
    touchStartX = e.touches[0].clientX;
});

canvas.addEventListener('touchend', (e) => {
    const touchEndX = e.changedTouches[0].clientX;
    const swipeDistance = touchEndX - touchStartX;
    
    if (swipeDistance > 50 && player.grounded && gameRunning && !player.sliding) {
        // Swipe right detected
        player.sliding = true;
        player.slideTimer = 0;
    }
});

update();
</script>

</body>
</html>