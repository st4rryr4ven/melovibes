<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { getProfilePictureUrl } from '@/util/avatar'
import { useFlashStore } from '@/stores/flashStore'

const router = useRouter()
const authStore = useStoreAuthentification()
const flash = useFlashStore()

const loading = ref(false)

const login = ref('')
const email = ref('')
const plainPassword = ref('')
const currentPlainPassword = ref('')
const profilePictureUrl = ref('')

const hasUser = computed(() => !!authStore.utilisateurConnecte)

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

async function updateProfilePicture() {
  if (!email.value) return
  profilePictureUrl.value = await getProfilePictureUrl(email.value)
}

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.src =
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzBiMTIxYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE2IiBmaWxsPSJyZ2JhKDI0NSwyNDgsMjUyLDAuNzUpIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+QXZhdGFyPC90ZXh0Pjwvc3ZnPg=='
}

function syncFromStore() {
  login.value = authStore.utilisateurConnecte?.login ?? ''
  email.value = authStore.utilisateurConnecte?.email ?? ''
}

onMounted(async () => {
  if (!authStore.utilisateurConnecte) {
    await router.replace({ name: 'login' })
    return
  }
  syncFromStore()
  await updateProfilePicture()
})

watch(
  () => authStore.utilisateurConnecte?.email,
  async () => {
    if (!authStore.utilisateurConnecte) return
    syncFromStore()
    await updateProfilePicture()
  }
)

watch(email, async () => {
  await updateProfilePicture()
})

async function update(): Promise<void> {
  if (!currentPlainPassword.value) {
    flash.warning('Mot de passe actuel requis.')
    return
  }

  loading.value = true
  try {
    const res = await authStore.updateMyProfile({
      email: email.value,
      plainPassword: plainPassword.value || undefined,
      currentPlainPassword: currentPlainPassword.value
    })

    if (!res.success) {
      const msg = res.error ?? 'Erreur lors de la mise à jour.'
      if (msg.includes('401')) {
        flash.warning('Session expirée. Reconnexion requise.')
        await router.push({ name: 'login' })
        return
      }
      if (msg.includes('422')) {
        flash.error('Mot de passe incorrect.')
        return
      }
      flash.error(msg)
      return
    }

    syncFromStore()
    plainPassword.value = ''
    currentPlainPassword.value = ''
    await updateProfilePicture()

    flash.success('Profil mis à jour.')
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la mise à jour.'))
  } finally {
    loading.value = false
  }
}

async function deleteAccount(): Promise<void> {
  const ok = confirm('Supprimer votre compte ? Cette action est irréversible.')
  if (!ok) return

  loading.value = true
  try {
    const res = await authStore.deleteMyAccount()
    if (!res.success) {
      const msg = res.error ?? 'Erreur lors de la suppression.'
      if (msg.includes('401')) {
        flash.warning('Session expirée. Reconnexion requise.')
        await router.push({ name: 'login' })
        return
      }
      flash.error(msg)
      return
    }

    flash.success('Compte supprimé.')
    await router.push({ name: 'melovibes' })
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la suppression.'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="hasUser" class="page">
    <div class="top">
      <div class="top__title">Mon profil</div>
      <button class="btn btn--ghost" type="button" :disabled="loading" @click="router.push({ name: 'melovibes' })">
        Retour
      </button>
    </div>

    <section class="layout">
      <div class="side card">
        <div class="side__header">
          <div class="avatar">
            <img :src="profilePictureUrl" :alt="`Avatar de ${login}`" class="avatar__img" @error="handleImageError" />
          </div>

          <div class="side__identity">
            <div class="side__login">{{ login }}</div>
            <div class="side__email muted">{{ email }}</div>
          </div>
        </div>
      </div>

      <div class="main card">
        <form class="form" @submit.prevent="update">
          <div class="grid">
            <div class="field">
              <label class="label" for="login">Login</label>
              <input id="login" class="input" type="text" v-model="login" disabled />
              <div class="hint muted">Non modifiable</div>
            </div>

            <div class="field">
              <label class="label" for="email">Email</label>
              <input id="email" class="input" type="email" v-model="email" required />
            </div>

            <div class="field">
              <label class="label" for="plainPassword">
                Nouveau mot de passe <span class="hint muted">(optionnel)</span>
              </label>
              <input id="plainPassword" class="input" type="password" v-model="plainPassword" />
            </div>

            <div class="field">
              <label class="label" for="currentPlainPassword">Mot de passe actuel</label>
              <input id="currentPlainPassword" class="input" type="password" v-model="currentPlainPassword" required />
            </div>
          </div>

          <div class="actions">
            <button class="btn btn--primary" type="submit" :disabled="loading">
              {{ loading ? 'Mise à jour…' : 'Enregistrer' }}
            </button>
            <button class="btn btn--ghost" type="button" :disabled="loading" @click="router.push({ name: 'melovibes' })">
              Annuler
            </button>
            <button class="btn btn--danger" type="button" :disabled="loading" @click="deleteAccount">
              Supprimer mon compte
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.top__title {
  font-size: 18px;
  font-weight: 950;
  letter-spacing: 0.2px;
}

.layout {
  display: grid;
  grid-template-columns: minmax(260px, 340px) 1fr;
  gap: 14px;
  align-items: start;
}

.side {
  padding: 14px;
  display: grid;
  gap: 12px;
}

.side__header {
  display: grid;
  grid-template-columns: 74px 1fr;
  gap: 12px;
  align-items: center;
}

.avatar {
  width: 74px;
  height: 74px;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(29, 185, 84, 0.35);
  background: #0b121a;
  box-shadow: 0 0 0 6px rgba(29, 185, 84, 0.10);
}

.avatar__img {
  width: 74px;
  height: 74px;
  object-fit: cover;
}

.side__identity {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.side__login {
  font-weight: 950;
  letter-spacing: 0.2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.side__email {
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.side__text {
  font-size: 13px;
  line-height: 1.45;
}

.side__tips {
  padding: 12px;
  display: grid;
  gap: 10px;
}

.tip {
  display: grid;
  grid-template-columns: 10px 1fr;
  gap: 10px;
  align-items: start;
  color: rgba(245, 248, 252, 0.82);
  font-weight: 650;
  line-height: 1.35;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  margin-top: 4px;
  background: linear-gradient(90deg, rgba(29, 185, 84, 1), rgba(17, 217, 138, 1));
  box-shadow: 0 0 0 4px rgba(29, 185, 84, 0.12);
}

.main {
  padding: 14px;
}

.form {
  display: grid;
  gap: 12px;
}

.grid {
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
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  padding-top: 6px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }
}
</style>
