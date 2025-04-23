<template>
  <CFooter>
    <div class="ms-auto">
        <span class="me-1 text-muted" target="_blank">Versión {{ version }} - Gestor Automóvil El Roble</span>
    </div>
  </CFooter>
</template>

<script>

import {config} from "/src/config";

export default {
  name: 'AppFooter',
    data() {
        return {
            version: ''
        };
    },
    mounted() {
        this.version = config.appVersion;
    },
}
</script>
