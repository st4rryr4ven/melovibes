<template>
  <div class="profile-container">
    <h2>Mettre à jour mon profil</h2>

    <form @submit.prevent="update" class="profile-form">
      <div class="form-group">
        <label for="login">Login</label>
        <input
          id="login"
          type="text"
          v-model="login"
          disabled
        />
        <small class="hint">Le login ne peut pas être modifié</small>
      </div>

      <div class="form-group">
        <label for="email">Adresse email</label>
        <input
          id="email"
          type="email"
          v-model="email"
          required
        />
      </div>

      <div class="form-group">
        <label for="plainPassword">
          Nouveau mot de passe
          <span class="hint">(laisser vide pour ne pas changer)</span>
        </label>
        <input
          id="plainPassword"
          type="password"
          v-model="plainPassword"
        />
      </div>

      <div class="form-group">
        <label for="currentPlainPassword">
          Mot de passe actuel
        </label>
        <input
          id="currentPlainPassword"
          type="password"
          v-model="currentPlainPassword"
          required
        />
      </div>

      <div class="form-actions">
        <button type="submit">Mettre à jour</button>
        <button type="button" @click="router.push({ name: 'melovibes' })">
          Annuler
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import {ref} from 'vue'
import {useRouter} from 'vue-router'
import {storeAuthentification as store} from '@/stores/storeAuthentification'
import {apiStore} from '@/util/apiStore'

// console.log("start")
const router = useRouter()

if (!store.utilisateurConnecte) {
  router.push({name: 'login'})
}

const login = ref(store.utilisateurConnecte?.login ?? '')
const email = ref(store.utilisateurConnecte?.email ?? '')
const plainPassword = ref('')
const currentPlainPassword = ref('')
// console.log("end")

function update() {
  if (!currentPlainPassword.value) {
    alert("Vous devez entrer votre mot de passe actuel.")
    return
  }

  apiStore.updateUser(store.utilisateurConnecte!.id, {
    login: login.value,
    email: email.value,
    plainPassword: plainPassword.value || undefined,
    currentPlainPassword: currentPlainPassword.value
  })
    .then(user => {
      store.utilisateurConnecte = user
      alert('Profil mis à jour')
    })
    .catch(err => alert(err.message))
}
</script>
<style scoped>
@import "@/components/css/layout.css";
</style>

