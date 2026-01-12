<script setup lang="ts">
import { computed, ref } from 'vue'
import type { User } from '@/types'
import { userApi } from '@/api/userApi'

const props = defineProps<{ user: User }>()

const emit = defineEmits<{
  (e: 'deleted', id: number): void
}>()

const loading = ref(false)

const isAdmin = computed(() => props.user.roles.includes('ROLE_ADMIN'))

const roleLabels = computed(() => {
  const mapRole = (role: string) => {
    switch (role) {
      case 'ROLE_ADMIN':
        return 'Administrateur'
      case 'ROLE_USER':
        return 'Utilisateur'
      default:
        return role
    }
  }

  return (props.user.roles ?? []).map(mapRole)
})

async function deleteUser(): Promise<void> {
  if (!confirm(`Voulez-vous vraiment supprimer ${props.user.login} ?`)) return

  try {
    loading.value = true
    await userApi.delete(props.user.id)
    emit('deleted', props.user.id)
    alert('Utilisateur supprimé avec succès !')
  } catch (e: any) {
    console.error(e)
    alert(e?.message ?? 'Erreur lors de la suppression de l’utilisateur.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="content-box">
    <div class="top">Profil de {{ user.login }}</div>

    <div class="content">
      <div class="group">
        <label>Login</label>
        <input :value="user.login" readonly />
      </div>

      <div class="group">
        <label>Adresse e-mail</label>
        <input :value="user.email" readonly />
      </div>

      <div class="group">
        <label>Rôles</label>
        <input :value="roleLabels.join(', ')" readonly />
      </div>

      <button v-if="!isAdmin" class="delete-button" type="button" :disabled="loading" @click="deleteUser">
        {{ loading ? 'Suppression...' : 'Supprimer l’utilisateur' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";
</style>
