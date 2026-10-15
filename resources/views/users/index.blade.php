@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Akun Pengguna')

@section('topbar-actions')
<button onclick="document.getElementById('addUserModal').classList.add('active')" class="btn-topbar-action">
    <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-user-gear" style="color:var(--primary-light);margin-right:6px;"></i> Daftar Akun</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Role</th><th>Bergabung</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;background:{{ $user->role==='owner' ? 'var(--accent)' : 'var(--primary-light)' }};border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:700;">{{ $user->name }}</div>
                                @if($user->id === auth()->id())
                                <span style="font-size:10px;background:#dbeafe;color:#1e40af;padding:1px 7px;border-radius:10px;">Anda</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->role === 'owner')
                            <span class="badge badge-orange">Pemilik</span>
                        @else
                            <span class="badge badge-info">Kasir</span>
                        @endif
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);">{{ $user->created_at->isoFormat('D MMM Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button onclick='openEditUser(@json($user))' class="btn btn-outline btn-xs">
                                <i class="fa-solid fa-pen"></i> Edit
                            </button>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Add User Modal --}}
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus"></i> Tambah Pengguna</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role *</label>
                <select name="role" class="form-control">
                    <option value="kasir">Kasir</option>
                    <option value="owner">Pemilik</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password *</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-save"></i> Simpan
            </button>
        </form>
    </div>
</div>

{{-- Edit User Modal --}}
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen"></i> Edit Pengguna</div>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editUserForm" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="name" id="editUserName" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" id="editUserEmail" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Role *</label>
                <select name="role" id="editUserRole" class="form-control">
                    <option value="kasir">Kasir</option>
                    <option value="owner">Pemilik</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password Baru (kosongkan jika tidak diubah)</label>
                <input type="password" name="password" class="form-control" minlength="6">
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fa-solid fa-save"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditUser(user) {
    document.getElementById('editUserForm').action = '/users/' + user.id;
    document.getElementById('editUserName').value  = user.name;
    document.getElementById('editUserEmail').value = user.email;
    document.getElementById('editUserRole').value  = user.role;
    document.getElementById('editUserModal').classList.add('active');
}
</script>
@endpush
@endsection
