<div class="space-y-6">
    <!-- Summary Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Akun -->
        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-[#828282] uppercase tracking-wide mb-1">Total Akun</p>
                    <p class="text-2xl font-bold text-[#212121]">—</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <i class="fas fa-users text-blue-500 text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Siswa / Teknisi -->
        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-[#828282] uppercase tracking-wide mb-1">Siswa / Teknisi</p>
                    <p class="text-2xl font-bold text-[#212121]">—</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <i class="fas fa-user-graduate text-green-500 text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Mitra Sponsor -->
        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-[#828282] uppercase tracking-wide mb-1">Mitra Sponsor</p>
                    <p class="text-2xl font-bold text-[#212121]">—</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i class="fas fa-handshake text-purple-500 text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Administrator -->
        <div class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium text-[#828282] uppercase tracking-wide mb-1">Administrator</p>
                    <p class="text-2xl font-bold text-[#212121]">—</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center">
                    <i class="fas fa-user-shield text-orange-500 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl p-4 shadow-sm">
        <div class="flex flex-wrap items-center gap-3">
            <!-- Search Input (Left, Wide) -->
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-[#828282] text-sm"></i>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari nama, email..." 
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm text-[#212121] placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent"
                    >
                </div>
            </div>

            <!-- Role Filter Dropdown -->
            <div class="w-full sm:w-auto sm:min-w-[180px] relative">
                <select 
                    wire:model.live="roleFilter"
                    class="w-full px-3.5 py-2.5 pr-9 border border-gray-200 rounded-lg text-sm text-[#212121] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent appearance-none bg-white cursor-pointer"
                >
                    <option value="all">Semua Role</option>
                    <option value="student">Siswa / Teknisi</option>
                    <option value="sponsor">Mitra Sponsor</option>
                    <option value="admin">Administrator</option>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[#828282] text-xs pointer-events-none"></i>
            </div>

            <!-- KTA Status Filter -->
            <div class="w-full sm:w-auto sm:min-w-[180px] relative">
                <select 
                    wire:model.live="ktaStatusFilter"
                    class="w-full px-3.5 py-2.5 pr-9 border border-gray-200 rounded-lg text-sm text-[#212121] focus:outline-none focus:ring-2 focus:ring-[#1a73e8] focus:border-transparent appearance-none bg-white cursor-pointer"
                >
                    <option value="all">Status KTA</option>
                    <option value="free">Free Member</option>
                    <option value="premium">Premium</option>
                </select>
                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[#828282] text-xs pointer-events-none"></i>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Table Header -->
        <div class="bg-[#F4F6F8] border-b border-gray-200">
            <div class="grid grid-cols-12 gap-4 px-6 py-3.5 text-xs font-semibold text-[#828282] uppercase tracking-wide">
                <div class="col-span-3">Pengguna</div>
                <div class="col-span-2">Peran</div>
                <div class="col-span-2">Demografi & Domisili</div>
                <div class="col-span-2">Status KTA</div>
                <div class="col-span-2">Terdaftar</div>
                <div class="col-span-1 text-center">Aksi</div>
            </div>
        </div>

        <!-- Table Body (Empty State) -->
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <i class="fas fa-users text-gray-400 text-2xl"></i>
            </div>
            <p class="text-sm font-medium text-[#212121] mb-1">Belum Ada Data Pengguna</p>
            <p class="text-xs text-[#828282]">Data pengguna akan muncul di sini setelah integrasi backend.</p>
        </div>

        <!-- ponytail: table rows ketika ada data
        <div class="divide-y divide-gray-100">
            <div class="grid grid-cols-12 gap-4 px-6 py-4 hover:bg-[#F4F6F8] transition-colors">
                <div class="col-span-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-semibold text-blue-700">JD</div>
                    <div>
                        <p class="text-sm font-semibold text-[#212121]">John Doe</p>
                        <p class="text-xs text-[#828282]">john@example.com</p>
                    </div>
                </div>
                <div class="col-span-2 flex items-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">Siswa / Teknisi</span>
                </div>
                <div class="col-span-2 flex items-center">
                    <div class="text-xs">
                        <p class="text-[#212121] font-medium">Usia 21</p>
                        <p class="text-[#828282]">Kota Bandung</p>
                    </div>
                </div>
                <div class="col-span-2 flex items-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border border-gray-300 text-[#212121]">Free Member</span>
                </div>
                <div class="col-span-2 flex items-center">
                    <p class="text-xs text-[#212121]">21 Okt 2026</p>
                </div>
                <div class="col-span-1 flex items-center justify-center">
                    <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-[#212121] hover:bg-gray-50 transition-colors">
                        <i class="fas fa-eye text-[#828282]"></i>
                        Detail
                    </button>
                </div>
            </div>
        </div>
        -->
    </div>
</div>
