@extends('layout')
@section('title', 'Registrasi Member Perpustakaan')

@section('content')
<div class="container py-5" style="max-width: 1120px;">
    <div class="card shadow border-0">
        <div class="card-header text-white text-center fs-4 fw-semibold" style="background:#0d6efd;">
            Pendaftaran Member Baru
        </div>
        <form id="regForm" method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="face_descriptor" id="face_descriptor">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 pe-md-4 border-end">
                        <h5 class="fw-semibold">1. Data Identitas</h5>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email &amp; Password (Untuk Login)</label>
                            <div class="input-group">
                                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Nomor Induk Kependudukan (NIK)</label>
                                <input type="text" name="nik" class="form-control" maxlength="16" inputmode="numeric" value="{{ old('nik') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" rows="3" required>{{ old('alamat') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. HP / WhatsApp</label>
                            <input type="text" name="no_hp" class="form-control" inputmode="tel" value="{{ old('no_hp') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Upload Foto KTP</label>
                            <input type="file" name="foto_ktp" class="form-control" accept="image/*" required>
                        </div>
                    </div>

                    <div class="col-md-6 ps-md-4 text-center">
                        <h5 class="fw-semibold">2. Verifikasi Wajah Biometrik</h5>
                        <p class="text-muted small">Pastikan wajah Anda terlihat jelas dan terang. Tunggu hingga kotak hijau muncul.</p>

                        <div class="mx-auto position-relative rounded overflow-hidden shadow-sm"
                             style="width:100%; max-width:312px; aspect-ratio:4/3; background:#8c8c8c;">
                            <video id="video" autoplay muted playsinline
                                   style="width:100%; height:100%; object-fit:cover; transform:scaleX(-1);"></video>
                            <canvas id="overlay"
                                    style="position:absolute; inset:0; width:100%; height:100%; transform:scaleX(-1);"></canvas>
                        </div>

                        <button type="button" id="statusBtn" class="btn btn-warning fw-semibold mt-3" disabled>Memuat model wajah...</button>

                        <hr class="my-4">

                        <button type="submit" id="submitBtn" class="btn btn-success btn-lg w-100" disabled>Selesaikan Pendaftaran</button>
                        <div class="mt-3">Sudah punya akun? <a href="{{ route('login') }}">Login</a></div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/dist/face-api.js"></script>
<script>
    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model/';
    const video = document.getElementById('video');
    const overlay = document.getElementById('overlay');
    const statusBtn = document.getElementById('statusBtn');
    const submitBtn = document.getElementById('submitBtn');
    const descInput = document.getElementById('face_descriptor');
    const form = document.getElementById('regForm');

    function setReady(ok) {
        submitBtn.disabled = !ok;
        if (ok) {
            statusBtn.className = 'btn btn-success fw-semibold mt-3';
            statusBtn.textContent = 'Wajah Terdeteksi ✓';
        } else {
            statusBtn.className = 'btn btn-warning fw-semibold mt-3';
            statusBtn.textContent = 'Mencari Wajah...';
        }
    }

    async function loop() {
        try {
            if (video.readyState >= 2 && video.videoWidth > 0) {
                const det = await faceapi
                    .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                overlay.width = video.videoWidth;
                overlay.height = video.videoHeight;
                const ctx = overlay.getContext('2d');
                ctx.clearRect(0, 0, overlay.width, overlay.height);

                if (det) {
                    const b = det.detection.box;
                    ctx.strokeStyle = '#00e676';
                    ctx.lineWidth = 4;
                    ctx.strokeRect(b.x, b.y, b.width, b.height);
                    descInput.value = JSON.stringify(Array.from(det.descriptor));
                    setReady(true);
                } else {
                    descInput.value = '';
                    setReady(false);
                }
            }
        } catch (e) { /* abaikan error frame */ }
        setTimeout(loop, 250);
    }

    async function init() {
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
            ]);
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480, facingMode: 'user' }, audio: false
            });
            video.srcObject = stream;
            await video.play();
            setReady(false);
            loop();
        } catch (e) {
            statusBtn.className = 'btn btn-danger fw-semibold mt-3';
            statusBtn.textContent = 'Kamera/model gagal: ' + e.message;
        }
    }

    form.addEventListener('submit', function (ev) {
        if (!descInput.value) {
            ev.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Wajah belum terdeteksi', text: 'Hadapkan wajah ke kamera sampai kotak hijau muncul.' });
        }
    });

    init();
</script>
@endpush
