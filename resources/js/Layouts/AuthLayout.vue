<script setup>
const getImageUrl = (imageName) => `/storage/images/${imageName}`;
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <v-row class="ma-0 min-h-screen">
            <!-- Left Side - Branding/Image -->
            <v-col
                cols="0"
                sm="6"
                md="7"
                lg="8"
                class="hidden sm:flex align-center justify-center pa-0 position-relative"
            >
                <!-- Background with overlay -->
                <div
                    class="absolute inset-0 bg-gradient-to-br from-primary/90 to-blue-600/90"
                ></div>

                <!-- Content -->
                <div class="relative z-10 text-center text-white pa-8">
                    <v-icon size="120" class="mb-6">mdi-city-variant</v-icon>
                    <h1 class="text-h3 font-weight-bold mb-4">Bapenda AI</h1>
                    <h2 class="text-h5 font-weight-medium mb-6">
                        Intelligent Tax Management System
                    </h2>
                    <p
                        class="text-h6 font-weight-light max-w-md mx-auto leading-relaxed"
                    >
                        Streamline your tax operations with AI-powered solutions
                        designed for modern government agencies.
                    </p>

                    <!-- Features -->
                    <div class="mt-12">
                        <v-row class="justify-center">
                            <v-col cols="12" md="4" class="text-center">
                                <v-icon size="40" class="mb-3"
                                    >mdi-robot</v-icon
                                >
                                <h3 class="text-h6 mb-2">
                                    AI Customer Service
                                </h3>
                                <p class="text-body-2">
                                    24/7 automated support
                                </p>
                            </v-col>
                            <v-col cols="12" md="4" class="text-center">
                                <v-icon size="40" class="mb-3"
                                    >mdi-chart-line</v-icon
                                >
                                <h3 class="text-h6 mb-2">Analytics</h3>
                                <p class="text-body-2">Real-time insights</p>
                            </v-col>
                            <v-col cols="12" md="4" class="text-center">
                                <v-icon size="40" class="mb-3"
                                    >mdi-shield-check</v-icon
                                >
                                <h3 class="text-h6 mb-2">Secure</h3>
                                <p class="text-body-2">
                                    Enterprise-grade security
                                </p>
                            </v-col>
                        </v-row>
                    </div>
                </div>
            </v-col>

            <!-- Right Side - Auth Form -->
            <v-col
                cols="12"
                sm="6"
                md="5"
                lg="4"
                class="d-flex align-center justify-center pa-6"
            >
                <div class="w-100 max-w-sm">
                    <slot />
                </div>
            </v-col>
        </v-row>
    </div>
</template>

<style scoped>
.absolute {
    position: absolute;
}

.inset-0 {
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}

.z-10 {
    z-index: 10;
}

.max-w-md {
    max-width: 28rem;
}

.max-w-sm {
    max-width: 24rem;
}

.leading-relaxed {
    line-height: 1.625;
}
</style>
