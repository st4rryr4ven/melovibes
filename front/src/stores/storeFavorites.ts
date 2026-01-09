
import { defineStore } from 'pinia'
import { apiStore } from '@/util/apiStore'

export const storeFavorites = defineStore('favorites', {
  state: () => ({
    musics: [] as any[],
  }),
  actions: {
    async fetchFavorites(userId: number) {
      try {
        const response = await apiStore.getFavorites(userId)
        this.musics = response
      } catch (err) {
        console.error('Error fetching favorites:', err)
      }
    },
    async toggleFavorite(userId: number, musicId: number) {
      try {
        await apiStore.toggleFavorite(userId, musicId)
        const index = this.musics.findIndex(m => m.id === musicId)
        if (index === -1) {
          const music = await apiStore.getMusic(musicId)
          this.musics.push(music)
        } else {
          this.musics.splice(index, 1)
        }
      } catch (err) {
        console.error('Error toggling favorite:', err)
      }
    }
  },
})
