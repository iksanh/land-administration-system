<div class="flex flex-col gap-6">
    <x-flash />
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800 tracking-tight">Manajemen User</h2>
            <p class="text-sm text-gray-500 mt-1">Kelola akun, hak akses (role), dan status aktif pengguna sistem.</p>
        </div>
        <button wire:click="$toggle('showForm')"
            class="px-4 py-2 rounded-md font-medium text-sm transition-colors shadow-sm
            {{ $showForm ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' : 'bg-[#1677ff] hover:bg-[#0958d9] text-white' }}">
            {{ $showForm ? '✕ Batal Tambah' : '+ Tambah User Baru' }}
        </button>
    </div>

    {{-- Create form --}}
    @if ($showForm)
        <form wire:submit="create" class="bg-gray-50/50 p-5 rounded-lg border border-gray-200 flex flex-col gap-4">
            <h3 class="text-base font-semibold text-gray-800 pb-2 border-b border-gray-200">Form Registrasi User Baru</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" placeholder="Misal: Budi Santoso"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Alamat Email <span class="text-red-500">*</span></label>
                    <input type="email" wire:model="email" placeholder="user@example.com"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                    <input type="password" wire:model="password" placeholder="Minimal 6 karakter"
                        class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff]">
                    @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col gap-1.5 md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Role Sistem <span class="text-red-500">*</span> <span class="font-normal text-gray-400">— boleh lebih dari satu</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @foreach ($roleOptions as $r)
                            <label class="flex items-start gap-2.5 bg-white border rounded-md px-3 py-2.5 cursor-pointer transition-colors {{ in_array($r->value, $roles, true) ? 'border-[#1677ff] ring-1 ring-[#1677ff]/30' : 'border-gray-300 hover:border-gray-400' }}">
                                <input type="checkbox" wire:model.live="roles" value="{{ $r->value }}" class="mt-0.5 rounded border-gray-300 text-[#1677ff] focus:ring-[#1677ff]">
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">{{ $r->label() }}</span>
                                    <span class="block text-[11px] text-gray-400 leading-snug">{{ $r->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('roles') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-[#1677ff] hover:bg-[#0958d9] text-white px-6 py-2 rounded-md font-medium text-sm shadow-sm">
                    Simpan User Baru
                </button>
            </div>
        </form>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500 mb-1">Total Pengguna</p>
            <p class="text-3xl font-semibold text-gray-800">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500 mb-1">Akun Aktif</p>
            <p class="text-3xl font-semibold text-[#52c41a]">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
            <p class="text-sm font-medium text-gray-500 mb-1">Admin Sistem</p>
            <p class="text-3xl font-semibold text-[#722ed1]">{{ $stats['admin'] }}</p>
        </div>
    </div>

    {{-- Toolbar --}}
    <x-search-bar model="search" placeholder="Cari nama, email, atau role..." :count="$users->count()" />

    {{-- Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-[#fafafa] border-b border-gray-200 text-gray-800 font-medium">
                <tr>
                    <th class="px-4 py-3 text-center w-12">No</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3 text-center">Role</th>
                    <th class="px-4 py-3 text-center">MFA</th>
                    <th class="px-4 py-3 text-center">Aktif</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $i => $user)
                    <tr class="hover:bg-gray-50 {{ $user->is_active ? '' : 'bg-gray-50/50 opacity-70' }}">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex flex-wrap justify-center gap-1">
                                @forelse ($user->roles ?? [] as $r)
                                    @php $enum = \App\Enums\UserRoleEnum::tryFrom($r); @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold border {{ $enum?->badgeClass() ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                        {{ $enum?->label() ?? $r }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($user->hasMfaEnabled())
                                <div class="flex flex-col items-center gap-1">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f6ffed] text-[#52c41a] border border-[#b7eb8f]">
                                        ● Aktif
                                    </span>
                                    <button wire:click="disableMfa('{{ $user->id }}')" wire:confirm="Nonaktifkan MFA untuk {{ $user->name }}?"
                                        class="text-[10px] text-[#ff4d4f] hover:text-[#cf1322] font-medium">Nonaktifkan</button>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                                    ○ Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleActive('{{ $user->id }}')"
                                class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors {{ $user->is_active ? 'bg-[#52c41a]' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $user->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-center">
                                <x-action-menu>
                                    <x-action-menu.item icon="edit" variant="primary" wire:click="startEdit('{{ $user->id }}')">Edit</x-action-menu.item>
                                    <x-action-menu.item icon="key" variant="warning" wire:click="startResetPassword('{{ $user->id }}')">Reset Password</x-action-menu.item>
                                    <x-action-menu.divider />
                                    <x-action-menu.item icon="delete" variant="danger" wire:click="delete('{{ $user->id }}')" wire:confirm="Hapus user {{ $user->name }}?">Hapus</x-action-menu.item>
                                </x-action-menu>
                            </div>
                        </td>
                    </tr>

                    @if ($editingId === $user->id)
                        <tr class="bg-[#fffbe6] border-y border-[#ffe58f]">
                            <td colspan="7" class="px-6 py-5">
                                <form wire:submit="update" class="flex flex-col gap-4">
                                    <p class="text-sm font-semibold text-[#d48806] border-b border-[#ffe58f] pb-2">Edit: {{ $user->name }}</p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-semibold text-gray-500 uppercase">Nama</label>
                                            <input type="text" wire:model="editName" class="border border-[#ffe58f] rounded-md px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#faad14]">
                                            @error('editName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-semibold text-gray-500 uppercase">Role <span class="normal-case font-normal text-gray-400">(boleh lebih dari satu)</span></label>
                                            <div class="flex flex-col gap-1.5">
                                                @foreach ($roleOptions as $r)
                                                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                                        <input type="checkbox" wire:model="editRoles" value="{{ $r->value }}" class="rounded border-[#ffe58f] text-[#1677ff] focus:ring-[#faad14]">
                                                        {{ $r->label() }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('editRoles') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-semibold text-gray-500 uppercase">Status Aktif</label>
                                            <button type="button" wire:click="$toggle('editActive')"
                                                class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors mt-1 {{ $editActive ? 'bg-[#52c41a]' : 'bg-gray-300' }}">
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $editActive ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" wire:click="cancelEdit" class="px-5 py-1.5 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
                                        <button type="submit" class="bg-[#faad14] hover:bg-[#e69b0d] text-white px-6 py-1.5 rounded-md font-medium text-sm shadow-sm">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif

                    @if ($resettingId === $user->id)
                        <tr class="bg-[#fff7e6] border-y border-[#ffd591]">
                            <td colspan="7" class="px-6 py-5">
                                <form wire:submit="saveResetPassword" class="flex flex-col gap-4">
                                    <p class="text-sm font-semibold text-[#d46b08] border-b border-[#ffd591] pb-2">Reset Password: {{ $user->name }}</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-2xl">
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-semibold text-gray-500 uppercase">Password Baru</label>
                                            <input type="password" wire:model="resetPassword" placeholder="Minimal 6 karakter"
                                                class="border border-[#ffd591] rounded-md px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#fa8c16]">
                                            @error('resetPassword') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-semibold text-gray-500 uppercase">Konfirmasi Password</label>
                                            <input type="password" wire:model="resetPasswordConfirmation" placeholder="Ulangi password baru"
                                                class="border border-[#ffd591] rounded-md px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#fa8c16]">
                                            @error('resetPasswordConfirmation') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" wire:click="cancelResetPassword" class="px-5 py-1.5 rounded-md text-sm text-gray-600 bg-white border border-gray-300 hover:bg-gray-50">Batal</button>
                                        <button type="submit" class="bg-[#fa8c16] hover:bg-[#d46b08] text-white px-6 py-1.5 rounded-md font-medium text-sm shadow-sm">Simpan Password Baru</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">{{ $search !== '' ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Tidak ada data pengguna.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
