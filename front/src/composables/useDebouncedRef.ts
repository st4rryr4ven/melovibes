import { ref, watch, type Ref } from 'vue'

/**
 * Creates a debounced Ref that updates after a delay when the source changes.
 *
 * Typical usage:
 * - Bind user input to a source ref.
 * - Use the debounced ref to trigger API calls less frequently.
 *
 * @typeParam T Ref value type.
 * @param source Source ref to watch.
 * @param delayMs Debounce delay in milliseconds.
 * @returns A ref that lags behind {@link source} by {@link delayMs}.
 */
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
