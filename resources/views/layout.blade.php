<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Perpustakaan')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')) });
        @endif
        @if (session('otp_demo'))
            Swal.fire({ icon: 'info', title: 'Kode OTP Anda', html: '<h1 style="letter-spacing:8px">' + @json(session('otp_demo')) + '</h1>' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')) });
        @endif
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Periksa kembali',
                html: @json(collect($errors->all())->map(fn ($e) => e($e))->implode('<br>'))
            });
        @endif
    </script>
    @stack('scripts')
</body>
</html>
