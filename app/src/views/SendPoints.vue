<template>
  <div class="commits">	
	<b-form @submit.prevent="sendPoints">
		
		<b-container >
			<b-row>
				<b-col>
					<b-button 
						type="button"
						@click="addRow" 
						class="flex-grow-1 btn-block mb-2" 
						variant="primary">+</b-button>
				</b-col>
			</b-row>
			<b-row>
				<b-col>
					<transaction-form
						v-for="(commit,idx) in commits"
						:key="$id('commit-'+idx)"
						:value="commit"
						@delete="removeRow(idx)"
						@input="updateRow(idx,$event)"
					/>
				</b-col>
			</b-row>
			<b-row class="my-2">
				<b-col>
					<b-alert class="w-100" :show="!validInput" variant="danger">Du kannst nicht mehr Punkte senden als du besitzt</b-alert>		
					<b-alert class="w-100" :show="sendingPoints" variant="warning"><b-spinner type="grow"></b-spinner> Punkte werden versendet</b-alert>	
					<b-alert class="w-100" :show="error && !sendingPoints" variant="danger">Beim Senden ist ein Fehler aufgetreten</b-alert>	
				</b-col>
			</b-row>
			<b-row class="my-2">
				<b-col class="d-flex justify-content-end">
						
					<b-button 
						v-if="validInput && !sendingPoints"
						type="submit"  
						class="w-100">
						<b-iconstack font-scale="1.5" width="4em" height="1.2em">
							<b-icon stacked icon="chevron-compact-right"  shift-h="-2"></b-icon>
							<b-icon stacked icon="chevron-compact-right" ></b-icon>
							<b-icon stacked icon="chevron-compact-right"  shift-h="2"></b-icon>
						</b-iconstack>
					</b-button>
				</b-col>
			</b-row>
		</b-container>
	</b-form>
  </div>
</template>


<script>
import transactionForm from '@/components/TransactionForm'
import config from '@/config.json'

export default {
	name: "SendPoints",
	components: {
		'transactionForm' : transactionForm
	},
	methods: {
		removeRow(idx) {
			this.commits.splice(idx,1)
		},
		addRow(ev) {
			
			this.commits.splice(0,0,JSON.parse(JSON.stringify(this.newItem)))
		},
		updateRow(idx,newValue) {
			this.commits.splice(idx,1,{...newValue})
		},
		sendPoints: async function(ev) {
			var isCommitable = true;
			if(!this.validInput || this.sendingPoints) { return; }
			
			//detach the commits from the responsive set to keep data in case of a failure
			var sendData = JSON.parse(JSON.stringify(this.commits))
			sendData.forEach(transaction => {
				transaction.target = this.$store.getters.getUserFromName(transaction.user)
				if(transaction.target === false) { 
					isCommitable = false; 
				}
			})
			
			if(!isCommitable) { 
				this.$bvToast.toast('ungültige Benutzer angegeben', {variant: 'danger'})
				return; 
			}
			this.sendingPoints = true
			this.error = false
			
			try {
				let response = await this.$request.sendJsonRequest(config.baseUrl+"transfer",'POST',sendData)
				
				this.$store.commit('setUserScore', response.userScore)
				this.$root.$bvToast.toast('Punkte erfolgreich gesendet', {variant: 'success'})
				this.commits = [{}]
			} catch {
				this.error = true
				this.$root.$bvToast.toast('Punkte senden fehlgeschlagen', {variant: 'danger'})
			} finally {
				this.sendingPoints = false
			}
		}
		
	},
	data() {return {
		sendingPoints: false,
		error: false,
		newItem: {
                amount: 0,
                createPoints: false,
                user: null,
                description: ""
            },			
		commits: [JSON.parse(JSON.stringify({
                amount: 0,
                createPoints: false,
                user: null,
                description: ""
            }))]
	}},
	computed: {		
		validInput: (vm) => {
			let pointsToSend = vm.commits.reduce((previousValue,item) => {
				return previousValue + (item.createPoints ? 0 : parseInt(item.amount))
			},0)
			if(isNaN(pointsToSend)) return true
			return pointsToSend <= vm.$store.getters.getUserScore;
		
		}
	}
}
</script>
