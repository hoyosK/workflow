<template>
    <ul class="sidebar-nav">
        <li class="nav-item show" v-for="item in menu" v-show="typeof access[item.access] !== 'undefined'">
            <a :class="{'nav-link nav-group-toggle': (typeof item.items !== 'undefined'), 'nav-link': (typeof item.items === 'undefined')}" @click="(item.to) ? $router.push(item.to) : item.active = (!item.active)">
                <span class="me-3">
                    <i :class="item.icon"></i>
                </span>
                {{item.name}}
            </a>
            <ul class="nav-group-items" style="height: auto;" v-show="item.active">
                <li class="nav-item" v-for="child in item.items" v-show="typeof access[child.access] !== 'undefined'">
                    <a class="nav-link" aria-current="page" @click="$router.push(child.to)">{{child.name}}</a>
                </li>
            </ul>
        </li>
    </ul>
</template>

<script>
import {computed} from 'vue'
import {mapGetters, useStore} from 'vuex'
import logoNegative from '@/assets/images/logo.png'
import {sygnet} from '@/assets/brand/sygnet'
import navMenu from '/src/_nav'

export default {
    name: 'AppSidebar',
    components: {},
    data() {
        return {
            menu: [],
            access: {},
        };
    },
    computed: {
        ...mapGetters({
            loading: 'loading',
            authInfo: 'authInfo',
        })
    },
    watch: {
        authInfo(data) {
            console.log(data);
        }
    },
    mounted() {
        this.menu = navMenu;

        if (typeof this.authInfo.m !== 'undefined') {
            this.access = this.authInfo.m;
        }
    }
}
</script>
