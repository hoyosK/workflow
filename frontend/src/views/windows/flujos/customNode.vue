<script setup>
import { Handle, Position } from '@vue-flow/core'

const props = defineProps({
    data: {
        type: Object,
        required: true,
        color: '',
    },
})
</script>
<template class="decision-diamond">
        <div class="p-2">Condición</div>
        <Handle id="salidaFalse" type="source" :position="Position.Left"  style="background: #f30000; width: 10px; height: 10px;" />
        <Handle id="salidaTrue" type="source" :position="Position.Right" style="background: #00c200; width: 10px; height: 10px;"  />
        <Handle id="entrada" type="target" :position="Position.Top"/>
</template>
