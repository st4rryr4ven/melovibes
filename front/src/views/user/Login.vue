<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreAuthentification } from '@/stores/storeAuthentification.ts'
import { useFlashStore } from '@/stores/flashStore.ts'
import { userApi } from '@/api/userApi'

const authStore = useStoreAuthentification()
const flash = useFlashStore()
const router = useRouter()

const connectingUser = ref({email: '', password: ''})
const loading = ref(false)

const canSubmit = computed(() => {
  return connectingUser.value.email.trim().length > 0 && connectingUser.value.password.length > 0
})

async function connect(): Promise<void> {
  const email = connectingUser.value.email.trim()
  const password = connectingUser.value.password

  if (!email || !password) {
    flash.error('Connexion échouée.')
    return
  }

  loading.value = true

  try {
    const result = await authStore.login(email, password)
    if (result.success) {
      flash.success('Connexion effectuée.')
      await router.push({name: 'melovibes'})
      return
    }
    const errorMsg = result.error || 'Connexion échouée.'
    if (errorMsg.includes('401') || errorMsg.includes('Invalid') || errorMsg.includes('credentials')) {
      flash.error('Email ou mot de passe incorrect.')
    } else {
      flash.error(errorMsg)
    }
  } catch (e: any) {
    const errorMsg = e?.message || 'Connexion échouée.'
    if (errorMsg.includes('401') || errorMsg.includes('Invalid') || errorMsg.includes('credentials')) {
      flash.error('Email ou mot de passe incorrect.')
    } else {
      flash.error(errorMsg)
    }
  } finally {
    loading.value = false
  }
}

function connectWithSpotify(): void {
  window.location.href = userApi.getSpotifyLoginUrl()
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
          <div class="poster__title">Retrouve ta musique.</div>
          <div class="poster__subtitle">
            Garde tes favoris, partage tes découvertes, et publie tes avis.
          </div>

          <div class="poster__bullets">
            <div class="bullet">
              <span class="bullet__dot" aria-hidden="true"/>
              <span>Accède à tes musiques favorites en un clic</span>
            </div>
            <div class="bullet">
              <span class="bullet__dot" aria-hidden="true"/>
              <span>Partage tes découvertes avec les autres</span>
            </div>
            <div class="bullet">
              <span class="bullet__dot" aria-hidden="true"/>
              <span>Écris des avis et retrouve ceux de la communauté</span>
            </div>
          </div>
        </div>
      </div>

      <div class="form">
        <div class="form__head">
          <h1 class="form__title">Connexion</h1>
          <div class="form__meta muted">
            Pas de compte ?
            <RouterLink class="link" :to="{ name: 'register' }">Créer un compte</RouterLink>
          </div>
        </div>

        <form class="form__body" @submit.prevent="connect">
          <div class="field">
            <label class="label" for="email">Email</label>
            <input
              id="email"
              v-model="connectingUser.email"
              class="input"
              type="email"
              autocomplete="email"
              inputmode="email"
              spellcheck="false"
              required
            />
          </div>

          <div class="field">
            <div class="field__header">
              <label class="label" for="password">Mot de passe</label>
              <RouterLink :to="{ name: 'forgotPassword' }" class="hint-link">
                Oublié ?
              </RouterLink>
            </div>
            <input
              id="password"
              v-model="connectingUser.password"
              class="input"
              type="password"
              autocomplete="current-password"
              required
            />
          </div>

          <div class="actions">
            <button class="btn btn--primary" type="submit" :disabled="loading || !canSubmit">
              {{ loading ? 'Connexion…' : 'Se connecter' }}
            </button>
            <RouterLink class="btn btn--ghost" :to="{ name: 'melovibes' }">Retour</RouterLink>
          </div>

          <div class="oauth">
            <div class="divider" aria-hidden="true" />
            <button class="btn btn--primary btn--full" type="button" :disabled="loading" @click="connectWithSpotify">
              Se connecter avec Spotify
            </button>
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
  min-height: 460px;
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
  max-width: 42ch;
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

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 4px;
}

.oauth {
  display: grid;
  gap: 12px;
  margin-top: 8px;
}

.divider {
  height: 1px;
  background: var(--c-border);
  opacity: 0.8;
}

.btn--full {
  width: 100%;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

.field__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.hint-link {
  font-size: 11px;
  font-weight: 700;
  color: var(#1db954);
  text-decoration: none;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.hint-link:hover {
  text-decoration: underline;
}

@media (max-width: 900px) {
  .shell {
    grid-template-columns: 1fr;
  }

  .poster {
    border-right: none;
    border-bottom: 1px solid var(--c-border);
    min-height: 220px;
  }
}
</style>
