import {reactive} from 'vue'
import type {LoginResult, User} from "@/types.ts";
import {apiStore} from "@/util/apiStore";

export const storeAuthentification = reactive({
  utilisateurConnecte: null as User | null,
  estConnecte: false,

  login(login: string, password: string): Promise<LoginResult> {
    return apiStore.login(login, password)
      .then(user => {
        this.utilisateurConnecte = user
        this.estConnecte = true
        if (user.token) {
          apiStore.currentToken = user.token
        }
        return {success: true}
      })
      .catch(err => ({success: false, error: err.message}))
  },

  logout(): Promise<LoginResult> {
    return apiStore.logout()
      .then(() => {
        this.utilisateurConnecte = null
        this.estConnecte = false
        return {success: true}
      })
      .catch(err => ({success: false, error: err.message}))
  },

  refresh(): Promise<LoginResult> {
    return apiStore.refresh()
      .then(user => {
        this.utilisateurConnecte = user
        this.estConnecte = true
        if (user.token) {
          apiStore.currentToken = user.token
        }
        return {success: true}
      })
      .catch(err => {
        this.utilisateurConnecte = null
        this.estConnecte = false
        return {success: false, error: err.message}
      })
  }
})
