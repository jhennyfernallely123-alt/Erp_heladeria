<template>
    <div class="min-h-screen flex bg-slate-100 font-sans">
        <Sidebar />
        <div class="flex-1 flex flex-col min-w-0 h-screen">
            <TopBar />
            <main class="flex-1 overflow-y-auto">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import Sidebar from './Sidebar.vue';
import TopBar from './TopBar.vue';
</script>
