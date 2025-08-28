<template>
    <nav
        class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
        v-if="links.length > 3"
    >
        <div
            class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between"
        >
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ from }}</span>
                    to
                    <span class="font-medium">{{ to }}</span>
                    of
                    <span class="font-medium">{{ total }}</span>
                    results
                </p>
            </div>
            <div>
                <nav
                    class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                    aria-label="Pagination"
                >
                    <Link
                        v-for="(link, index) in links"
                        :key="index"
                        :href="link.url"
                        :class="linkClasses(link, index)"
                        :aria-current="link.active ? 'page' : undefined"
                        v-html="link.label"
                        preserve-scroll
                    />
                </nav>
            </div>
        </div>

        <!-- Mobile pagination -->
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="previousPageUrl"
                :href="previousPageUrl"
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                preserve-scroll
            >
                Previous
            </Link>
            <Link
                v-if="nextPageUrl"
                :href="nextPageUrl"
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                preserve-scroll
            >
                Next
            </Link>
        </div>
    </nav>
</template>

<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    links: Array,
    from: Number,
    to: Number,
    total: Number,
});

const previousPageUrl = computed(() => {
    return props.links.find((link) => link.label.includes("Previous"))?.url;
});

const nextPageUrl = computed(() => {
    return props.links.find((link) => link.label.includes("Next"))?.url;
});

const linkClasses = (link, index) => {
    const baseClasses =
        "relative inline-flex items-center px-4 py-2 text-sm font-medium focus:z-20";

    if (link.active) {
        return `${baseClasses} z-10 bg-blue-50 border-blue-500 text-blue-600`;
    }

    if (!link.url) {
        return `${baseClasses} text-gray-300 bg-white border-gray-300 cursor-not-allowed`;
    }

    let classes = `${baseClasses} bg-white border-gray-300 text-gray-500 hover:bg-gray-50`;

    if (index === 0) {
        classes += " rounded-l-md";
    }

    if (index === props.links.length - 1) {
        classes += " rounded-r-md";
    }

    return classes;
};
</script>
