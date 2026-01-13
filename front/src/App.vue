<template>
  <div class="app">
    <header class="topbar">
      <div class="topbar__inner container">
        <button class="brand" type="button" @click="router.push({ name: 'melovibes' })">
          <span class="brand__mark" aria-hidden="true">♪</span>
          <span class="brand__name">Melovibes</span>
        </button>

        <nav class="nav">
          <RouterLink v-if="authStore.estAdmin" class="nav__link" :to="{ name: 'music' }">Musiques</RouterLink>
          <RouterLink
            v-if="authStore.estConnecte"
            class="nav__link"
            :to="{ name: 'music-create' }"
          >
            Ajouter
          </RouterLink>
          <RouterLink
            v-if="authStore.estConnecte && authStore.estAdmin"
            class="nav__link"
            :to="{ name: 'allUsers' }"
          >
            Utilisateurs
          </RouterLink>
        </nav>

        <div class="actions">
          <RouterLink v-if="!authStore.estConnecte" class="btn btn--ghost" :to="{ name: 'register' }">
            S'inscrire
          </RouterLink>
          <RouterLink v-if="!authStore.estConnecte" class="btn btn--primary" :to="{ name: 'login' }">
            Connexion
          </RouterLink>
          <RouterLink v-if="authStore.estConnecte" class="btn btn--ghost" :to="{ name: 'profile' }">
            Profil
          </RouterLink>
          <button v-if="authStore.estConnecte" class="btn btn--danger" type="button" @click="logout">
            Déconnexion
          </button>
        </div>
      </div>
    </header>

    <FlashMessages />

    <main class="main container">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import FlashMessages from '@/components/FlashMessages.vue'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { useFlashStore } from '@/stores/flashStore'

const router = useRouter()
const authStore = useStoreAuthentification()
const flash = useFlashStore()

onMounted(() => {
  authStore.init()
})

async function logout(): Promise<void> {
  try {
    await authStore.logout()
    flash.info('Déconnecté.')
    await router.push({name: 'melovibes'})
  } catch (err: any) {
    flash.error(err?.message ?? 'Erreur lors de la déconnexion.')
  }
}
</script>

<style scoped>
.app {
  min-height: 100vh;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(9, 13, 19, 0.82);
  backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--c-border);
}

.topbar__inner {
  height: 62px;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 16px;
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  border: none;
  background: transparent;
  color: var(--c-text);
  cursor: pointer;
  padding: 8px 10px;
  border-radius: 12px;
}

.brand:hover {
  background: rgba(255, 255, 255, 0.05);
}

.brand__mark {
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(29, 185, 84, 0.95), rgba(17, 217, 138, 0.9));
  color: #07120b;
  font-weight: 800;
}

.brand__name {
  font-weight: 800;
  letter-spacing: 0.2px;
}

.nav {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.nav__link {
  color: var(--c-text-soft);
  padding: 9px 12px;
  border-radius: 999px;
  border: 1px solid transparent;
  transition: background 0.12s ease, color 0.12s ease, border-color 0.12s ease;
}

.nav__link:hover {
  background: rgba(255, 255, 255, 0.05);
  color: var(--c-text);
}

.nav__link.router-link-active {
  border-color: rgba(29, 185, 84, 0.35);
  background: rgba(29, 185, 84, 0.12);
  color: var(--c-text);
}

.actions {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.main {
  padding: 18px 20px 28px;
}

@media (max-width: 860px) {
  .topbar__inner {
    grid-template-columns: auto auto;
    grid-template-areas:
      'brand actions'
      'nav nav';
    height: auto;
    padding: 10px 0 12px;
  }

  .brand {
    grid-area: brand;
  }

  .actions {
    grid-area: actions;
    justify-content: flex-end;
  }

  .nav {
    grid-area: nav;
    justify-content: flex-start;
    overflow-x: auto;
    padding: 2px 0 0;
  }
}

@media (max-width: 520px) {
  .btn {
    padding: 9px 12px;
  }

  .brand__name {
    display: none;
  }
}
</style>
