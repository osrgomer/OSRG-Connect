// --- DOM Elements ---
const gameContainer = document.getElementById('game-container');
const road = document.getElementById('road');
const car = document.getElementById('car');
const scoreDisplay = document.getElementById('score-display'); // Now just the number span
const scoreContainer = document.getElementById('score-container'); // The box
const livesDisplay = document.getElementById('lives-display');
const levelDisplay = document.getElementById('level-display');
const speedValueDisplay = document.getElementById('speed-value');
const levelTitleDisplay = document.getElementById('level-title-display');
const sceneryLeft = document.getElementById('scenery-left');
const sceneryRight = document.getElementById('scenery-right');
const startScreen = document.getElementById('start-screen');
const startBtn = document.getElementById('start-btn');
const gameOverScreen = document.getElementById('game-over');
const finalScoreDisplay = document.getElementById('final-score');
const restartBtn = document.getElementById('restart-btn');
const leftBtn = document.getElementById('left-btn');
const rightBtn = document.getElementById('right-btn');
const pauseBtn = document.getElementById('pause-btn');
const muteBtn = document.getElementById('mute-btn'); // Mute Button
const levelPopup = document.getElementById('level-popup');

// --- Game State Variables ---
let gameStarted = false, gameOver = false, score = 0, lives = 4, carPosition = 1;
let laneWidth = gameContainer.offsetWidth / 3;
let boreholes = [], sceneryItems = [], cracks = []; // Added cracks array
let animationId = null, lastTimestamp = 0, paused = false;
let levelPopupTimeoutId = null; // To clear popup timeout
let levelTransitionTimeoutId = null; // Add this to manage the new delay timeout
let isMuted = false; // Mute state
const defaultVolumes = {}; // To store original volumes
let availableWheels = []; // NEW: Track available wheels

// --- Speed Configuration ---
const baseDisplayedSpeed = 20; // baseline in km/h for player's car
const speedIncrementPerLevel = 18; // Increased so top level can reach ~100 km/h
const basePixelSpeed = 1; // base pixel speed mapping factor
const pixelSpeedFactor = basePixelSpeed / baseDisplayedSpeed;
const MAX_DISPLAYED_SPEED = 180;
const MIN_DISPLAYED_SPEED = 0;
const SLOWDOWN_ON_HIT = 20; // km/h reduced on damaging hit
const SLOW_RECOVERY_MS = 4000; // ms until speed is restored to baseline

let displayedSpeedBaseline = baseDisplayedSpeed;
let speedRestoreTimeout = null;

let playerSpeedAdjustment = 0;
const SPEED_STEP = 5; // km/h per key press

let currentDisplayedSpeed = baseDisplayedSpeed;
let gameSpeed = basePixelSpeed;
let boreholeSpeed = basePixelSpeed;

function getTargetDisplayedSpeed() {
    return Math.max(MIN_DISPLAYED_SPEED, Math.min(MAX_DISPLAYED_SPEED, displayedSpeedBaseline + playerSpeedAdjustment));
}

function setCurrentDisplayedSpeed(speed) {
    currentDisplayedSpeed = Math.max(MIN_DISPLAYED_SPEED, Math.min(MAX_DISPLAYED_SPEED, speed));
    gameSpeed = currentDisplayedSpeed * pixelSpeedFactor;
    boreholeSpeed = gameSpeed;
    updateGameUI();
}

function changePlayerSpeed(delta) {
    playerSpeedAdjustment += delta;
    // Clamp to valid range relative to baseline
    if (displayedSpeedBaseline + playerSpeedAdjustment > MAX_DISPLAYED_SPEED) playerSpeedAdjustment = MAX_DISPLAYED_SPEED - displayedSpeedBaseline;
    if (displayedSpeedBaseline + playerSpeedAdjustment < MIN_DISPLAYED_SPEED) playerSpeedAdjustment = MIN_DISPLAYED_SPEED - displayedSpeedBaseline;
    const target = getTargetDisplayedSpeed();
    if (speedRestoreTimeout) {
        clearTimeout(speedRestoreTimeout);
        // Reschedule restore to the new target after the slowdown timeout
        speedRestoreTimeout = setTimeout(() => {
            setCurrentDisplayedSpeed(getTargetDisplayedSpeed());
            speedRestoreTimeout = null;
        }, SLOW_RECOVERY_MS);
    } else {
        setCurrentDisplayedSpeed(target);
    }
}

function applyHitSlowdown() {
    if (speedRestoreTimeout) { clearTimeout(speedRestoreTimeout); speedRestoreTimeout = null; }
    const slowed = Math.max(MIN_DISPLAYED_SPEED, getTargetDisplayedSpeed() - SLOWDOWN_ON_HIT);
    setCurrentDisplayedSpeed(slowed);
    speedRestoreTimeout = setTimeout(() => {
        setCurrentDisplayedSpeed(getTargetDisplayedSpeed());
        speedRestoreTimeout = null;
    }, SLOW_RECOVERY_MS);
}

// --- Level Configuration (with Colors and Icons) ---
const roofColors = ['#FF0000', '#0000FF', '#FFA500', '#008000', '#FFFFFF']; // Red, Blue, Orange, Green, White
const levelPopups = [
    '&#128235;', // 0: Entrada do Vale (Down Arrow emoji)
    '&#128016;',       // 1: Cerca dos Pomares (goat)
    '&#128020;',       // 2: Monte do Galo (rooster - using chicken emoji)
    '&#127795;',       // 3: Varzea da Gonçala (forest/trees)
    '&#9875;',        // 4: Moinho do Bispo (watermill - using ferry symbol as placeholder)
    '&#128058;',        // 5: Monte da Rā (frog)
    '&#127811;'        // 6: Rocha (National Park emoji)
];

// --- Level Configuration (with Colors) ---
const levels = [
    { name: "Entrada do Vale", scoreThreshold: 0, bgColor: "#6a8d5f", roadColor: "#5a4b40", treeTrunkColor: "#6d4c41", treeFoliageColor: "#388e3c", holeBorderColor: "#4e342e", holeFillColor: "#3e2723" }, // Updated Name
    { name: "Cerca dos Pomares", scoreThreshold: 150, bgColor: "#8d7a5f", roadColor: "#795548", treeTrunkColor: "#8d6e63", treeFoliageColor: "#558b2f", holeBorderColor: "#5d4037", holeFillColor: "#4e342e" },
    { name: "Monte do Galo", scoreThreshold: 350, bgColor: "#8d6f5f", roadColor: "#8d6e63", treeTrunkColor: "#a1887f", treeFoliageColor: "#689f38", holeBorderColor: "#6d4c41", holeFillColor: "#5d4037" },
    { name: "Varzea da Gonçala", scoreThreshold: 600, bgColor: "#5f8d84", roadColor: "#616161", treeTrunkColor: "#757575", treeFoliageColor: "#2e7d32", holeBorderColor: "#424242", holeFillColor: "#303030" },
    { name: "Moinho do Bispo", scoreThreshold: 900, bgColor: "#787878", roadColor: "#424242", treeTrunkColor: "#616161", treeFoliageColor: "#1b5e20", holeBorderColor: "#313131", holeFillColor: "#212121" },
    { name: "Monte da Rā", scoreThreshold: 1250, bgColor: "#5f6a8d", roadColor: "#455a64", treeTrunkColor: "#607d8b", treeFoliageColor: "#37474f", holeBorderColor: "#37474f", holeFillColor: "#263238" } // Updated Name with macron
];
levels.forEach(level => { level.waterHoleBorderColor = "#000000"; level.waterHoleFillColor = "#00008B"; });
let currentLevelIndex = 0;

// --- Sound Effects ---
const sounds = {
    collision: null, passSmallHole: null, levelUp: null, gameOver: null, engine: null,
    last_collision: null // Add last collision sound
};

// Helper function to create an Audio object, with a fallback for missing files
function createAudio(src) {
    try {
        const audio = new Audio(src);
        audio.onerror = () => {
            console.warn(`Failed to load audio: ${src}. Using dummy audio.`);
            // Assign a dummy audio element that won't throw errors
            // This is a workaround for the 'NotSupportedError' when source is truly missing.
            // A more robust solution for production would be to ensure files exist or provide proper fallbacks.
            audio.src = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARAEA'; // Minimal silent WAV
            audio.load(); // Reload with dummy source
        };
        return audio;
    } catch (e) {
        console.error(`Error creating Audio object for ${src}:`, e);
        // Fallback for environments where Audio constructor might fail
        const dummyAudio = {
            play: () => console.log(`Dummy play for ${src}`),
            pause: () => console.log(`Dummy pause for ${src}`),
            stop: () => console.log(`Dummy stop for ${src}`),
            currentTime: 0,
            loop: false,
            volume: 0,
            paused: true
        };
        return dummyAudio;
    }
}

function loadSounds() {
    try {
        sounds.collision = createAudio('collision.wav');
        sounds.passSmallHole = createAudio('pass.wav');
        sounds.levelUp = createAudio('levelup.wav');
        sounds.gameOver = createAudio('gameover.wav');
        sounds.engine = createAudio('engine_loop.wav');
        sounds.last_collision = createAudio('last_collision.wav'); // Load last collision sound

        // Store default volumes safely
        defaultVolumes.collision = sounds.collision ? sounds.collision.volume : 1.0;
        defaultVolumes.passSmallHole = sounds.passSmallHole ? sounds.passSmallHole.volume : 1.0;
        defaultVolumes.levelUp = sounds.levelUp ? sounds.levelUp.volume : 1.0;
        defaultVolumes.gameOver = sounds.gameOver ? sounds.gameOver.volume : 1.0;
        defaultVolumes.last_collision = sounds.last_collision ? sounds.last_collision.volume : 1.0;
        defaultVolumes.engine = 0.3; // Specific default for engine

        if (sounds.engine) {
            sounds.engine.loop = true;
            sounds.engine.volume = defaultVolumes.engine; // Use stored default
        }
        console.log("Sounds loaded (or attempted).");
    } catch (e) {
        console.error("Error loading sounds:", e);
    }
}

function playSound(soundName) {
    const sound = sounds[soundName];
    if (sound) {
        if (soundName !== 'engine' || sound.paused) {
            sound.currentTime = 0;
        }
        sound.play().catch(error => {
            if (soundName === 'engine') {
                console.warn("Engine sound autoplay blocked. User interaction required to play.");
                // Add a one-time listener to play the engine sound on the first user interaction
                document.body.addEventListener('click', () => {
                    if (sounds.engine && sounds.engine.paused) {
                        sounds.engine.play().catch(e => console.error("Engine sound play on click failed:", e));
                    }
                }, { once: true });
            } else {
                console.warn(`Sound play failed for ${soundName}:`, error);
            }
        });
    } else {
        console.warn(`Sound "${soundName}" not found or not loaded yet.`);
    }
}

function stopSound(soundName) {
    const sound = sounds[soundName];
    if (sound && !sound.paused) {
        sound.pause();
        sound.currentTime = 0;
    }
}

// --- Mute Function ---
function toggleMute() {
    isMuted = !isMuted;
    console.log("Mute toggled:", isMuted);
    for (const soundName in sounds) {
        if (sounds[soundName] instanceof Audio) {
            sounds[soundName].volume = isMuted ? 0 : (defaultVolumes[soundName] || 1.0); // Restore default or set to 0
        }
    }
    // Update mute button icon/state
    if (muteBtn) { // Check if muteBtn exists
        muteBtn.classList.toggle('muted', isMuted);
        const muteIconSpan = muteBtn.querySelector('span');
        if (muteIconSpan) {
            muteIconSpan.innerHTML = isMuted ? '&#128263;' : '&#128266;'; // Muted speaker vs Speaker High Volume
        }
    }
}

// --- Game Initialization ---
function initGame() {
    console.log("initGame: Starting...");
    // Ensure sounds are loaded before starting the game
    if (!sounds.collision) {
        loadSounds();
    }

    gameStarted = true; gameOver = false; score = 0; lives = 4; carPosition = 1;
    laneWidth = gameContainer.offsetWidth / 3;
    if (laneWidth <= 0) {
        console.error("initGame: Lane width invalid. Cannot start game.");
        // Optionally, display an error message to the user
        return;
    }

    boreholes = []; sceneryItems = []; cracks = []; // Clear cracks array
    paused = false;
    // Reset pause button icon and state safely
    if (pauseBtn) { // Check if button exists
        pauseBtn.classList.remove('paused');
        const pauseIconSpan = pauseBtn.querySelector('span'); // Find span inside
        if (pauseIconSpan) { // Check if span exists
            pauseIconSpan.innerHTML = '&#10074;&#10074;'; // Set icon only if span exists
        } else {
            console.error("initGame: Could not find span inside pause button!");
        }
    } else {
         console.error("initGame: Could not find pause button element!");
    }


    // Reset mute button state (optional, could persist)
    // isMuted = false; // Uncomment to always start unmuted
    // Apply current mute state to volumes on init
    for (const soundName in sounds) {
        if (sounds[soundName] instanceof Audio) {
            sounds[soundName].volume = isMuted ? 0 : (defaultVolumes[soundName] || 1.0);
        }
    }
    // Update mute button visual state based on isMuted
    if (muteBtn) {
        muteBtn.classList.toggle('muted', isMuted);
        const muteIconSpan = muteBtn.querySelector('span');
        if (muteIconSpan) {
             muteIconSpan.innerHTML = isMuted ? '&#128263;' : '&#128266;';
        }
    }


    road.innerHTML = ''; sceneryLeft.innerHTML = ''; sceneryRight.innerHTML = '';
    setRandomRoofColor();

    // --- Reset Wheels ---
    availableWheels = ['fl', 'fr', 'rl', 'rr']; // Reset available wheels
    car.classList.remove('hide-fl', 'hide-fr', 'hide-rl', 'hide-rr'); // Remove hiding classes
    // --- End Reset Wheels ---

    if (levelTransitionTimeoutId) clearTimeout(levelTransitionTimeoutId); levelTransitionTimeoutId = null;
    if (levelPopupTimeoutId) clearTimeout(levelPopupTimeoutId); levelPopupTimeoutId = null;
    levelPopup.style.display = 'none'; levelTitleDisplay.style.opacity = 0;

    updateLevel();
    positionCar();

    startScreen.style.display = 'none'; gameOverScreen.style.display = 'none';
    gameContainer.style.opacity = 1;

    playSound('engine');

    if (animationId) cancelAnimationFrame(animationId);
    lastTimestamp = performance.now();
    animationId = requestAnimationFrame(gameLoop);
    console.log("initGame: Finished. Animation ID:", animationId);
}

function createCrack() {
    if (paused || laneWidth <= 0) return;

    // Adjust spawn chance as needed
    const crackSpawnChance = 0.15; // Chance to spawn a crack segment each frame

    if (Math.random() < crackSpawnChance) {
        const crack = document.createElement('div');
        crack.className = 'crack';

        // Choose lane separator (0 for left, 1 for right)
        const separatorIndex = Math.floor(Math.random() * 2);
        const separatorX = laneWidth * (separatorIndex + 1);

        // Random horizontal offset around the separator line
        const offsetX = (Math.random() - 0.5) * 10; // +/- 5px offset

        // Random rotation
        const rotation = (Math.random() - 0.5) * 15; // +/- 7.5 degrees

        crack.style.left = `${separatorX + offsetX - (parseInt(crack.style.width || 3) / 2)}px`; // Center the crack horizontally
        crack.style.top = '-20px'; // Start just above the screen
        crack.style.transform = `rotate(${rotation}deg)`;

        // Avoid spawning directly on top of another recent crack in the same separator area
        const tooClose = cracks.some(c =>
            Math.abs(parseInt(c.element.style.left) - parseInt(crack.style.left)) < 15 &&
            parseInt(c.element.style.top) < 30
        );

        if (!tooClose) {
            road.appendChild(crack);
            cracks.push({ element: crack });
        }
    }
}
function moveCracks() {
    if (paused) return;
    for (let i = cracks.length - 1; i >= 0; i--) {
        const crack = cracks[i]; if (!crack.element || !crack.element.parentNode) { cracks.splice(i, 1); continue; }
        let top = parseInt(crack.element.style.top) + gameSpeed; // Move at game speed
        if (top > gameContainer.offsetHeight) { crack.element.remove(); cracks.splice(i, 1); }
        else { crack.element.style.top = top + 'px'; }
    }
}

// --- Car ---
function positionCar() {
     if (laneWidth <= 0) return; // Prevent errors if width is invalid
     const laneCenters = [laneWidth / 2, laneWidth * 1.5, laneWidth * 2.5];
     car.style.left = (laneCenters[carPosition] - car.offsetWidth / 2) + 'px';
}
function moveCar(direction) {
    if (!gameStarted || gameOver || paused) return;
    if (direction === 'left' && carPosition > 0) carPosition--;
    else if (direction === 'right' && carPosition < 2) carPosition++;
    positionCar();
}

function setRandomRoofColor() {
    const randomColor = roofColors[Math.floor(Math.random() * roofColors.length)];
    car.style.setProperty('--roof-color', randomColor);
}

// --- NEW: Wheel Removal Logic ---
function removeRandomWheel() {
    if (availableWheels.length === 0) {
        console.log("No more wheels to remove!");
        return; // No wheels left
    }

    // Select a random index from the available wheels
    const randomIndex = Math.floor(Math.random() * availableWheels.length);
    const wheelToRemove = availableWheels[randomIndex];

    // Remove the wheel from the available list
    availableWheels.splice(randomIndex, 1);

    // Add the corresponding CSS class to the car to hide the wheel
    const hideClass = `hide-${wheelToRemove}`; // e.g., 'hide-fl'
    car.classList.add(hideClass);
    console.log(`Removed wheel: ${wheelToRemove}. Remaining:`, availableWheels);
}
// --- End Wheel Removal Logic ---

// --- Level Management & Color Update ---
function updateLevel() {
    console.log("updateLevel: Updating level", currentLevelIndex);
    const level = levels[currentLevelIndex]; if (!level) { console.error("updateLevel: Invalid level index", currentLevelIndex); return; }

    // --- Clear previous timeouts ---
    if (levelTransitionTimeoutId) clearTimeout(levelTransitionTimeoutId);
    levelTransitionTimeoutId = null;
    if (levelPopupTimeoutId) clearTimeout(levelPopupTimeoutId);
    levelPopupTimeoutId = null;
    levelPopup.style.display = 'none'; // Hide previous popup immediately
    levelTitleDisplay.style.opacity = 0; // Hide previous title immediately


    // --- Update Speeds, Colors, Engine Sound ---
    displayedSpeedBaseline = Math.min(MAX_DISPLAYED_SPEED, baseDisplayedSpeed + (currentLevelIndex * speedIncrementPerLevel));
    // If not currently slowed by a hit, apply target speed immediately; otherwise keep slowdown until it restores
    if (!speedRestoreTimeout) {
        setCurrentDisplayedSpeed(getTargetDisplayedSpeed());
    }
    console.log(`updateLevel: Speeds - Baseline=${displayedSpeedBaseline}, Displayed=${currentDisplayedSpeed}, Game=${gameSpeed.toFixed(2)}`);

    const style = gameContainer.style;
    style.setProperty('--bg-color', level.bgColor);
    style.setProperty('--road-color', level.roadColor);
    style.setProperty('--tree-trunk-color', level.treeTrunkColor);
    style.setProperty('--tree-foliage-color', level.treeFoliageColor);
    style.setProperty('--hole-border-color', level.holeBorderColor);
    style.setProperty('--hole-fill-color', level.holeFillColor);
    style.setProperty('--hole-water-border-color', level.waterHoleBorderColor);
    style.setProperty('--hole-water-fill-color', level.waterHoleFillColor);
    gameContainer.style.backgroundColor = level.bgColor;
    console.log("updateLevel: Colors set for level", currentLevelIndex);
    setRandomRoofColor();

    if (sounds.engine) {
        const minSpeed = baseDisplayedSpeed; const maxSpeed = baseDisplayedSpeed + ((levels.length - 1) * speedIncrementPerLevel);
        const minRate = 0.8; const maxRate = 1.5;
        const speedRatio = (currentDisplayedSpeed - minSpeed) / (maxSpeed - minSpeed);
        sounds.engine.playbackRate = minRate + (speedRatio * (maxRate - minRate));
    }

    updateGameUI(); // Update score, lives, etc. immediately

    // --- DELAYED display of Title and Popup ---
    levelTransitionTimeoutId = setTimeout(() => {
        // Show Level Popup
        const popupContent = levelPopups[currentLevelIndex] || '?';
        levelPopup.innerHTML = popupContent;
        levelPopup.style.display = 'flex';
        levelPopupTimeoutId = setTimeout(() => { // Hide popup after its own delay
            levelPopup.style.display = 'none';
            levelPopupTimeoutId = null;
        }, 3500); // Popup visible for 3.5s

        // Display level transition title (Fading Text)
        levelTitleDisplay.textContent = level.name;
        levelTitleDisplay.style.opacity = 1;
        setTimeout(() => { levelTitleDisplay.style.opacity = 0; }, 2500); // Title fades out after 2.5s

        // Speak level name (can happen immediately or be delayed too)
        speakText(level.name);

        levelTransitionTimeoutId = null; // Clear the ID after execution
    }, 1000); // <<< Wait 1 second (1000ms) before showing title/popup
    // --- End DELAYED display ---
}

// --- Boreholes ---
function createBorehole() {
    if (paused || laneWidth <= 0) return;
    const spawnChance = 0.04 + (currentLevelIndex * 0.005);
    if (Math.random() < spawnChance) {
         const lane = Math.floor(Math.random() * 3);

         // --- MODIFICATION START: Adjust small hole probability ---
         const baseSmallHoleChance = 0.4; // Chance of small hole at level 0 (40%)
         const smallHoleReductionPerLevel = 0.05; // Reduce chance by 5% per level
         const minSmallHoleChance = 0.1; // Minimum chance of small hole (10%)
         const currentSmallHoleChance = Math.max(minSmallHoleChance, baseSmallHoleChance - (currentLevelIndex * smallHoleReductionPerLevel));
         const isSmallCheck = Math.random() < currentSmallHoleChance;
         // --- MODIFICATION END ---

         let potentialDamage = true; if (isSmallCheck) potentialDamage = false;
         if (potentialDamage) { const otherLanes = [0, 1, 2].filter(l => l !== lane); const checkRangeTop = -150; const checkRangeBottom = car.offsetTop - 50; const lane1Blocked = boreholes.some(b => b.damage && b.lane === otherLanes[0] && parseInt(b.element.style.top) > checkRangeTop && parseInt(b.element.style.top) < checkRangeBottom); const lane2Blocked = boreholes.some(b => b.damage && b.lane === otherLanes[1] && parseInt(b.element.style.top) > checkRangeTop && parseInt(b.element.style.top) < checkRangeBottom); if (lane1Blocked && lane2Blocked) return; }
         const tooClose = boreholes.some(b => b.lane === lane && parseInt(b.element.style.top) < 50); if (tooClose) return;

         const borehole = document.createElement('div');
         borehole.className = 'borehole';
         let holeType = 'normal', points = 10, damage = true, sizeW = 70, sizeH = 70, hasWater = false;

         if (isSmallCheck) {
             holeType = 'small'; points = 5; damage = false; borehole.classList.add('small');
             if (Math.random() < 0.5) { sizeW = 40 + Math.random() * 10; sizeH = 20 + Math.random() * 10; }
             else { sizeW = 20 + Math.random() * 10; sizeH = 40 + Math.random() * 10; }
             if (Math.random() < 0.2) hasWater = true;
         } else { // This is now more likely at higher levels
             holeType = 'large_elliptical'; sizeW = 60 + Math.random() * 40; sizeH = 30 + Math.random() * 20; points = 15; damage = true;
             if (Math.random() < 0.4) hasWater = true;
             borehole.style.borderRadius = '50% / 50%';
         }

         if (hasWater) borehole.classList.add('water');
         borehole.style.width = sizeW + 'px'; borehole.style.height = sizeH + 'px';
         borehole.style.left = (lane * laneWidth) + (laneWidth / 2 - sizeW / 2) + 'px';
         borehole.style.top = -(sizeH + 10) + 'px';

         // --- Add Random Shadow ---
         const shadowX = Math.random() < 0.5 ? Math.random() * 4 + 2 : -(Math.random() * 4 + 2);
         const shadowY = Math.random() < 0.5 ? Math.random() * 4 + 2 : -(Math.random() * 4 + 2);
         const shadowBlur = 4 + Math.random() * 3;
         const shadowColor = 'rgba(0, 0, 0, 0.4)';
         borehole.style.boxShadow = `${shadowX.toFixed(1)}px ${shadowY.toFixed(1)}px ${shadowBlur.toFixed(1)}px ${shadowColor}`;
         // --- End Random Shadow ---

         road.appendChild(borehole);
         boreholes.push({ element: borehole, lane, type: holeType, sizeW, sizeH, points, damage, hit: false, hasWater });
    }
}
function moveBoreholes() {
     if (paused) return;
     for (let i = boreholes.length - 1; i >= 0; i--) {
        const borehole = boreholes[i]; if (!borehole.element || !borehole.element.parentNode) { boreholes.splice(i, 1); continue; }
        let top = parseInt(borehole.element.style.top) + boreholeSpeed;
        if (top > gameContainer.offsetHeight) { borehole.element.remove(); if (!borehole.hit || !borehole.damage) { score += borehole.points; updateGameUI(); } boreholes.splice(i, 1); const nextLevelIndex = currentLevelIndex + 1; if (nextLevelIndex < levels.length && score >= levels[nextLevelIndex].scoreThreshold) { currentLevelIndex = nextLevelIndex; updateLevel(); playSound('levelUp'); } }
        else { borehole.element.style.top = top + 'px'; if (!gameOver && (!borehole.hit || !borehole.damage)) { const carRect = car.getBoundingClientRect(); const holeRect = borehole.element.getBoundingClientRect(); if (borehole.lane === carPosition && carRect.bottom > holeRect.top + (borehole.sizeH * 0.15) && carRect.top < holeRect.bottom - (borehole.sizeH * 0.15)) { handleCollision(borehole); } } }
    }
}
function handleCollision(borehole) {
    // Small holes no longer removed immediately
    if (borehole.damage && !borehole.hit) {
        borehole.hit = true;

        // Check if this is the last life BEFORE decrementing
        const isLastLife = (lives === 1);

        lives--; // Decrement lives
        updateGameUI(); // Update display (hearts will reduce)
        removeRandomWheel();
        applyHitSlowdown();

        // Play appropriate sound
        if (isLastLife) {
            playSound('last_collision'); // Play special sound for the final hit
        } else {
            playSound('collision'); // Play normal collision sound
        }

        // Visual feedback
        car.style.background = '#ffcccc';
        setTimeout(() => { if (!gameOver) car.style.background = '#e6e1d2'; }, 300);

        // Check game over AFTER decrementing and playing sound
        if (lives <= 0) {
            endGame();
            return;
        }
    } else if (!borehole.damage && !borehole.hit) { // Only process non-damaging hit once
         borehole.hit = true; // Mark as hit so sound doesn't repeat
         playSound('passSmallHole');
         // DO NOT REMOVE ELEMENT HERE ANYMORE
    }
}

// --- Scenery (Trees and Flowers) ---
function createScenery() {
     if (paused || laneWidth <= 0) return;
     if (Math.random() < 0.08) {
         const side = Math.random() < 0.5 ? 'left' : 'right';
         const container = (side === 'left') ? sceneryLeft : sceneryRight;
         let element, itemWidth = 0;
         if (Math.random() < 0.7) { element = document.createElement('div'); const treeType = Math.floor(Math.random() * 3) + 1; element.className = `tree type${treeType}`; itemWidth = 30; }
         else { element = document.createElement('div'); const colors = ['yellow', 'white', 'blue', 'red', 'purple', 'orange', '']; const randomColor = colors[Math.floor(Math.random() * colors.length)]; element.className = `flower ${randomColor}`.trim(); itemWidth = 15; }
         element.style.top = '-60px';
         const containerWidth = container.offsetWidth; const randomOffset = Math.random() * (containerWidth - itemWidth); element.style.left = randomOffset + 'px';
         container.appendChild(element); sceneryItems.push(element);
    }
}
function moveScenery() {
    if (paused) return;
    for (let i = sceneryItems.length - 1; i >= 0; i--) { const item = sceneryItems[i]; if (!item || !item.parentNode) { sceneryItems.splice(i, 1); continue; } let top = parseInt(item.style.top) + gameSpeed; if (top > gameContainer.offsetHeight) { item.remove(); sceneryItems.splice(i, 1); } else { item.style.top = top + 'px'; } }
}

// --- UI Update Function ---
function updateGameUI() {
    scoreDisplay.textContent = "SCORE: " + score; // Update only the number

    let heartsHTML = ''; for (let i = 0; i < lives; i++) heartsHTML += '❤️';
    livesDisplay.innerHTML = heartsHTML; // Only hearts

    const level = levels[currentLevelIndex];
    if (level) {
         levelDisplay.textContent = `${currentLevelIndex + 1}: ${level.name}`; // Removed "NÍVEL"
    } else {
         levelDisplay.textContent = `${currentLevelIndex + 1}: ???`;
    }
    speedValueDisplay.textContent = currentDisplayedSpeed;
}

// --- Game Over ---
function endGame() {
    if (gameOver) return;
    gameOver = true; gameStarted = false; paused = false;
    if (animationId) cancelAnimationFrame(animationId); animationId = null;
    stopSound('engine'); playSound('gameOver');
    const levelName = levels[currentLevelIndex].name;

    // Clear level transition timeouts on game over
    if (levelTransitionTimeoutId) clearTimeout(levelTransitionTimeoutId); levelTransitionTimeoutId = null;
    if (levelPopupTimeoutId) clearTimeout(levelPopupTimeoutId); levelPopupTimeoutId = null;
    levelPopup.style.display = 'none'; levelTitleDisplay.style.opacity = 0;

    // Update existing elements instead of replacing innerHTML if possible
    if (finalScoreDisplay) { // Check if the span exists
        finalScoreDisplay.textContent = score;
    } else {
        console.error("endGame: Could not find final-score span!");
    }

    const gameOverText = gameOverScreen.querySelector('p'); // Find the existing p
    if (gameOverText) {
         // Update the text part, keeping the span structure
         gameOverText.innerHTML = `Pontuação: <span id="final-score">${score}</span><br/>RIP em ${levelName}`;
         // Re-select finalScoreDisplay just in case innerHTML replacement affected the reference
         const updatedFinalScoreDisplay = gameOverScreen.querySelector('#final-score');
         if (updatedFinalScoreDisplay) updatedFinalScoreDisplay.textContent = score;

    } else {
         console.error("endGame: Could not find the <p> tag inside game over screen!");
    }

    gameOverScreen.style.display = 'flex';

    // Reset pause button visually safely
    if (pauseBtn) {
        pauseBtn.classList.remove('paused');
        const pauseIconSpan = pauseBtn.querySelector('span');
        if (pauseIconSpan) {
            pauseIconSpan.innerHTML = '&#10074;&#10074;';
        }
    }
}

// --- Pause Function ---
function togglePause() {
    if (!gameStarted || gameOver) return;
    paused = !paused;
    if (pauseBtn) { // Check if pauseBtn exists
        pauseBtn.classList.toggle('paused', paused); // Toggle class
        const pauseIconSpan = pauseBtn.querySelector('span');
        if (pauseIconSpan) {
            pauseIconSpan.innerHTML = paused ? '&#9654;' : '&#10074;&#10074;'; // Play vs Pause icon
        }
    }

    if (paused) {
        if (animationId) cancelAnimationFrame(animationId);
        animationId = null;
        if (sounds.engine) sounds.engine.pause();
    } else {
        lastTimestamp = performance.now();
        gameLoop(lastTimestamp);
        if (sounds.engine) sounds.engine.play();
    }
}

// --- Speech Synthesis ---
function speakText(text) {
    if ('speechSynthesis' in window) {
        console.log(`speakText called for: "${text}", isMuted: ${isMuted}`); // Log call
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'pt-PT';
        utterance.pitch = 1;
        utterance.rate = 1;
        utterance.volume = isMuted ? 0 : 0.8; // Use mute state for volume
        console.log(`speakText volume set to: ${utterance.volume}`); // Log volume
        window.speechSynthesis.speak(utterance);
    } else {
        console.warn("Speech Synthesis not supported by this browser.");
    }
}
// Pre-load voices if needed (sometimes helps)
if ('speechSynthesis' in window && window.speechSynthesis.onvoiceschanged !== undefined) {
    window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
}
// --- END Speech Synthesis ---

// --- Main Game Loop ---
function gameLoop(timestamp) {
    if (paused || gameOver) { if (animationId) cancelAnimationFrame(animationId); animationId = null; return; }
    lastTimestamp = timestamp;
    try { createBorehole();
        moveBoreholes();
        createScenery();
        moveScenery();
        createCrack();
        moveCracks();
    }
     // Removed horseshit for now
    catch (error) { console.error("Error within game loop:", error); gameOver = true; return; }
    animationId = requestAnimationFrame(gameLoop);
}

// --- Event Listeners ---
document.addEventListener('keydown', (e) => {
    if (e.code === 'Space' || e.key === ' ') {
        e.preventDefault();
        // Check game state for Spacebar action
        if (gameOverScreen.style.display === 'flex') { // If game over screen is showing
            console.log("Space pressed: Restarting game");
            restartBtn.click(); // Simulate click on restart button
        } else if (startScreen.style.display !== 'none') { // If start screen is showing
             console.log("Space pressed: Starting game");
             startBtn.click(); // Simulate click on start button
        } else if (gameStarted && !gameOver) { // If game is running
            console.log("Space pressed: Toggling pause");
            togglePause(); // Toggle pause/resume
        }
        return;
    }
    // Movement keys only work if game is running and not paused
    if (!gameStarted || gameOver || paused) return;
    if (e.key === 'ArrowLeft' || e.key === 'a' || e.key === 'A') moveCar('left');
    else if (e.key === 'ArrowRight' || e.key === 'd' || e.key === 'D') moveCar('right');
    else if (e.key === 'ArrowUp' || e.key === 'w' || e.key === 'W') { changePlayerSpeed(SPEED_STEP); }
    else if (e.key === 'ArrowDown' || e.key === 's' || e.key === 'S') { changePlayerSpeed(-SPEED_STEP); }
});
leftBtn.addEventListener('click', () => moveCar('left')); rightBtn.addEventListener('click', () => moveCar('right'));
leftBtn.addEventListener('touchstart', (e) => { if (!paused) { e.preventDefault(); moveCar('left'); } }); rightBtn.addEventListener('touchstart', (e) => { if (!paused) { e.preventDefault(); moveCar('right'); } });
pauseBtn.addEventListener('click', togglePause);
muteBtn.addEventListener('click', toggleMute); // Add listener for mute button
startBtn.addEventListener('click', initGame);
restartBtn.addEventListener('click', initGame);

// --- Initial Setup on Load ---
console.log("Initial setup: Updating UI and positioning car.");
updateGameUI();
positionCar();
// Set initial mute button state based on isMuted
if (muteBtn) { // Add check for muteBtn existence
    muteBtn.classList.toggle('muted', isMuted);
    const muteIconSpan = muteBtn.querySelector('span');
    if (muteIconSpan) {
        muteIconSpan.innerHTML = isMuted ? '&#128263;' : '&#128266;';
    }
}
console.log("Initial setup: Complete. Waiting for Start button.");
