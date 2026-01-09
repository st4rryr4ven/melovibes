<script setup lang="ts">
import {useRoute} from 'vue-router'
import UserBox from '@/components/UserBox.vue';
import type {User} from '@/types';

const route = useRoute()
const userId = Number(route.params.id)
const user: User = {
  id: userId,
  login: "user" + userId,
  email: "user" + userId + "@example.com"
};
</script>

<template>
  <UserBox :user="user"/>
</template>
