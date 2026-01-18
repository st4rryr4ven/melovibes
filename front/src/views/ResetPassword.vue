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

const passwordTooShort = computed(() => {
  return newPassword.value.length > 0 && newPassword.value.length < 6
})

onMounted(() => {
  const queryToken = typeof route.query.token === 'string' ? route.query.token : ''
  if (!queryToken) {
    flash.error('Lien de réinitialisation invalide ou manquant.')
    router.push({ name: 'login' })
  } else {
    token.value = queryToken
  }
})

async function handleReset(): Promise<void> {
  if (!canSubmit.value) return

  loading.value = true
  try {
    await userApi.resetPassword(token.value, newPassword.value)
    flash.success('Mot de passe mis à jour ! Vous pouvez vous connecter.')
    await router.push({name: 'login'})
  } catch {
    flash.error('Le lien a expiré ou est invalide.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">
    <section class="card shell">
      <div class="head">
        <div class="head__title">Nouveau mot de passe</div>
        <div class="head__subtitle muted">Choisissez un mot de passe sécurisé pour votre compte.</div>
      </div>

      <form class="form" @submit.prevent="handleReset">
        <div class="field">
          <label class="label" for="newPassword">Nouveau mot de passe</label>
          <input
            id="newPassword"
            v-model="newPassword"
            type="password"
            class="input"
            required
            placeholder="••••••••"
            autocomplete="new-password"
          />
          <div v-if="passwordTooShort" class="hint hint--warn" role="alert">6 caractères minimum.</div>
          <div v-else class="hint muted">6 caractères minimum.</div>
        </div>

        <div class="field">
          <label class="label" for="confirmPassword">Confirmer le mot de passe</label>
          <input
            id="confirmPassword"
            v-model="confirmPassword"
            type="password"
            class="input"
            required
            placeholder="••••••••"
            autocomplete="new-password"
          />
          <div v-if="passwordMismatch" class="hint hint--error" role="alert">Les mots de passe ne correspondent pas.</div>
        </div>

        <div class="actions">
          <button class="btn btn--primary btn--full" type="submit" :disabled="loading || !canSubmit">
            {{ loading ? 'Mise à jour…' : 'Réinitialiser le mot de passe' }}
          </button>

          <RouterLink class="btn btn--ghost btn--full" :to="{ name: 'login' }">Retour à la connexion</RouterLink>
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

.hint--error {
  color: rgba(255, 245, 245, 0.92);
  background: #3a1a1e;
  border: 1px solid rgba(255, 77, 79, 0.45);
  border-radius: 12px;
  padding: 8px 10px;
}

.hint--warn {
  color: rgba(255, 250, 235, 0.92);
  background: rgba(255, 184, 77, 0.12);
  border: 1px solid rgba(255, 184, 77, 0.35);
  border-radius: 12px;
  padding: 8px 10px;
}

.actions {
  display: grid;
  gap: 10px;
  margin-top: 6px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}
</style>
