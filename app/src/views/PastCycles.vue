<template>
  <div>
    <div class="p-2 m-auto">
        <table v-if="!loading" class="m-auto">
            <thead class="bg-light">
                <tr class="border-bottom border-secondary">
                    <td class="p-1">Saison</td>
                    <td class="p-1">erreicht</td>
                    <td class="p-1">benötigt</td>
                    <td class="p-1">Bezahlstatus</td>
                    <td class="w-100">&nbsp;</td>
                </tr>
            </thead>
            <tbody>
                <template 
                    v-for="(item) in $store.getters.getPastCycles" >
                <tr 
                    
                    :key="item.cycle.id"
                    class="border-bottom border-secondary"
                    v-if="item.cycle.id != $store.getters.getCurrentCycle.id"
                    >
                    <td class="p-1">{{ item.cycle.name }}</td>
                    <td class="p-1 text-center">{{ item.amount }}</td>
                    <td class="p-1 text-center">{{ item.targetAmount }}</td>
                    <td class="p-1 text-center">{{ buildStatus(item) }}</td>
                    <td class="w-100">&nbsp;</td>
                </tr>
                </template>
            </tbody>
        </table>
        <b-spinner v-else />
    </div>
  </div>
</template>

<script>
export default {
	name: "ScoreBoard",
	data() {
		return {
            loading: true
		}
	},
	computed: {
	},
	mounted() {
		this.$store.dispatch('getPastCycles').then(() => {
            this.loading = false
        })
	},
	methods: {
        buildStatus(item) {
            let amountDue = (item.cycle.costPerPoint * Math.max(0,item.targetAmount - item.amount)) / 100

            let amountString = amountDue.toLocaleString('de-DE',{
                style: "currency", currency: "EUR"
            })
            //
            if(item.targetAmount == 0 || item.amount >= item.targetAmount) return "-"
            if(item.targetAmount < item.amount || item.paymentStatus == 3) return amountString + " ✔"
            if(item.targetAmount > item.amount && item.paymentStatus != 3) return amountString + " ✖"
        }
	}
}
</script>

<style>

</style>