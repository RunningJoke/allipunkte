<template>
	<div>
		<div class="d-flex flex-row mb-2 align-items-center bg-white position-sticky py-2" style="top: 0; z-index:5;">
        
         <b-input-group class="flex-nowrap ml-2" >
            <b-input-group-prepend is-text>
                <b-icon-search />
            </b-input-group-prepend>
            <b-form-input placeholder="Suche..." type="search" v-model="filterString"></b-form-input>       
        </b-input-group>

		<div class="d-flex ml-2 mr-2">
			<b-form-checkbox
				button
				v-model="showReceived"
				name="checkbox-1"
				button-variant="outline-success"
			>
				+
			</b-form-checkbox>
			<b-form-checkbox
				button
				class="mx-1"
				v-model="showSent"
				name="checkbox-1"
				button-variant="outline-danger"
			>
				-
			</b-form-checkbox>
			<b-form-checkbox
				v-if="canCreate"
				button
				v-model="showCreated"
				name="checkbox-1"
				button-variant="outline-warning"
			>
				*
			</b-form-checkbox>
		</div>
    </div>
		<b-list-group>
			<b-list-group-item 
				v-for="(item, idx) in transactions" 
				:key="'transaction-'+idx"
				:variant="item['@variant']" 
				class="mx-1">
				<div class="d-flex flex-wrap">
					<div class="w-25 text-left mr-2">
						<h1><b-badge :variant="item['@variant']">
							{{ item['@icon']}} {{ item.amount }}</b-badge>
						</h1>
					</div>
					<div class="flex-grow-1">
						<div class="text-left">{{ item.description }}</div>
						<div class="d-flex justify-content-between">
							<div class="text-black-50">
								{{ item['@date'] }}
							</div>
							<div class="text-black-50" >
								<template v-if="!item['@targetUser']">
								gelöschter Benutzer
								</template>
								<template v-else>
									{{ item['@targetUser'].firstname }} {{ item['@targetUser'].lastname }}
								</template>
							</div>
								
						</div>
						</div>
				</div>				
			</b-list-group-item>
			<b-list-group-item v-if="transactions.length == 0">
				Noch keine Transaktionen vorhanden
			</b-list-group-item>
		</b-list-group>
	</div>
</template>
<style lang="scss">
.transaction--table {
	padding: 1em;
	width: 100%;

	tr:nth-child(odd) {
		background-color: lightgrey;
	}
	
	.transaction--row {
		padding: 1em;
			border-spacing: 5px;
		
		td {
			padding: 1em;
			&.transaction--in, .transaction--out {
				white-space: nowrap;
			}
		}
		
	}
}



</style>
<script>

const moment = require('moment')

export default {
	name: "ScoreBoard",
	data() {
		return {
			filterString: "",
			showReceived: true,
			showSent: true,
			showCreated: true
		}
	},
	computed: {
		canCreate: (vm) => vm.$store.getters.isLoggedInUserAdmin || vm.$store.getters.isLoggedInUserCreator,
		transactions: function(vm) {
			let transactionsForLoop = JSON.parse(JSON.stringify(vm.$store.getters.getTransactions)).slice().reverse()
			transactionsForLoop.forEach(transaction => {
				transaction['@variant'] = vm.transactionVariant(transaction)
				transaction['@date'] = vm.formatDateTime(transaction)
				transaction['@icon'] = transaction['@variant'] == "success" ? '+' : (transaction['@variant'] == "warning" ? '*' : '-')
				transaction['@targetUser'] = vm.transactionSenderOrReceiver(transaction)
			})
			return transactionsForLoop.filter(vm.transactionFilterFunction)
		}
	},
	mounted() {
		this.$store.dispatch('getTransactions')
	},
	methods: {
		formatDateTime: function(item) { 
			return moment(item.timestamp).format('DD.MM.YYYY') 
		},
		loadUser: function(userIRI) { 
			return this.$store.getters.getUserFromIRI(userIRI) 
		},
		transactionVariant: function(item) {
			if(item.origin === this.$store.getters.getUserIRI && item.createdPoints) { return 'warning'; }
			if(item.origin === this.$store.getters.getUserIRI ) { return 'danger'; }
			return 'success';
		},
		transactionSenderOrReceiver: function(item) {
			var showUser = null;
			if(item.target === this.$store.getters.getUserIRI) { 
				showUser = item.origin 
			}
			else { 
				showUser = item.target 
			}			
			var userElement = showUser && this.loadUser(showUser)
			if(!userElement) { return null }
			return userElement
		},
		transactionFilterFunction: function(transaction) {
			
			let regexFilterString = ""
			try {
			regexFilterString = new RegExp(this.filterString,'i')
			} catch {
				return true
			}
			return ((this.showReceived && transaction['@variant'] == "success") ||
						(this.showCreated && transaction['@variant'] == "warning") ||
						(this.showSent && transaction['@variant'] == "danger")) &&
						(this.filterString == "" || //short circuit empty filter string
						regexFilterString.test(transaction['@date']) || 
						regexFilterString.test(transaction['description']) ||
						regexFilterString.test(transaction['@targetUser']?.firstname) ||
						regexFilterString.test(transaction['@targetUser']?.lastname) ) 
		}
	}
}


</script>