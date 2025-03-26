<template>
    <div class="text-left p-1 position-relative">
    <div class="d-flex flex-row mb-2 align-items-center bg-white position-sticky py-2 flex-wrap-reverse " style="top: 0; z-index:5;">
        
        <b-button variant="success" v-b-modal.createNewUser class="mr-2 text-nowrap" size="sm">
            + <b-icon-person />
        </b-button>

        <b-pagination
            size="sm"
            class="mb-0 mx-2"
            hide-goto-end-buttons
            v-model="currentPage"
            :total-rows="userItems.length"
            :per-page="perPage"
        ></b-pagination>

        <b-input-group class="flex-nowrap justify-content-end mr-2">
            <b-input-group-prepend is-text>
                <b-icon-files />
            </b-input-group-prepend>
            <b-form-input style="max-width: 80px;" type="number" :step="10" v-model="perPage"></b-form-input>           
        </b-input-group>


         <b-input-group class="flex-wrap" style="width: 600px;">
            <b-input-group-prepend is-text>
                <b-icon-search />
            </b-input-group-prepend>
            <b-form-input placeholder="Suche..." type="search" v-model="filterItem.filterString"></b-form-input>
            <b-input-group-append is-text>
                <b-form-checkbox switch :value="true" :unchecked-value="false" v-model="filterItem.hideWithTargetPoints" >
                    ohne Zielwert
                </b-form-checkbox>
            </b-input-group-append>            
        </b-input-group>

     

    </div>

        <b-table
            striped 
            hover 
            :per-page="perPage"
            :current-page="currentPage"
            :filter="filterItem"
            :filter-function="filterRowsFunction"
            :items="userItems" 
            responsive
            :fields="fields">
            <template #cell(actions)="row">
                <div class="d-flex flex-nowrap">
                    <b-input-group prepend="Neuer Zielwert" size="sm">
                        <b-form-input type="number" v-model="newTarget" style="max-width: 100px;"></b-form-input>
                            <b-input-group-append>
                                <b-button variant="outline-success" @click="updateTargetScore(row.item)"><b-icon-arrow-clockwise /></b-button>
                            </b-input-group-append>
                    </b-input-group>
                    
                    <b-button size="sm" @click="detailUser = JSON.parse(JSON.stringify(row.item))" v-b-modal.displayUser class="mr-1">
                    Details
                    </b-button>
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
            ok-title="Aktualisieren"
            cancel-title="Abbrechen"
            @ok="updateUserData"
            >
            <user-form :value="detailUser" :disabled="!$store.getters.isLoggedInUserAdmin" />
            
            <table >
                <thead class="bg-light">
                    <tr class="border-bottom border-secondary">
                        <td class="p-1">Saison</td>
                        <td class="p-1">erreicht</td>
                        <td class="p-1">benötigt</td>
                        <td class="p-1">Betrag</td>
                        <td class="p-1">Status</td>
                    </tr>
                </thead>
                <tbody>
                    <template 
                        v-for="(score,idx) in detailUser.scores" >
                    <tr 
                        
                        :key="'user-score-'+idx"
                        class="border-bottom border-secondary"
                        v-if="score.cycle.id != $store.getters.getCurrentCycle.id"
                        >
                        <td class="p-1">{{ getCycleById(score.cycle.id).name }}</td>
                        <td class="p-1 text-center">{{ score.amount }}</td>
                        <td class="p-1 text-center">{{ score.targetAmount }}</td>
                        <td class="p-1 text-center">{{ buildStatus(score) }}</td>
                        <td class="p-1"><b-form-checkbox @input="updatePaymentStatus(score,$event)" :checked="score.paymentStatus == 3" /></td>
                    </tr>
                    </template>
                </tbody>
            </table>

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
            return vm.$store.getters.getUsers.map(user => {
                let currentScore = user?.scores?.find(score => score?.cycle?.id == this.$store.getters.getCurrentCycle?.id) ?? null
                let targetAmount = currentScore?.targetAmount ?? -1
                let enhancedUser = {...user, 'targetAmount' : targetAmount}
                if(targetAmount > 0) {
                 Object.assign(enhancedUser, {'_rowVariant' : 'success'})
                }
                return enhancedUser
            })
        }
    },
    methods: {
        getCycleById: function(cycleId) {
            let cycle = this.$store.getters.getCycles.find(cyc => cyc.id == cycleId)
            return cycle
        },
        buildStatus: function(score) {
            let cycle = this.getCycleById(score.cycle.id)
            let amountDue = (cycle.costPerPoint * Math.max(0,score.targetAmount - score.amount)) / 100

            let amountString = amountDue.toLocaleString('de-DE',{
                style: "currency", currency: "EUR"
            })
            //
            if(score.targetAmount == 0 || score.amount >= score.targetAmount) return "-"
            if(score.targetAmount < score.amount || score.paymentStatus == 3) return amountString + " ✔"
            if(score.targetAmount > score.amount && score.paymentStatus != 3) return amountString + " ✖"
        },
        filterRowsFunction: function(user,filterObj) {
            //filter should check lastname, firstname, mail, username
            if(filterObj.hideWithTargetPoints) {
                if(user?.targetAmount > 0) {
                    return false
                }
            }
            let fs = filterObj.filterString
            return user.lastname.includes(fs) || user.firstname.includes(fs) || user.mail.includes(fs) || user.username.includes(fs)
        },
        updatePaymentStatus: async function(score,ev) {
            try {
                await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'api/scores/'+score.id+'/setPaymentStatus' , 'POST', {"newPaymentStatus" : ev ? 3 : 0})

                score.paymentStatus = ev ? 3 : 0
            } catch {
                this.$bvToast.toast('Zahlstatus konnte nicht aktualisiert werden', {variant: 'danger'})
            }
        },
        updateTargetScore: async function(targetUser) {
             try {
                await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'api/users/'+targetUser.id+'/setTargetPoints' , 'POST', {"newTargetScore" : this.newTarget})

                this.$bvToast.toast('Zielwert für '+targetUser.firstname+' '+targetUser.lastname+' aktualisiert', {variant: 'success'})
                let currentScore = targetUser?.scores?.find(score => score?.cycle?.id == this.$store.getters.getCurrentCycle?.id) ?? null
                currentScore.targetAmount = this.newTarget


            } catch {
                this.$bvToast.toast('Zielwert konnte nicht aktualisiert werden', {variant: 'danger'})
            }
        },
        updateUserData: async function($event) {
           try {
            $event.preventDefault();
            //validate the initial password request and check against the password_confirm field
            if(!!this.detailUser.password && !!this.detailUser.password_confirm && this.detailUser.password !== this.detailUser.password_confirm) {
                this.$bvToast.toast('Passwort nicht angegeben/ stimmt nicht überein', {variant: 'danger'})
                //dont continue saving
                $event.preventDefault();
                return
            }

            if(!!this.detailUser.password) {
                let transmitObject = {
                    targetUser: '/users/' + this.detailUser.id,
                    newPassword: this.detailUser.password,
                    newPasswordVerification: this.detailUser.password_confirm
                }
                let res = await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'updatePasswordAdmin' , 'POST', transmitObject)
                this.$bvToast.toast('Passwort geändert', {variant: 'primary'})

                this.detailUser.password = ""
                this.detailUser.password_confirm = ""
            }

            let dataTransmit = JSON.parse(JSON.stringify(this.detailUser))

            delete dataTransmit.scores
            delete dataTransmit._rowVariant

            await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'users/'+this.detailUser.id , 'PUT', dataTransmit)

            this.$bvToast.toast('Benutzer erfolgreich aktualisiert', {variant: 'success'})
            
            //update stored user
            let original = this.$store.getters.getUsers.find(u => u.id == dataTransmit.id)
            
            if(!!original) {
                original.username       = dataTransmit.username
                original.firstname      = dataTransmit.firstname
                original.lastname       = dataTransmit.lastname
                original.mail           = dataTransmit.mail
                original.license        = dataTransmit.license
                original.targetAmount   = dataTransmit.targetAmount
                original.isAdmin        = dataTransmit.isAdmin
                original.isCreator      = dataTransmit.isCreator
            }
            this.$bvModal.hide("displayUser")

           } catch {               
               this.$bvToast.toast('Benutzer konnte nicht aktualisiert werden', {variant: 'danger'})
           }
        },

        sendUserData: async function() {
           try {
            //validate the initial password request and check against the password_confirm field
            if(this.newUser.password == "" || this.newUser.password !== this.newUser.password_confirm) {
                this.$bvToast.toast('Passwort nicht angegeben/ stimmt nicht überein', {variant: 'danger'})
                //dont continue saving
                return
            }

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
                isCreator: false,
                password: "",
                password_confirm: ""
            }
        }
    },
    data() {
      return {
        currentPage: 1,
        perPage: 50,
        filterItem: {
            filterString: "",
            hideWithTargetPoints: false
        },
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