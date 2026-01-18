<script setup lang="ts">
import {computed, ref} from 'vue'
import {useRouter} from 'vue-router'
import {useStoreAuthentification} from '@/stores/storeAuthentification.ts'
import {useFlashStore} from '@/stores/flashStore.ts'

const authStore = useStoreAuthentification()
const flash = useFlashStore()
const router = useRouter()

const form = ref({
  login: '',
  email: '',
  password: '',
  confirmPassword: ''
})

const loading = ref(false)

const canSubmit = computed(() => {
  const loginOk = form.value.login.trim().length >= 3
  const emailOk = form.value.email.trim().length > 3
  const passOk = form.value.password.length >= 6
  const match = form.value.password === form.value.confirmPassword
  return loginOk && emailOk && passOk && match
})

const passwordMismatch = computed(() => {
  return form.value.confirmPassword.length > 0 && form.value.password !== form.value.confirmPassword
})

function normalizeEmail(v: string): string {
  return v.trim()
}

async function register(): Promise<void> {
  const login = form.value.login.trim()
  const email = normalizeEmail(form.value.email)
  const password = form.value.password

  if (!login || !email || !password || password !== form.value.confirmPassword) {
    flash.error("Inscription échouée.")
    return
  }

  loading.value = true

  try {
    const result = await authStore.register(login, email, password)
    if (result?.success) {
      flash.success('Compte créé.')
      await router.push({name: 'login'})
      return
    }
    flash.error("Inscription échouée.")
  } catch {
    flash.error("Inscription échouée.")
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">
    <section class="card shell">
      <div class="poster">
        <div class="poster__bg" aria-hidden="true"/>
        <div class="poster__shade" aria-hidden="true"/>
        <div class="poster__content">
          <div class="poster__mark">M</div>
          <div class="poster__title">Créer un compte</div>
          <div class="poster__subtitle">
            Suis tes favoris, construis ta collection, et partage tes avis.
          </div>

          <div class="poster__bullets">
            <div class="bullet">
              <span class="bullet__dot" aria-hidden="true"/>
              <span>Ajoute des musiques à ta collection</span>
            </div>
            <div class="bullet">
              <span class="bullet__dot" aria-hidden="true"/>
              <span>Découvre les avis des autres</span>
            </div>
            <div class="bullet">
              <span class="bullet__dot" aria-hidden="true"/>
              <span>Partage tes coups de cœur</span>
            </div>
          </div>
        </div>
      </div>

      <div class="form">
        <div class="form__head">
          <h1 class="form__title">Inscription</h1>
          <div class="form__meta muted">
            Déjà un compte ?
            <RouterLink class="link" :to="{ name: 'login' }">Se connecter</RouterLink>
          </div>
        </div>

        <form class="form__body" @submit.prevent="register">
          <div class="field">
            <label class="label" for="login">Pseudo</label>
            <input
              id="login"
              v-model="form.login"
              class="input"
              autocomplete="username"
              inputmode="text"
              spellcheck="false"
            />
            <div class="hint muted">3 caractères minimum.</div>
          </div>

          <div class="field">
            <label class="label" for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              class="input"
              type="email"
              autocomplete="email"
              inputmode="email"
              spellcheck="false"
            />
          </div>

          <div class="field">
            <label class="label" for="password">Mot de passe</label>
            <input
              id="password"
              v-model="form.password"
              class="input"
              type="password"
              autocomplete="new-password"
            />
            <div class="hint muted">6 caractères minimum.</div>
          </div>

          <div class="field">
            <label class="label" for="confirmPassword">Confirmer</label>
            <input
              id="confirmPassword"
              v-model="form.confirmPassword"
              class="input"
              type="password"
              autocomplete="new-password"
            />
            <div v-if="passwordMismatch" class="hint hint--error" role="alert">
              Les mots de passe ne correspondent pas.
            </div>
          </div>

          <div class="actions">
            <button class="btn btn--primary" type="submit" :disabled="loading || !canSubmit">
              {{ loading ? 'Création…' : 'Créer mon compte' }}
            </button>
            <RouterLink class="btn btn--ghost" :to="{ name: 'melovibes' }">Retour</RouterLink>
          </div>
        </form>
      </div>
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
  width: min(980px, 100%);
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.poster {
  position: relative;
  min-height: 520px;
  border-right: 1px solid var(--c-border);
  background: #0b121a;
}

.poster__bg {
  position: absolute;
  inset: 0;
  background: radial-gradient(520px 320px at 20% 20%, rgba(29, 185, 84, 0.22), transparent 55%),
  radial-gradient(420px 300px at 80% 85%, rgba(17, 217, 138, 0.16), transparent 55%),
  linear-gradient(180deg, #07110d, #0b1712);
  transform: scale(1.02);
}

.poster__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.05), rgba(0, 0, 0, 0.45));
}

.poster__content {
  position: relative;
  padding: 22px;
  display: grid;
  gap: 12px;
}

.poster__mark {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  border: 1px solid rgba(29, 185, 84, 0.45);
  background: #0f2018;
  color: rgba(220, 255, 235, 0.95);
  font-weight: 950;
  letter-spacing: 0.4px;
}

.poster__title {
  font-size: 22px;
  font-weight: 950;
  letter-spacing: 0.2px;
  color: rgba(245, 248, 252, 0.98);
  line-height: 1.12;
}

.poster__subtitle {
  color: rgba(245, 248, 252, 0.74);
  font-weight: 650;
  max-width: 44ch;
}

.poster__bullets {
  margin-top: 6px;
  display: grid;
  gap: 10px;
  max-width: 46ch;
}

.bullet {
  display: grid;
  grid-template-columns: 10px 1fr;
  gap: 10px;
  align-items: start;
  color: rgba(245, 248, 252, 0.82);
  font-weight: 650;
  line-height: 1.35;
}

.bullet__dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  margin-top: 4px;
  background: linear-gradient(90deg, rgba(29, 185, 84, 1), rgba(17, 217, 138, 1));
  box-shadow: 0 0 0 4px rgba(29, 185, 84, 0.12);
}

.form {
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  background: var(--c-surface);
}

.form__head {
  display: grid;
  gap: 6px;
}

.form__title {
  margin: 0;
  font-size: 22px;
  font-weight: 950;
  letter-spacing: 0.2px;
}

.form__meta {
  font-size: 13px;
}

.form__body {
  display: grid;
  gap: 12px;
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
  color: var(--c-text-mute);
  font-weight: 650;
}

.hint--error {
  color: rgba(255, 245, 245, 0.92);
  background: #3a1a1e;
  border: 1px solid rgba(255, 77, 79, 0.45);
  border-radius: 12px;
  padding: 8px 10px;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

@media (max-width: 900px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .poster {
    border-right: none;
    border-bottom: 1px solid var(--c-border);
    min-height: 240px;
  }
}
</style>
