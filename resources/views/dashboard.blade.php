@extends('layout')
@section('title', 'Dashboard')

@section('content')
<div class="container py-5" style="max-width: 640px;">
    <div class="card shadow border-0">
        <div class="card-body">
            <h4>Selamat datang, {{ auth()->user()->name }} 👋</h4>
            <p class="text-muted mb-1">Email: {{ auth()->user()->email }}</p>
            <p class="text-muted">NIK: {{ auth()->user()->nik }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-danger">Logout</button>
            </form>
        </div>
    </div>
</div>
@endsection
