@extends('layouts.app')
@section('title', 'Pelanggan')
@section('page-title', 'Data Pelanggan')

@section('topbar-actions')
<button onclick="document.getElementById('addCustomerModal').classList.add('active')" class="btn-topbar-action">
    <i class="fa-solid fa-user-plus"></i> Tambah Pelanggan
</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-users" style="color:var(--primary-light);margin-right:6px;"></i> Daftar Pelanggan</span>
    </div>
    <div class="card-body" style="padding:14px;">
        <form method="GET" style="display:flex;gap:10px;">
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="🔍 Nama / No. HP / NIK" style="max-width:300px;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('customers.index') }}" class="btn btn-outline">Reset</a>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Nama</th><th>NIK</th><th>No. HP</th><th>Tipe</th>
                    <th>Alamat</th><th>Total Transaksi</th><th>Piutang</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>
                        <a href="{{ route('customers.show', $customer) }}" style="font-weight:700;color:var(--primary-light);text-decoration:none;">
                            {{ $customer->name }}
                        </a>
                    </td>
                    <td style="font-size:12px;font-family:monospace;">{{ $customer->nik ?? '-' }}</td>
                    <td>{{ $customer->phone ?? '-' }}</td>
                    <td>
                        @php $types = ['rumah_tangga'=>['Rmh Tangga','badge-info'],'umkm'=>['UMKM','badge-warning'],'pengecer'=>['Pengecer','badge-orange']]; @endphp
                        <span class="badge {{ $types[$customer->customer_type][1] }}">{{ $types[$customer->customer_type][0] }}</span>
                    </td>
                    <td style="font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $customer->address ?? '-' }}</td>
                    <td style="font-weight:700;">{{ $customer->transactions_count }}</td>
                    <td style="{{ ($customer->total_debt ?? 0) > 0 ? 'color:var(--danger);font-weight:700;' : 'color:var(--text-muted);' }}">
                        Rp {{ number_format($customer->total_debt ?? 0, 0, ',', '.') }}
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline btn-xs">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <button onclick='openEditCustomer(@json($customer))' class="btn btn-outline btn-xs">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada data pelanggan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:0 16px;">{{ $customers->links('vendor.pagination.simple') }}</div>
</div>

{{-- Add Customer Modal --}}
<div class="modal-overlay" id="addCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus"></i> Tambah Pelanggan</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Nama *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label class="form-label">NIK (opsional)</label>
                    <input type="text" name="nik" class="form-control" maxlength="16">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Pelanggan</label>
                    <select name="customer_type" class="form-control">
                        <option value="rumah_tangga">Rumah Tangga</option>
                        <option value="umkm">UMKM</option>
                        <option value="pengecer">Pengecer</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>

{{-- Edit Customer Modal --}}
<div class="modal-overlay" id="editCustomerModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen"></i> Edit Pelanggan</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editCustomerForm" method="POST">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Nama *</label>
                    <input type="text" name="name" id="editCustName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="phone" id="editCustPhone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">NIK</label>
                    <input type="text" name="nik" id="editCustNik" class="form-control" maxlength="16">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Pelanggan</label>
                    <select name="customer_type" id="editCustType" class="form-control">
                        <option value="rumah_tangga">Rumah Tangga</option>
                        <option value="umkm">UMKM</option>
                        <option value="pengecer">Pengecer</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea name="address" id="editCustAddress" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-save"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditCustomer(c) {
    document.getElementById('editCustomerForm').action = '/customers/' + c.id;
    document.getElementById('editCustName').value    = c.name;
    document.getElementById('editCustPhone').value   = c.phone ?? '';
    document.getElementById('editCustNik').value     = c.nik ?? '';
    document.getElementById('editCustType').value    = c.customer_type;
    document.getElementById('editCustAddress').value = c.address ?? '';
    document.getElementById('editCustomerModal').classList.add('active');
}
</script>
@endpush
@endsection
