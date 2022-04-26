<template>
  <b-container fluid>
            <b-row>
                <b-col  cols="12" md="6">
                    <b-form-group
                        label="Titel"
                        label-for="name"
                    >
                        <b-form-input
                        id="name"
                        v-model="cycleData.name"
                        type="text"
                        :disabled="disabled"
                        placeholder="Titel"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="3">
                    <b-form-group
                        label="Startdatum"
                        label-for="fromDate"
                    >
                        <b-form-input
                        id="fromDate"
                        v-model="cycleData.fromDate"
                        type="date"
                        :disabled="disabled"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="3">
                    <b-form-group
                        label="Enddatum"
                        label-for="toDate"
                    >
                        <b-form-input
                        id="toDate"
                        v-model="cycleData.toDate"
                        type="date"
                        :disabled="disabled"
                        required
                        ></b-form-input>
                    </b-form-group>
                </b-col>
            </b-row>
            <b-row>
                <b-col  cols="12" md="6">
                    <b-form-group
                        label="Beschreibung"
                        label-for="description"
                    >
                        <b-form-textarea
                        id="description"
                        v-model="cycleData.description"
                        :disabled="disabled"
                        placeholder="Beschreibung"
                        required
                        ></b-form-textarea>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="3">
                    <b-form-group
                        label="zu erreichende Punkte"
                        label-for="targetAmount"
                    >
                        <b-form-input
                        id="targetAmount"
                        v-model.number="cycleData.targetAmount"
                        type="number"
                        :disabled="disabled"
                        required                     
                        ></b-form-input>
                    </b-form-group>
                </b-col>
                <b-col  cols="12" md="3">
                    <b-form-group
                        label="Kosten je Punkt [cents]"
                        label-for="costPerPoint"
                    >
                        <b-form-input
                        id="costPerPoint"
                        v-model.number="cycleData.costPerPoint"
                        type="number"
                        :disabled="disabled"
                        required                     
                        ></b-form-input>
                    </b-form-group>
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
                this.cycleData = this.value
            }
        },
        'cycleData' : {
            handler: function() {
                this.$emit('input', this.cycleData)
            },
            deep: true
        }
    },
    data() { 
        return {
            cycleData: {
                name: '',
                toDate: '',
                fromDate: '',
                description: '',
                costPerPoint: 0,
                targetAmount: 0
            }
        }
    }
}
</script>

<style>

</style>