<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFlashStore } from '@/stores/flashStore'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { apiJson } from '@/api/httpClient'
import type { SpotifySessionResponse } from '@/types'

const route = useRoute()
const router = useRouter()
const flash = useFlashStore()
const authStore = useStoreAuthentification()

const isLinkFlow = computed(() => {
  const v = route.query.linked
  return v === '' || v === '1' || v === 'true'
})

const phase = ref<'loading' | 'success' | 'error'>('loading')
const headline = isLinkFlow.value ? ref('Liaison à Spotify…') : ref('Connexion Spotify…')
const subtitle = ref('Finalisation de la session.')

const successRedirectRoute = computed(() => {
  return isLinkFlow.value ? { name: 'profile' } : { name: 'melovibes' }
})

const errorRedirectRoute = computed(() => {
  return isLinkFlow.value ? { name: 'profile' } : { name: 'login' }
})

function getErrorMessage(err: unknown): string {
  if (!err || typeof err !== 'object' || !('message' in err)) return 'Connexion Spotify échouée.'
  const msg = String((err as { message?: unknown }).message ?? '')
  return msg || 'Connexion Spotify échouée.'
}

onMounted(async () => {
  const code = typeof route.query.code === 'string' ? route.query.code : ''

  if (!code) {
    flash.error('Connexion Spotify échouée.')
    phase.value = 'error'
    headline.value = 'Connexion échouée'
    subtitle.value = 'Le lien de connexion est incomplet ou a expiré.'
    await router.replace(errorRedirectRoute.value)
    return
  }

  try {
    const res = await apiJson<SpotifySessionResponse>('spotify/session', {
      method: 'POST',
      json: { code }
    })

    await authStore.refresh()

    if (res.tempPassword) {
      flash.warning(
        `Compte créé.
      Pensez à modifier votre mot de passe !
      Mot de passe temporaire: ${res.tempPassword}`,
        0
      )
      headline.value = 'Compte créé'
      subtitle.value = 'Votre compte est prêt. Un mot de passe temporaire a été généré.'
    } else if (isLinkFlow.value) {
      flash.success('Compte Spotify lié.')
      headline.value = 'Compte lié'
      subtitle.value = 'Votre compte Spotify est maintenant associé à Melovibes.'
    } else {
      flash.success('Connexion Spotify réussie.')
      headline.value = 'Connexion réussie'
      subtitle.value = 'Redirection en cours vers Melovibes.'
    }

    phase.value = 'success'
    await router.replace(successRedirectRoute.value)
  } catch (e: unknown) {
    flash.error(getErrorMessage(e))
    phase.value = 'error'
    headline.value = 'Connexion échouée'
    subtitle.value = isLinkFlow.value
      ? 'Impossible de lier votre compte Spotify. Réessayez depuis votre profil.'
      : 'Impossible de finaliser la connexion Spotify. Réessayez depuis la page de connexion.'
    await router.replace(errorRedirectRoute.value)
  }
})
</script>

<template>
  <div class="page">
    <section class="card shell">
      <div class="poster">
        <div class="poster__bg" aria-hidden="true" />
        <div class="poster__shade" aria-hidden="true" />
        <div class="poster__content">
          <div class="poster__mark" aria-hidden="true">S</div>
          <div class="poster__title">Spotify</div>
          <div class="poster__subtitle">Synchronisation de votre session.</div>
        </div>
      </div>

      <div class="panel">
        <div class="panel__head">
          <div class="badge" :class="{ 'badge--ok': phase === 'success', 'badge--error': phase === 'error' }">
            {{ phase === 'loading' ? (isLinkFlow ? 'Liaison' : 'Connexion') : phase === 'success' ? 'OK' : 'Erreur' }}
          </div>
          <div class="panel__meta muted">
            {{ phase === 'loading' ? 'Un instant…' : phase === 'success' ? 'Terminé' : 'Action requise' }}
          </div>
        </div>

        <div class="state">
          <div v-if="phase === 'loading'" class="loader" aria-hidden="true">
            <span class="loader__dot" />
            <span class="loader__dot" />
            <span class="loader__dot" />
          </div>

          <h1 class="title">{{ headline }}</h1>
          <p class="subtitle muted">{{ subtitle }}</p>

          <div class="hint" v-if="phase === 'loading'">
            Ne fermez pas cette page. Vous serez redirigé automatiquement.
          </div>

          <div class="actions" v-if="phase !== 'loading'">
            <RouterLink class="btn btn--primary" :to="errorRedirectRoute">Continuer</RouterLink>
            <RouterLink class="btn btn--ghost" :to="{ name: 'melovibes' }">Accueil</RouterLink>
          </div>
        </div>
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
  width: min(920px, 100%);
  display: grid;
  grid-template-columns: 0.95fr 1.05fr;
  overflow: hidden;
}

.poster {
  position: relative;
  min-height: 320px;
  border-right: 1px solid var(--c-border);
  background: #0b121a;
}

.poster__bg {
  position: absolute;
  inset: 0;
  background: radial-gradient(520px 320px at 20% 20%, rgba(29, 185, 84, 0.24), transparent 55%),
  radial-gradient(420px 300px at 80% 85%, rgba(17, 217, 138, 0.18), transparent 55%),
  linear-gradient(180deg, #06120c, #0b1712);
  transform: scale(1.02);
}

.poster__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.04), rgba(0, 0, 0, 0.42));
}

.poster__content {
  position: relative;
  padding: 22px;
  display: grid;
  gap: 10px;
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
  color: rgba(245, 248, 252, 0.76);
  font-weight: 650;
  max-width: 42ch;
}

.panel {
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  background: var(--c-surface);
}

.panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.panel__meta {
  font-size: 13px;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 999px;
  padding: 7px 12px;
  font-weight: 850;
  font-size: 12px;
  letter-spacing: 0.2px;
  border: 1px solid var(--c-border);
  background: rgba(0, 0, 0, 0.04);
}

.badge--ok {
  border-color: rgba(29, 185, 84, 0.35);
  background: rgba(29, 185, 84, 0.1);
}

.badge--error {
  border-color: rgba(255, 85, 85, 0.35);
  background: rgba(255, 85, 85, 0.08);
}

.state {
  display: grid;
  gap: 10px;
  padding-top: 8px;
}

.title {
  margin: 0;
  font-size: 22px;
  font-weight: 950;
  letter-spacing: 0.2px;
}

.subtitle {
  margin: 0;
  max-width: 60ch;
  line-height: 1.45;
}

.hint {
  font-size: 13px;
  color: var(--c-text-soft);
  font-weight: 650;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 6px;
}

.loader {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  height: 18px;
}

.loader__dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: rgba(29, 185, 84, 0.95);
  opacity: 0.25;
  animation: bounce 1.1s infinite;
}

.loader__dot:nth-child(2) {
  animation-delay: 0.12s;
}

.loader__dot:nth-child(3) {
  animation-delay: 0.24s;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

@keyframes bounce {
  0%,
  100% {
    transform: translateY(0);
    opacity: 0.25;
  }
  50% {
    transform: translateY(-4px);
    opacity: 1;
  }
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
