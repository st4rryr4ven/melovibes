<template>
  <div id="wrapper">
    <header>
      <h1 @click="router.push({ name: 'melovibes' })">Mélovibes</h1>
      <nav>
        <div @click="router.push({ name: 'music' })">Les musiques</div>
        <div v-if="authStore.estConnecte" @click="router.push({ name: 'music-create' })">Créer une
          musique
        </div>
        <div v-if="!authStore.estConnecte" @click="router.push({ name: 'register' })">S'inscrire
        </div>
        <div v-if="!authStore.estConnecte" @click="router.push({ name: 'login' })">Se connecter
        </div>
        <div v-if="authStore.estConnecte" @click="router.push({ name: 'profile' })">Mon profil</div>
        <div v-if="authStore.estConnecte" @click="logout">Se déconnecter</div>
      </nav>
    </header>
    <main>
      <router-view/>
    </main>
  </div>
</template>

<script setup lang="ts">
import {useRouter} from 'vue-router'
import {useStoreAuthentification} from '@/stores/storeAuthentification'

const authStore = useStoreAuthentification();
authStore.init();

const router = useRouter()

function logout() {
  authStore.logout()
    .then(() => {
      router.push({name: 'melovibes'})
    })
    .catch(err => {
      alert(err.message)
    })
}
</script>


<style scoped>
#wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
  background-color: rgb(225, 235, 250);
}

header {
  width: 100%;
  position: sticky;
  top: 0;
  background-color: rgb(20, 101, 66);
  padding: 20px;
}

header h1 {
  text-align: center;
  font-family: helvetica, serif;
  font-weight: 700;
  cursor: pointer;
}

nav {
  box-shadow: 0 0 0.5rem #999;
  display: flex;
  justify-content: space-evenly;
  width: 80%;
  margin: 0 auto;
}

nav > div {
  padding: 10px;
  background-color: rgb(7, 48, 30);
  flex-grow: 1;
  text-align: center;
  border: solid #000 1px;
  cursor: pointer;
}

nav > div:hover {
  box-shadow: 0 0 0.3rem #000;
}

main {
  max-width: 1280px;
  width: 780px;
  padding: 10px;
  background-color: rgb(225, 240, 255);
  flex-grow: 1;
}

@media (max-width: 800px) {
  main {
    width: 80%;
  }
}
</style>
