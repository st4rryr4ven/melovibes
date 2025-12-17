<script setup lang="ts">
import {ref} from 'vue'
import {useRouter} from 'vue-router'
import {storeAuthentification} from '@/stores/storeAuthentification'

const router = useRouter()
const connectingUser = ref({login: '', password: ''})
const errorMsg = ref<string | null>(null)

function connect(): void {
  storeAuthentification.login(connectingUser.value.login, connectingUser.value.password)
      .then(result => {
        if (result.success) {
          router.push({name: 'melovibes'})
        } else {
          errorMsg.value = result.error ?? null
          alert(result.error)
        }
      })
}
</script>


<template>
  <div class="wrapper">
    <div class="top">
      <h3>Création du profil</h3>
    </div>
    <form @submit.prevent="connect" class="content">
      <div class="group">
        <label>Login</label>
        <input v-model="connectingUser.login">
      </div>
      <div class="group">
        <label>Mot de passe</label>
        <input type="password" v-model="connectingUser.password">
      </div>
      <button type="submit">
        Connexion
      </button>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";
</style>
