import {defineStore} from 'pinia'
import {apiStore} from '@/util/apiStore'

export const storeFavorites = defineStore('favorites', {
  state: () => ({
    music: [] as any[],
  }),
  actions: {
    async fetchFavorites(userId: number) {
      try {
        this.music = await apiStore.getFavorites(userId)
      } catch (err) {
        console.error('Error fetching favorites:', err)
      }
    },
    async toggleFavorite(userId: number, musicId: number) {
      try {
        await apiStore.toggleFavorite(userId, musicId)
        const index = this.music.findIndex(m => m.id === musicId)
        if (index === -1) {
          const music = await apiStore.getMusic(musicId)
          this.music.push(music)
        } else {
          this.music.splice(index, 1)
        }
      } catch (err) {
        console.error('Error toggling favorite:', err)
      }
    }
  },
})
