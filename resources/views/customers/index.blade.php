@extends('layouts.app')
@section('title', 'Pelanggan')
@section('page-title', 'Pelanggan')
@section('page-sub', 'Data pelanggan, piutang, dan riwayat belanja')

@section('topbar-actions')
    <button type="button" class="btn btn-primary" data-modal-open="addCustomerModal">
        <x-icon name="user-plus" /> Tambah Pelanggan
    </button>
@endsection

@section('content')
@php $reopen = old('_modal'); @endphp

<div class="card">
    <form method="GET" class="toolbar">
        <div class="search">
            <x-icon name="search" size="17" />
            <input type="search" name="search" class="input" value="{{ request('search') }}" placeholder="Cari nama, nomor telepon, atau NIK">
        </div>
        <button type="submit" class="btn btn-outline">Cari</button>
        @if(request('search'))
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">Reset</a>
        @endif
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th><th>Nomor telepon</th><th>Tipe</th><th>Alamat</th>
                    <th class="num">Transaksi</th><th class="num">Piutang</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}" class="cell-title">{{ $customer->name }}</a>
                        @if($customer->nik)<div class="cell-sub mono">{{ $customer->nik }}</div>@endif
                    </td>
                    <td>{{ $customer->phone ?? '-' }}</td>
                    <td>
                        @if($customer->customer_type === 'umkm')
                            <span class="badge badge-warning">UMKM</span>
                        @elseif($customer->customer_type === 'pengecer')
                            <span class="badge badge-info">Pengecer</span>
                        @else
                            <span class="badge">Rumah tangga</span>
                        @endif
                    </td>
                    <td class="truncate" style="max-width:220px;" title="{{ $customer->address }}">{{ $customer->address ?? '-' }}</td>
                    <td class="num">{{ $customer->transactions_count }}</td>
                    <td class="num {{ ($customer->total_debt ?? 0) > 0 ? 'text-danger fw' : 'muted' }}">@rupiah($customer->total_debt ?? 0)</td>
                    <td>
                        <div class="actions">
                            @if(auth()->user()->isOwner())
                            <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm="Hapus pelanggan {{ $customer->name }}? Riwayat pembeliannya tetap tersimpan. Penghapusan tercatat di riwayat penghapusan.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus pelanggan" aria-label="Hapus pelanggan"><x-icon name="trash" size="15" /></button>
                            </form>
                            @endif
                            <button type="button" class="btn btn-ghost btn-xs btn-icon" title="Edit pelanggan" aria-label="Edit pelanggan"
                                    data-modal-open="editCustomerModal"
                                    data-action="{{ route('customers.update', $customer) }}"
                                    data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}" data-nik="{{ $customer->nik }}"
                                    data-customer-type="{{ $customer->customer_type }}" data-address="{{ $customer->address }}">
                                <x-icon name="pencil" size="15" />
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="empty">
                        <div class="empty-icon"><x-icon name="users" size="24" /></div>
                        <div class="empty-title">{{ request('search') ? 'Pelanggan tidak ditemukan' : 'Belum ada pelanggan' }}</div>
                        <div>{{ request('search') ? 'Coba kata kunci lain.' : 'Tambahkan pelanggan pertama Anda.' }}</div>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $customers->links('vendor.pagination.simple') }}
</div>

{{-- Modal tambah --}}
<div class="modal-overlay {{ $reopen === 'addCustomerModal' ? 'active' : '' }}" id="addCustomerModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Tambah pelanggan</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="{{ route('customers.store') }}" class="modal-body">
            @csrf
            <input type="hidden" name="_modal" value="addCustomerModal">
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label">Nama <span class="req">*</span></label>
                    <input type="text" name="name" class="input" value="{{ $reopen === 'addCustomerModal' ? old('name') : '' }}" required>
                </div>
                <div class="field">
                    <label class="label">Nomor telepon</label>
                    <input type="text" name="phone" class="input" placeholder="08xxxxxxxxxx" value="{{ $reopen === 'addCustomerModal' ? old('phone') : '' }}">
                </div>
                <div class="field">
                    <label class="label">Tipe pelanggan</label>
                    <select name="customer_type" class="select">
                        <option value="rumah_tangga">Rumah tangga</option>
                        <option value="umkm" {{ $reopen === 'addCustomerModal' && old('customer_type') === 'umkm' ? 'selected' : '' }}>UMKM</option>
                        <option value="pengecer" {{ $reopen === 'addCustomerModal' && old('customer_type') === 'pengecer' ? 'selected' : '' }}>Pengecer</option>
                    </select>
                </div>
                <div class="field span-all">
                    <label class="label">NIK (opsional)</label>
                    <input type="text" name="nik" class="input" maxlength="16" inputmode="numeric" value="{{ $reopen === 'addCustomerModal' ? old('nik') : '' }}">
                </div>
                <div class="field span-all">
                    <label class="label">Alamat</label>
                    <textarea name="address" class="textarea" rows="2">{{ $reopen === 'addCustomerModal' ? old('address') : '' }}</textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan</button>
        </form>
    </div>
</div>

{{-- Modal edit (diisi lewat data-* tombol edit) --}}
<div class="modal-overlay" id="editCustomerModal">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-title">Edit pelanggan</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="" class="modal-body">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="editCustomerModal">
            <div class="form-grid">
                <div class="field span-all">
                    <label class="label">Nama <span class="req">*</span></label>
                    <input type="text" name="name" class="input" required>
                </div>
                <div class="field">
                    <label class="label">Nomor telepon</label>
                    <input type="text" name="phone" class="input">
                </div>
                <div class="field">
                    <label class="label">Tipe pelanggan</label>
                    <select name="customer_type" class="select">
                        <option value="rumah_tangga">Rumah tangga</option>
                        <option value="umkm">UMKM</option>
                        <option value="pengecer">Pengecer</option>
                    </select>
                </div>
                <div class="field span-all">
                    <label class="label">NIK</label>
                    <input type="text" name="nik" class="input" maxlength="16" inputmode="numeric">
                </div>
                <div class="field span-all">
                    <label class="label">Alamat</label>
                    <textarea name="address" class="textarea" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan perubahan</button>
        </form>
    </div>
</div>
@endsection
