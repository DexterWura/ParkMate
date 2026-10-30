@extends('admin.layout')

@section('title', 'Users')
@section('subtitle', 'Manage app and admin accounts')

@section('content')
<div class="ol-toolbar toolbar">
    <form method="GET">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search name or email" aria-label="Search users">
        <button type="submit" class="secondary">Search</button>
    </form>
</div>

<div class="ol-card card" style="margin-bottom:16px;">
    <h3>Create user</h3>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="ol-grid grid">
            <div class="ol-field field"><label>Name</label><input name="name" required></div>
            <div class="ol-field field"><label>Email</label><input type="email" name="email" required></div>
            <div class="ol-field field"><label>Role</label>
                <select name="role"><option value="user">user</option><option value="admin">admin</option></select>
            </div>
            <div class="ol-field field"><label>Password</label><input type="password" name="password" required></div>
        </div>
        <button type="submit" class="primary">Create</button>
    </form>
</div>

<div class="ol-card card">
    <div class="ol-table-wrap">
        <table>
            <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role ?? 'user' }}</td>
                    <td>{{ ($user->is_active ?? true) ? 'yes' : 'no' }}</td>
                    <td class="ol-row-actions row-actions">
                        <a class="ol-btn btn secondary" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-ol-confirm="Delete this user?">
                            @csrf @method('DELETE')
                            <button type="submit" class="danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="ol-muted muted">No users yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:12px;">{{ $users->links() }}</div>
</div>
@endsection
