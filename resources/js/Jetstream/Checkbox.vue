<template>
    <input type="checkbox" :value="value" v-model="proxyChecked"
           class="bg-gray-800 rounded border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
</template>

<script>
    export default {
        model: {
            prop: "checked",
            event: "change",
        },

        props: {
            checked: {
                type: [Array, Boolean],
                default: false,
            },
            value: {
                default: null,
            },
        },

        computed: {
            proxyChecked: {
                get() {
                    return this.checked;
                },
                set(val) {
                    this.$emit("change", val);
                },
            },
        },
    }
</script>

