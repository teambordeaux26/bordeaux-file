<template>
    <Head title="Welcome" />
    <div class="min-h-screen bg-[#f5f5f5] text-gray-900 font-sans">
        <!-- Top Government Banner -->
        <div
            class="fade-in bg-[#003366] text-white text-[10px] sm:text-xs py-1 text-center tracking-widest uppercase leading-relaxed px-4"
            style="--delay: 0ms"
        >
            Republic of the Philippines &nbsp;|&nbsp; Province of Albay
            &nbsp;|&nbsp; Municipality of Oas
        </div>

        <!-- Header -->
        <header
            class="fade-in relative z-50 bg-white border-b-4 border-[#FFD700] shadow-md"
            style="--delay: 120ms"
        >
            <div
                class="mx-auto max-w-6xl px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3"
            >
                <!-- Branding -->
                <div class="flex items-center gap-3">
                    <img
                        :src="'/images/logo.png'"
                        alt="Oas Albay Seal"
                        class="h-10 w-10 sm:h-16 sm:w-16 object-contain shrink-0"
                    />
                    <div>
                        <p
                            class="text-[9px] sm:text-[10px] uppercase tracking-widest text-gray-500 font-semibold hidden sm:block"
                        >
                            Official Website
                        </p>
                        <h1
                            class="text-sm sm:text-xl font-bold text-[#003366] leading-tight"
                        >
                            Document Management System
                        </h1>
                        <p class="text-xs text-gray-600 hidden sm:block">
                            Office of the Vice Mayor &mdash; Oas, Albay
                        </p>
                    </div>
                </div>

                <!-- Desktop Nav -->
                <nav
                    class="hidden md:flex items-center gap-2 text-sm font-semibold"
                >
                    <a
                        href="#services"
                        class="px-4 py-2 border border-[#003366] text-[#003366] hover:bg-[#003366] hover:text-white transition"
                    >
                        Services
                    </a>
                    <Link
                        href="/get-documents"
                        class="px-4 py-2 border border-[#003366] text-[#003366] hover:bg-[#003366] hover:text-white transition"
                    >
                        Get Documents
                    </Link>
                    <Link
                        href="/requests/new"
                        class="px-4 py-2 border border-[#003366] text-[#003366] hover:bg-[#003366] hover:text-white transition"
                    >
                        New Request
                    </Link>
                    <Link
                        href="/requests/status"
                        class="px-4 py-2 border border-[#003366] text-[#003366] hover:bg-[#003366] hover:text-white transition"
                    >
                        Request Status
                    </Link>
                    <Link
                        href="/login"
                        class="px-4 py-2 bg-[#003366] text-white hover:bg-[#002244] transition"
                    >
                        Sign In
                    </Link>
                </nav>

                <!-- Mobile Hamburger -->
                <button
                    @click="menuOpen = !menuOpen"
                    class="md:hidden flex flex-col justify-center items-center gap-1.5 p-2 border border-[#003366] text-[#003366]"
                    aria-label="Toggle menu"
                >
                    <span
                        class="block w-5 h-0.5 bg-[#003366] transition-all"
                        :class="menuOpen ? 'rotate-45 translate-y-2' : ''"
                    ></span>
                    <span
                        class="block w-5 h-0.5 bg-[#003366] transition-all"
                        :class="menuOpen ? 'opacity-0' : ''"
                    ></span>
                    <span
                        class="block w-5 h-0.5 bg-[#003366] transition-all"
                        :class="menuOpen ? '-rotate-45 -translate-y-2' : ''"
                    ></span>
                </button>
            </div>

            <!-- Mobile Menu Dropdown -->
            <div
                v-if="menuOpen"
                class="md:hidden absolute left-0 right-0 top-full z-50 border-t border-gray-200 bg-white shadow-lg"
            >
                <div class="px-4 py-3 flex flex-col gap-2">
                    <a
                        href="#services"
                        @click="menuOpen = false"
                        class="px-4 py-2 border border-[#003366] text-[#003366] text-sm font-semibold text-center"
                    >
                        Services
                    </a>
                    <Link
                        href="/get-documents"
                        @click="menuOpen = false"
                        class="px-4 py-2 border border-[#003366] text-[#003366] text-sm font-semibold text-center"
                    >
                        Get Documents
                    </Link>
                    <Link
                        href="/requests/new"
                        @click="menuOpen = false"
                        class="px-4 py-2 border border-[#003366] text-[#003366] text-sm font-semibold text-center"
                    >
                        New Request
                    </Link>
                    <Link
                        href="/requests/status"
                        @click="menuOpen = false"
                        class="px-4 py-2 border border-[#003366] text-[#003366] text-sm font-semibold text-center"
                    >
                        Request Status
                    </Link>
                    <Link
                        href="/login"
                        @click="menuOpen = false"
                        class="px-4 py-2 bg-[#003366] text-white text-sm font-semibold text-center"
                    >
                        Staff Login
                    </Link>
                </div>
            </div>
        </header>

        <div
            v-if="menuOpen"
            class="fixed inset-0 z-40 bg-black/30 md:hidden"
            @click="menuOpen = false"
        ></div>

        <!-- Hero Section -->
        <section class="relative text-white py-8 sm:py-12 overflow-hidden">
            <!-- Background photo with soft blur -->
            <div
                class="absolute inset-0"
                style="
                    background-image: url(&quot;/images/hero.jpg&quot;);
                    background-size: cover;
                    background-position: center;
                    filter: blur(3px);
                    transform: scale(1.06);
                "
            ></div>
            <!-- Gradient blue overlay -->
            <div
                class="absolute inset-0"
                style="
                    background: linear-gradient(
                        135deg,
                        rgba(0, 26, 61, 0.95) 0%,
                        rgba(0, 51, 102, 0.82) 50%,
                        rgba(0, 51, 102, 0.6) 100%
                    );
                "
            ></div>
            <div
                class="relative mx-auto max-w-6xl px-4 sm:px-6 grid gap-8 lg:grid-cols-2 items-center"
            >
                <div class="fade-in space-y-4" style="--delay: 250ms">
                    <p
                        class="text-xs uppercase tracking-widest text-[#FFD700] font-bold"
                    >
                        Public Service Portal
                    </p>
                    <h2 class="text-2xl sm:text-3xl font-bold leading-snug">
                        Web-Based Document Management System (DMS)
                    </h2>
                    <p class="text-sm text-blue-200 max-w-lg">
                        A unified platform for receiving, tracking, approving,
                        and archiving official documents. Built to provide clear
                        visibility for staff and accessible services for the
                        public.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <Link
                            href="/login"
                            class="px-5 py-2.5 bg-[#FFD700] text-[#003366] font-bold hover:bg-yellow-400 transition text-sm text-center"
                        >
                            Office Access
                        </Link>
                        <Link
                            href="/requests/new"
                            class="px-5 py-2.5 border border-white text-white hover:bg-white hover:text-[#003366] transition text-sm text-center"
                        >
                            Submit a Request
                        </Link>
                    </div>
                </div>

                <!-- Logo -->
                <div class="fade-in flex justify-center lg:justify-end" style="--delay: 380ms">
                    <div class="h-44 w-44 sm:h-56 sm:w-56 rounded-full overflow-hidden border-4 border-[#FFD700] shadow-lg bg-white">
                        <img
                            :src="'/images/logo.png'"
                            alt="Office of the Vice Mayor — Oas, Albay"
                            class="h-full w-full object-cover object-center"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Notice Bar (Marquee) -->
        <div
            class="fade-in bg-[#FFD700] text-[#003366] py-2 overflow-hidden"
            style="--delay: 700ms"
        >
            <div
                class="marquee-track flex whitespace-nowrap text-xs font-semibold tracking-wide"
            >
                <span
                    class="marquee-item"
                    v-for="n in 6"
                    :key="n"
                    aria-hidden="n > 1 ? true : undefined"
                >
                    Office Hours: {{ site.office_hours || 'Monday to Friday, 8:00 AM – 5:00 PM' }}
                    &nbsp;&nbsp;&bull;&nbsp;&nbsp; Hotline: {{ site.hotline || '(052) 555-0198' }}
                    &nbsp;&nbsp;&bull;&nbsp;&nbsp; Office of the Vice Mayor,
                    Oas, Albay &nbsp;&nbsp;&bull;&nbsp;&nbsp; Republic of the
                    Philippines &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                </span>
            </div>
        </div>

        <main
            class="mx-auto max-w-6xl px-4 sm:px-6 pb-12 sm:pb-16 pt-8 sm:pt-10 space-y-10 sm:space-y-14"
        >
            <!-- Services -->
            <section id="services">
                <div
                    class="fade-in border-l-4 border-[#003366] pl-4 mb-6"
                    style="--delay: 0ms"
                >
                    <p
                        class="text-xs uppercase tracking-widest text-gray-500 font-semibold"
                    >
                        Available Services
                    </p>
                    <h3 class="text-xl sm:text-2xl font-bold text-[#003366]">
                        Built for the Public, Trusted by the Office
                    </h3>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        class="fade-in bg-white border border-gray-300 border-t-4 border-t-[#003366] p-5 sm:p-6 shadow-sm"
                        style="--delay: 100ms"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-widest text-[#003366] bg-blue-50 px-2 py-1"
                        >
                            Request Intake
                        </span>
                        <h4 class="mt-4 text-base font-bold text-gray-900">
                            Online Submissions
                        </h4>
                        <p class="mt-2 text-sm text-gray-600">
                            Accept document requests with complete details,
                            contact information, and required attachments.
                        </p>
                    </div>
                    <div
                        class="fade-in bg-white border border-gray-300 border-t-4 border-t-[#003366] p-5 sm:p-6 shadow-sm"
                        style="--delay: 230ms"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-widest text-[#003366] bg-blue-50 px-2 py-1"
                        >
                            Tracking
                        </span>
                        <h4 class="mt-4 text-base font-bold text-gray-900">
                            Real-Time Status
                        </h4>
                        <p class="mt-2 text-sm text-gray-600">
                            Guests can monitor progress with a tracking number
                            from submission to release.
                        </p>
                    </div>
                    <div
                        class="fade-in bg-white border border-gray-300 border-t-4 border-t-[#003366] p-5 sm:p-6 shadow-sm sm:col-span-2 lg:col-span-1"
                        style="--delay: 360ms"
                    >
                        <span
                            class="text-xs font-bold uppercase tracking-widest text-[#003366] bg-blue-50 px-2 py-1"
                        >
                            Archive
                        </span>
                        <h4 class="mt-4 text-base font-bold text-gray-900">
                            Secure Records
                        </h4>
                        <p class="mt-2 text-sm text-gray-600">
                            Digital archiving with audit trails for compliance
                            and reporting.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Process + Assurance -->
            <section id="process" class="grid gap-6 lg:grid-cols-2">
                <div
                    class="fade-in bg-white border border-gray-300 p-5 sm:p-6 shadow-sm"
                    style="--delay: 0ms"
                >
                    <div class="border-l-4 border-[#003366] pl-4 mb-4">
                        <p
                            class="text-xs uppercase tracking-widest text-gray-500 font-semibold"
                        >
                            How It Works
                        </p>
                        <h3 class="text-lg sm:text-xl font-bold text-[#003366]">
                            Steps from Request to Release
                        </h3>
                    </div>
                    <ol class="space-y-4">
                        <li class="flex items-start gap-3">
                            <span
                                class="min-w-[28px] h-7 bg-[#003366] text-white text-xs font-bold flex items-center justify-center shrink-0"
                                >1</span
                            >
                            <div>
                                <p class="text-sm font-bold text-gray-900">
                                    Submit Request
                                </p>
                                <p class="text-xs text-gray-500">
                                    Provide document details and contact
                                    information.
                                </p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span
                                class="min-w-[28px] h-7 bg-[#003366] text-white text-xs font-bold flex items-center justify-center shrink-0"
                                >2</span
                            >
                            <div>
                                <p class="text-sm font-bold text-gray-900">
                                    Office Review
                                </p>
                                <p class="text-xs text-gray-500">
                                    Team checks completeness and approvals.
                                </p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span
                                class="min-w-[28px] h-7 bg-[#003366] text-white text-xs font-bold flex items-center justify-center shrink-0"
                                >3</span
                            >
                            <div>
                                <p class="text-sm font-bold text-gray-900">
                                    Release and Archive
                                </p>
                                <p class="text-xs text-gray-500">
                                    Receive status updates and release schedule.
                                </p>
                            </div>
                        </li>
                    </ol>
                </div>

                <div
                    class="fade-in bg-white border border-gray-300 p-5 sm:p-6 shadow-sm"
                    style="--delay: 160ms"
                >
                    <div class="border-l-4 border-[#FFD700] pl-4 mb-4">
                        <p
                            class="text-xs uppercase tracking-widest text-gray-500 font-semibold"
                        >
                            Governance
                        </p>
                        <h3 class="text-lg sm:text-xl font-bold text-[#003366]">
                            Governance-First Delivery
                        </h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-gray-50 border border-gray-200 p-3">
                            <p
                                class="font-bold text-[#003366] text-xs sm:text-sm"
                            >
                                Role-Based Access
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Data security
                            </p>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 p-3">
                            <p
                                class="font-bold text-[#003366] text-xs sm:text-sm"
                            >
                                Transparent Timelines
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                Service visibility
                            </p>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 p-3">
                            <p
                                class="font-bold text-[#003366] text-xs sm:text-sm"
                            >
                                Executive Summaries
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Reporting</p>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 p-3">
                            <p
                                class="font-bold text-[#003366] text-xs sm:text-sm"
                            >
                                Cloud-Ready Backups
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Continuity</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer
            class="fade-in bg-[#003366] text-white border-t-4 border-[#FFD700]"
            style="--delay: 0ms"
        >
            <div
                class="mx-auto max-w-6xl px-4 sm:px-6 py-5 sm:py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
            >
                <div class="flex items-center gap-3">
                    <img
                        :src="'/images/logo.png'"
                        alt="Seal"
                        class="h-10 w-10 object-contain opacity-80 shrink-0"
                    />
                    <div>
                        <p
                            class="text-xs font-bold uppercase tracking-widest text-[#FFD700]"
                        >
                            Office of the Vice Mayor
                        </p>
                        <p class="text-xs text-blue-200">
                            Municipality of Oas, Albay &mdash; Philippines
                        </p>
                    </div>
                </div>
                <div class="text-xs text-blue-200 space-y-1 sm:text-right">
                    <p>Hotline: {{ site.hotline || '(052) 555-0198' }}</p>
                    <p>Email: info@oas-albay.gov.ph</p>
                    <p class="text-[10px] text-blue-300 mt-1">
                        All Rights Reserved &copy;
                        {{ new Date().getFullYear() }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";

const menuOpen = ref(false);
const page = usePage();
const site = computed(() => page.props.site ?? {});

onMounted(() => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const delay =
                        entry.target.style.getPropertyValue("--delay") || "0ms";
                    setTimeout(() => {
                        entry.target.classList.add("is-visible");
                    }, parseInt(delay));
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1 },
    );

    document.querySelectorAll(".fade-in").forEach((el) => observer.observe(el));
});
</script>

<style scoped>
@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(22px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    opacity: 0;
}

.fade-in.is-visible {
    animation: fadeSlideUp 0.55s ease forwards;
}

/* Marquee ticker */
@keyframes marquee {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}

.marquee-track {
    width: max-content;
    animation: marquee 55s linear infinite;
}

.marquee-item {
    display: inline-block;
    padding-right: 0;
}
</style>
