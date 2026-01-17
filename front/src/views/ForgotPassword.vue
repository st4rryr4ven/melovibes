<script setup lang="ts">
import {ref} from 'vue'
import {userApi} from '@/api/userApi'
import {useFlashStore} from '@/stores/flashStore'

const email = ref('')
const loading = ref(false)
const sent = ref(false)
const flash = useFlashStore()

async function handleSubmit() {
  loading.value = true
  try {
    await userApi.requestPasswordReset(email.value)
    sent.value = true
    flash.success("Si un compte existe, un email a été envoyé.")
  } catch (e) {
    flash.error("Une erreur est survenue.")
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="card auth-card">
      <h2>Mot de passe oublié</h2>

      <div v-if="sent" class="success-state">
        <p>Un lien de réinitialisation a été envoyé à <strong>{{ email }}</strong>.</p>
        <p class="muted">Pensez à vérifier vos courriers indésirables (spams).</p>
        <router-link to="/login" class="btn btn--ghost">Retour à la connexion</router-link>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="form">
        <p class="muted">Entrez votre email pour recevoir un lien de récupération.</p>
        <div class="field">
          <label class="label">Email</label>
          <input v-model="email" type="email" class="input" required placeholder="mon@email.com"/>
        </div>
        <button class="btn btn--primary btn--full" :disabled="loading">
          {{ loading ? 'Envoi...' : 'Envoyer le lien' }}
        </button>
      </form>
    </div>
  </div>
</template>
