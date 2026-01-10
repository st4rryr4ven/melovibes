import { ref, watch, type Ref } from 'vue'

export function useDebouncedRef<T>(source: Ref<T>, delayMs: number): Ref<T> {
  const debounced = ref(source.value) as Ref<T>
  let timer: number | undefined

  watch(
    source,
    (val) => {
      if (timer) window.clearTimeout(timer)
      timer = window.setTimeout(() => {
        debounced.value = val
      }, delayMs)
    },
    { immediate: true }
  )

  return debounced
}
