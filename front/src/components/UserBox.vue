<script setup lang="ts">
import type {User} from '@/types';

defineProps<{ user: User }>();
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
    </div>
  </div>
</template>


<style scoped>
@import "@/components/css/content-box.css";
</style>
