<template>
  <b-form @submit="loginClickHandler" class="login" v-if="!$store.getters.isLoggedIn">
	<b-form-group
      id="fieldset-horizontal"
      label-cols-sm="4"
      label-cols-lg="3"
      label="Benutzername"
      label-for="input-horizontal"
		>
		<b-form-input type="text" v-model="login.username" ></b-form-input>
	</b-form-group>
	<b-form-group
      id="fieldset-horizontal"
      label-cols-sm="4"
      label-cols-lg="3"
      label="Passwort"
      label-for="input-horizontal"
		>
		<b-form-input type="password" v-model="login.password" ></b-form-input>
	</b-form-group>
	<div class="d-flex justify-content-between">
		<b-button type="submit"><b-spinner size="sm" v-if="isLoggingIn" class="mr-2" /> Login</b-button>
		<b-link class="ml-auto p-1" href="https://localhost:8000/reset-password">Passwort vergessen?</b-link>
	</div>
  </b-form>
  
  
</template>

<style>
	.login {
		padding: 1em;
	}
</style>

<script>
// @ is an alias to /src

export default {
  name: 'Login',
  data() {
	return { 
		login: { username: "", password: ""},
		isLoggingIn: false
	}
  },
  methods: {
	loginClickHandler: async function(ev) {
		ev.preventDefault()
		this.isLoggingIn = true
		try {
			let resp = await this.$store.dispatch('logInUser', this.login)
			this.$bvToast.toast("Login erfolgreich", {title: "Login erfolgreich",  variant: "success", solid: true, "auto-hide-delay": 1000})
			await this.$store.dispatch("loadUserData")
			this.$router.push('/score')
		} catch {
			this.$bvToast.toast("Ungültige Zugangsdaten", {title: "Login fehlgeschlagen", variant: "danger", solid: true, "auto-hide-delay": 1000})
		} finally {
			this.isLoggingIn = false
		}
	}
  }
}
</script>
