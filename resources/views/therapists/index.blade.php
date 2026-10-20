@extends('layouts.app')

@section('title', 'Master Data Terapis - GeoTherapist')

@section('content')
<div x-data="therapistMaster()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">

    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <span>Master Data Terapis</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/30 font-medium">
                    {{ $stats['total'] }} Terapis
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Kelola data personal, keahlian, status tugas, dan titik koordinat home base terapis.</p>
        </div>

        <button 
            type="button" 
            @click="openCreateModal()" 
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold shadow-lg shadow-blue-600/30 transition-all transform active:scale-95 shrink-0"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tambah Terapis Baru</span>
        </button>
    </div>

    <!-- Quick Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total -->
        <div class="bg-[#131b2a] border border-blue-900/30 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center font-bold">
                {{ $stats['total'] }}
            </div>
            <div>
                <p class="text-xs text-slate-400">Total Terapis</p>
                <p class="text-sm font-bold text-slate-200">Terdaftar di Surabaya</p>
            </div>
        </div>

        <!-- Available -->
        <div class="bg-[#131b2a] border border-emerald-900/30 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                {{ $stats['available'] }}
            </div>
            <div>
                <p class="text-xs text-slate-400">Status Tersedia</p>
                <p class="text-sm font-bold text-emerald-400">Siap Dispatch</p>
            </div>
        </div>

        <!-- On Duty -->
        <div class="bg-[#131b2a] border border-amber-900/30 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                {{ $stats['on_duty'] }}
            </div>
            <div>
                <p class="text-xs text-slate-400">Sedang Bertugas</p>
                <p class="text-sm font-bold text-amber-400">Melayani Customer</p>
            </div>
        </div>

        <!-- Off -->
        <div class="bg-[#131b2a] border border-rose-900/30 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center font-bold">
                {{ $stats['off'] }}
            </div>
            <div>
                <p class="text-xs text-slate-400">Status Libur</p>
                <p class="text-sm font-bold text-rose-400">Tidak Menerima Order</p>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-[#131b2a] border border-blue-900/30 rounded-2xl p-4">
        <form method="GET" action="{{ route('therapists.index') }}" class="space-y-3">
            <!-- Row 1: Search Input & Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari nama terapis, nomor WA, spesialisasi, atau alamat..."
                        class="w-full pl-10 pr-4 py-2 bg-[#0b0f17] border border-slate-700/80 rounded-xl text-xs text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                    <button 
                        type="submit" 
                        class="w-full sm:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-semibold shadow transition-colors flex items-center justify-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span>Cari</span>
                    </button>
                    @if(request()->hasAny(['search', 'status', 'city', 'subdistrict']) || (request('per_page') && request('per_page') != 10))
                        <a 
                            href="{{ route('therapists.index') }}" 
                            class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-medium transition-colors"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </div>

            <!-- Row 2: Filter Dropdowns (Grid 4 Kolom Rata & Seimbang) -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2.5 border-t border-slate-800/60">
                <!-- Status Filter -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Status Terapis</label>
                    <select 
                        name="status" 
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700/80 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Tersedia (Ready)</option>
                        <option value="on_duty" {{ request('status') === 'on_duty' ? 'selected' : '' }}>Bertugas (On Duty)</option>
                        <option value="off" {{ request('status') === 'off' ? 'selected' : '' }}>Libur (Off)</option>
                    </select>
                </div>

                <!-- City Filter -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Kota</label>
                    <select 
                        name="city" 
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700/80 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="">Semua Kota</option>
                        <option value="Surabaya" {{ request('city') === 'Surabaya' ? 'selected' : '' }}>Surabaya</option>
                        <option value="Sidoarjo" {{ request('city') === 'Sidoarjo' ? 'selected' : '' }}>Sidoarjo</option>
                    </select>
                </div>

                <!-- Subdistrict Filter -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Kecamatan</label>
                    <select 
                        name="subdistrict" 
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700/80 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="">Semua Kecamatan</option>
                        @foreach($subdistricts as $sub)
                            <option value="{{ $sub }}" {{ request('subdistrict') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page Filter -->
                <div>
                    <label class="block text-[11px] font-medium text-slate-400 mb-1">Tampilkan per Hal</label>
                    <select 
                        name="per_page" 
                        onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700/80 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="5" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5 data / hal</option>
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 data / hal</option>
                        <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25 data / hal</option>
                        <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50 data / hal</option>
                        <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100 data / hal</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Master Data Table -->
    <div class="bg-[#131b2a] border border-blue-900/30 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300 divide-y divide-slate-800">
                <thead class="bg-[#0f172a] text-slate-400 uppercase font-semibold text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">Terapis</th>
                        <th class="py-3.5 px-4">Spesialisasi</th>
                        <th class="py-3.5 px-4">Kota</th>
                        <th class="py-3.5 px-4">Kecamatan</th>
                        <th class="py-3.5 px-4">Alamat Home Base</th>
                        <th class="py-3.5 px-4">Koordinat</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($therapists as $t)
                        <tr class="hover:bg-blue-950/20 transition-colors">
                            <!-- Avatar, Name & Phone -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $t->avatar_url }}" alt="{{ $t->name }}" class="w-10 h-10 rounded-xl object-cover border border-slate-700">
                                    <div>
                                        <p class="font-bold text-slate-100 text-sm">{{ $t->name }}</p>
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $t->phone) }}" target="_blank" class="text-blue-400 hover:underline font-mono-val flex items-center gap-1">
                                            <span>{{ $t->phone }}</span>
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <!-- Specialization -->
                            <td class="py-3.5 px-4">
                                <p class="font-medium text-slate-200">{{ $t->specialization }}</p>
                            </td>

                            <!-- Kota -->
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-slate-200">{{ $t->city ?? 'Surabaya' }}</span>
                            </td>

                            <!-- Kecamatan -->
                            <td class="py-3.5 px-4">
                                <span class="text-slate-300 font-medium">{{ $t->subdistrict ?? '-' }}</span>
                            </td>

                            <!-- Home Base Address -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="text-xs text-slate-300 truncate" title="{{ $t->address_label }}">{{ $t->address_label }}</p>
                            </td>

                            <!-- Coordinates -->
                            <td class="py-3.5 px-4 font-mono-val text-[11px] text-slate-400">
                                <span class="bg-slate-900/80 px-2 py-1 rounded border border-slate-800 inline-block">
                                    {{ number_format($t->latitude, 6) }}, {{ number_format($t->longitude, 6) }}
                                </span>
                            </td>

                            <!-- Status with Quick Toggle -->
                            <td class="py-3.5 px-4">
                                <form method="POST" action="{{ route('therapists.update-status', $t->id) }}" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <select 
                                        name="status" 
                                        onchange="this.form.submit()"
                                        class="text-xs font-semibold bg-transparent border-0 focus:outline-none cursor-pointer py-1 pr-4
                                            {{ $t->status === 'available' ? 'text-emerald-400' : ($t->status === 'on_duty' ? 'text-amber-400' : 'text-rose-400') }}"
                                    >
                                        <option value="available" class="bg-[#131b2a] text-emerald-400" {{ $t->status === 'available' ? 'selected' : '' }}>Tersedia</option>
                                        <option value="on_duty" class="bg-[#131b2a] text-amber-400" {{ $t->status === 'on_duty' ? 'selected' : '' }}>Bertugas</option>
                                        <option value="off" class="bg-[#131b2a] text-rose-400" {{ $t->status === 'off' ? 'selected' : '' }}>Libur</option>
                                    </select>
                                </form>
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="openEditModal({{ json_encode($t) }})"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-blue-600 hover:text-white text-slate-300 transition-colors"
                                        title="Edit Terapis"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    <form method="POST" action="{{ route('therapists.destroy', $t->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus terapis {{ $t->name }}?')" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-600 hover:text-white text-slate-300 transition-colors"
                                            title="Hapus Terapis"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                Tidak ada data terapis yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar & Info -->
        <div class="p-4 border-t border-slate-800/80 bg-[#0f172a]/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
            <div class="flex items-center gap-1.5">
                <span>Menampilkan</span>
                <span class="font-bold text-slate-200">{{ $therapists->firstItem() ?? 0 }} - {{ $therapists->lastItem() ?? 0 }}</span>
                <span>dari total</span>
                <span class="font-bold text-slate-200">{{ $therapists->total() }}</span>
                <span>terapis</span>
            </div>

            @if($therapists->hasPages())
                <div class="overflow-x-auto max-w-full">
                    {{ $therapists->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Form Tambah / Edit Terapis (dengan Mini-Map Picker Leaflet) -->
    <div 
        x-show="modalOpen" 
        x-transition.opacity 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm overflow-y-auto"
        style="display: none;"
    >
        <div 
            @click.outside="modalOpen = false" 
            class="bg-[#131b2a] border border-blue-900/60 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 my-8"
        >
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="font-bold text-base text-slate-100 flex items-center gap-2">
                    <span x-text="isEdit ? 'Edit Data Terapis' : 'Tambah Terapis Baru'"></span>
                </h3>
                <button @click="modalOpen = false" class="text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Nama -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Nama Lengkap Terapis *</label>
                        <input 
                            type="text" 
                            name="name" 
                            x-model="formData.name" 
                            required 
                            placeholder="Contoh: Budi Santoso"
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    <!-- Nomor WhatsApp -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Nomor WhatsApp *</label>
                        <input 
                            type="text" 
                            name="phone" 
                            x-model="formData.phone" 
                            required 
                            placeholder="Contoh: 081234567890"
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    <!-- Spesialisasi -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Spesialisasi Pijat *</label>
                        <input 
                            type="text" 
                            name="specialization" 
                            x-model="formData.specialization" 
                            required 
                            placeholder="Contoh: Deep Tissue & Shiatsu"
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Status Keberadaan *</label>
                        <select 
                            name="status" 
                            x-model="formData.status" 
                            required
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        >
                            <option value="available">Tersedia (Ready)</option>
                            <option value="on_duty">Sedang Bertugas (On Duty)</option>
                            <option value="off">Libur / Istirahat (Off)</option>
                        </select>
                    </div>


                    <!-- URL Foto Profil Avatar -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">URL Avatar (Opsional)</label>
                        <input 
                            type="url" 
                            name="avatar_url" 
                            x-model="formData.avatar_url" 
                            placeholder="https://..."
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Wilayah: Kota & Kecamatan Dropdown -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Kota Dropdown -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Kota *</label>
                        <select 
                            name="city" 
                            x-model="formData.city" 
                            @change="onCityChange()"
                            required
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
                        >
                            <template x-for="c in cities" :key="c">
                                <option :value="c" x-text="c" :selected="formData.city === c"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Kecamatan Dropdown (Cascading sesuai Kota) -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Kecamatan *</label>
                        <select 
                            name="subdistrict" 
                            x-model="formData.subdistrict" 
                            @change="onSubdistrictChange()"
                            required
                            class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none cursor-pointer"
                        >
                            <template x-for="sub in availableSubdistricts" :key="sub">
                                <option :value="sub" x-text="sub" :selected="formData.subdistrict === sub"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Alamat Label Home Base -->
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Alamat Label Home Base *</label>
                    <input 
                        type="text" 
                        name="address_label" 
                        x-model="formData.address_label" 
                        required 
                        placeholder="Contoh: Jl. Rungkut Asri Barat No. 18"
                        class="w-full px-3 py-2 bg-[#0b0f17] border border-slate-700 rounded-xl text-xs text-slate-100 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                </div>

                <!-- Leaflet Mini-Map Coordinates Picker -->
                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-200 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Tentukan Titik Koordinat Home Base (Mini-Map Picker)
                        </span>
                        <span class="text-[11px] text-blue-400">💡 Klik di peta atau geser pin</span>
                    </div>

                    <!-- Mini map container -->
                    <div id="pickerMap" class="w-full h-48 rounded-xl border border-blue-900/40 z-10 dark-tiles"></div>

                    <!-- Readonly / Manual Lat Lng -->
                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-0.5">Latitude *</label>
                            <input 
                                type="number" 
                                step="0.0000001" 
                                name="latitude" 
                                x-model="formData.latitude" 
                                required
                                class="w-full px-3 py-1.5 bg-[#0b0f17] border border-slate-700 rounded-lg text-xs font-mono-val text-blue-300 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-400 mb-0.5">Longitude *</label>
                            <input 
                                type="number" 
                                step="0.0000001" 
                                name="longitude" 
                                x-model="formData.longitude" 
                                required
                                class="w-full px-3 py-1.5 bg-[#0b0f17] border border-slate-700 rounded-lg text-xs font-mono-val text-blue-300 focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-800">
                    <button 
                        type="button" 
                        @click="modalOpen = false" 
                        class="px-4 py-2 rounded-xl text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition-all"
                    >
                        <span x-text="isEdit ? 'Simpan Perubahan' : 'Tambahkan Terapis'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function therapistMaster() {
    return {
        modalOpen: false,
        isEdit: false,
        formAction: '{{ route('therapists.store') }}',
        pickerMap: null,
        pickerMarker: null,

        cities: ['Surabaya', 'Sidoarjo'],
        subdistrictsByCity: {
            'Surabaya': [
                'Asemrowo', 'Benowo', 'Bubutan', 'Bulak', 'Dukuh Pakis', 'Gayungan',
                'Genteng', 'Gubeng', 'Gunung Anyar', 'Jambangan', 'Karangpilang',
                'Kenjeran', 'Krembangan', 'Lakarsantri', 'Mulyorejo', 'Pabean Cantikan',
                'Pakal', 'Rungkut', 'Sambikerep', 'Sawahan', 'Semampir', 'Simokerto',
                'Sukolilo', 'Sukomanunggal', 'Tambaksari', 'Tandes', 'Tegalsari',
                'Tenggilis Mejoyo', 'Wiyung', 'Wonocolo', 'Wonokromo'
            ],
            'Sidoarjo': [
                'Balongbendo', 'Buduran', 'Candi', 'Gedangan', 'Jabon', 'Krembung',
                'Krian', 'Porong', 'Prambon', 'Sedati', 'Sidoarjo', 'Sukodono',
                'Taman', 'Tanggulangin', 'Tarik', 'Tulangan', 'Waru', 'Wonoayu'
            ]
        },

        subdistrictCoords: {
            // Surabaya
            'Asemrowo': [-7.2514, 112.7050],
            'Benowo': [-7.2346, 112.6393],
            'Bubutan': [-7.2505, 112.7314],
            'Bulak': [-7.2356, 112.7937],
            'Dukuh Pakis': [-7.2917, 112.7022],
            'Gayungan': [-7.3328, 112.7275],
            'Genteng': [-7.2604, 112.7482],
            'Gubeng': [-7.2758, 112.7538],
            'Gunung Anyar': [-7.3364, 112.7946],
            'Jambangan': [-7.3197, 112.7161],
            'Karangpilang': [-7.3358, 112.6953],
            'Kenjeran': [-7.2185, 112.7766],
            'Krembangan': [-7.2289, 112.7303],
            'Lakarsantri': [-7.3061, 112.6464],
            'Mulyorejo': [-7.2662, 112.7842],
            'Pabean Cantikan': [-7.2336, 112.7408],
            'Pakal': [-7.2386, 112.6053],
            'Rungkut': [-7.3195, 112.7753],
            'Sambikerep': [-7.2796, 112.6517],
            'Sawahan': [-7.2750, 112.7247],
            'Semampir': [-7.2144, 112.7472],
            'Simokerto': [-7.2417, 112.7553],
            'Sukolilo': [-7.2922, 112.7831],
            'Sukomanunggal': [-7.2683, 112.6975],
            'Tambaksari': [-7.2528, 112.7667],
            'Tandes': [-7.2575, 112.6778],
            'Tegalsari': [-7.2722, 112.7411],
            'Tenggilis Mejoyo': [-7.3175, 112.7592],
            'Wiyung': [-7.3075, 112.6908],
            'Wonocolo': [-7.3150, 112.7375],
            'Wonokromo': [-7.2992, 112.7381],

            // Sidoarjo
            'Balongbendo': [-7.4114, 112.5186],
            'Buduran': [-7.4294, 112.7233],
            'Candi': [-7.4811, 112.7153],
            'Gedangan': [-7.3828, 112.7269],
            'Jabon': [-7.5458, 112.7789],
            'Krembung': [-7.5147, 112.6186],
            'Krian': [-7.3997, 112.5769],
            'Porong': [-7.5367, 112.7003],
            'Prambon': [-7.4644, 112.5694],
            'Sedati': [-7.3789, 112.7725],
            'Sidoarjo': [-7.4478, 112.7183],
            'Sukodono': [-7.3986, 112.6789],
            'Taman': [-7.3525, 112.6931],
            'Tanggulangin': [-7.5028, 112.7161],
            'Tarik': [-7.4447, 112.5022],
            'Tulangan': [-7.4664, 112.6469],
            'Waru': [-7.3517, 112.7481],
            'Wonoayu': [-7.4336, 112.6289]
        },

        get availableSubdistricts() {
            return this.subdistrictsByCity[this.formData.city] || [];
        },

        onCityChange() {
            const list = this.availableSubdistricts;
            if (!list.includes(this.formData.subdistrict)) {
                this.formData.subdistrict = list[0] || '';
            }
            this.onSubdistrictChange();
        },

        onSubdistrictChange() {
            if (!this.isEdit && this.formData.subdistrict) {
                const coords = this.subdistrictCoords[this.formData.subdistrict];
                if (coords) {
                    this.formData.latitude = coords[0];
                    this.formData.longitude = coords[1];
                    if (this.pickerMap && this.pickerMarker) {
                        this.pickerMarker.setLatLng(coords);
                        this.pickerMap.flyTo(coords, 14, { duration: 0.8 });
                    }
                }
            }
        },
        
        formData: {
            name: '',
            phone: '',
            specialization: 'Traditional Javanese Massage',
            status: 'available',
            avatar_url: '',
            address_label: '',
            city: 'Surabaya',
            subdistrict: 'Rungkut',
            latitude: -7.31952,
            longitude: 112.77531,
        },

        openCreateModal() {
            this.isEdit = false;
            this.formAction = '{{ route('therapists.store') }}';
            this.formData = {
                name: '',
                phone: '',
                specialization: 'Traditional Javanese Massage',
                status: 'available',
                avatar_url: '',
                address_label: '',
                city: 'Surabaya',
                subdistrict: 'Rungkut',
                latitude: -7.3195200,
                longitude: 112.7753100,
            };
            this.modalOpen = true;
            this.initOrUpdatePickerMap();
        },

        openEditModal(t) {
            this.isEdit = true;
            this.formAction = `/therapists/${t.id}`;
            const city = t.city && this.cities.includes(t.city) ? t.city : 'Surabaya';
            const subdistrict = t.subdistrict || (city === 'Sidoarjo' ? 'Waru' : 'Rungkut');
            this.formData = {
                name: t.name,
                phone: t.phone,
                specialization: t.specialization,
                status: t.status,
                avatar_url: t.avatar_url || '',
                address_label: t.address_label,
                city: city,
                subdistrict: subdistrict,
                latitude: t.latitude,
                longitude: t.longitude,
            };
            this.modalOpen = true;
            this.initOrUpdatePickerMap();
        },

        initOrUpdatePickerMap() {
            this.$nextTick(() => {
                setTimeout(() => {
                    const lat = parseFloat(this.formData.latitude) || -7.2800;
                    const lng = parseFloat(this.formData.longitude) || 112.7400;

                    if (!this.pickerMap) {
                        this.pickerMap = L.map('pickerMap').setView([lat, lng], 13);
                        
                        const savedStyle = localStorage.getItem('preferred_map_style') || 'osm';
                        const tileUrls = {
                            osm: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                            satellite: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
                        };
                        const url = tileUrls[savedStyle] || tileUrls['osm'];
                        L.tileLayer(url, { maxZoom: 19 }).addTo(this.pickerMap);

                        this.pickerMarker = L.marker([lat, lng], { draggable: true }).addTo(this.pickerMap);

                        this.pickerMarker.on('dragend', (e) => {
                            const p = e.target.getLatLng();
                            this.formData.latitude = parseFloat(p.lat.toFixed(7));
                            this.formData.longitude = parseFloat(p.lng.toFixed(7));
                        });

                        this.pickerMap.on('click', (e) => {
                            this.formData.latitude = parseFloat(e.latlng.lat.toFixed(7));
                            this.formData.longitude = parseFloat(e.latlng.lng.toFixed(7));
                            this.pickerMarker.setLatLng(e.latlng);
                        });
                    } else {
                        this.pickerMap.invalidateSize();
                        this.pickerMap.setView([lat, lng], 14);
                        this.pickerMarker.setLatLng([lat, lng]);
                    }
                }, 250);
            });
        }
    };
}
</script>
@endpush
