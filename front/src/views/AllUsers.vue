<script setup lang="ts">

import {ref, type Ref} from 'vue';
import UserBox from '@/components/UserBox.vue';
import type {User} from '@/types';
import {apiStore} from '@/util/apiStore';

const users: Ref<User[]> = ref([])
apiStore.getAll('users').then((data) => {
  users.value = data as User[];
});

</script>
<template>
  <div>
    <UserBox v-for="user in users" :key="user.id" :user="user"/>
  </div>
</template>
