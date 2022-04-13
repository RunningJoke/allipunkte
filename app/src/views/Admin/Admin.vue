<template>
  <div>
    <b-nav class="admin-navbar">
      <b-link to="/admin/users" >Benutzer</b-link>
      <b-link to="/admin/cycles" >Zyklen</b-link>
    </b-nav>
    <router-view/>
  </div>
</template>

<script>
export default {

}
</script>

<style>

</style>