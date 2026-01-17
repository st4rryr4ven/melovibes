<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFlashStore } from '@/stores/flashStore'
import { userApi } from '@/api/userApi'

const route = useRoute()
const router = useRouter()
const flash = useFlashStore()

const token = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)

const canSubmit = computed(() => {
  const passOk = newPassword.value.length >= 6
  const match = newPassword.value === confirmPassword.value
  return passOk && match
})

const passwordMismatch = computed(() => {
  return confirmPassword.value.length > 0 && newPassword.value !== confirmPassword.value
})

onMounted(() => {
  const queryToken = route.query.token as string
  if (!queryToken) {
    flash.error("Lien de réinitialisation invalide ou manquant.")
    router.push({ name: 'login' })
  } else {
    token.value = queryToken
  }
})

async function handleReset() {
  if (!canSubmit.value) return

  loading.value = true
  try {
    await userApi.resetPassword(token.value, newPassword.value)
    flash.success("Mot de passe mis à jour ! Vous pouvez vous connecter.")
    router.push({ name: 'login' })
  } catch (e) {
    flash.error("Le lien a expiré ou est invalide.")
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="card auth-card">
      <h2>Nouveau mot de passe</h2>
      <p class="muted">Choisissez un mot de passe sécurisé pour votre compte.</p>

      <form @submit.prevent="handleReset" class="form">
        <div class="field">
          <label class="label">Nouveau mot de passe</label>
          <input
            v-model="newPassword"
            type="password"
            class="input"
            required
            placeholder="••••••••"
          />
          <div class="hint muted">6 caractères minimum.</div>
        </div>

        <div class="field">
          <label class="label">Confirmer le mot de passe</label>
          <input
            v-model="confirmPassword"
            type="password"
            class="input"
            required
            placeholder="••••••••"
          />
          <div v-if="passwordMismatch" class="hint hint--error" role="alert">
            Les mots de passe ne correspondent pas.
          </div>
        </div>

        <button class="btn btn--primary btn--full" :disabled="loading || !canSubmit">
          {{ loading ? 'Mise à jour...' : 'Réinitialiser le mot de passe' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.hint {
  font-size: 12px;
  color: var(--c-text-mute);
  font-weight: 650;
  margin-top: 4px;
}

.hint--error {
  color: rgba(255, 245, 245, 0.92);
  background: #3a1a1e;
  border: 1px solid rgba(255, 77, 79, 0.45);
  border-radius: 12px;
  padding: 8px 10px;
}

.auth-page {
  display: grid;
  place-items: center;
  padding: 40px 20px;
  min-height: 60vh;
}

.auth-card {
  width: 100%;
  max-width: 400px;
  padding: 24px;
}

.form {
  display: grid;
  gap: 16px;
  margin-top: 20px;
}

.label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.2px;
  color: var(--c-text-soft);
  margin-bottom: 8px;
  display: block;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}
</style>
