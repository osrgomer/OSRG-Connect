let isGenerating = false
let logs = []

const btn = document.getElementById("generateBtn")
const logsEl = document.getElementById("logs")
const textEl = document.getElementById("text")
const voiceEl = document.getElementById("voice")
const personaEl = document.getElementById("persona")
const clearBtn = document.getElementById("clearLogs")

function addLog(msg) {
  const time = new Date().toLocaleTimeString("en-GB")
  logs.unshift(`[${time}] ${msg}`)
  logs = logs.slice(0, 15)
  logsEl.innerHTML = logs.map(l => `<div class="mb-1">${l}</div>`).join("")
}

// clear logs
clearBtn.onclick = () => {
  logs = []
  logsEl.innerHTML = "Awaiting Pipeline Input"
}

// Wait for voices to load
function waitForVoices() {
  return new Promise(resolve => {
    if (speechSynthesis.getVoices().length > 0) {
      resolve()
    } else {
      speechSynthesis.onvoiceschanged = () => resolve()
    }
  })
}

// browser TTS handler
btn.onclick = async () => {
  if (isGenerating) return
  const text = textEl.value.trim()
  if (!text) {
    addLog("Error: No text provided")
    return
  }

  const voiceName = voiceEl.value
  const persona = personaEl.value

  addLog(`Initiating request for ${voiceName}...`)
  isGenerating = true
  btn.textContent = "Speaking..."
  btn.disabled = true

  try {
    // Wait for voices to be available
    await waitForVoices()
    
    speechSynthesis.cancel()
    const utterance = new SpeechSynthesisUtterance(text)

    // Set voice properties
    const voices = speechSynthesis.getVoices()
    let selectedVoice = null
    
    // Try to find the requested voice
    if (voiceName && voiceName !== 'default') {
      selectedVoice = voices.find(v => 
        v.name.toLowerCase().includes(voiceName.toLowerCase())
      )
    }
    
    // Fallback to first English voice if specific voice not found
    if (!selectedVoice) {
      selectedVoice = voices.find(v => v.lang.startsWith('en')) || voices[0]
    }
    
    if (selectedVoice) {
      utterance.voice = selectedVoice
      addLog(`Using voice: ${selectedVoice.name}`)
    } else {
      addLog(`Using system default voice`)
    }

    // Adjust speech based on persona
    switch(persona.toLowerCase()) {
      case 'cheerful':
        utterance.pitch = 1.2
        utterance.rate = 1.1
        break
      case 'serious':
        utterance.pitch = 0.8
        utterance.rate = 0.9
        break
      case 'calm':
        utterance.pitch = 1.0
        utterance.rate = 0.8
        break
      default:
        utterance.pitch = 1.0
        utterance.rate = 1.0
    }

    utterance.onstart = () => {
      addLog("Playback started successfully.")
    }

    utterance.onend = () => {
      isGenerating = false
      btn.textContent = "Generate Voice"
      btn.disabled = false
      addLog("Playback finished.")
    }

    utterance.onerror = (event) => {
      addLog(`Speech Error: ${event.error}`)
      isGenerating = false
      btn.textContent = "Generate Voice"
      btn.disabled = false
    }

    speechSynthesis.speak(utterance)

  } catch (err) {
    addLog("Speech Error: " + err.message)
    isGenerating = false
    btn.textContent = "Generate Voice"
    btn.disabled = false
  }
}

// Initialize voices when page loads
window.addEventListener('load', async () => {
  await waitForVoices()
  addLog("Speech synthesis ready")
  addLog(`Available voices: ${speechSynthesis.getVoices().length}`)
})