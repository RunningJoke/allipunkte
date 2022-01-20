<template>
	<div>
		<b-list-group>
			<b-list-group-item 
				v-for="(item, idx) in transactions" 
				:key="'transaction-'+idx"
				:variant="item['@variant']" 
				class="mx-1">
				<b-media>
					<template v-slot:aside>
						<h1><b-badge :variant="item['@variant']">
							{{ item['@icon']}} {{ item.amount }}</b-badge>
						</h1>
					</template>
					<div>{{ item.description }}</div>
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
				</b-media>				
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
	created() {
		this.$store.dispatch("loadUserData")
		this.$store.dispatch("getTransactions")
	},
	computed: {
		transactions: function(vm) {
			let transactionsForLoop = vm.$store.getters.getTransactions.slice().reverse()
			transactionsForLoop.forEach(transaction => {
				transaction['@variant'] = vm.transactionVariant(transaction)
				transaction['@date'] = vm.formatDateTime(transaction)
				transaction['@icon'] = transaction['@variant'] == "success" ? '+' : '-'
				transaction['@targetUser'] = vm.transactionSenderOrReceiver(transaction)
			})
		}
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
		}
	}
}


</script>