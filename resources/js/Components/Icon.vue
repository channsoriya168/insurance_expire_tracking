<script setup>
defineProps({
    name: { type: String, required: true },
});

const PATHS = {
    search: 'M21 21l-4.3-4.3m1.8-5.2a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z',
    filter: 'M4 6h16M7 12h10M10 18h4',
    plus: 'M12 4v16m8-8H4',
    edit: 'm16.5 4.5 3 3L8 19l-4 1 1-4L16.5 4.5Z',
    trash: 'M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-8 0 .7 12.1A2 2 0 0 0 10.7 21h2.6a2 2 0 0 0 2-1.9L16 7H8Z',
    bell: 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9',
    list: 'M4 6h16M4 12h16M4 18h10',
    'chevron-left': 'm15 19-7-7 7-7',
    'chevron-right': 'm9 5 7 7-7 7',
    calendar: 'M8 7V3m8 4V3M4 11h16M5 21h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1Z',
    building: 'M4 21V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16M12 21v-8a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v8M8 9h.01M8 13h.01M12 21h9',
    'check-circle': 'M9 12.5 11 15l4-4.5M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
    'alert-triangle': 'M12 9v4m0 4h.01M10.3 4.3 2.6 18a1.7 1.7 0 0 0 1.5 2.5h15.8a1.7 1.7 0 0 0 1.5-2.5L13.7 4.3a1.7 1.7 0 0 0-3 0Z',
    inbox: 'M4 12h4l2 3h4l2-3h4M5 5h14l1.5 7v6a1 1 0 0 1-1 1H4.5a1 1 0 0 1-1-1v-6L5 5Z',
    shield: 'm12 3 7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z',
};
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
    >
        <path :d="PATHS[name]" />
    </svg>
</template>
