<template>
    <div class="text-left"><h4>Zykluslisten</h4>

        <b-button variant="success" v-b-modal.createNewCycle>
            <b-icon-calendar />
        </b-button>

        <b-table
            striped 
            hover 
            :items="cycleItems" 
            :fields="fields">
            <template #cell(actions)="row">
                <b-button size="sm" @click="detailCycle = row.item" v-b-modal.displayCycle class="mr-1">
                Details
                </b-button>
            </template>
        </b-table>
        <b-modal 
            id="createNewCycle" 
            size="lg" 
            ok-variant="success"
            cancel-variant="danger"
            ok-title="Benutzer erstellen"
            cancel-title="Abbrechen"
            @show="resetForm"
            @ok="sendCycleData($event)"
            >
            <cycle-form v-model="newCycle" />
        </b-modal>
        <b-modal 
            id="displayCycle" 
            size="lg" 
            hide-footer
            >
            <cycle-form :value="detailCycle" disabled />
        </b-modal>
    </div>
</template>

<script>

import CycleForm from './CycleForm.vue'

export default {
    name: "Cycles",
    components: {
        "cycleForm" : CycleForm
    },
    computed: {
        cycleItems: function(vm) {
            return vm.$store.getters.getCycles
        }
    },
    methods: {
        sendCycleData: async function() {
           try {
            await this.$request.sendJsonRequest(this.$store.getters.baseUrl+'api/cycles' , 'POST', this.newCycle)

            this.$bvToast.toast('Zyklus erfolgreich erstellt', {variant: 'success'})
           } catch {
               this.$bvToast.toast('Zyklus konnte nicht erstellt werden', {variant: 'danger'})
           }
        },
        resetForm: function() {
            this.newCycle = {
                name: '',
                toDate: '',
                fromDate: '',
                description: '',
                costPerPoint: 0,
                targetAmount: 0
            }
        }
    },
    data() {
      return {
        newCycle: {},
        detailCycle: {},

        fields: [
          {
            key: 'name',
            sortable: true,
            label: 'Titel',
          },
          {
            key: 'fromDate',
            label: 'Startdatum'
          },
          {
            key: 'toDate',
            label: 'Enddatum'
          },
          {
            key: 'costPerPoint',
            label: 'Kosten je Punkt [Cents]'
          },
          { key: 'actions', label: 'Aktionen' }
        ]
      }
    }
}
</script>

<style>

</style>