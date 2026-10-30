@extends('admin.layout')

@section('title', 'Users')
@section('subtitle', 'Manage app and admin accounts')

@section('content')
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; flex:1;">
        <input type="search" name="q" value="{{ $q }}" placeholder="Search name or email">
        <button type="submit" class="secondary">Search</button>
    </form>
</div>

<div class="card" style="margin-bottom:16px;">
    <h3>Create user</h3>
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="grid" style="grid-template-columns: repeat(auto-fit,minmax(160px,1fr));">
            <div class="field"><label>Name</label><input name="name" required></div>
            <div class="field"><label>Email</label><input type="email" name="email" required></div>
            <div class="field"><label>Role</label>
                <select name="role"><option value="user">user</option><option value="admin">admin</option></select>
            </div>
            <div class="field"><label>Password</label><input type="password" name="password" required></div>
        </div>
        <button type="submit">Create</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th></th></tr>
        </thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role ?? 'user' }}</td>
                <td>{{ ($user->is_active ?? true) ? 'yes' : 'no' }}</td>
                <td class="row-actions">
                    <a class="btn secondary" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:12px;">{{ $users->links() }}</div>
</div>
@endsection
