<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string
    loading?: boolean
    placeholder?: string
    disabled?: boolean
  }>(),
  {
    loading: false,
    placeholder: 'Rechercher…',
    disabled: false
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', v: string): void
  (e: 'submit'): void
  (e: 'clear'): void
}>()

const value = computed({
  get: () => props.modelValue,
  set: (v: string) => emit('update:modelValue', v)
})

const hasValue = computed(() => value.value.trim().length > 0)

function onSubmit() {
  emit('submit')
}

function clear() {
  emit('update:modelValue', '')
  emit('clear')
}
</script>

<template>
  <form class="search" @submit.prevent="onSubmit">
    <span class="search__icon" aria-hidden="true">⌕</span>

    <input
      v-model="value"
      class="search__input"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      autocomplete="off"
      spellcheck="false"
    />

    <div class="search__right">
      <button
        v-if="hasValue"
        class="search__btn"
        type="button"
        :disabled="disabled"
        @click="clear"
        aria-label="Effacer"
      >
        ✕
      </button>

      <span v-if="loading" class="search__spinner" aria-label="Chargement" />
    </div>
  </form>
</template>

<style scoped>
.search {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  border-radius: 14px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-2);
  transition: border-color 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
}

.search:focus-within {
  border-color: rgba(29, 185, 84, 0.65);
  box-shadow: 0 0 0 4px rgba(29, 185, 84, 0.14);
  background: #193024;
}

.search__icon {
  position: absolute;
  left: 12px;
  color: var(--c-text-mute);
  font-size: 14px;
  pointer-events: none;
}

.search__input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  color: var(--c-text);
  padding: 12px 44px 12px 34px;
  font-size: 14px;
}

.search__input::placeholder {
  color: rgba(245, 248, 252, 0.55);
}

.search__right {
  position: absolute;
  right: 10px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.search__btn {
  border: 1px solid var(--c-border);
  background: var(--c-surface-3);
  color: var(--c-text);
  border-radius: 10px;
  padding: 6px 9px;
  cursor: pointer;
}

.search__btn:hover {
  background: #193024;
  border-color: var(--c-border-2);
}

.search__btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.search__spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(245, 248, 252, 0.22);
  border-top-color: rgba(29, 185, 84, 0.95);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
