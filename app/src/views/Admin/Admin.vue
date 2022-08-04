<template>
  <div>
    <b-nav class="admin-navbar" tabs>
      <b-nav-item  to="/admin/users" active-class="active">Benutzer</b-nav-item>
      <b-nav-item  to="/admin/cycles" active-class="active" >Zyklen</b-nav-item >
    </b-nav>
    <router-view/>
  </div>
</template>

<script>
export default {
  name: "Admin"
}
</script>

<style>

</style>