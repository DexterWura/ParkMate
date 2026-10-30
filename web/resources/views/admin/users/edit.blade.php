@extends('admin.layout')

@section('title', 'Edit user')
@section('subtitle', $user->email)

@section('content')
<div class="ol-card card" style="max-width:640px;">
    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf @method('PUT')
        <div class="ol-field field"><label>Name</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
        <div class="ol-field field"><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
        <div class="ol-field field"><label>Role</label>
            <select name="role">
                <option value="user" @selected(old('role', $user->role) === 'user')>user</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>admin</option>
            </select>
        </div>
        <div class="ol-field field">
            <label class="ol-check-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Active</label>
        </div>
        <div class="ol-field field"><label>New password (optional)</label><input type="password" name="password"></div>
        <div class="ol-row-actions row-actions">
            <button type="submit" class="primary">Save</button>
            <a class="ol-btn btn secondary" href="{{ route('admin.users.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
