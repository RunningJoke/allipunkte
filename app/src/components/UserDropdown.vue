<template>
    <div>
        <b-form-input 
            type="text" 
            @input="sendUpdate($event)" 
            @change="sendUpdate($event)" 
            :value="curValue"
            placeholder="User" 
            :list="$id('userlist')" 
            :state="validUser">
        </b-form-input>
        <datalist :id="$id('userlist')">
            <option 
                v-for="(elem, idy) in dataUserList" 
                :key="$id('dropdown_'+idy)"
                >{{elem.firstname}} {{elem.lastname}}</option>
        </datalist>
    </div>
</template>

<script>
export default {
    props: {
        value: String
    },
    data() { 
        return {
            curValue: this.value
    }},
    watch: {
        'value' : function() { this.curValue = this.value }
    },
    methods: {
        sendUpdate: function(value) {
            //try to convert to user object
            this.curValue = value
            this.$emit('input',value)
        }
    },
    computed: {
        dataUserList: (vm) => {
			if(vm.curValue && vm.curValue.length > 2) {
				return vm.$store.getters.getUsers.filter((user) => {
                    return (user.firstname && user.firstname.indexOf(vm.curValue)) || (user.lastname && user.lastname.indexOf(vm.curValue)) || false
                })
			} else {
				return []
			}
        },
        validUser: (vm) => {
            return vm.$store.getters.getUserFromName(vm.curValue) !== false
        }
    }

}
</script>