<script setup lang="ts">
import {ref} from 'vue'
import {apiStore} from '@/util/apiStore'
import {useRouter} from 'vue-router'

const router = useRouter()

const newUser = ref({
  login: '',
  password: '',
  email: ''
})

async function register() {
  try {
    const response = await apiStore.register(newUser.value)
    if (!response.ok) throw new Error('Registration failed')
    alert('Utilisateur créé avec succès !')
    router.push({name: 'login'})
  } catch (err) {
    console.error(err)
    alert('Erreur lors de l\'inscription')
  }
}
</script>

<template>
  <div class="wrapper">
    <h3>Créer un compte</h3>
    <form @submit.prevent="register">
      <div>
        <label>Login</label>
        <input v-model="newUser.login"/>
      </div>
      <div>
        <label>Email</label>
        <input v-model="newUser.email" type="email"/>
      </div>
      <div>
        <label>Mot de passe</label>
        <input v-model="newUser.password" type="password"/>
      </div>
      <button type="submit">S'inscrire</button>
    </form>
  </div>
</template>
