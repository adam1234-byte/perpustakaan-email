@extends('layout')
@section('title', 'Verifikasi Email')

@section('content')
<div class="container py-5" style="max-width: 480px;">
    <div class="card shadow border-0">
        <div class="card-header text-white text-center fs-5 fw-semibold" style="background:#0d6efd;">Verifikasi Email</div>
        <div class="card-body">
            <p>Kami mengirim kode 6 digit ke <strong>{{ $email }}</strong>. Cek inbox (atau folder Spam) Gmail Anda.</p>
            <form method="POST" action="{{ route('verify.check') }}">
                @csrf
                <input type="text" name="otp" class="form-control form-control-lg text-center mb-3"
                       maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="••••••" autofocus required>
                <button class="btn btn-success w-100">Verifikasi</button>
            </form>
            <form method="POST" action="{{ route('verify.resend') }}" class="mt-3 text-center">
                @csrf
                <button class="btn btn-link">Kirim ulang kode</button>
            </form>
        </div>
    </div>
</div>
@endsection
