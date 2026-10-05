@extends('layout')
@section('title', 'Login Member')

@section('content')
<div class="container py-5" style="max-width: 480px;">
    <div class="card shadow border-0">
        <div class="card-header text-white text-center fs-5 fw-semibold" style="background:#0d6efd;">Login Member</div>
        <div class="card-body">
            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Login</button>
            </form>
            <div class="mt-3 text-center">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></div>
        </div>
    </div>
</div>
@endsection
