import { ref } from 'vue';

/**
 * Professional Web Audio API sound engine for the Lottery / Prize Wheel.
 * Zero external audio assets required; generates crisp mechanical wheel ticks
 * synchronized to reel speed and a multi-layered triumphant jackpot fanfare.
 */
export function useLotreAudio() {
  const isMuted = ref(
    typeof window !== 'undefined'
      ? localStorage.getItem('lotre_sound_muted') === 'true'
      : false,
  );

  let audioCtx = null;
  let noiseBuffer = null;
  let masterCompressor = null;

  /**
   * Lazily initialize or resume the Web Audio API context and shared buffers.
   * Must be called inside a user gesture (e.g., button click) for browser autoplay compliance.
   */
  const ensureAudioContext = () => {
    if (typeof window === 'undefined') return null;

    if (!audioCtx) {
      const AudioContextClass = window.AudioContext || window.webkitAudioContext;
      if (!AudioContextClass) return null;
      audioCtx = new AudioContextClass();

      // Master compressor prevents clipping when fanfare + bells + applause overlap
      masterCompressor = audioCtx.createDynamicsCompressor();
      masterCompressor.threshold.setValueAtTime(-12, audioCtx.currentTime);
      masterCompressor.knee.setValueAtTime(18, audioCtx.currentTime);
      masterCompressor.ratio.setValueAtTime(6, audioCtx.currentTime);
      masterCompressor.attack.setValueAtTime(0.003, audioCtx.currentTime);
      masterCompressor.release.setValueAtTime(0.2, audioCtx.currentTime);
      masterCompressor.connect(audioCtx.destination);

      // Pre-generate 3 seconds of white noise buffer for ticks & applause
      const sampleRate = audioCtx.sampleRate;
      const bufferSize = sampleRate * 3;
      noiseBuffer = audioCtx.createBuffer(1, bufferSize, sampleRate);
      const data = noiseBuffer.getChannelData(0);
      for (let i = 0; i < bufferSize; i++) {
        data[i] = Math.random() * 2 - 1;
      }
    }

    if (audioCtx.state === 'suspended') {
      audioCtx.resume().catch(() => {});
    }

    return audioCtx;
  };

  /**
   * Toggle mute state and persist to localStorage.
   */
  const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (typeof window !== 'undefined') {
      localStorage.setItem('lotre_sound_muted', String(isMuted.value));
    }
    if (!isMuted.value) {
      ensureAudioContext();
      playClick();
    }
  };

  /**
   * Subtle tactile button click sound.
   */
  const playClick = () => {
    if (isMuted.value) return;
    const ctx = ensureAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(680, now);
    osc.frequency.exponentialRampToValueAtTime(280, now + 0.045);

    gain.gain.setValueAtTime(0.18, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05);

    osc.connect(gain);
    gain.connect(masterCompressor);

    osc.start(now);
    osc.stop(now + 0.055);
  };

  /**
   * Play a single crisp mechanical prize-wheel peg tick ("drt" / "tak").
   * Combines a percussive pitch-drop body with a short filtered woody click.
   *
   * @param {number} volumeScale 0.4 - 1.0 depending on wheel speed/deceleration
   */
  const playWheelTick = (volumeScale = 0.85) => {
    if (isMuted.value) return;
    const ctx = ensureAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;
    // Slight organic pitch variation (+/- 7%) so rapid ticks sound like a real physical wheel
    const pitchJitter = 0.93 + Math.random() * 0.14;

    // 1. Percussive wooden/plastic peg body
    const osc = ctx.createOscillator();
    const oscGain = ctx.createGain();
    osc.type = 'triangle';
    osc.frequency.setValueAtTime(980 * pitchJitter, now);
    osc.frequency.exponentialRampToValueAtTime(210 * pitchJitter, now + 0.022);

    oscGain.gain.setValueAtTime(0.24 * volumeScale, now);
    oscGain.gain.exponentialRampToValueAtTime(0.001, now + 0.026);

    osc.connect(oscGain);
    oscGain.connect(masterCompressor);
    osc.start(now);
    osc.stop(now + 0.03);

    // 2. Crisp transient snap (filtered white noise)
    if (noiseBuffer) {
      const noise = ctx.createBufferSource();
      noise.buffer = noiseBuffer;

      const filter = ctx.createBiquadFilter();
      filter.type = 'bandpass';
      filter.frequency.setValueAtTime(2400 * pitchJitter, now);
      filter.Q.setValueAtTime(2.2, now);

      const noiseGain = ctx.createGain();
      noiseGain.gain.setValueAtTime(0.22 * volumeScale, now);
      noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.018);

      noise.connect(filter);
      filter.connect(noiseGain);
      noiseGain.connect(masterCompressor);

      noise.start(now, Math.random() * 0.5, 0.02);
    }
  };

  /**
   * Helper: Play a warm brass/synth fanfare note with rich harmonics.
   */
  const playBrassNote = (ctx, freq, startTime, duration, volume = 0.18) => {
    const osc1 = ctx.createOscillator();
    const osc2 = ctx.createOscillator();
    const filter = ctx.createBiquadFilter();
    const gain = ctx.createGain();

    // Slightly detuned sawtooth + triangle for rich stadium brass texture
    osc1.type = 'sawtooth';
    osc1.frequency.setValueAtTime(freq, startTime);

    osc2.type = 'triangle';
    osc2.frequency.setValueAtTime(freq * 1.003, startTime);

    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(1200, startTime);
    filter.frequency.exponentialRampToValueAtTime(4200, startTime + 0.04);
    filter.frequency.exponentialRampToValueAtTime(2200, startTime + duration);

    gain.gain.setValueAtTime(0.001, startTime);
    gain.gain.linearRampToValueAtTime(volume, startTime + 0.025);
    gain.gain.setValueAtTime(volume * 0.85, startTime + duration * 0.7);
    gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

    osc1.connect(filter);
    osc2.connect(filter);
    filter.connect(gain);
    gain.connect(masterCompressor);

    osc1.start(startTime);
    osc2.start(startTime);
    osc1.stop(startTime + duration + 0.02);
    osc2.stop(startTime + duration + 0.02);
  };

  /**
   * Helper: Play a sparkling high bell/chime note for jackpot shimmer.
   */
  const playBellChime = (ctx, freq, startTime, duration = 0.7, volume = 0.14) => {
    const osc = ctx.createOscillator();
    const overtone = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(freq, startTime);

    overtone.type = 'sine';
    overtone.frequency.setValueAtTime(freq * 2.0, startTime);

    const overtoneGain = ctx.createGain();
    overtoneGain.gain.setValueAtTime(0.35, startTime);

    gain.gain.setValueAtTime(0.001, startTime);
    gain.gain.linearRampToValueAtTime(volume, startTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0008, startTime + duration);

    osc.connect(gain);
    overtone.connect(overtoneGain);
    overtoneGain.connect(gain);
    gain.connect(masterCompressor);

    osc.start(startTime);
    overtone.start(startTime);
    osc.stop(startTime + duration + 0.02);
    overtone.stop(startTime + duration + 0.02);
  };

  /**
   * Helper: Play punchy bass/timpani impact for excitement.
   */
  const playDrumImpact = (ctx, startTime, volume = 0.35) => {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(150, startTime);
    osc.frequency.exponentialRampToValueAtTime(46, startTime + 0.22);

    gain.gain.setValueAtTime(volume, startTime);
    gain.gain.exponentialRampToValueAtTime(0.001, startTime + 0.32);

    osc.connect(gain);
    gain.connect(masterCompressor);

    osc.start(startTime);
    osc.stop(startTime + 0.35);
  };

  /**
   * Helper: Play celebratory crowd cheer / applause / cymbal shimmer swell.
   */
  const playCelebrationApplause = (ctx, startTime, duration = 2.4) => {
    if (!noiseBuffer) return;

    const noise = ctx.createBufferSource();
    noise.buffer = noiseBuffer;
    noise.loop = true;

    // Bandpass shapes noise into warm stadium applause + cymbal shimmer
    const bandpass = ctx.createBiquadFilter();
    bandpass.type = 'bandpass';
    bandpass.frequency.setValueAtTime(1800, startTime);
    bandpass.Q.setValueAtTime(0.7, startTime);

    const highShelf = ctx.createBiquadFilter();
    highShelf.type = 'highshelf';
    highShelf.frequency.setValueAtTime(3500, startTime);
    highShelf.gain.setValueAtTime(4, startTime);

    // Subtle LFO flutter for clapping texture
    const lfo = ctx.createOscillator();
    const lfoGain = ctx.createGain();
    lfo.frequency.setValueAtTime(9.5, startTime);
    lfoGain.gain.setValueAtTime(0.04, startTime);

    const gain = ctx.createGain();
    gain.gain.setValueAtTime(0.001, startTime);
    gain.gain.linearRampToValueAtTime(0.22, startTime + 0.18);
    gain.gain.setValueAtTime(0.20, startTime + duration * 0.45);
    gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

    lfo.connect(lfoGain);
    lfoGain.connect(gain.gain);

    noise.connect(bandpass);
    bandpass.connect(highShelf);
    highShelf.connect(gain);
    gain.connect(masterCompressor);

    noise.start(startTime);
    lfo.start(startTime);
    noise.stop(startTime + duration + 0.05);
    lfo.stop(startTime + duration + 0.05);
  };

  /**
   * Grand celebratory lottery winner fanfare!
   * Plays a triumphant rising brass arpeggio -> sustained victory chord ->
   * cascading jackpot bells -> festive drum impact & crowd applause.
   */
  const playWinnerFanfare = () => {
    if (isMuted.value) return;
    const ctx = ensureAudioContext();
    if (!ctx) return;

    const now = ctx.currentTime;

    // 1. Initial drum impact
    playDrumImpact(ctx, now, 0.35);

    // 2. Rising triumphant fanfare arpeggio: C5 -> E5 -> G5 -> C6!
    playBrassNote(ctx, 523.25, now + 0.00, 0.13, 0.22); // C5
    playBrassNote(ctx, 659.25, now + 0.10, 0.13, 0.22); // E5
    playBrassNote(ctx, 783.99, now + 0.20, 0.14, 0.24); // G5
    playBrassNote(ctx, 1046.50, now + 0.31, 0.14, 0.26); // C6

    // Quick pickup note G5 -> Grand Victory Chord!
    playBrassNote(ctx, 783.99, now + 0.44, 0.11, 0.22); // G5

    // 3. Grand Sustained Major Chord (C5 + E5 + G5 + C6) at 0.55s
    const chordStart = now + 0.55;
    playDrumImpact(ctx, chordStart, 0.42);
    playBrassNote(ctx, 523.25, chordStart, 1.55, 0.16); // C5
    playBrassNote(ctx, 659.25, chordStart, 1.55, 0.16); // E5
    playBrassNote(ctx, 783.99, chordStart, 1.55, 0.17); // G5
    playBrassNote(ctx, 1046.50, chordStart, 1.65, 0.20); // C6

    // 4. Jackpot Sparkling Bell Cascade
    const bellNotes = [1046.50, 1318.51, 1567.98, 2093.00, 2637.02, 2093.00, 2637.02];
    bellNotes.forEach((freq, idx) => {
      playBellChime(ctx, freq, chordStart + idx * 0.085, 0.85, 0.14);
    });

    // 5. Festive Crowd Applause & Celebration Swell
    playCelebrationApplause(ctx, chordStart - 0.05, 2.3);
  };

  return {
    isMuted,
    toggleMute,
    ensureAudioContext,
    playClick,
    playWheelTick,
    playWinnerFanfare,
  };
}
