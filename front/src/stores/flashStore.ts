import { defineStore } from 'pinia'

/**
 * Supported flash message types.
 */
export type FlashType = 'success' | 'error' | 'info' | 'warning'

/**
 * Flash message stored in-memory.
 */
export type FlashMessage = {
  /**
   * Unique message id.
   */
  id: string

  /**
   * Message severity.
   */
  type: FlashType

  /**
   * Displayed text.
   */
  text: string

  /**
   * Creation timestamp (ms).
   */
  createdAt: number
}

function uid(): string {
  return `${Date.now()}_${Math.random().toString(16).slice(2)}`
}

/**
 * Flash messages store.
 *
 * Behavior:
 * - Messages can be pushed with an optional TTL.
 * - A TTL of 0 disables auto-removal (persistent message).
 */
export const useFlashStore = defineStore('flash', {
  state: () => ({
    messages: [] as FlashMessage[]
  }),

  actions: {
    /**
     * Pushes a new message and optionally schedules its removal.
     *
     * @param type Message type.
     * @param text Message text.
     * @param ttlMs Auto-remove delay in ms (0 to disable).
     * @returns The created message id.
     */
    push(type: FlashType, text: string, ttlMs = 4500): string {
      const id = uid()
      this.messages.push({ id, type, text, createdAt: Date.now() })

      if (ttlMs === 0) return id

      window.setTimeout(() => this.remove(id), ttlMs)
      return id
    },

    /**
     * Pushes a success message.
     */
    success(text: string, ttlMs?: number): string {
      return this.push('success', text, ttlMs)
    },

    /**
     * Pushes an error message.
     */
    error(text: string, ttlMs?: number): string {
      return this.push('error', text, ttlMs)
    },

    /**
     * Pushes an info message.
     */
    info(text: string, ttlMs?: number): string {
      return this.push('info', text, ttlMs)
    },

    /**
     * Pushes a warning message.
     */
    warning(text: string, ttlMs?: number): string {
      return this.push('warning', text, ttlMs)
    },

    /**
     * Removes a message by id.
     *
     * @param id Message id.
     */
    remove(id: string): void {
      this.messages = this.messages.filter((m) => m.id !== id)
    },

    /**
     * Clears all messages.
     */
    clear(): void {
      this.messages = []
    }
  }
})
