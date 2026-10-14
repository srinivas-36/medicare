@extends('layouts.dashboard')

@php
    $role = $role ?? '';
    $status = $status ?? '';
@endphp

@section('title', 'Users')

@section('content')
    <x-ui.page-header title="Users" description="Search accounts and activate or deactivate access. You cannot deactivate your own account." />

    @if (session('status'))
        <p class="alert-success">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert-error">{{ $errors->first() }}</p>
    @endif

    <form method="GET" class="mb-4 grid gap-2 rounded-xl border border-line bg-white p-3 shadow-card sm:grid-cols-2 lg:grid-cols-[minmax(0,1.4fr)_160px_160px_auto]">
        <div>
            <label for="user-search" class="sr-only">Search users</label>
            <input id="user-search" type="search" name="q" value="{{ $search }}" placeholder="Name or email" class="field">
        </div>
        <div>
            <label for="user-role" class="sr-only">Role</label>
            <select id="user-role" name="role" class="field">
                <option value="">All roles</option>
                @foreach (['patient' => 'Patient', 'doctor' => 'Doctor', 'receptionist' => 'Receptionist', 'admin' => 'Admin'] as $value => $label)
                    <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="user-status" class="sr-only">Account status</label>
            <select id="user-status" name="status" class="field">
                <option value="">Any access</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
        </div>
        <button class="btn-primary" type="submit">Search</button>
    </form>

    <section class="panel">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Access</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$user->name" :photo="$user->photo" size="sm" />
                                    <span class="font-medium text-heading">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="text-body">{{ $user->email }}</td>
                            <td><span class="badge-info">{{ ucfirst($user->role) }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" @if ($user->is_active) data-confirm="They will not be able to sign in until the account is activated again." data-confirm-title="Deactivate this account?" data-confirm-ok="Deactivate" data-confirm-tone="danger" @endif>
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                    <button type="submit" class="text-sm font-semibold {{ $user->is_active ? 'text-danger' : 'text-primary' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-sm text-body">No users match that search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
