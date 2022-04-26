<template>
  <b-container fluid>
            <b-row align-h="start">
                <b-col cols="12" md="4">
                    <b-form-group
                        label="Vorname"
                        label-for="firstname"
                    >
                        <b-form-input
                        id="firstname"
                        v-model="userData.firstname"
                        type="text"
                        :disabled="disabled"
                        placeholder="Vorname"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="4">
                    <b-form-group
                        label="Nachname"
                        label-for="lastname"
                    >
                        <b-form-input
                        id="lastname"
                        v-model="userData.lastname"
                        type="text"
                        :disabled="disabled"
                        placeholder="Nachname"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="4">
                    <b-form-group
                        label="Benutzername"
                        label-for="username"
                    >
                        <b-form-input
                        id="username"
                        v-model="userData.username"
                        type="text"
                        :disabled="disabled"
                        placeholder="Benutzername"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row align-h="start">
                <b-col  cols="12" md="8">
                    <b-form-group
                        label="E-Mail"
                        label-for="mail"
                    >
                        <b-form-input
                        id="mail"
                        v-model="userData.mail"
                        type="email"
                        :disabled="disabled"
                        placeholder="Mail"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="4">
                    <b-form-group
                        label="Lizenznummer"
                        label-for="license"
                    >
                        <b-form-input
                        id="license"
                        v-model="userData.license"
                        type="text"
                        :disabled="disabled"
                        placeholder="Lizenznummer"   
                        required                     
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>

            <b-row align-h="start">
                <b-col  cols="12" md="3">
                    <b-form-group
                        label="zu erreichende Punkte"
                        label-for="targetAmount"
                    >
                        <b-form-input
                        id="targetAmount"
                        v-model.number="userData.targetAmount"
                        type="number"
                        :disabled="disabled"
                        placeholder="10"   
                        required                     
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="5">
                    <b-form-checkbox
                        v-model="userData.isCreator"
                        :disabled="disabled">
                        kann Punkte generieren
                    </b-form-checkbox>
                </b-col>
                <b-col  cols="12" md="4">
                    <b-form-checkbox
                        v-model="userData.isAdmin"
                        :disabled="disabled">
                        Administrator
                    </b-form-checkbox>
                    
                </b-col>
            </b-row>
            </b-container>
</template>

<script>
export default {
    name: "UserForm",
    props: {
        value: {
            type: Object
        },
        disabled: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    watch: {
        'value' : {
            immediate: true,
            handler: function() {
                this.userData = this.value
            }
        },
        'userData' : {
            handler: function() {
                this.$emit('input', this.userData)
            },
            deep: true
        }
    },
    data() { 
        return {
            userData: {
                username: '',
                firstname: '',
                lastname: '',
                mail: '',
                license: '',
                targetAmount: 0,
                isAdmin: false,
                isCreator: false
            }
        }
    }
}
</script>

<style>

</style>