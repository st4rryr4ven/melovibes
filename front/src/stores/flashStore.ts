import { defineStore } from 'pinia'

export type FlashType = 'success' | 'error' | 'info' | 'warning'

export type FlashMessage = {
  id: string
  type: FlashType
  text: string
  createdAt: number
}

function uid(): string {
  return `${Date.now()}_${Math.random().toString(16).slice(2)}`
}

export const useFlashStore = defineStore('flash', {
  state: () => ({
    messages: [] as FlashMessage[]
  }),

  actions: {
    push(type: FlashType, text: string, ttlMs = 4500): string {
      const id = uid()
      this.messages.push({ id, type, text, createdAt: Date.now() })
      if(ttlMs === 0) return id;
      window.setTimeout(() => this.remove(id), ttlMs)
      return id
    },

    success(text: string, ttlMs?: number): string {
      return this.push('success', text, ttlMs)
    },

    error(text: string, ttlMs?: number): string {
      return this.push('error', text, ttlMs)
    },

    info(text: string, ttlMs?: number): string {
      return this.push('info', text, ttlMs)
    },

    warning(text: string, ttlMs?: number): string {
      return this.push('warning', text, ttlMs)
    },

    remove(id: string): void {
      this.messages = this.messages.filter((m) => m.id !== id)
    },

    clear(): void {
      this.messages = []
    }
  }
})
