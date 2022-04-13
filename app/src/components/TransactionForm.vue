<template>
  <b-container 
    class="transaction--block py-2 rounded" 
    :class="{'createPoints': localData.createPoints}"
    >
        <b-row>
            <b-col
                v-if="canCreate"  
                class="my-1 d-flex justify-content-start"
            >
                <b-form-checkbox 
                    v-model="localData.createPoints" 
                    :value="true" 
                    :unchecked-value="false" 
                    class="my-1 px-1 text-right ml-3"
                    @input="$emit('input',localData)"
                >
                    Punkte neu erzeugen
                </b-form-checkbox>
            </b-col>
        </b-row>
        <b-row>					
            <b-col 
                class="my-1 d-flex justify-content-end">
                <b-button 
                    type="button" 
                    variant="danger"  
                    @click="$emit('delete')">-</b-button>
            </b-col>
        </b-row>
        <b-row >
            <b-col cols="8" class="my-1 px-1">
                <user-dropdown 
                    v-model="localData.user" 
                    @input="$emit('input',localData)">
                </user-dropdown>						
            </b-col>
            <b-col cols="4" class="my-1 px-1">
                <b-form-input 
                    type="number" 
                    v-model="localData.amount" 
                    min="0" 
                    placeholder="0" 
                    :state="(localData.amount == parseInt(localData.amount)) && localData.amount > 0"
                    @input="$emit('input',localData)">
                </b-form-input>
            </b-col>
        </b-row>
        <b-row>	
            <b-col cols="12 px-1">
                <b-form-input 
                    v-model="localData.description"
                    @input="$emit('input',localData)"
                    type="text" 
                    placeholder="Beschreibung" 
                    :state="!!localData.description" />
            </b-col>
        </b-row>
    </b-container>
</template>

<script>
import userDropdown from '@/components/UserDropdown'
		
export default {
    name: "transactionForm",
    components: {
        'userDropdown' : userDropdown
    },
    props: {
        value: {
            required: true,
            type: Object
        }
    },
    watch : {
        "value" : {
            immediate: true,
            handler: function() {
                this.localData = Object.assign(this.localData,this.value)
            }
        }
    },
    data() {
        return {
            localData: {
                amount: 0,
                createPoints: false,
                user: null,
                description: ""
            }
        }
    },
    computed: {
        canCreate: (vm) => vm.$store.getters.isLoggedInUserAdmin || vm.$store.getters.isLoggedInUserCreator
    },
    methods: {

    }
}
</script>

<style lang="scss">
.createPoints {
	background-color: red;
	color: white;
}

.transaction--block:nth-child(odd) {
	background-color: lightgrey;
	
	&.createPoints {
		background-color: darkred;
		color: white;
	}
}
</style>