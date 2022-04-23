<template>
  <div id="app">
    <b-nav>
      <b-link to="/send">Senden</b-link>
      <b-link to="/score">Punkte</b-link>
      <b-link to="/changePassword">Passwort ändern</b-link>
      <b-link @click="logout">Logout</b-link>
      <b-link to="/admin" v-if="$store.getters.isLoggedInUserAdmin">Admin</b-link>
    </b-nav>
    <router-view/>
  </div>
</template>
<script>
export default {
  name: "Main",
  methods: {
    logout() {
      this.$store.dispatch('logoutUser')
    }
  }
}
</script>
<style lang="scss">
@import "~@/assets/scss/vendors/bootstrap-vue/index";
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}

#nav {
  padding: 30px;

  a {
    font-weight: bold;
    color: #2c3e50;

    &.router-link-exact-active {
      color: #42b983;
    }
  }
}
</style>
