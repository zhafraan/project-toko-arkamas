@extends('layouts.app')
@section('title', 'Akun Pengguna')
@section('page-title', 'Akun Pengguna')
@section('page-sub', 'Kelola hak akses dan akun pengguna sistem')

@section('topbar-actions')
    <button type="button" class="btn btn-primary" data-modal-open="addUserModal">
        <x-icon name="user-plus" /> Tambah Pengguna
    </button>
@endsection

@section('content')
@php $reopen = old('_modal'); @endphp

<div class="card">
    <div class="table-wrap">
        <table class="table table-stack">
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Bergabung</th><th></th></tr></thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td data-label="Nama">
                        <div class="row">
                            <div class="avatar" style="width:34px;height:34px;">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <div>
                                <div class="cell-title">{{ $user->name }}</div>
                                @if($user->id === auth()->id())<span class="badge badge-info" style="height:18px;font-size:11px;">Anda</span>@endif
                            </div>
                        </div>
                    </td>
                    <td data-label="Email">{{ $user->email }}</td>
                    <td data-label="Peran">
                        @if($user->role === 'owner')
                            <span class="badge badge-warning">Pemilik</span>
                        @else
                            <span class="badge badge-info">Kasir</span>
                        @endif
                    </td>
                    <td class="muted" data-label="Bergabung">{{ $user->created_at->isoFormat('D MMM Y') }}</td>
                    <td data-label="Aksi">
                        <div class="actions">
                            <button type="button" class="btn btn-ghost btn-xs btn-icon" title="Edit pengguna" aria-label="Edit pengguna"
                                    data-modal-open="editUserModal"
                                    data-action="{{ route('users.update', $user) }}"
                                    data-name="{{ $user->name }}" data-email="{{ $user->email }}" data-role="{{ $user->role }}">
                                <x-icon name="pencil" size="15" />
                            </button>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="Hapus akun {{ $user->name }}?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-xs btn-icon text-danger" title="Hapus akun" aria-label="Hapus akun"><x-icon name="trash" size="15" /></button>
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

{{-- Tambah pengguna --}}
<div class="modal-overlay {{ $reopen === 'addUserModal' ? 'active' : '' }}" id="addUserModal">
    <div class="modal sm">
        <div class="modal-head">
            <div class="modal-title">Tambah pengguna</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="{{ route('users.store') }}" class="modal-body">
            @csrf
            <input type="hidden" name="_modal" value="addUserModal">
            <div class="field">
                <label class="label">Nama lengkap <span class="req">*</span></label>
                <input type="text" name="name" class="input" value="{{ $reopen === 'addUserModal' ? old('name') : '' }}" required>
            </div>
            <div class="field">
                <label class="label">Email <span class="req">*</span></label>
                <input type="email" name="email" class="input" value="{{ $reopen === 'addUserModal' ? old('email') : '' }}" required>
            </div>
            <div class="field">
                <label class="label">Peran</label>
                <select name="role" class="select">
                    <option value="kasir">Kasir</option>
                    <option value="owner" {{ $reopen === 'addUserModal' && old('role') === 'owner' ? 'selected' : '' }}>Pemilik</option>
                </select>
            </div>
            <div class="field">
                <label class="label">Kata sandi <span class="req">*</span></label>
                <input type="password" name="password" class="input" minlength="6" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label class="label">Konfirmasi kata sandi <span class="req">*</span></label>
                <input type="password" name="password_confirmation" class="input" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan</button>
        </form>
    </div>
</div>

{{-- Edit pengguna --}}
<div class="modal-overlay" id="editUserModal">
    <div class="modal sm">
        <div class="modal-head">
            <div class="modal-title">Edit pengguna</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm" data-modal-close aria-label="Tutup"><x-icon name="x" /></button>
        </div>
        <form method="POST" action="" class="modal-body">
            @csrf @method('PUT')
            <input type="hidden" name="_modal" value="editUserModal">
            <div class="field">
                <label class="label">Nama lengkap <span class="req">*</span></label>
                <input type="text" name="name" class="input" required>
            </div>
            <div class="field">
                <label class="label">Email <span class="req">*</span></label>
                <input type="email" name="email" class="input" required>
            </div>
            <div class="field">
                <label class="label">Peran</label>
                <select name="role" class="select">
                    <option value="kasir">Kasir</option>
                    <option value="owner">Pemilik</option>
                </select>
            </div>
            <div class="field">
                <label class="label">Kata sandi baru</label>
                <input type="password" name="password" class="input" minlength="6" autocomplete="new-password">
                <div class="hint">Kosongkan jika kata sandi tidak diubah.</div>
            </div>
            <div class="field">
                <label class="label">Konfirmasi kata sandi baru</label>
                <input type="password" name="password_confirmation" class="input" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><x-icon name="check" /> Simpan perubahan</button>
        </form>
    </div>
</div>
@endsection
