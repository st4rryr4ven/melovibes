<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { getProfilePictureUrl } from '@/util/avatar'

const router = useRouter()
const authStore = useStoreAuthentification()

if (!authStore.utilisateurConnecte) {
  router.push({ name: 'login' })
}

const login = ref(authStore.utilisateurConnecte?.login ?? '')
const email = ref(authStore.utilisateurConnecte?.email ?? '')
const plainPassword = ref('')
const currentPlainPassword = ref('')
const profilePictureUrl = ref('')
const loading = ref(false)

async function updateProfilePicture() {
  if (email.value) {
    profilePictureUrl.value = await getProfilePictureUrl(email.value)
  }
}

onMounted(() => {
  updateProfilePicture()
})

watch(email, () => {
  updateProfilePicture()
})

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.src =
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+QXZhdGFyPC90ZXh0Pjwvc3ZnPg=='
}

async function update() {
  if (!currentPlainPassword.value) {
    alert('Vous devez entrer votre mot de passe actuel.')
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
        alert('Session expirée. Veuillez vous reconnecter.')
        await router.push({ name: 'login' })
        return
      }
      if (msg.includes('422')) {
        alert('Mot de passe incorrect.')
        return
      }
      alert(msg)
      return
    }

    login.value = authStore.utilisateurConnecte?.login ?? login.value
    email.value = authStore.utilisateurConnecte?.email ?? email.value
    plainPassword.value = ''
    currentPlainPassword.value = ''

    await updateProfilePicture()
    alert('Profil mis à jour')
  } finally {
    loading.value = false
  }
}

async function deleteAccount() {
  if (!confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) return

  loading.value = true
  try {
    const res = await authStore.deleteMyAccount()
    if (!res.success) {
      const msg = res.error ?? 'Erreur lors de la suppression'
      if (msg.includes('401')) {
        alert('Session expirée. Veuillez vous reconnecter.')
        await router.push({ name: 'login' })
        return
      }
      alert(msg)
      return
    }

    alert('Votre compte a été supprimé.')
    await router.push({ name: 'melovibes' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="profile-container">
    <h2>Mettre à jour mon profil</h2>

    <div class="profile-picture-section">
      <img
        :src="profilePictureUrl"
        :alt="`Avatar de ${login}`"
        class="profile-picture"
        @error="handleImageError"
      />
    </div>

    <form @submit.prevent="update" class="profile-form">
      <div class="form-group">
        <label for="login">Login</label>
        <input id="login" type="text" v-model="login" disabled />
        <small class="hint">Le login ne peut pas être modifié</small>
      </div>

      <div class="form-group">
        <label for="email">Adresse email</label>
        <input id="email" type="email" v-model="email" required />
      </div>

      <div class="form-group">
        <label for="plainPassword">
          Nouveau mot de passe
          <span class="hint">(laisser vide pour ne pas changer)</span>
        </label>
        <input id="plainPassword" type="password" v-model="plainPassword" />
      </div>

      <div class="form-group">
        <label for="currentPlainPassword">Mot de passe actuel</label>
        <input id="currentPlainPassword" type="password" v-model="currentPlainPassword" required />
      </div>

      <div class="form-actions">
        <button type="submit" :disabled="loading">{{ loading ? 'Mise à jour...' : 'Mettre à jour' }}</button>
        <button type="button" :disabled="loading" @click="router.push({ name: 'melovibes' })">Annuler</button>
        <button type="button" :disabled="loading" @click="deleteAccount" class="delete-button">Supprimer mon compte</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";
</style>
