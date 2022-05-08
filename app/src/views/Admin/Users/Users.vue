<template>
    <div class="text-left"><h4>Benutzerlisten</h4>

        <b-button variant="success" v-b-modal.createNewUser>
            <b-icon-person />
        </b-button>

        <b-table
            striped 
            hover 
            :items="userItems" 
            :fields="fields">
            <template #cell(actions)="row">
                <div class="d-flex flex-nowrap">
                    <b-button size="sm" @click="detailUser = row.item" v-b-modal.displayUser class="mr-1">
                    Details
                    </b-button>
                    <b-input-group prepend="Neuer Zielwert" size="sm">
                        <b-form-input type="number" v-model="newTarget" style="max-width: 100px;"></b-form-input>
                            <b-input-group-append>
                                <b-button variant="outline-success" @click="updateTargetScore(row.item)"><b-icon-arrow-clockwise /></b-button>
                            </b-input-group-append>
                    </b-input-group>
                </div>
            </template>
        </b-table>
        <b-modal 
            id="createNewUser" 
            size="lg" 
            ok-variant="success"
            cancel-variant="danger"
            ok-title="Benutzer erstellen"
            cancel-title="Abbrechen"
            @show="resetForm"
            @ok="sendUserData($event)"
            >
            <user-form v-model="newUser" />
        </b-modal>
        <b-modal 
            id="displayUser" 
            size="lg" 
            hide-footer
            >
            <user-form :value="detailUser" disabled />
        </b-modal>
    </div>
</template>

<script>

import UserForm from './UserForm.vue'

export default {
    name: "Users",
    components: {
        "userForm" : UserForm
    },
    computed: {
        userItems: function(vm) {
            return vm.$store.getters.getUsers
        }
    },
    methods: {
        updateTargetScore: async function(targetUser) {
             try {
                await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'api/users/'+targetUser.id+'/setTargetPoints' , 'POST', {"newTargetScore" : this.newTarget})

                this.$bvToast.toast('Zielwert für '+targetUser.firstname+' '+targetUser.lastname+' aktualisiert', {variant: 'success'})
            } catch {
                this.$bvToast.toast('Zielwert konnte nicht aktualisiert werden', {variant: 'danger'})
            }
        },
        sendUserData: async function() {
           try {
            await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'api/users' , 'POST', this.newUser)

            this.$bvToast.toast('Benutzer erfolgreich erstellt', {variant: 'success'})
           } catch {
               this.$bvToast.toast('Benutzer konnte nicht erstellt werden', {variant: 'danger'})
           }
        },
        resetForm: function() {
            this.newUser = {
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
    },
    data() {
      return {
        newTarget: 0,
        newUser: {},
        detailUser: {},

        fields: [
          {
            key: 'lastname',
            sortable: true,
            label: 'Nachname',
          },
          {
            key: 'firstname',
            sortable: true,
            label: 'Vorname'
          },
          {
            key: 'mail',
            sortable: true,
            label: 'E-Mail'
          },
          {
            key: 'username',
            sortable: true,
            label: 'Benutzername'
          },
          { key: 'actions', label: 'Aktionen' }
        ]
      }
    }
}
</script>

<style>

</style>