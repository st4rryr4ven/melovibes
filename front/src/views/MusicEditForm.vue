<script setup lang="ts">
import {onMounted, ref} from 'vue'
import {useRoute, useRouter} from 'vue-router'
import type {Music} from '@/types'
import {apiStore} from '@/util/apiStore'

const route = useRoute()
const router = useRouter()

const music = ref<Music | null>(null)
const loading = ref(false)
const newGenre = ref('')

onMounted(async () => {
  music.value = await apiStore.get(`music/${route.params.id}`)
})

function addGenre() {
  if (!newGenre.value || music.value?.genre?.includes(newGenre.value)) return
  music.value!.genre!.push(newGenre.value)
  newGenre.value = ''
}

function removeGenre(i: number) {
  music.value!.genre!.splice(i, 1)
}

async function save() {
  loading.value = true

  await apiStore.patch(`music/${music.value!.id}`, {
    picture: music.value!.picture,
    link: music.value!.link,
    genre: music.value!.genre
  })

  loading.value = false
  router.back()
}
</script>

<template>
  <div v-if="music" class="edit-form">
    <h2>Modifier la musique</h2>

    <label>Image</label>
    <input v-model="music.picture"/>

    <label>Lien</label>
    <input v-model="music.link"/>

    <label>Genres</label>
    <div class="genres">
      <span v-for="(g, i) in music.genre" :key="g">
        {{ g }} <button @click="removeGenre(i)">✕</button>
      </span>
    </div>

    <input
      v-model="newGenre"
      placeholder="Ajouter un genre"
      @keyup.enter="addGenre"
    />

    <div class="actions">
      <button @click="save" :disabled="loading">💾 Sauvegarder</button>
      <button @click="router.back()">Annuler</button>
    </div>
  </div>
</template>

<style scoped>
.edit-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

input {
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
}

.genres {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.genre-chip {
  background: #f1f5f9;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 12px;
}

.genre-chip button {
  background: none;
  border: none;
  margin-left: 6px;
  cursor: pointer;
}

.add-genre {
  display: flex;
  gap: 8px;
}

.actions {
  display: flex;
  gap: 10px;
}
</style>



<script setup lang="ts">
import { computed, onMounted, ref, watchEffect } from 'vue'
import type { Artist, Music } from '@/types'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { apiStore } from '@/util/apiStore'
import { useRouter } from 'vue-router'

const props = defineProps<{ music: Music }>()
const emit = defineEmits<{
  (e: 'deleted', id: number): void
  (e: 'validated', id: number): void
}>()

const router = useRouter()
const authStore = useStoreAuthentification()
const isFavorite = ref(false)
const loading = ref(false)

/* ================= FAVORITES ================= */
async function initFavorite() {
  if (!authStore.utilisateurConnecte) {
    isFavorite.value = false
    return
  }

  const user = await apiStore.me()
  if (!user.favoriteMusic) return

  isFavorite.value = user.favoriteMusic.some(
    (m: any) => m.id === props.music.id
  )
}

initFavorite()

watchEffect(() => {
  if (!authStore.utilisateurConnecte) {
    isFavorite.value = false
  }
})

async function toggleFavorite() {
  if (!authStore.utilisateurConnecte) {
    alert('Vous devez être connecté.')
    return
  }

  await apiStore.toggleFavorite(
    authStore.utilisateurConnecte.id,
    props.music.id
  )
  await initFavorite()
}

/* ================= ADMIN ================= */
const isAdmin = computed(() =>
  authStore.utilisateurConnecte?.roles?.includes('ROLE_ADMIN')
)

function goToEdit() {
  router.push(`/music/${props.music.id}/edit`)
}

async function deleteMusic() {
  if (!confirm('Supprimer cette musique ?')) return
  loading.value = true
  await apiStore.delete(`music/${props.music.id}`)
  emit('deleted', props.music.id)
  loading.value = false
}

async function validateMusic() {
  if (!confirm(`Valider ${props.music.title} ?`)) return
  loading.value = true
  await apiStore.patch(`music/${props.music.id}`, { isValidated: true })
  emit('validated', props.music.id)
  loading.value = false
}

/* ================= ARTISTS ================= */
async function resolveArtists(artists: Artist[]) {
  const baseUrl = import.meta.env.VITE_API_URL.replace(/\/$/, '')
  return Promise.all(
    artists.map(async iri => {
      const res = await fetch(`${baseUrl}${iri.replace('/api/', '/')}`)
      return (await res.json()).name
    })
  )
}

const artistNames = ref<string[]>([])
onMounted(async () => {
  artistNames.value = await resolveArtists(props.music.artists)
})
</script>
