<script setup lang="ts">

import {ref, type Ref} from 'vue';
import UserBox from '@/components/UserBox.vue';
import type {User} from '@/types';
import {apiStore} from '@/util/apiStore';


const users = ref<User[]>([])

apiStore.getAll('users')
  .then((data) => {
    console.log('USERS FROM API', data)
    users.value = data
  })
  .catch(console.error)

;

</script>
<template>
  <div>
    <UserBox v-for="user in users" :key="user.id" :user="user"/>
  </div>

</template>
