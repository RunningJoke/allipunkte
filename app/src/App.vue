<template>
  <div id="app">
    <div class="d-flex flex-column justify-content-between vh-100">
      <header>
          <b-navbar toggleable="lg" type="dark" variant="primary">
          <b-navbar-brand href="#"><img src="./assets/skyline.svg" height="30px"/></b-navbar-brand>

          <b-navbar-toggle target="nav-collapse"></b-navbar-toggle>

          <b-collapse id="nav-collapse" is-nav>      
              <b-navbar-nav v-if="$store.getters.isLoggedIn">  
                <b-nav-item variant="dark" to="/petitions">Angebote</b-nav-item>
                <b-nav-item variant="dark" to="/score">Transaktionen</b-nav-item>
                <b-nav-item variant="dark" to="/send">Punkte senden</b-nav-item>
                <b-nav-item variant="dark" to="/changePassword">Passwort ändern</b-nav-item>
                <b-nav-item variant="dark" to="/admin" v-if="$store.getters.isLoggedInUserAdmin">Admin</b-nav-item>
                <b-nav-item variant="dark" @click="logout">Ausloggen</b-nav-item>
              </b-navbar-nav>
              <b-navbar-nav v-else>  
                <b-nav-item variant="dark" to="/">Einloggen</b-nav-item>
              </b-navbar-nav>
          </b-collapse>
        </b-navbar>
      </header>
      <main class="overflow-auto h-100 py-1">
        <router-view/>
      </main>
      <footer class="app--footer bg-primary d-flex justify-content-center align-items-center">
        <h4 v-if="$store.getters.isLoggedIn" class="text-white p-1 mb-0">Deine Punkte: {{ $store.getters.getUserScore || 0 }} / {{ $store.getters.getTargetScore || 0 }}</h4>
        <h4 v-else class="text-white p-1 mb-0">&nbsp;</h4>
      </footer>
    </div>
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
@import "./style/_custom.scss";


#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}


</style>
