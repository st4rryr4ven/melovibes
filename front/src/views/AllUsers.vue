<script setup lang="ts">

import {ref, type Ref, computed} from 'vue';
import UserBox from '@/components/UserBox.vue';
import type {User} from '@/types';
import {apiStore} from '@/util/apiStore';


const users = ref<User[]>([]);
const search = ref('');

apiStore.getAll('users')
  .then((data) => {
    console.log('USERS FROM API', data);
    users.value = data.filter(u => u.id && u.login && u.email && u.roles);
  })
  .catch(console.error);


const filteredUsers = computed(() => {
  const term = search.value.toLowerCase();
  return users.value.filter(user =>
    user.login.toLowerCase().includes(term) ||
    user.email.toLowerCase().includes(term)
  );
});

function removeUser(id: number) {
  users.value = users.value.filter(u => u.id !== id);
}

</script>
<template>
  <div>
    <input
      v-model="search"
      type="text"
      placeholder="Rechercher un utilisateur..."
      class="search-input"
    />

    <UserBox
      v-for="user in filteredUsers"
      :key="user.id"
      :user="user"
      @deleted="removeUser"
    />
  </div>
</template>

<style scoped>
.search-input {
  width: 100%;
  padding: 8px 12px;
  margin-bottom: 16px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
</style>
