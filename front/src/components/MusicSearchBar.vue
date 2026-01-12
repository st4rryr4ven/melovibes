<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    modelValue: string
    placeholder?: string
    loading?: boolean
  }>(),
  {
    placeholder: 'Rechercher une musique...',
    loading: false
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'submit'): void
}>()

function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLInputElement).value)
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter') emit('submit')
}
</script>

<template>
  <div class="searchbar">
    <div class="searchbar__row">
      <input
        class="searchbar__input"
        :value="props.modelValue"
        :placeholder="props.placeholder"
        type="text"
        @input="onInput"
        @keydown="onKeydown"
      />
      <button type="button" class="searchbar__btn" :disabled="props.loading" @click="emit('submit')">
        Rechercher
      </button>
    </div>

    <div v-if="props.loading" class="searchbar__status">Recherche...</div>
  </div>
</template>

<style scoped>
.searchbar {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.searchbar__row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
  align-items: center;
}

.searchbar__input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: white;
}

.searchbar__btn {
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  cursor: pointer;
}

.searchbar__btn:disabled {
  cursor: not-allowed;
  opacity: 0.75;
}

.searchbar__status {
  font-size: 12px;
  opacity: 0.7;
}
</style>
