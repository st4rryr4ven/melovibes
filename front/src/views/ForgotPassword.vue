<script setup lang="ts">
import { ref, computed } from 'vue'
import { userApi } from '@/api/userApi'
import { useFlashStore } from '@/stores/flashStore'

const email = ref('')
const loading = ref(false)
const sent = ref(false)
const flash = useFlashStore()

const canSubmit = computed(() => {
  return email.value.trim().length > 0 && email.value.includes('@')
})

async function handleSubmit(): Promise<void> {
  if (!canSubmit.value) return

  loading.value = true
  try {
    await userApi.requestPasswordReset(email.value.trim())
    sent.value = true
    flash.success('Si un compte existe, un email a été envoyé.')
  } catch (e) {
    flash.error('Une erreur est survenue.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">
    <section class="card shell">
      <div class="head">
        <div class="head__title">Mot de passe oublié</div>
        <div class="head__subtitle muted">Entrez votre email pour recevoir un lien de récupération.</div>
      </div>

      <div v-if="sent" class="success">
        <div class="success__box">
          <div class="success__title">Email envoyé</div>
          <div class="success__text">
            Un lien de réinitialisation a été envoyé à <strong>{{ email }}</strong>.
          </div>
          <div class="muted success__hint">Pensez à vérifier vos courriers indésirables (spams).</div>
        </div>

        <div class="actions">
          <RouterLink class="btn btn--primary btn--full" :to="{ name: 'login' }">Retour à la connexion</RouterLink>
          <RouterLink class="btn btn--ghost btn--full" :to="{ name: 'melovibes' }">Accueil</RouterLink>
        </div>
      </div>

      <form v-else class="form" @submit.prevent="handleSubmit">
        <div class="field">
          <label class="label" for="email">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            class="input"
            required
            placeholder="mon@email.com"
            autocomplete="email"
          />
          <div class="hint muted">Nous n’affichons jamais si un compte existe ou non.</div>
        </div>

        <div class="actions">
          <button class="btn btn--primary btn--full" type="submit" :disabled="loading || !canSubmit">
            {{ loading ? 'Envoi…' : 'Envoyer le lien' }}
          </button>
          <RouterLink class="btn btn--ghost btn--full" :to="{ name: 'login' }">Retour</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<style scoped>
.page {
  min-height: calc(100vh - 140px);
  display: grid;
  place-items: center;
  padding: 18px 0;
}

.shell {
  width: min(520px, 100%);
  padding: 22px;
  display: grid;
  gap: 16px;
}

.head {
  display: grid;
  gap: 6px;
}

.head__title {
  font-size: 18px;
  font-weight: 950;
  letter-spacing: 0.2px;
  line-height: 1.15;
}

.head__subtitle {
  max-width: 56ch;
  line-height: 1.45;
}

.form {
  display: grid;
  gap: 14px;
}

.field {
  display: grid;
  gap: 8px;
}

.label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.2px;
  color: var(--c-text-soft);
}

.hint {
  font-size: 12px;
  font-weight: 650;
  line-height: 1.35;
}

.success {
  display: grid;
  gap: 12px;
}

.success__box {
  border: 1px solid rgba(29, 185, 84, 0.28);
  background: rgba(29, 185, 84, 0.08);
  border-radius: 16px;
  padding: 14px;
  display: grid;
  gap: 8px;
}

.success__title {
  font-weight: 950;
  letter-spacing: 0.2px;
}

.success__text {
  line-height: 1.45;
}

.success__hint {
  font-size: 12px;
}

.actions {
  display: grid;
  gap: 10px;
  margin-top: 4px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}
</style>
