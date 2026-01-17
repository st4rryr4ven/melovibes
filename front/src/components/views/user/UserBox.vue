<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { User } from '@/types.ts'
import { userApi } from '@/api/userApi.ts'
import { useFlashStore } from '@/stores/flashStore.ts'
import { getProfilePictureUrl } from '@/util/avatar.ts'

const props = defineProps<{ user: User }>()

const emit = defineEmits<{
  (e: 'deleted', id: number): void
}>()

const router = useRouter()
const flash = useFlashStore()

const busy = ref(false)
const avatarUrl = ref('')

const isAdmin = computed(() => (props.user.roles ?? []).includes('ROLE_ADMIN'))
const rolesLabel = computed(() => (props.user.roles ?? []).join(', '))
const favoritesCount = computed(() => props.user.favoriteMusic?.length ?? null)

async function loadAvatar(): Promise<void> {
  const email = props.user.email
  if (!email) {
    avatarUrl.value = ''
    return
  }

  try {
    avatarUrl.value = await getProfilePictureUrl(email)
  } catch {
    avatarUrl.value = ''
  }
}

function goUser(): void {
  router.push({ name: 'singleUser', params: { id: props.user.id } })
}

async function deleteUser(): Promise<void> {
  if (isAdmin.value) return
  if (!confirm(`Supprimer ${props.user.login} ?`)) return

  busy.value = true
  try {
    await userApi.delete(props.user.id)
    emit('deleted', props.user.id)
    flash.success('Utilisateur supprimé.')
  } catch (e: unknown) {
    const msg = e instanceof Error ? e.message : 'Erreur lors de la suppression.'
    flash.error(msg)
  } finally {
    busy.value = false
  }
}

onMounted(loadAvatar)
watch(
  () => props.user.email,
  async () => {
    await loadAvatar()
  }
)
</script>

<template>
  <article class="row card">
    <div class="media" :class="{ 'media--empty': !avatarUrl }" @click="goUser" role="button" tabindex="0">
      <img v-if="avatarUrl" class="media__img" :src="avatarUrl" :alt="props.user.login" />
      <div v-else class="media__ph" aria-hidden="true">@</div>
      <div class="badge" :class="isAdmin ? 'badge--ok' : 'badge--soft'">{{ isAdmin ? 'ADMIN' : 'USER' }}</div>
    </div>

    <div class="main" @click="goUser" role="button" tabindex="0">
      <div class="top">
        <div class="login" :title="props.user.login">{{ props.user.login }}</div>
        <div class="ids">
          <span class="id">#{{ props.user.id }}</span>
          <span v-if="favoritesCount !== null" class="id id--soft">Fav {{ favoritesCount }}</span>
        </div>
      </div>

      <div class="email" :title="props.user.email">{{ props.user.email }}</div>
      <div class="roles" :title="rolesLabel">{{ rolesLabel }}</div>
    </div>

    <div class="actions">
      <button class="btn btn--ghost" type="button" :disabled="busy" @click="goUser">Voir</button>
      <button v-if="!isAdmin" class="btn btn--danger" type="button" :disabled="busy" @click="deleteUser">
        Supprimer
      </button>
    </div>
  </article>
</template>

<style scoped>
.row {
  padding: 12px;
  display: grid;
  grid-template-columns: 64px 1fr auto;
  gap: 12px;
  align-items: center;
}

.media {
  position: relative;
  width: 64px;
  height: 64px;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--c-border);
  background: #0b121a;
  cursor: pointer;
}

.media__img {
  width: 64px;
  height: 64px;
  object-fit: cover;
}

.media__ph {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  font-weight: 950;
  color: rgba(245, 248, 252, 0.72);
  background: radial-gradient(90px 90px at 25% 20%, rgba(29, 185, 84, 0.22), transparent 60%),
  radial-gradient(120px 120px at 90% 85%, rgba(17, 217, 138, 0.16), transparent 60%),
  #0b121a;
}

.badge {
  position: absolute;
  left: 6px;
  bottom: 6px;
  padding: 4px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 900;
  border: 1px solid var(--c-border);
  background: rgba(0, 0, 0, 0.55);
  color: rgba(245, 248, 252, 0.92);
}

.badge--ok {
  border-color: rgba(29, 185, 84, 0.45);
}

.badge--soft {
  opacity: 0.85;
}

.main {
  min-width: 0;
  display: grid;
  gap: 6px;
  cursor: pointer;
}

.top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
}

.login {
  font-weight: 950;
  letter-spacing: 0.2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ids {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.id {
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-3);
  color: var(--c-text-soft);
  font-weight: 750;
}

.id--soft {
  color: var(--c-text-mute);
}

.email {
  font-size: 13px;
  color: var(--c-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.roles {
  font-size: 12px;
  color: var(--c-text-mute);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

@media (max-width: 860px) {
  .row {
    grid-template-columns: 64px 1fr;
    align-items: start;
  }

  .actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
