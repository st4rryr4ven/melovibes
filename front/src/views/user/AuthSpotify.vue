<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useFlashStore } from '@/stores/flashStore'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { apiJson } from '@/api/httpClient'

const route = useRoute()
const router = useRouter()
const flash = useFlashStore()
const authStore = useStoreAuthentification()

onMounted(async () => {
  const code = typeof route.query.code === 'string' ? route.query.code : ''
  if (!code) {
    flash.error('Connexion Spotify échouée.')
    await router.replace({ name: 'login' })
    return
  }

  try {
    const res = await apiJson<{ success: boolean; tempPassword?: string | null }>('spotify/session', {
      method: 'POST',
      json: { code }
    })

    await authStore.refresh()

    if (res.tempPassword) {
      flash.warning(`Compte créé.
      Pensez à modifier votre mot de passe !
      Mot de passe temporaire: ${res.tempPassword}`, 0)
    } else if (route.query.linked) {
      flash.success('Compte Spotify lié.')
    } else {
      flash.success('Connexion Spotify réussie.')
    }

    await router.replace({ name: 'melovibes' })
  } catch (e: any) {
    flash.error(e?.message ?? 'Connexion Spotify échouée.')
    await router.replace({ name: 'login' })
  }
})
</script>

<template>
  <div class="page">
    <div class="card">
      <div class="title">Connexion Spotify…</div>
      <div class="muted">Finalisation de la session.</div>
    </div>
  </div>
</template>

<style scoped>
.page {
  min-height: calc(100vh - 140px);
  display: grid;
  place-items: center;
  padding: 18px 0;
}
.card {
  padding: 18px;
}
.title {
  font-weight: 900;
  letter-spacing: 0.2px;
}
.muted {
  margin-top: 8px;
  color: var(--c-text-mute);
  font-weight: 650;
}
</style>
