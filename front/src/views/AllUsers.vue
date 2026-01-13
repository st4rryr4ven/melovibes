<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { User } from '@/types'
import { userApi } from '@/api/userApi'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { useFlashStore } from '@/stores/flashStore'
import UserBox from '@/components/UserBox.vue'

const authStore = useStoreAuthentification()
const flash = useFlashStore()
const router = useRouter()

const users = ref<User[]>([])
const search = ref('')
const roleFilter = ref<'all' | 'admins' | 'users'>('all')
const loading = ref(false)
const error = ref<string | null>(null)

const counts = computed(() => {
  const total = users.value.length
  const admins = users.value.filter((u) => (u.roles ?? []).includes('ROLE_ADMIN')).length
  return { total, admins, users: total - admins }
})

const filteredUsers = computed(() => {
  const q = search.value.toLowerCase().trim()
  let base = users.value

  if (roleFilter.value === 'admins') {
    base = base.filter((u) => (u.roles ?? []).includes('ROLE_ADMIN'))
  } else if (roleFilter.value === 'users') {
    base = base.filter((u) => !(u.roles ?? []).includes('ROLE_ADMIN'))
  }

  if (!q) return base

  return base.filter((u) => {
    const loginMatch = (u.login ?? '').toLowerCase().includes(q)
    const emailMatch = (u.email ?? '').toLowerCase().includes(q)
    const idMatch = String(u.id ?? '').includes(q)
    return loginMatch || emailMatch || idMatch
  })
})

async function loadUsers(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const data = await userApi.list()
    users.value = (data ?? []).filter((u) => !!u?.id && !!u?.login && !!u?.email && Array.isArray(u?.roles))
  } catch (e: unknown) {
    const msg = e instanceof Error ? e.message : 'Erreur lors du chargement des utilisateurs.'
    users.value = []
    error.value = msg
    flash.error(msg)
  } finally {
    loading.value = false
  }
}

function removeUser(id: number): void {
  users.value = users.value.filter((u) => u.id !== id)
}

onMounted(async () => {
  if (!authStore.estConnecte) {
    router.replace({ name: 'login' })
    return
  }
  if (!authStore.estAdmin) {
    router.replace({ name: 'melovibes' })
    return
  }
  await loadUsers()
})
</script>

<template>
  <div class="page">
    <header class="head card">
      <div class="head__left">
        <div class="title">Utilisateurs</div>
        <div class="meta">
          <span class="pill">Total {{ counts.total }}</span>
          <span class="pill pill--ok">Admins {{ counts.admins }}</span>
          <span class="pill">Users {{ counts.users }}</span>
        </div>
      </div>

      <div class="head__right">
        <div class="seg">
          <button class="seg__btn" type="button" :class="{ 'is-on': roleFilter === 'all' }" @click="roleFilter = 'all'">
            Tous
          </button>
          <button
            class="seg__btn"
            type="button"
            :class="{ 'is-on': roleFilter === 'admins' }"
            @click="roleFilter = 'admins'"
          >
            Admins
          </button>
          <button
            class="seg__btn"
            type="button"
            :class="{ 'is-on': roleFilter === 'users' }"
            @click="roleFilter = 'users'"
          >
            Users
          </button>
        </div>

        <input v-model="search" class="input search" type="text" placeholder="Rechercher…" />

        <button class="btn btn--ghost" type="button" :disabled="loading" @click="loadUsers">
          Rafraîchir
        </button>
      </div>
    </header>

    <div v-if="loading" class="state card">
      <div class="muted">Chargement…</div>
      <div class="skeleton" />
      <div class="skeleton" />
    </div>

    <div v-else-if="error" class="state card errorBox">
      <div class="errorBox__title">Chargement impossible</div>
      <div class="errorBox__text">{{ error }}</div>
      <button class="btn btn--ghost" type="button" @click="loadUsers">Réessayer</button>
    </div>

    <div v-else class="list">
      <UserBox v-for="user in filteredUsers" :key="user.id" :user="user" @deleted="removeUser" />

      <div v-if="!filteredUsers.length" class="empty card">
        <div class="muted">Aucun résultat.</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.head {
  padding: 12px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  justify-content: space-between;
}

.title {
  font-weight: 950;
  letter-spacing: 0.2px;
  font-size: 16px;
}

.meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 6px;
}

.pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-3);
  color: var(--c-text-soft);
  font-size: 12px;
  font-weight: 750;
}

.pill--ok {
  border-color: rgba(29, 185, 84, 0.45);
  background: #12311f;
  color: rgba(220, 255, 235, 0.95);
}

.head__right {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
}

.search {
  min-width: min(420px, 70vw);
}

.seg {
  display: inline-flex;
  border: 1px solid var(--c-border);
  background: var(--c-surface-2);
  border-radius: 999px;
  overflow: hidden;
}

.seg__btn {
  border: none;
  background: transparent;
  color: var(--c-text-mute);
  padding: 10px 12px;
  cursor: pointer;
  font-weight: 800;
  letter-spacing: 0.2px;
}

.seg__btn.is-on {
  background: var(--c-surface-3);
  color: var(--c-text);
}

.list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.state {
  padding: 12px;
  display: grid;
  gap: 10px;
}

.skeleton {
  height: 54px;
  border-radius: 14px;
  border: 1px solid var(--c-border);
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.04),
    rgba(255, 255, 255, 0.08),
    rgba(255, 255, 255, 0.04)
  );
  background-size: 200% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.errorBox {
  border: 1px solid rgba(255, 77, 79, 0.45);
  background: #3a1a1e;
}

.errorBox__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.errorBox__text {
  color: rgba(255, 245, 245, 0.92);
  font-size: 14px;
}

.empty {
  padding: 12px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

@media (max-width: 820px) {
  .head {
    flex-direction: column;
    align-items: stretch;
  }

  .head__right {
    justify-content: flex-start;
  }

  .search {
    min-width: 100%;
  }
}
</style>
