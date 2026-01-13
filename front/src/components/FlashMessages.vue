<template>
  <div class="flash" aria-live="polite" aria-relevant="additions removals">
    <TransitionGroup name="toast" tag="div" class="flash__stack">
      <div
        v-for="m in flash.messages"
        :key="m.id"
        class="flash__toast"
        :class="`flash__toast--${m.type}`"
        role="status"
      >
        <div class="flash__dot" />
        <div class="flash__text">{{ m.text }}</div>
        <button class="flash__close" type="button" @click="flash.remove(m.id)" aria-label="Fermer">
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { useFlashStore } from '@/stores/flashStore'

const flash = useFlashStore()
</script>

<style scoped>
.flash {
  position: fixed;
  top: 76px;
  right: 18px;
  z-index: 2000;
  pointer-events: none;
}

.flash__stack {
  display: grid;
  gap: 10px;
}

.flash__toast {
  width: min(420px, calc(100vw - 36px));
  pointer-events: auto;
  display: grid;
  grid-template-columns: 10px 1fr auto;
  align-items: center;
  gap: 10px;
  padding: 12px 12px;
  border-radius: 14px;
  border: 1px solid var(--c-border);
  background: rgba(12, 18, 26, 0.88);
  backdrop-filter: blur(10px);
  box-shadow: var(--shadow-2);
}

.flash__dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: var(--c-info);
}

.flash__text {
  color: var(--c-text);
  font-size: 14px;
  line-height: 1.35;
}

.flash__close {
  background: transparent;
  color: var(--c-text-mute);
  border: none;
  cursor: pointer;
  padding: 6px 8px;
  border-radius: 10px;
}

.flash__close:hover {
  color: var(--c-text);
  background: rgba(255, 255, 255, 0.06);
}

.flash__toast--success {
  border-color: rgba(29, 185, 84, 0.35);
}

.flash__toast--success .flash__dot {
  background: var(--c-green);
}

.flash__toast--error {
  border-color: rgba(255, 77, 79, 0.35);
}

.flash__toast--error .flash__dot {
  background: var(--c-danger);
}

.flash__toast--warning {
  border-color: rgba(255, 176, 32, 0.35);
}

.flash__toast--warning .flash__dot {
  background: var(--c-warning);
}

.flash__toast--info {
  border-color: rgba(77, 163, 255, 0.35);
}

.flash__toast--info .flash__dot {
  background: var(--c-info);
}

.toast-enter-active,
.toast-leave-active {
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@media (max-width: 560px) {
  .flash {
    top: 66px;
    right: 12px;
  }
}
</style>
