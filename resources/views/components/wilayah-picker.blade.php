@props(['provinsiList', 'kabupatenList', 'kecamatanList', 'desaList'])

{{-- Cascading Provinsi → Kabupaten → Kecamatan → Desa picker. Bound to the host
     Livewire component's wProvinsi/wKabupaten/wKecamatan + desa_id (via the
     WithWilayahPicker trait). Each select narrows the next. --}}
@php $sel = 'border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#1677ff]/20 focus:border-[#1677ff] disabled:bg-gray-50 disabled:text-gray-400'; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-medium text-gray-700">Provinsi</label>
        <select wire:model.live="wProvinsi" class="{{ $sel }}">
            <option value="">— Pilih provinsi —</option>
            @foreach ($provinsiList as $p)
                <option value="{{ $p->id }}">{{ $p->nama }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-medium text-gray-700">Kabupaten/Kota</label>
        <select wire:model.live="wKabupaten" @disabled($kabupatenList->isEmpty()) class="{{ $sel }}">
            <option value="">{{ $kabupatenList->isEmpty() ? 'Pilih provinsi dulu' : '— Pilih kabupaten/kota —' }}</option>
            @foreach ($kabupatenList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-medium text-gray-700">Kecamatan</label>
        <select wire:model.live="wKecamatan" @disabled($kecamatanList->isEmpty()) class="{{ $sel }}">
            <option value="">{{ $kecamatanList->isEmpty() ? 'Pilih kabupaten dulu' : '— Pilih kecamatan —' }}</option>
            @foreach ($kecamatanList as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col gap-1.5">
        <label class="text-sm font-medium text-gray-700">Desa/Kelurahan</label>
        <select wire:model="desa_id" @disabled($desaList->isEmpty()) class="{{ $sel }}">
            <option value="">{{ $desaList->isEmpty() ? 'Pilih kecamatan dulu' : '— Pilih desa/kelurahan —' }}</option>
            @foreach ($desaList as $d)
                <option value="{{ $d->id }}">{{ $d->nama }}</option>
            @endforeach
        </select>
        @error('desa_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
    </div>
</div>
