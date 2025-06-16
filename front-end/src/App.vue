<template>
  <!-- Sólo delega al layout adecuado -->
  <router-view/>
</template>

<script setup>
defineOptions({ name: 'AppRoot' })
</script>