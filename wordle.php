<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
$db = init_db();

if (!$db) {
    error_log('Failed to initialize database');
    http_response_code(500);
    die('Database initialization failed');
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Create table if not exists (simplified version)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS wordle_saves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        game_state TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id)
    )");
} catch (PDOException $e) {
    die('Database Error: ' . $e->getMessage());
}

// Handle AJAX requests for saving/loading game state
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $user_id = $_SESSION['user_id'];

    try {
        if ($_POST['action'] === 'save_game' && isset($_POST['gameState'])) {
            // Simple save - just store the JSON string using SQLite UPSERT
            $stmt = $db->prepare("INSERT INTO wordle_saves (user_id, game_state) VALUES (?, ?)
                                ON CONFLICT(user_id) DO UPDATE SET game_state = excluded.game_state");
            $stmt->execute([$user_id, $_POST['gameState']]);
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($_POST['action'] === 'load_game') {
            // Simple load - just get the JSON string
            $stmt = $db->prepare("SELECT game_state FROM wordle_saves WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['game_state']) {
                echo json_encode([
                    'success' => true,
                    'gameState' => json_decode($result['game_state'], true)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No saved game found'
                ]);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Create wordle_games table if it doesn't exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS wordle_games (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        answer TEXT NOT NULL,
        current_row INTEGER NOT NULL DEFAULT 0,
        guesses TEXT,
        date_started DATETIME NOT NULL,
        UNIQUE(user_id, date(date_started))
    )");
} catch (Exception $e) {
    error_log('Error creating wordle_games table: ' . $e->getMessage());
}

// Function to handle errors in JSON responses
function sendJsonError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

$page_title = 'Wordle - OSRG Connect';
$additional_css = '
    body { 
        font-family: Arial, sans-serif; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        margin-top: 50px; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        min-height: 100vh; 
    }
    .game-container {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(20px);
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        text-align: center;
    }
    h1 {
        color: #1877f2;
        margin-bottom: 30px;
        font-size: 2.5em;
        font-weight: 700;
    }
    #grid { 
        display: grid; 
        grid-template-columns: repeat(5, 60px); 
        gap: 5px; 
        margin-bottom: 20px; 
        justify-content: center;
    }
    .cell { 
        width: 60px; 
        height: 60px; 
        border: 2px solid #888; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        font-size: 32px; 
        text-transform: uppercase; 
        font-weight: bold; 
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .green { background-color: #6aaa64; color: white; border-color: #6aaa64; }
    .yellow { background-color: #c9b458; color: white; border-color: #c9b458; }
    .gray { background-color: #787c7e; color: white; border-color: #787c7e; }
    #message {
        font-size: 18px;
        font-weight: 600;
        color: #1877f2;
        margin-top: 20px;
        min-height: 25px;
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
    }
    .back-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(24,119,242,0.4);
        color: white;
    }
    .instructions {
        background: rgba(24,119,242,0.1);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        color: #333;
        line-height: 1.6;
    }
    @media (max-width: 768px) {
        .game-container { padding: 25px 20px; margin: 20px; }
        h1 { font-size: 2em; }
        .cell { width: 50px; height: 50px; font-size: 24px; }
        #grid { grid-template-columns: repeat(5, 50px); }
    }
';

require_once 'header.php';
?>

<div class="game-container">
    <a href="games.php" class="back-button">← Back to Games</a>
    
    <h1>📝 Wordle</h1>
    
    <div class="instructions">
        <strong>How to Play:</strong><br>
        • Guess the 5-letter word in 6 tries<br>
        • Type letters and press ENTER to submit<br>
        • Green = correct letter in correct position<br>
        • Yellow = correct letter in wrong position<br>
        • Gray = letter not in the word
    </div>
    
    <div id="grid"></div>
    <p id="message"></p>
    
    <!-- Hidden input for mobile keyboard -->
    <input type="text" id="mobileInput" style="position: absolute; left: -9999px; opacity: 0;" autocomplete="off" autocapitalize="characters" maxlength="1">
</div>

<script>

// === DEDUPED WORD LIST (no duplicates) ===
const wordList = [
  "ABOUT","ABOVE","ABUSE","ACTOR","ACUTE","ADMIT","ADOPT","ADULT","AFTER","AGAIN","AGENT","AGREE","AHEAD","ALARM","ALBUM","ALERT","ALIEN","ALIGN","ALIKE","ALIVE","ALLOW","ALONE","ALONG","ALTER","AMONG","ANGER","ANGLE","ANGRY","APART","APPLE","APPLY","ARENA","ARGUE","ARISE","ARRAY","ASIDE","ASSET","AVOID","AWAKE","AWARD","AWARE","BADLY","BAKER","BASES","BASIC","BEACH","BEGAN","BEGIN","BEING","BELOW","BENCH","BILLY","BIRTH","BLACK","BLANK","BLAST","BLEND","BLIND","BLINK","BLOCK","BLOOD","BLUFF","BLURB","BOARD","BOOST","BOOTH","BOUND","BRACE","BRAIN","BRAND","BRASH","BRAVE","BREAD","BREAK","BREED","BRIEF","BRING","BRINK","BRISK","BROAD","BROKE","BROWN","BRUSH","BUILD","BUILT","BUYER","CABLE","CALIF","CARET","CARRY","CARTE","CATCH","CAUSE","CHAIN","CHAIR","CHAOS","CHARM","CHART","CHASE","CHECK","CHEST","CHIEF","CHILD","CHINA","CHOSE","CIVIL","CLAIM","CLASH","CLASS","CLEAN","CLEAR","CLICK","CLIMB","CLOCK","CLOSE","CLOUT","CLOUD","COACH","COAST","COULD","COUNT","COURT","COVER","CRAFT","CRANE","CRANK","CRASH","CRATE","CRAVE","CRAZY","CREAM","CRIME","CRISP","CROSS","CROWD","CROWN","CRUDE","CRUST","CURVE","CYCLE","DAILY","DANCE","DEALT","DEATH","DEBUT","DELAY","DEPTH","DINGO","DOING","DOUBT","DOZEN","DRAFT","DRAMA","DRANK","DRAWN","DREAM","DROVE","DYING","EAGER","EARLY","EARTH","EIGHT","ELITE","EMPTY","ENEMY","ENJOY","ENTER","ENTRY","EQUAL","ERROR","EVENT","EVERY","EXACT","EXIST","EXTRA","FAITH","FALSE","FAULT","FIBER","FIELD","FIFTH","FIFTY","FIGHT","FINAL","FIRED","FIRST","FIXED","FLAGS","FLAME","FLARE","FLASH","FLEET","FLICK","FLINT","FLOOR","FLUID","FLUSH","FLUTE","FOCUS","FORCE","FORTH","FORTY","FORUM","FOUND","FRAME","FRANK","FRAUD","FRESH","FRONT","FRUIT","FULLY","FUNNY","GIANT","GIVEN","GIVON","GLARE","GLASS","GLINT","GLOBE","GOING","GRACE","GRADE","GRAND","GRANT","GRAPE","GRASP","GRASS","GRATE","GRAVE","GREAT","GREEN","GRILL","GRIND","GRIPE","GROSS","GROUP","GROWN","GUARD","GUESS","GUEST","GUIDE","HAPPY","HARRY","HEART","HEAVY","HENCE","HENRY","HORSE","HOTEL","HOUSE","HUMAN","IDEAL","IMAGE","INDEX","INNER","INPUT","ISSUE","JAPAN","JIMMY","JOINT","JONES","JUDGE","KNOWN","LABEL","LARGE","LASER","LATER","LAUGH","LAYER","LEARN","LEASE","LEAST","LEAVE","LEGAL","LEVEL","LEWIS","LIGHT","LIMIT","LINKS","LIVES","LOCAL","LOOSE","LOWER","LUCKY","LUNCH","LYING","MAGIC","MAJOR","MAKER","MARCH","MARIA","MATCH","MAYBE","MAYOR","MEANT","MEDIA","METAL","MIGHT","MINOR","MINUS","MIXED","MODEL","MONEY","MONTH","MORAL","MOTOR","MOUNT","MOUSE","MOUTH","MOVED","MOVIE","MUSIC","NEEDS","NEVER","NEWLY","NIGHT","NOISE","NORTH","NOTED","NOVEL","NURSE","OCCUR","OCEAN","OFFER","OFTEN","ORDER","OSRGG","OTHER","OUGHT","PAINT","PANEL","PAPER","PARSE","PARTY","PEACE","PETER","PHASE","PHONE","PHOTO","PIANO","PIECE","PILOT","PITCH","PLACE","PLAIN","PLANE","PLANT","PLATE","PLUCK","POINT","POUND","POWER","PRESS","PRICE","PRIDE","PRIME","PRINT","PRIOR","PRIZE","PROOF","PRONG","PROUD","PROVE","QUEEN","QUICK","QUIET","QUITE","RADIO","RAISE","RANGE","RAPID","RATIO","REACH","READY","REALM","REBEL","REFER","RELAX","REPAY","REPLY","RIGHT","RIGID","RIMON","RIVAL","RIVER","ROBIN","ROGER","ROMAN","ROUGH","ROUND","ROUTE","ROYAL","RULES","RURAL","SAINT","SCALE","SCENE","SCOPE","SCORE","SCOUT","SENSE","SERVE","SETUP","SEVEN","SHALL","SHAPE","SHARE","SHARP","SHEET","SHELF","SHELL","SHIFT","SHINE","SHIRT","SHOCK","SHOOT","SHORE","SHORT","SHOWN","SIDES","SIGHT","SILLY","SINCE","SIXTH","SIXTY","SIZED","SKILL","SLANT","SLASH","SLATE","SLEEP","SLICK","SLIDE","SMALL","SMART","SMILE","SMITH","SMOKE","SOLID","SOLVE","SORRY","SOUND","SOUTH","SPACE","SPARE","SPARK","SPEAK","SPEED","SPEND","SPENT","SPINE","SPLIT","SPOKE","SPOOF","SPORT","SQUAD","STAFF","STAGE","STAKE","STALE","STAND","START","STARE","STATE","STEAM","STEEL","STEEP","STEER","STERN","STICK","STILL","STINT","STOCK","STOMP","STONE","STOOD","STORE","STORK","STORM","STORY","STRAP","STRIP","STUCK","STUDY","STUFF","STYLE","SUGAR","SUITE","SUPER","SWEET","SWORD","TABLE","TAKEN","TASER","TASTE","TAXES","TEACH","TEAMS","TEETH","TERRY","TEXAS","THANK","THEFT","THEIR","THEME","THERE","THESE","THICK","THING","THINK","THIRD","THOSE","THREE","THREW","THROW","THUMB","TIGHT","TIRED","TITLE","TODAY","TOPIC","TOTAL","TOUCH","TOUGH","TOWER","TRACE","TRACK","TRADE","TRAIN","TREAT","TREND","TRIAL","TRIBE","TRICK","TRIED","TRIES","TRULY","TRUCK","TRUNK","TRUST","TRUTH","TWICE","TWIST","TYLER","TYPES","UNCLE","UNDUE","UNION","UNITY","UNTIL","UPPER","UPSET","URBAN","USAGE","USUAL","VALID","VALUE","VIDEO","VIRUS","VISIT","VITAL","VOCAL","VOICE","WASTE","WATCH","WATER","WHEEL","WHERE","WHICH","WHILE","WHITE","WHOLE","WOMAN","WOMEN","WORLD","WORRY","WORSE","WORST","WORTH","WOULD","WRITE","WRONG","WROTE","YOUNG","YOUTH",
  // user-requested additions
  "MAMMY","DADDY",
  // more fun rhyming words
  "RINGS","BINGS","GLING","DINGY","DINGS","RINGY","SLING","SWING","THING","BRING"
];

// Choose answer from the cleaned wordList
// Initialize with saved game or new game
let answer, currentRow, currentCol, savedGuesses;
const maxRows = 6;

// Function to save game state
async function saveGameState() {
    try {
        // Collect current game state
        const guesses = [];
        for(let row = 0; row < currentRow; row++) {
            let guess = "";
            for(let col = 0; col < 5; col++) {
                guess += cells[row*5 + col].textContent || '';
            }
            guesses.push(guess);
        }
        
        const gameState = {
            answer: answer,
            currentRow: currentRow,
            guesses: guesses
        };
        
        await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'save_game',
                'gameState': JSON.stringify(gameState)
            })
        });
    } catch (error) {
        console.error('Save error:', error);
    }
}

// Function to load saved game state
async function loadGameState() {
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'load_game'
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.gameState) {
            answer = data.gameState.answer;
            currentRow = data.gameState.currentRow;
            savedGuesses = data.gameState.guesses;
            return true;
        }
    } catch (error) {
        console.error('Load error:', error);
    }
    return false;
}

const grid = document.getElementById('grid');
const message = document.getElementById('message');

// Initialize the game (load saved state or start new game)
async function initGame() {
    const hasSavedGame = await loadGameState();
    
    if (!hasSavedGame) {
        // Start new game
        answer = wordList[Math.floor(Math.random() * wordList.length)];
        currentRow = 0;
        savedGuesses = [];
    }
    
    currentCol = 0;
    
    // Replay saved guesses if any
    if (savedGuesses && savedGuesses.length > 0) {
        for(let i = 0; i < savedGuesses.length; i++) {
            const guess = savedGuesses[i];
            for(let j = 0; j < guess.length; j++) {
                cells[i*5 + j].textContent = guess[j];
                
                // Apply appropriate color
                if(guess[j] === answer[j]) {
                    cells[i*5 + j].classList.add('green');
                } else if(answer.includes(guess[j])) {
                    cells[i*5 + j].classList.add('yellow');
                } else {
                    cells[i*5 + j].classList.add('gray');
                }
            }
        }
        
        if (savedGuesses[savedGuesses.length - 1] === answer) {
            message.textContent = "🎉 Congrats! You guessed the word!";
            currentRow = maxRows; // stop further input
        } else if (currentRow === maxRows) {
            message.textContent = "😔 Game over! The word was: " + answer;
        }
    }
}

// create grid
for(let i=0;i<maxRows*5;i++){
  const cell = document.createElement('div');
  cell.classList.add('cell');
  grid.appendChild(cell);
}

const cells = grid.children;

const mobileInput = document.getElementById('mobileInput');

function handleInput(key) {
  if(currentRow >= maxRows) return;

  if(key === "Backspace"){
    if(currentCol > 0){
      currentCol--;
      cells[currentRow*5 + currentCol].textContent = "";
    }
  } else if(key === "Enter"){
    if(currentCol === 5){
      let guess = "";
      for(let i=0;i<5;i++){
        guess += cells[currentRow*5 + i].textContent;
      }
      guess = guess.toUpperCase();

      if(!wordList.includes(guess)){
        message.textContent = "Word not in list!";
        return;
      }

      // give feedback
      for(let i=0;i<5;i++){
        const cell = cells[currentRow*5 + i];
        if(guess[i] === answer[i]) cell.classList.add('green');
        else if(answer.includes(guess[i])) cell.classList.add('yellow');
        else cell.classList.add('gray');
      }

      if(guess === answer){
        message.textContent = "🎉 Congrats! You guessed the word!";
        currentRow = maxRows; // stop further input
        saveGameState(); // Save final state
        return;
      }

      currentRow++;
      currentCol = 0;

      if(currentRow === maxRows){
        message.textContent = "😔 Game over! The word was: " + answer;
      } else {
        message.textContent = "";
      }
      
      // Save game state after each valid guess
      saveGameState();
    }
  } else if(key.length === 1 && /[a-zA-Z]/.test(key)){
    if(currentCol < 5){
      cells[currentRow*5 + currentCol].textContent = key.toUpperCase();
      currentCol++;
    }
  }
}

// Desktop keyboard
document.addEventListener('keydown', (e) => {
  handleInput(e.key);
});

// Mobile support
grid.addEventListener('click', () => {
  mobileInput.focus();
});

mobileInput.addEventListener('input', (e) => {
  const value = e.target.value.toUpperCase();
  if(value && /[A-Z]/.test(value)) {
    handleInput(value);
  }
  e.target.value = '';
});

mobileInput.addEventListener('keydown', (e) => {
  if(e.key === 'Backspace' || e.key === 'Enter') {
    handleInput(e.key);
  }
});

// Auto-focus on mobile
if(/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
  setTimeout(() => mobileInput.focus(), 500);
}

message.textContent = "Start typing your first guess!";

// Focus mobile input on page load for mobile devices
if(/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => mobileInput.focus(), 100);
  });
}

// Initialize game (load saved state or start new)
initGame();
</script>

</body>
</html>
