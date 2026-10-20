@extends('layouts.app')

@section('title', 'Dashboard Dispatch - Real-Time Therapist Allocator')

@section('content')
    <div x-data="dispatchApp()" x-init="init()" class="flex-1 flex flex-col lg:flex-row overflow-hidden relative">

        <!-- Left Sidebar: Search, Filters, and Ranked Therapist List -->
        <div
            class="w-full lg:w-[490px] xl:w-[530px] flex flex-col bg-[#0b0f17] border-r border-blue-900/30 z-20 h-auto lg:h-[calc(100vh-4rem)]">

            <!-- Search & Control Header (Sticky) -->
            <div class="p-4 sm:p-5 border-b border-blue-900/30 bg-[#0f172a]/70 backdrop-blur-md space-y-3.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-ping"></span>
                        <h1 class="text-base font-bold text-slate-100 tracking-tight">Pencarian Lokasi Pelanggan</h1>
                    </div>
                </div>

                <!-- Address Input with Debounced Autocomplete -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" x-model="searchQuery" @input.debounce.400ms="searchNominatim()"
                        placeholder="Cari alamat customer"
                        class="w-full pl-10 pr-10 py-2.5 bg-[#131b2a] border border-blue-900/40 rounded-xl text-sm text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all shadow-inner" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <template x-if="isSearching">
                            <svg class="animate-spin h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <template x-if="!isSearching && searchQuery.length > 0">
                            <button @click="searchQuery = ''; searchResults = []"
                                class="text-slate-400 hover:text-slate-200">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </template>
                    </div>

                    <!-- Autocomplete Suggestions Dropdown -->
                    <div x-show="searchResults.length > 0" @click.outside="searchResults = []" x-transition
                        class="absolute left-0 right-0 top-full mt-1.5 bg-[#131b2a] border border-blue-900/50 rounded-xl shadow-2xl z-50 max-h-60 overflow-y-auto divide-y divide-slate-800/60">
                        <template x-for="item in searchResults" :key="item.place_id">
                            <button type="button" @click="selectLocation(item)"
                                class="w-full text-left px-3.5 py-2.5 hover:bg-blue-600/20 text-xs text-slate-200 flex items-start gap-2.5 transition-colors">
                                <svg class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <div>
                                    <p class="font-semibold text-slate-100"
                                        x-text="item.name || item.display_name.split(',')[0]"></p>
                                    <p class="text-[11px] text-slate-400 line-clamp-1" x-text="item.display_name"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>


                <!-- Selected Customer Coordinates Info Card -->
                <div
                    class="flex items-center justify-between p-2.5 rounded-xl bg-blue-950/30 border border-blue-800/30 text-xs">
                    <div class="flex items-center gap-2 overflow-hidden pr-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                        <div class="truncate">
                            <span class="font-semibold text-slate-200">Titik Customer:</span>
                            <span class="text-slate-400 truncate ml-1" x-text="customerAddress"></span>
                        </div>
                    </div>
                    <div class="shrink-0 font-mono-val text-[11px] text-blue-400 font-semibold">
                        <span x-text="customerLat.toFixed(4)"></span>, <span x-text="customerLng.toFixed(4)"></span>
                    </div>
                </div>

                <!-- Filter Status & Therapist Count -->
                <div class="flex items-center justify-between pt-1">
                    <div class="flex items-center gap-1 bg-[#131b2a] p-1 rounded-lg border border-blue-900/30 text-xs">
                        <button @click="filterStatus = 'all'; fetchNearby()"
                            :class="filterStatus === 'all' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 rounded-md transition-colors">
                            Semua (<span x-text="totalCount"></span>)
                        </button>
                        <button @click="filterStatus = 'available'; fetchNearby()"
                            :class="filterStatus === 'available' ? 'bg-emerald-600 text-white font-semibold' : 'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 rounded-md transition-colors">
                            Tersedia
                        </button>
                        <button @click="filterStatus = 'on_duty'; fetchNearby()"
                            :class="filterStatus === 'on_duty' ? 'bg-amber-600 text-white font-semibold' : 'text-slate-400 hover:text-slate-200'"
                            class="px-2.5 py-1 rounded-md transition-colors">
                            Bertugas
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scrollable Therapist Cards List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3">

                <!-- Loading Indicator -->
                <div x-show="isLoading"
                    class="py-12 flex flex-col items-center justify-center text-center space-y-2 text-slate-400">
                    <svg class="animate-spin h-7 w-7 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="text-xs">Menghitung jarak terdekat...</p>
                </div>

                <!-- Empty State -->
                <div x-show="!isLoading && therapists.length === 0" class="py-12 text-center text-slate-400 space-y-2">
                    <p class="text-sm font-medium">Tidak ada terapis ditemukan untuk filter ini.</p>
                    <button @click="filterStatus = 'all'; fetchNearby()"
                        class="text-xs text-blue-400 hover:underline">Tampilkan semua status</button>
                </div>

                <!-- List of Ranked Therapists -->
                <template x-for="(t, index) in therapists" :key="t.id">
                    <div :class="{
                            'bg-[#162236] border-blue-400/50 shadow-md shadow-slate-950/40 ring-1 ring-blue-400/20': routedTherapistId === t.id,
                            'bg-[#131d2e] border-blue-900/50 shadow-sm': t.rank === 1 && routedTherapistId !== t.id,
                            'bg-[#101726] border-slate-800/80 hover:bg-[#141e30] hover:border-slate-700/60': t.rank !== 1 && routedTherapistId !== t.id
                        }"
                        class="rounded-2xl border p-4 transition-all duration-200 relative group cursor-pointer"
                        @click="drawRouteToTherapist(t)">
                        <!-- Rank #1 Header Info -->
                        <template x-if="t.rank === 1">
                            <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-blue-500/20">
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-400">
                                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    REKOMENDASI TERDEKAT (RANK #1)
                                </span>
                                <span class="text-xs text-emerald-400 font-semibold">
                                    Prioritas Utama
                                </span>
                            </div>
                        </template>

                        <!-- Main Info Row -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <!-- Avatar with Rank Badge -->
                                <div class="relative shrink-0">
                                    <img :src="t.avatar_url" :alt="t.name"
                                        class="w-12 h-12 rounded-xl object-cover border border-blue-800/40 shadow">
                                    <div :class="{
                                                                        'bg-blue-600 text-white font-extrabold': t.rank === 1,
                                                                        'bg-slate-800 text-slate-300 font-bold': t.rank !== 1
                                                                    }"
                                        class="absolute -top-1.5 -left-1.5 w-5 h-5 rounded-md text-[10px] flex items-center justify-center border border-slate-700 shadow"
                                        x-text="'#' + t.rank"></div>
                                </div>

                                <!-- Name, Specialization, Rating -->
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-sm text-slate-100 group-hover:text-blue-300 transition-colors"
                                            x-text="t.name"></h3>
                                        <!-- Status Text (No pill or box) -->
                                        <span :class="{
                                                'text-emerald-400': t.status === 'available',
                                                'text-amber-400': t.status === 'on_duty'
                                            }"
                                            class="text-xs font-medium"
                                            x-text="t.status === 'available' ? 'Tersedia' : 'Bertugas'"></span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="t.specialization"></p>

                                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                                        <span class="text-slate-300" x-text="t.subdistrict ? (t.subdistrict + ', ' + (t.city || '')) : (t.city || 'Surabaya')"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Distance Ribbon -->
                            <div class="text-right shrink-0">
                                <div class="text-lg font-extrabold text-blue-400 font-mono-val leading-none"
                                    x-text="t.road_km + ' km'"></div>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Jarak Jalan Riil</span>
                            </div>
                        </div>

                        <!-- ETA Badges (Motor vs Mobil) & Home base address -->
                        <div
                            class="mt-3 pt-2.5 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <!-- Travel time: pure text, no badge pill -->
                            <div class="flex items-center gap-3 text-xs font-mono-val">
                                <!-- Motor -->
                                <div class="text-emerald-400 font-medium"
                                    title="Termasuk persiapan & cari alamat">
                                    <span>🛵 Motor: <strong class="font-bold"
                                            x-text="t.eta_motor_text || (t.eta_motor_minutes + ' mnt')"></strong></span>
                                </div>
                                <!-- Mobil -->
                                <div class="text-slate-400 font-medium">
                                    <span>🚗 Mobil: <span class="text-slate-300"
                                            x-text="t.eta_car_text || (t.eta_car_minutes + ' mnt')"></span></span>
                                </div>
                            </div>

                            <!-- Address preview -->
                            <div class="text-[11px] text-slate-400 truncate max-w-[190px]" :title="t.address_label">
                                📍 <span x-text="t.address_label"></span>
                            </div>
                        </div>

                        <!-- Action Buttons: Focus Map & Send WA -->
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button type="button" @click="focusTherapist(t)"
                                class="px-3 py-2 rounded-xl text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 flex items-center justify-center gap-1.5 transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Lihat di Peta</span>
                            </button>

                            <button type="button" @click="dispatchWhatsApp(t)"
                                class="px-3 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-md shadow-emerald-900/30 flex items-center justify-center gap-1.5 transition-all transform active:scale-95">
                                <!-- WhatsApp Icon -->
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path
                                        d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.072.376-.044.102-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.086.289.13.332.202.043.072.043.419-.101.824z" />
                                </svg>
                                <span>Kirim Order WA</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Right Side: Interactive Leaflet Map -->
        <div class="flex-1 relative min-h-[450px] lg:min-h-0 bg-[#0b0f17]">
            <!-- Map Container -->
            <div id="leafletMap" class="w-full h-full z-10 dark-tiles"></div>

            <!-- Floating Map Top Bar: Customer indicator & Map Style Switcher -->
            <div class="absolute top-4 left-4 right-16 z-20 flex items-center justify-between gap-3 pointer-events-none">
                <!-- Left: Customer Draggable Hint -->
                <div
                    class="pointer-events-auto hidden sm:flex items-center gap-2 bg-[#131b2a]/95 backdrop-blur-md border border-blue-900/40 rounded-xl px-3.5 py-2 shadow-2xl text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                        <span class="text-slate-300">Customer Pin:</span>
                        <span class="font-semibold text-rose-400">Bisa digeser</span>
                    </div>
                    <span class="text-slate-600">|</span>
                    <div class="text-slate-400">
                        Total di Peta: <span class="font-bold text-blue-400" x-text="therapists.length"></span> Terapis
                    </div>
                </div>

                <!-- Right: Interactive Map Style Selector -->
                <div class="pointer-events-auto relative ml-auto" @click.outside="styleMenuOpen = false">
                    <button type="button" @click="styleMenuOpen = !styleMenuOpen"
                        class="flex items-center gap-2 px-3 py-2 bg-[#131b2a]/95 hover:bg-slate-800 text-slate-200 border border-blue-900/50 rounded-xl shadow-xl text-xs font-semibold backdrop-blur-md transition-all active:scale-95">
                        <span class="text-base" x-text="mapStyles[currentMapStyle]?.icon || '🗺️'"></span>
                        <span class="hidden md:inline text-slate-300 font-medium">Tampilan:</span>
                        <span class="text-blue-400 font-bold"
                            x-text="mapStyles[currentMapStyle]?.name || 'Gaya Peta'"></span>
                        <svg class="w-3.5 h-3.5 text-slate-400 ml-0.5 transition-transform duration-200"
                            :class="styleMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Map Style Dropdown Menu -->
                    <div x-show="styleMenuOpen" x-transition:enter="transition ease-out duration-200 transform"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-150 transform"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        class="absolute right-0 top-full mt-2 w-64 bg-[#131b2a] border border-blue-900/60 rounded-2xl shadow-2xl z-30 p-2 space-y-1"
                        style="display: none;">
                        <div class="px-2.5 py-1.5 border-b border-slate-800/80 mb-1 flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pilih Tampilan
                                Peta</span>
                            <span class="text-[10px] text-blue-400">Gratis</span>
                        </div>

                        <template x-for="(style, key) in mapStyles" :key="key">
                            <button type="button" @click="setMapStyle(key)"
                                :class="currentMapStyle === key ? 'bg-blue-600/20 border-blue-500/50 text-white' : 'hover:bg-slate-800/60 text-slate-300 border-transparent'"
                                class="w-full text-left px-2.5 py-2 rounded-xl text-xs flex items-center justify-between border transition-all">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-lg" x-text="style.icon"></span>
                                    <div>
                                        <p class="font-bold text-slate-100" x-text="style.name"></p>
                                        <p class="text-[10px] text-slate-400" x-text="style.subtitle"></p>
                                    </div>
                                </div>
                                <template x-if="currentMapStyle === key">
                                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Floating Reset Map Center Button -->
            <div class="absolute bottom-6 right-6 z-20 flex flex-col gap-2">
                <button type="button" @click="centerOnCustomer()" title="Pusatkan ke titik pelanggan"
                    class="bg-[#131b2a] hover:bg-blue-600 text-slate-200 hover:text-white border border-blue-900/40 p-3 rounded-xl shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
                <button type="button" @click="fitAllMarkers()" title="Lihat semua titik di peta"
                    class="bg-[#131b2a] hover:bg-slate-700 text-slate-200 border border-blue-900/40 p-3 rounded-xl shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Dispatch WhatsApp Preview & Confirmation Modal -->
        <div x-show="dispatchModalOpen" x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
            style="display: none;">
            <div @click.outside="dispatchModalOpen = false"
                class="bg-[#131b2a] border border-blue-900/50 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.007c.106.005.249-.04.39.299.144.347.491 1.199.534 1.286.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.072.376-.044.102-.116.433-.506.549-.68.116-.173.231-.144.39-.086.159.058 1.011.477 1.184.564.173.086.289.13.332.202.043.072.043.419-.101.824z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-100">Kirim Order via WhatsApp</h3>
                            <p class="text-xs text-slate-400">Pesan otomatis sesuai format standar PRD</p>
                        </div>
                    </div>
                    <button @click="dispatchModalOpen = false" class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Detail Target Terapis -->
                <template x-if="selectedTherapist">
                    <div class="space-y-3">
                        <div
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs">
                            <div>
                                <p class="text-slate-400">Terapis Tujuan:</p>
                                <p class="font-bold text-slate-100 text-sm" x-text="selectedTherapist.name"></p>
                                <p class="text-blue-400 font-mono-val" x-text="selectedTherapist.phone"></p>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-400 block">Jarak Rute Tempuh:</span>
                                <span class="text-base font-extrabold text-blue-400 font-mono-val"
                                    x-text="selectedTherapist.road_km + ' km'"></span>
                                <span class="text-[11px] text-emerald-400 block font-mono-val"
                                    x-text="'~' + (selectedTherapist.eta_motor_text || (selectedTherapist.eta_motor_minutes + ' mnt')) + ' (Motor)'"></span>
                            </div>
                        </div>

                        <!-- Optional Customer Name -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Nama Customer (Opsional):</label>
                            <input type="text" x-model="customerName" placeholder="Contoh: Ibu Rina / Bpk. Hadi"
                                class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-lg text-xs text-slate-100 focus:ring-1 focus:ring-blue-500 focus:outline-none" />
                        </div>

                        <!-- Message Preview -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Draf Pesan WhatsApp:</label>
                            <pre class="p-3 rounded-xl bg-[#0b0f17] border border-blue-950 text-xs text-slate-300 font-mono-val whitespace-pre-wrap leading-relaxed select-all"
                                x-text="generateMessagePreview()"></pre>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button type="button" @click="dispatchModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                                Batal
                            </button>
                            <a :href="getFinalWhatsAppUrl()" target="_blank" @click="confirmDispatchSent()"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-900/30 flex items-center gap-1.5 transition-all">
                                <span>Buka WhatsApp & Kirim</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function dispatchApp() {
            return {
                // State
                map: null,
                customerMarker: null,
                therapistMarkers: [],
                therapistMarkersMap: {},
                routeLayer: null,
                currentFetchSeq: 0,
                currentRouteSeq: 0,

                customerLat: {{ $defaultCustomer['lat'] }},
                customerLng: {{ $defaultCustomer['lng'] }},
                customerAddress: '{{ $defaultCustomer['address'] }}',
                customerName: '',

                searchQuery: '',
                searchResults: [],
                isSearching: false,

                filterStatus: 'all',
                isLoading: false,
                therapists: [],
                totalCount: 0,

                selectedTherapist: null,
                dispatchModalOpen: false,

                // Currently Routed Therapist
                routedTherapistId: null,

                // Routing Engine (OSRM Road Routing)
                routingEngine: 'osrm',
                actualEngineUsed: 'osrm',

                // Map Style Layer State
                currentMapStyle: ['osm', 'satellite'].includes(localStorage.getItem('preferred_map_style')) ? localStorage.getItem('preferred_map_style') : 'osm',
                currentTileLayer: null,
                styleMenuOpen: false,

                mapStyles: {
                    osm: {
                        name: 'OpenStreetMap',
                        icon: '🗺️',
                        subtitle: 'Peta jalan standar publik',
                        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    },
                    satellite: {
                        name: 'Citra Satelit',
                        icon: '🛰️',
                        subtitle: 'Foto satelit bumi (Esri World Imagery)',
                        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                        attribution: '&copy; Esri, Maxar, Earthstar Geographics',
                        maxZoom: 19
                    }
                },

                init() {
                    window.dispatchAppInstance = this;
                    this.$nextTick(() => {
                        this.initMap();
                        this.fetchNearby();
                    });
                },

                drawRouteToTherapistById(id) {
                    const t = this.therapists.find(x => x.id === id);
                    if (t) {
                        this.drawRouteToTherapist(t);
                    }
                },

                setMapStyle(styleKey) {
                    if (!this.mapStyles[styleKey]) styleKey = 'osm';
                    if (this.currentTileLayer && this.map) {
                        this.map.removeLayer(this.currentTileLayer);
                    }
                    const cfg = this.mapStyles[styleKey];
                    if (this.map) {
                        this.currentTileLayer = L.tileLayer(cfg.url, {
                            maxZoom: cfg.maxZoom || 19,
                            attribution: cfg.attribution
                        }).addTo(this.map);
                    }
                    this.currentMapStyle = styleKey;
                    localStorage.setItem('preferred_map_style', styleKey);
                    this.styleMenuOpen = false;
                },

                initMap() {
                    if (this.map) return;

                    // Initialize Leaflet Map
                    this.map = L.map('leafletMap', {
                        center: [this.customerLat, this.customerLng],
                        zoom: 13,
                        zoomControl: false,
                    });

                    // Zoom control on top right
                    L.control.zoom({ position: 'topright' }).addTo(this.map);

                    // Apply selected tile layer style
                    const savedStyle = localStorage.getItem('preferred_map_style');
                    this.setMapStyle(this.mapStyles[savedStyle] ? savedStyle : 'osm');

                    // Create Draggable Customer Marker
                    // Create Draggable Customer Marker (Accurate Vector Teardrop Pin)
                    const customerIcon = L.divIcon({
                        className: 'custom-customer-icon',
                        html: `
                                                    <div style="width: 36px; height: 46px; position: relative;">
                                                        <svg width="36" height="46" viewBox="0 0 36 46" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5)); display: block;">
                                                            <path d="M18 0C8.05887 0 0 8.05887 0 18C0 30 16 44.5 17.2 45.6C17.65 46 18.35 46 18.8 45.6C20 44.5 36 30 36 18C36 8.05887 27.9411 0 18 0Z" fill="#ef4444"/>
                                                            <circle cx="18" cy="18" r="14" fill="#991b1b"/>
                                                            <path d="M18 11.5C15.8 11.5 14 13.3 14 15.5C14 17.7 15.8 19.5 18 19.5C20.2 19.5 22 17.7 22 15.5C22 13.3 20.2 11.5 18 11.5ZM18 21.5C14.7 21.5 10.5 23.1 10.5 26.2V27.5H25.5V26.2C25.5 23.1 21.3 21.5 18 21.5Z" fill="white"/>
                                                        </svg>
                                                    </div>
                                                `,
                        iconSize: [36, 46],
                        iconAnchor: [18, 46],
                        popupAnchor: [0, -46],
                        tooltipAnchor: [0, -46],
                    });

                    this.customerMarker = L.marker([this.customerLat, this.customerLng], {
                        draggable: true,
                        icon: customerIcon,
                        title: 'Geser titik pelanggan ke lokasi persis'
                    }).addTo(this.map);

                    this.customerMarker.bindTooltip('📍 Titik Pelanggan (Bisa digeser)', {
                        permanent: false,
                        direction: 'top',
                        offset: [0, -46],
                        className: 'bg-slate-900 text-white text-xs px-2 py-1 rounded shadow'
                    });

                    // Dedicated LayerGroup for route polylines - prevents orphaned lines during rapid zoom/drag
                    this.routeLayer = L.layerGroup().addTo(this.map);

                    // When marker drag begins, immediately detach old route line and cancel in-flight route requests
                    this.customerMarker.on('dragstart', () => {
                        this.currentRouteSeq++;
                        this.routedTherapistId = null;
                        if (this.routeLayer) {
                            this.routeLayer.clearLayers();
                        }
                    });

                    // When marker is dragged and dropped, update coordinates and auto-route to new closest therapist
                    this.customerMarker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.customerLat = pos.lat;
                        this.customerLng = pos.lng;
                        this.customerAddress = `Koordinat (${pos.lat.toFixed(5)}, ${pos.lng.toFixed(5)})`;
                        this.routedTherapistId = null; // Auto-target new closest (Rank #1) therapist
                        this.currentRouteSeq++;
                        if (this.routeLayer) {
                            this.routeLayer.clearLayers();
                        }
                        this.fetchNearby();
                        this.reverseGeocode(pos.lat, pos.lng);
                    });

                    // Recalculate container dimensions so Leaflet zoom math stays exact
                    setTimeout(() => {
                        if (this.map) this.map.invalidateSize();
                    }, 250);

                    window.addEventListener('resize', () => {
                        if (this.map) this.map.invalidateSize();
                    });
                },

                // Reverse geocoding via Nominatim to get readable street/area name when pin dragged
                async reverseGeocode(lat, lng) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                        const data = await res.json();
                        if (data && data.display_name) {
                            const parts = data.display_name.split(',');
                            this.customerAddress = parts.slice(0, 3).join(',').trim();
                        }
                    } catch (err) {
                        console.warn('Reverse geocode error:', err);
                    }
                },

                // Nominatim address search with autocomplete suggestions
                async searchNominatim() {
                    const query = this.searchQuery.trim();
                    if (query.length < 3) {
                        this.searchResults = [];
                        return;
                    }

                    this.isSearching = true;
                    try {
                        // Focus on metropolitan region (Surabaya, Sidoarjo, Gresik)
                        // Bounding box: lon 112.45 to 112.95, lat -7.05 to -7.58
                        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&viewbox=112.45,-7.05,112.95,-7.58&bounded=0&limit=8&countrycodes=id`;
                        const res = await fetch(url);
                        const data = await res.json();
                        this.searchResults = data || [];
                    } catch (e) {
                        console.error('Nominatim search failed:', e);
                        this.searchResults = [];
                    } finally {
                        this.isSearching = false;
                    }
                },

                // Pick location from suggestions
                selectLocation(item) {
                    const lat = parseFloat(item.lat);
                    const lng = parseFloat(item.lon);
                    const name = item.name || item.display_name.split(',')[0];

                    this.searchQuery = name;
                    this.searchResults = [];
                    this.setCustomerLocation(lat, lng, item.display_name.split(',').slice(0, 3).join(','));
                },

                setCustomerLocation(lat, lng, addressLabel) {
                    this.customerLat = lat;
                    this.customerLng = lng;
                    this.customerAddress = addressLabel;
                    this.routedTherapistId = null; // Auto-target new closest (Rank #1) therapist
                    this.currentRouteSeq++;

                    if (this.routeLayer) {
                        this.routeLayer.clearLayers();
                    }

                    if (this.customerMarker) {
                        this.customerMarker.setLatLng([lat, lng]);
                    }
                    if (this.map) {
                        this.map.flyTo([lat, lng], 14, { duration: 1.2 });
                    }

                    this.fetchNearby();
                },

                centerOnCustomer() {
                    if (this.map) {
                        this.map.flyTo([this.customerLat, this.customerLng], 14, { duration: 0.8 });
                    }
                },

                fitAllMarkers() {
                    if (!this.map) return;
                    const group = [];
                    group.push([this.customerLat, this.customerLng]);
                    this.therapists.forEach(t => group.push([t.latitude, t.longitude]));
                    if (group.length > 0) {
                        this.map.fitBounds(group, { padding: [40, 40] });
                    }
                },

                // Fetch nearby therapists from backend API (OSRM Table Matrix or Haversine)
                async fetchNearby() {
                    const thisFetchSeq = ++this.currentFetchSeq;
                    this.isLoading = true;
                    try {
                        const params = new URLSearchParams({
                            lat: this.customerLat,
                            lng: this.customerLng,
                            status: this.filterStatus,
                            customer_address: this.customerAddress,
                            limit: 20,
                            engine: this.routingEngine
                        });

                        const res = await fetch(`/api/therapists/nearby?${params.toString()}`);
                        const json = await res.json();

                        // Discard stale response if a newer fetch was initiated
                        if (thisFetchSeq !== this.currentFetchSeq) return;

                        if (json.success) {
                            this.therapists = json.data;
                            this.totalCount = json.count;
                            this.actualEngineUsed = json.routing_engine || this.routingEngine;
                            this.renderMapMarkers();
                        }
                    } catch (e) {
                        if (thisFetchSeq === this.currentFetchSeq) {
                            console.error('Failed to fetch nearby therapists:', e);
                        }
                    } finally {
                        if (thisFetchSeq === this.currentFetchSeq) {
                            this.isLoading = false;
                        }
                    }
                },

                // Render Therapist markers & connecting route line
                renderMapMarkers() {
                    if (!this.map) return;

                    this.therapistMarkersMap = this.therapistMarkersMap || {};
                    const activeIds = new Set(this.therapists.map(t => t.id));

                    // Clean up markers no longer in dataset
                    for (const id in this.therapistMarkersMap) {
                        if (!activeIds.has(Number(id))) {
                            this.map.removeLayer(this.therapistMarkersMap[id]);
                            delete this.therapistMarkersMap[id];
                        }
                    }

                    this.therapists.forEach(t => {
                        const isRankOne = t.rank === 1;

                        // Status color dot
                        const pinBg = isRankOne ? '#2563eb' : '#1e293b';
                        const pinBorder = isRankOne ? '#60a5fa' : '#475569';
                        const statusColor = t.status === 'available' ? '#10b981' : (t.status === 'on_duty' ? '#f59e0b' : '#f43f5e');

                        const iconHtml = `
                                                    <div style="width: 32px; height: 42px; position: relative;">
                                                        <svg width="32" height="42" viewBox="0 0 32 42" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 3px 5px rgba(0,0,0,0.5)); display: block;">
                                                            <path d="M16 0C7.16 0 0 7.16 0 16C0 27 14 39.8 15.25 41.1C15.65 41.5 16.35 41.5 16.75 41.1C18 39.8 32 27 32 16C32 7.16 24.84 0 16 0Z" fill="${pinBg}" stroke="${pinBorder}" stroke-width="1.5"/>
                                                            <circle cx="16" cy="15" r="10.5" fill="#0f172a"/>
                                                            <text x="16" y="19" font-family="system-ui, -apple-system, sans-serif" font-weight="800" font-size="11" fill="white" text-anchor="middle">${t.rank}</text>
                                                            <circle cx="25" cy="7" r="4" fill="${statusColor}" stroke="#0f172a" stroke-width="1.5"/>
                                                        </svg>
                                                    </div>
                                                `;

                        const icon = L.divIcon({
                            className: 'custom-therapist-icon',
                            html: iconHtml,
                            iconSize: [32, 42],
                            iconAnchor: [16, 42],
                            popupAnchor: [0, -42],
                            tooltipAnchor: [0, -42],
                        });

                        // Popup content with crystal-clear high-contrast typography
                        const popupContent = `
                                                    <div style="background: #0f172a; color: #f8fafc;" class="p-3.5 space-y-2.5 min-w-[240px] text-slate-100 rounded-xl">
                                                        <!-- Header: Avatar, Name, Rank Badge, Distance -->
                                                        <div class="flex items-center gap-2.5">
                                                            <img src="${t.avatar_url}" class="w-10 h-10 rounded-xl object-cover border border-blue-500/40 shadow shrink-0">
                                                            <div class="flex-1 min-w-0">
                                                                <h4 class="font-extrabold text-white text-sm truncate" style="color: #ffffff !important;">${t.name}</h4>
                                                                <div class="flex items-center gap-1.5 text-[11px] mt-0.5">
                                                                    <span class="font-bold text-[11px] text-blue-400" style="color: #60a5fa !important;">Rank #${t.rank}</span>
                                                                    <span class="text-slate-400 font-extrabold font-mono-val" style="color: #94a3b8 !important;">•</span>
                                                                    <span class="text-blue-400 font-bold font-mono-val" style="color: #60a5fa !important;">${t.road_km} km</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- ETA Info: Plain Text without box/border -->
                                                        <div class="text-xs space-y-1.5 py-0.5">
                                                            <div class="flex items-center justify-between">
                                                                <span style="color: #cbd5e1 !important;" class="text-slate-300">🛵 Motor:</span>
                                                                <strong style="color: #34d399 !important;" class="font-bold text-emerald-400 font-mono-val">${t.eta_motor_text || (t.eta_motor_minutes + ' mnt')}</strong>
                                                            </div>
                                                            <div class="flex items-center justify-between">
                                                                <span style="color: #94a3b8 !important;" class="text-slate-400">🚗 Mobil:</span>
                                                                <span style="color: #e2e8f0 !important;" class="font-medium text-slate-200 font-mono-val">${t.eta_car_text || (t.eta_car_minutes + ' mnt')}</span>
                                                            </div>
                                                            ${t.eta_breakdown ? `
                                                            <div class="text-[10px] pt-1 flex items-center justify-between" style="color: #94a3b8 !important;">
                                                                <span>Jalan: <strong style="color: #e2e8f0 !important;">~${t.eta_breakdown.travel_minutes}m</strong></span>
                                                                <span>Cari alamat: <strong style="color: #fbbf24 !important;">~${t.eta_breakdown.buffer_minutes}m</strong></span>
                                                            </div>
                                                            ` : ''}
                                                            <div class="text-[11px] truncate pt-1 flex items-center gap-1" style="color: #94a3b8 !important;">
                                                                <span class="shrink-0">📍</span>
                                                                <span class="truncate" title="${t.address_label}">${t.address_label}</span>
                                                            </div>
                                                        </div>

                                                        <!-- Action Buttons: Focus Route & WhatsApp -->
                                                        <div class="grid grid-cols-1 gap-1.5 pt-0.5">
                                                            <button 
                                                                type="button"
                                                                onclick="window.dispatchAppInstance && window.dispatchAppInstance.drawRouteToTherapistById(${t.id})" 
                                                                style="background: rgba(6, 182, 212, 0.2); border: 1px solid rgba(6, 182, 212, 0.6); color: #67e8f9;" 
                                                                class="w-full text-center py-2 px-3 rounded-lg font-bold text-xs hover:bg-cyan-600 hover:text-white transition-all flex items-center justify-center gap-1.5 shadow"
                                                            >
                                                                <svg class="w-4 h-4 fill-none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                                                <span>Fokus Rute Jalan</span>
                                                            </button>
                                                            <a 
                                                                href="${t.whatsapp_url}" 
                                                                target="_blank" 
                                                                style="background: #059669; color: #ffffff;"
                                                                class="block text-center py-2 px-3 rounded-lg hover:bg-emerald-500 font-bold text-xs shadow-md transition-colors"
                                                            >
                                                                Kirim Order WhatsApp
                                                            </a>
                                                        </div>
                                                    </div>
                                                `;

                        if (this.therapistMarkersMap[t.id]) {
                            // Reuse existing marker instance without DOM teardown
                            const existing = this.therapistMarkersMap[t.id];
                            existing.setLatLng([t.latitude, t.longitude]);
                            existing.setIcon(icon);
                            existing.setPopupContent(popupContent);
                            existing.off('click');
                            existing.on('click', () => {
                                this.drawRouteToTherapist(t);
                            });
                        } else {
                            const marker = L.marker([t.latitude, t.longitude], { icon: icon }).addTo(this.map);
                            marker.on('click', () => {
                                this.drawRouteToTherapist(t);
                            });
                            marker.bindPopup(popupContent);
                            this.therapistMarkersMap[t.id] = marker;
                        }
                    });

                    this.therapistMarkers = Object.values(this.therapistMarkersMap);

                    // Re-draw route line to currently selected therapist or rank 1 (without forced fitBounds on initial background render)
                    const targetTherapist = this.therapists.find(t => t.id === this.routedTherapistId) || this.therapists.find(t => t.rank === 1);
                    if (targetTherapist) {
                        this.drawRouteToTherapist(targetTherapist, false);
                    }
                },

                // Draw turn-by-turn road route from customer to chosen therapist and fit map canvas to cover both
                async drawRouteToTherapist(t, shouldFitBounds = true) {
                    if (!this.map || !t) return;
                    const thisRouteSeq = ++this.currentRouteSeq;
                    this.routedTherapistId = t.id;

                    // Ensure route layer exists
                    if (!this.routeLayer) {
                        this.routeLayer = L.layerGroup().addTo(this.map);
                    }
                    this.routeLayer.clearLayers();

                    // Immediately show direct route line from customer position
                    const directLine = L.polyline([
                        [this.customerLat, this.customerLng],
                        [t.latitude, t.longitude]
                    ], {
                        color: '#3b82f6',
                        weight: 3,
                        dashArray: '6, 8',
                        opacity: 0.85
                    });
                    this.routeLayer.addLayer(directLine);

                    // Fit canvas immediately to cover both customer and therapist
                    if (shouldFitBounds) {
                        const bounds = L.latLngBounds([
                            [this.customerLat, this.customerLng],
                            [t.latitude, t.longitude]
                        ]);
                        this.map.fitBounds(bounds, {
                            padding: [60, 60],
                            maxZoom: 16,
                            animate: true,
                            duration: 0.6
                        });
                    }

                    // Fetch real OSRM road geometry and upgrade line
                    try {
                        const url = `https://router.project-osrm.org/route/v1/driving/${this.customerLng},${this.customerLat};${t.longitude},${t.latitude}?overview=full&geometries=geojson`;
                        const res = await fetch(url);
                        const data = await res.json();

                        // If a newer route was requested while waiting for OSRM, ignore this stale result
                        if (thisRouteSeq !== this.currentRouteSeq) return;

                        if (data.code === 'Ok' && data.routes && data.routes[0]) {
                            this.routeLayer.clearLayers();
                            const latLngs = data.routes[0].geometry.coordinates.map(pt => [pt[1], pt[0]]);
                            const roadLine = L.polyline(latLngs, {
                                color: '#06b6d4', // Vibrant Cyan neon
                                weight: 5,
                                opacity: 0.95,
                                lineCap: 'round',
                                lineJoin: 'round'
                            });
                            this.routeLayer.addLayer(roadLine);

                            // Ensure complete curved road path + customer + therapist fit in map canvas
                            if (shouldFitBounds) {
                                const routeBounds = roadLine.getBounds();
                                routeBounds.extend([this.customerLat, this.customerLng]);
                                routeBounds.extend([t.latitude, t.longitude]);
                                this.map.fitBounds(routeBounds, {
                                    padding: [60, 60],
                                    maxZoom: 16,
                                    animate: true,
                                    duration: 0.4
                                });
                            }
                        }
                    } catch (e) {
                        console.warn('OSRM Route geometry error, fallback to straight line kept:', e);
                    }
                },

                focusTherapist(t) {
                    if (!this.map) return;
                    this.drawRouteToTherapist(t, true);
                    const marker = this.therapistMarkersMap[t.id];
                    if (marker) {
                        setTimeout(() => marker.openPopup(), 600);
                    }
                },

                dispatchWhatsApp(t) {
                    this.selectedTherapist = t;
                    this.dispatchModalOpen = true;
                },

                generateMessagePreview() {
                    if (!this.selectedTherapist) return '';
                    const cust = this.customerName ? `${this.customerName} (${this.customerAddress})` : this.customerAddress;
                    const etaMotor = this.selectedTherapist.eta_motor_text || `${this.selectedTherapist.eta_motor_minutes} menit`;
                    return `Halo ${this.selectedTherapist.name}, ada order masuk:\n- Alamat Pelanggan: ${cust}\n- Jarak Tempuh: ${this.selectedTherapist.road_km} km dari home base kamu\n- Estimasi Waktu Tiba (Motor): ${etaMotor} (termasuk persiapan & cari alamat)\n- Maps Lokasi: https://maps.google.com/?q=${this.customerLat},${this.customerLng}\n\nMohon konfirmasi kesiapannya segera. Terima kasih!`;
                },

                getFinalWhatsAppUrl() {
                    if (!this.selectedTherapist) return '#';
                    let phone = this.selectedTherapist.phone.replace(/[^0-9]/g, '');
                    if (phone.startsWith('0')) {
                        phone = '62' + phone.substring(1);
                    }
                    return `https://wa.me/${phone}?text=${encodeURIComponent(this.generateMessagePreview())}`;
                },

                async confirmDispatchSent() {
                    if (!this.selectedTherapist) return;
                    try {
                        // Record dispatch history in backend
                        await fetch('/api/dispatch-logs', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                therapist_id: this.selectedTherapist.id,
                                customer_name: this.customerName || null,
                                customer_address: this.customerAddress,
                                customer_lat: this.customerLat,
                                customer_lng: this.customerLng,
                                distance_km: this.selectedTherapist.road_km,
                                est_minutes_motor: this.selectedTherapist.eta_motor_minutes
                            })
                        });
                    } catch (e) {
                        console.error('Failed to log dispatch:', e);
                    }
                    this.dispatchModalOpen = false;
                }
            };
        }
    </script>
@endpush