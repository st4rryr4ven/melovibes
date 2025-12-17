import {reactive} from 'vue'
import type {JwtResponse, LoginResult} from '@/types'

export const storeAuthentification = reactive({
  apiUrl: "https://localhost/site_de_musique/api/public/api/",
  utilisateurConnecte: null as JwtResponse | null,
  estConnecte: false,

  login(login: string, password: string): Promise<LoginResult> {
    return fetch(this.apiUrl + 'auth', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({login, password})
    })
      .then(async response => {
        const data = await response.json()
        if (!response.ok) {
          return {success: false, error: data.message || 'Login failed'}
        }
        this.utilisateurConnecte = data
        this.estConnecte = true
        return {success: true}
      })
      .catch(err => ({success: false, error: err.message}))
  },

  logout(): Promise<LoginResult> {
    return fetch(this.apiUrl + 'token/invalidate', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include'
    })
      .then(async response => {
        const data = await response.json().catch(() => ({}))
        if (!response.ok) {
          return {success: false, error: data.message || 'Logout failed'}
        }
        this.utilisateurConnecte = null
        this.estConnecte = false
        return {success: true}
      })
      .catch(err => ({success: false, error: err.message}))
  },

  refresh(): Promise<LoginResult> {
    return fetch(this.apiUrl + 'token/refresh', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include'
    })
      .then(async response => {
        const data = await response.json()
        if (!response.ok) {
          this.utilisateurConnecte = null
          this.estConnecte = false
          return {success: false, error: data.message || 'Refresh failed'}
        }
        this.utilisateurConnecte = data
        this.estConnecte = true
        return {success: true}
      })
      .catch(err => ({success: false, error: err.message}))
  }
})
