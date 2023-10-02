<template>
    <div>
        
        <div class="d-flex flex-row mb-2 ml-2 align-items-center bg-white position-sticky py-2" style="top: 0; z-index:5;">
        
        <b-button size="sm" variant="success" type="button" @click="createNew($event)"><b-icon-file-plus /></b-button>

         <b-input-group class="flex-nowrap ml-2" size="sm">
            <b-input-group-prepend is-text>
                <b-icon-search />
            </b-input-group-prepend>
            <b-form-input placeholder="Suche..." type="search" v-model="filterString"></b-form-input>       
        </b-input-group>

		<div class="d-flex ml-2 mr-2">
			<b-form-checkbox
                size="sm"
				button
				v-model="showOnlyOwn"
				name="checkbox-1"
				button-variant="outline-dark"
			>
				<b-icon-person-check-fill />
			</b-form-checkbox>
			<b-form-checkbox
                size="sm"
				button
				class="mx-1"
				v-model="showOld"
				name="checkbox-1"
				button-variant="outline-warning"
			>
				<b-icon-archive />
			</b-form-checkbox>
		</div>
    </div>


        <b-list-group>
                    <b-list-group-item 
                        v-for="(item, idx) in activePetitions" 
                        :key="'petition-'+idx"
                        class="mx-1 py-2 px-1"
                        @click="openModal(item)"
                        >

                        <div class="d-flex petition-titlerow flex-column mb-2">
                            <div class="petition-titled-flex flex-nowrap d-flex">   
                                <div class="w-75">
                                    <div class="d-flex flex-row flex-fill">
                                        <b-badge variant="primary" class="mr-1 mb-1">{{ item.offeredPoints }} Punkte</b-badge>                             
                                        
                                        
                                        
                                    </div>
                                    <div class="text-truncate  h5 mb-0" :title="item.title">
                                        {{ item.title }}                                    
                                    </div>  
                                    <div style="font-size: 10pt;">
                                        {{ item['@createUserString'].firstname + ' ' +  item['@createUserString'].lastname }}
                                    </div>
                                </div>


                                <b-badge variant="light" class="d-flex flex-column justify-content-center align-items-end w-25"  style="font-size: 10pt; font-weight: bold;">
                                    
                                    <div class="petition-date">{{ item['@dueDate'].format('DD.MM.YYYY') }}</div>
                                    <div class="mt-2 petition-time">{{ item['@dueDate'].format('HH:mm') }} Uhr</div>                                    

                                </b-badge>   

                            </div>
                            
                        </div>

                        <div class="petition-titled-flex flex-nowrap d-flex">  
                            <div class="text-truncate w-75" style="font-size: 10pt;">{{ item.description }}</div>
                            <div class="status-bar d-flex align-items-center w-25 position-relative">
                                
                                <b-badge v-if="item.status == 'closed'" :variant="item['@statusVariant']" class="petition-status w-100">{{ item['@statusString'] }}</b-badge>
                                <template v-else>
                                <b-progress :max="item.openPositions" class="flex-grow-1" :class="'bg-'+item['@statusVariant']" >
                                    <b-progress-bar :value="item.filledPositions" :variant="'secondary'" >
                                        
                                    </b-progress-bar>
                                </b-progress>
                                <div class="position-absolute text-white" style="font-size: 8pt;font-weight: bold;left: 50%;transform: translateX(-50%);">
                                    {{ item.filledPositions }} / {{ item.openPositions }}
                                </div>
                                </template>
                            </div>
                        </div>

                        
                    </b-list-group-item>
        </b-list-group>

        <b-modal 
            :id="$id('petition')" 
            :title="petitionInModal.title"
            size="xl"
            :ok-title="petitionInModal['@new'] ? 'Erstellen' : 'Aktualisieren'"
            cancel-title="Abbrechen"
            @ok="submitModal($event)"

            :hide-footer="!canEditPetition"

            >

            <template v-if="canEditPetition">
                <b-form-group                
                    label="Titel"
                >
                    <b-form-input size="lg" type="text" v-model="petitionInModal.title" />
                </b-form-group>

                <b-form-group
                    label="Datum"
                >
                    <b-form-input  
                        type="date" 
                        v-model="petitionInModal['@date']" 
                        :disabled="!canEditPetition"
                        />
                </b-form-group>
                <b-form-group
                    label="Uhrzeit"
                >
                    <b-form-input  
                        type="time" 
                        v-model="petitionInModal['@time']" 
                        :disabled="!canEditPetition"
                        />
                </b-form-group>

                <b-form-group
                label="Punkte"
            >
                <b-form-input  
                    type="number" 
                    v-model.number="petitionInModal.offeredPoints" 
                    :disabled="!canEditPetition"
                />
             </b-form-group>
            </template>
            <template v-else>
                <div class="my-1 font-weight-bold">{{ petitionInModal['@dueDate'] && petitionInModal['@dueDate'].format('DD.MM.YYYY HH:mm') || "" }}</div>
                <div class="my-1 small font-weight-lighter">{{ petitionInModal['@createUserString'] && (petitionInModal['@createUserString'].firstname + ' ' +  petitionInModal['@createUserString'].lastname) || '' }}</div>
            </template>

             
            
            <b-form-group
                v-if="canEditPetition"
                label="Beschreibung"
            >
                <b-form-textarea  
                    rows="6"
                    v-model="petitionInModal.description" 
                    />
            </b-form-group>
            <div v-else class="p-2 border border rounded my-3">
                {{ petitionInModal.description }}
            </div>


            <b-input-group v-if="canEditPetition" prepend="besetzt" class="flex-nowrap mb-2">
                <b-input-group-prepend>
                    <b-form-input  
                        type="number" 
                        v-model.number="petitionInModal.filledPositions" 
                        :disabled="!canEditPetition"
                    />
                </b-input-group-prepend>
                
                <b-input-group-text>
                    benötigt
                </b-input-group-text>

                <b-input-group-append>
                    <b-form-input  
                        type="number" 
                        v-model.number="petitionInModal.openPositions" 
                        :disabled="!canEditPetition"
                    />
                </b-input-group-append>
            </b-input-group>

            <div v-else class="d-flex">
                <div>Plätze besetzt/gesamt {{ petitionInModal.filledPositions }} / {{ petitionInModal.openPositions}}</div>
                <b-badge class="ml-auto">{{ petitionInModal.offeredPoints || 0 }} Punkte</b-badge>
            </div>


            <b-form-group
                v-if="canEditPetition"
                label="Status"
            >
            <b-form-radio-group
                v-model="petitionInModal.status"
                :options="petitionStatusOptions"
                buttons
            ></b-form-radio-group>
            </b-form-group>



        </b-modal>

    </div>
</template>

<script>

import moment from 'moment'
import config from '@/config.json'

export default {
    name: "PetitionsList",
    props: {

    },
    mounted: function() {
        this.fetchPetitions()
    },
    computed: {
        activePetitions: function(vm) {
            let regexFilterString
            try {
                regexFilterString = new RegExp(vm.filterString,'i')
            } catch {
                regexFilterString = ""
            }

            return vm.petitions
                .filter(petition => (vm.showOld  || (moment(petition.dueDate).isAfter() && petition.status == "open")) &&
                            (!vm.showOnlyOwn || (vm.showOnlyOwn && petition.createUser == vm.$store.getters.getUserIRI)) &&
                            (regexFilterString == ""  || (regexFilterString.test(petition.description) || regexFilterString.test(petition.title)))
                        )
                .sort((a,b) => moment(a.dueDate).valueOf() - moment(b.dueDate).valueOf())
                .map(petition => {
                    let newPetition = {...petition}
                    newPetition['@createUserString'] = vm.$store.getters['getUserFromIRI'](petition.createUser)
                    newPetition['@dueDate'] = moment(petition.dueDate)
                    newPetition['@date'] = newPetition['@dueDate'].format('YYYY-MM-DD')
                    newPetition['@time'] = newPetition['@dueDate'].format('HH:mm')
                    newPetition['@statusString'] = "open"
                    newPetition['@statusVariant'] = "success"

                    switch(petition.status)
                    {
                        case "closed" : 
                            newPetition['@statusString'] = "geschlossen"; 
                            newPetition['@statusVariant'] = "danger"
                            break;
                        case "open" : 
                            newPetition['@statusString'] = "offen"; 
                            newPetition['@statusVariant'] = "success"                            
                            break;
                    }


                    return newPetition
                })
        },
        canEditPetition: function(vm) {
            return vm.petitionInModal?.createUser === vm.$store.getters.getUserIRI
        }
    },
    methods: {
        fetchPetitions: async function() {
            let response = await this.$request.sendJsonRequest(config.baseUrl+"petitions", 'GET')
            this.$set(this , 'petitions' , response)
        },
        submitModal: async function(ev) {
            try {
                this.petitionInModal['dueDate'] = this.petitionInModal['@date'] + 'T' + this.petitionInModal['@time']
                if(this.petitionInModal?.['@new']) {
                    //create new element
                    await this.$request.sendJsonRequest(config.baseUrl+"petitions", 'POST', this.petitionInModal)
                } else {
                    await this.$request.sendJsonRequest(config.baseUrl+"petitions/"+this.petitionInModal.id, 'PUT', this.petitionInModal)
                }
                this.$bvToast.toast('Erstellung/Aktualisierung erfolgreich',{variant: 'success'})

                await this.fetchPetitions()
            } catch {
                this.$bvToast.toast('Erstellung/Aktualisierung fehlgeschlagen',{variant: 'danger'})
            }
        },
        openModal(item) {
            this.$set(this,'petitionInModal',{...item})
            this.$bvModal.show(this.$id('petition'))
        },
        createNew(ev) {
            this.$set(this,'petitionInModal',{
                createUser: this.$store.getters.getUserIRI,
                createDate: moment().format(),
                dueDate: moment(),
                description: "",
                title: "",
                filledPositions: 0,
                openPositions: 1,
                offeredPoints: 0,
                status: "open",
                "@new" : true,
                "@time" : moment().format('HH:mm'),
                "@date" : moment().format('YYYY-MM-DD')
            })
            this.$bvModal.show(this.$id('petition'))
        }
    },
    data() {
        return {
            filterString: "",
            showOld: false,
            showOnlyOwn: false,
            petitions: [],
            petitionInModal: {},
            petitionStatusOptions: [
                { text: "offen" , value: "open" },
                { text: "geschlossen" , value: "closed" },

            ]
        }
    }

}
</script>

<style>
    .create--button {
        position: fixed;
        right: 10px;
        bottom: 50px;
        z-index: 200;
    }
</style>