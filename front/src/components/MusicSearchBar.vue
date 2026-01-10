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
    <input
      class="searchbar__input"
      :value="props.modelValue"
      :placeholder="props.placeholder"
      type="text"
      @input="onInput"
      @keydown="onKeydown"
    />
  </div>
</template>

<style scoped>
.searchbar {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.searchbar__input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: white;
}

.searchbar__status {
  font-size: 12px;
  opacity: 0.7;
}
</style>
