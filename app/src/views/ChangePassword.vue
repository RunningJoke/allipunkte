<template>
  <b-form @submit="changePassword" class="passwordChange" >
	<b-form-group
      id="fieldset-horizontal"
      label-cols-sm="4"
      label-cols-lg="3"
      label="Aktuelles Passwort"
      label-for="input-horizontal"
		>
		<b-form-input type="password" v-model="password.oldPassword" ></b-form-input>
	</b-form-group>
	<b-form-group
      id="fieldset-horizontal"
      label-cols-sm="4"
      label-cols-lg="3"
      label="Neues Passwort"
      label-for="input-horizontal"
		>
		<b-form-input type="password" v-model="password.newPassword" ></b-form-input>
	</b-form-group>
	<b-form-group
      id="fieldset-horizontal"
      label-cols-sm="4"
      label-cols-lg="3"
      label="Passwort wiederholen"
      label-for="input-horizontal"
		>
		<b-form-input type="password" v-model="password.newPasswordVerification" ></b-form-input>
	</b-form-group>
	<b-button type="submit" variant="success"  :disabled="password.newPassword != password.newPasswordVerification">
		<b-spinner size="sm" v-if="isProcessing" class="mr-2" /> Passwort ändern
	</b-button>
  </b-form>
</template>

<style>
	.passwordChange {
		padding: 1em;
	}
</style>

<script>
// @ is an alias to /src

import config from '@/config.json'

export default {
  name: 'ChangePassword',
  data() {
	return { 
		password: { oldPassword: "", newPasswordVerification: "", newPassword: ""},
		isProcessing: false
	}
  },
  methods: {
	changePassword: async function(ev) {
		ev.preventDefault()
		this.isProcessing = true
		try {
			let resp = await this.$request.sendJsonRequest(config.baseUrl+"updatePassword", 'POST', this.password)
			this.$bvToast.toast("Passwort erfolgreich aktualisiert", {title: "Aktualisierung erfolgreich",  variant: "success", solid: true, "auto-hide-delay": 1000})
		} catch {
			this.$bvToast.toast("Passwortaktualisierung fehlgeschlagen", {title: "Aktualisierung fehlgeschlagen", variant: "danger", solid: true, "auto-hide-delay": 1000})
		} finally {
			this.isProcessing = false
		}
	}
  }
}
</script>
