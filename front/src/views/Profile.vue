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
        <input
          id="login"
          type="text"
          v-model="login"
          disabled
        />
        <small class="hint">Le login ne peut pas être modifié</small>
      </div>

      <div class="form-group">
        <label for="email">Adresse email</label>
        <input
          id="email"
          type="email"
          v-model="email"
          required
        />
      </div>

      <div class="form-group">
        <label for="plainPassword">
          Nouveau mot de passe
          <span class="hint">(laisser vide pour ne pas changer)</span>
        </label>
        <input
          id="plainPassword"
          type="password"
          v-model="plainPassword"
        />
      </div>

      <div class="form-group">
        <label for="currentPlainPassword">
          Mot de passe actuel
        </label>
        <input
          id="currentPlainPassword"
          type="password"
          v-model="currentPlainPassword"
          required
        />
      </div>

      <div class="form-actions">
        <button type="submit">Mettre à jour</button>
        <button type="button" @click="router.push({ name: 'melovibes' })">
          Annuler
        </button>
        <button type="button" @click="deleteAccount" class="delete-button">
          Supprimer mon compte
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import {ref, computed, onMounted, watch} from 'vue'
import {useRouter} from 'vue-router'
import {storeAuthentification as store} from '@/stores/storeAuthentification'
import {apiStore, getProfilePictureUrl} from '@/util/apiStore'

const router = useRouter()

if (!store.utilisateurConnecte) {
  router.push({name: 'login'})
}

const login = ref(store.utilisateurConnecte?.login ?? '')
const email = ref(store.utilisateurConnecte?.email ?? '')
const plainPassword = ref('')
const currentPlainPassword = ref('')
const profilePictureUrl = ref('')
const imageError = ref(false)

const updateProfilePicture = async () => {
  if (email.value) {
    profilePictureUrl.value = await getProfilePictureUrl(email.value)
  }
}

onMounted(() => {
  updateProfilePicture()
})

watch(email, () => {
  imageError.value = false
  updateProfilePicture()
})

function handleImageError(event: Event) {
  imageError.value = true
  const img = event.target as HTMLImageElement
  // Set a default placeholder or hide the image
  img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+QXZhdGFyPC90ZXh0Pjwvc3ZnPg=='
}

function update() {
  if (!currentPlainPassword.value) {
    alert("Vous devez entrer votre mot de passe actuel.")
    return
  }

  const token = store.utilisateurConnecte?.token || apiStore.currentToken ||
                (typeof localStorage !== 'undefined' ? localStorage.getItem('jwt_token') : null)

  apiStore.updateUser(store.utilisateurConnecte!.id, {
    login: login.value,
    email: email.value,
    plainPassword: plainPassword.value || undefined,
    currentPlainPassword: currentPlainPassword.value
  }, token)
    .then(user => {
      store.utilisateurConnecte = { ...user, token: user.token || token }
      updateProfilePicture()
      alert('Profil mis à jour')
    })
    .catch(err => {
      console.error('Update error:', err)
      if (err.message && err.message.includes('401') || err.message.includes('Unauthorized')) {
        alert("Session expirée. Veuillez vous reconnecter.")
        router.push({name: 'login'})
      } else {
        alert(err.message || 'Erreur lors de la mise à jour')
      }
    })
}

function deleteAccount() {
  if (!confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
    return
  }

  if (!currentPlainPassword.value) {
    alert("Vous devez entrer votre mot de passe actuel pour supprimer votre compte.")
    return
  }

  const token = store.utilisateurConnecte?.token || apiStore.currentToken ||
                (typeof localStorage !== 'undefined' ? localStorage.getItem('jwt_token') : null)

  apiStore.deleteUser(store.utilisateurConnecte!.id, token)
    .then(() => {
      store.utilisateurConnecte = null
      store.estConnecte = false
      alert('Votre compte a été supprimé.')
      router.push({name: 'melovibes'})
    })
    .catch(err => {
      console.error('Delete error:', err)
      if (err.message && (err.message.includes('401') || err.message.includes('Unauthorized'))) {
        alert("Session expirée. Veuillez vous reconnecter.")
        router.push({name: 'login'})
      } else {
        alert(err.message || 'Erreur lors de la suppression')
      }
    })
}
</script>
<style scoped>
@import "@/components/css/layout.css";

.profile-picture-section {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.profile-picture {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #146542;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.delete-button {
  background-color: #dc3545;
  color: white;
  margin-top: 10px;
}

.delete-button:hover {
  background-color: #c82333;
}
</style>

