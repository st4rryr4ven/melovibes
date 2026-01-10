<script setup lang="ts">
import type {User} from '@/types';
import {apiStore} from "@/util/apiStore.ts";
import {ref} from "vue";

const props = defineProps<{ user: User }>();

const emit = defineEmits<{
  (e: 'deleted', id: number): void
}>();

const loading = ref(false);

async function deleteUser() {
  if (!confirm(`Voulez-vous vraiment supprimer ${props.user.login} ?`)) return;

  try {
    loading.value = true;
    await apiStore.deleteUser(`${props.user.id}`);

    emit('deleted', props.user.id);

    alert('Utilisateur supprimé avec succès !');
  } catch (error) {
    console.error(error);
    alert('Erreur lors de la suppression de l’utilisateur.');
  } finally {
    loading.value = false;
  }
}
const isAdmin = () => props.user.roles.includes('ROLE_ADMIN');

const getRoleLabel = (role: string) => {
  switch(role) {
    case 'ROLE_ADMIN':
      return 'Administrateur';
    case 'ROLE_USER':
      return 'Utilisateur';
    default:
      return role;
  }
};
const roleLabels = props.user.roles.map(getRoleLabel);
</script>

<template>
  <div class="content-box">
    <div class="top">
      Profil de {{ user.login }}
    </div>
    <div class="content">
      <div class="group">
        <label>Login</label>
        <input :value="user.login">
      </div>
      <div class="group">
        <label>Adresse e-mail</label>
        <input :value="user.email">
      </div>
      <div class="group">
        <label>Rôles</label>
        <input :value="roleLabels.join(', ')" readonly>
      </div>
      <button
        v-if="!isAdmin()"
        @click="deleteUser"
        :disabled="loading"
        class="delete-button"
      >
        {{ loading ? 'Suppression...' : 'Supprimer l’utilisateur' }}
      </button>
    </div>
  </div>
</template>


<style scoped>
@import "@/components/css/content-box.css";
</style>
