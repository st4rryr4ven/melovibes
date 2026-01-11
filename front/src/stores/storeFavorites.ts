import {defineStore} from 'pinia'
import {API_URL, apiStore} from '@/util/apiStore'

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
        const res = await apiStore.toggleFavorite(userId, musicId)
        console.log('Favorite toggled:', res.action)
        await this.fetchFavorites(userId)
      } catch (err: any) {
        console.error(err)
        alert(err.message || 'Erreur lors de la gestion des favoris.')
      }
    }
  },
})
