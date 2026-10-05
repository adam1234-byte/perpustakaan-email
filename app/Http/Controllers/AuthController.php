<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        // Hapus pendaftaran lama yang belum pernah diverifikasi OTP (email / NIK sama)
        User::whereNull('email_verified_at')
            ->where(function ($q) use ($request) {
                $q->where('email', $request->input('email'))->orWhere('nik', $request->input('nik'));
            })->delete();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email:rfc,dns', 'max:150', 'unique:users,email'],
            'password' => 'required|string|min:8',
            'nik' => 'required|digits:16|unique:users,nik',
            'tanggal_lahir' => 'required|date|before:today',
            'alamat' => 'required|string|max:500',
            'no_hp' => ['required', 'regex:/^[0-9+]{9,15}$/'],
            'foto_ktp' => 'required|image|max:4096',
            'face_descriptor' => 'required|json',
        ], [
            'email.email' => 'Email tidak terdaftar / domain email tidak valid.',
            'email.unique' => 'Email sudah terdaftar. Silakan login.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'password.min' => 'Password minimal 8 karakter.',
            'no_hp.regex' => 'Nomor HP tidak valid (9-15 digit).',
            'foto_ktp.required' => 'Foto KTP wajib diupload.',
            'foto_ktp.image' => 'File KTP harus berupa gambar.',
            'face_descriptor.required' => 'Wajah belum terdeteksi. Hadapkan wajah ke kamera.',
            'face_descriptor.json' => 'Data wajah tidak valid.',
        ]);

        $desc = json_decode($data['face_descriptor'], true);
        if (!is_array($desc) || count($desc) !== 128) {
            return back()->withInput()->with('error', 'Data wajah tidak valid. Coba ulangi verifikasi wajah.');
        }

        // Cek wajah duplikat pada akun yang sudah terverifikasi
        foreach (User::whereNotNull('face_descriptor')->whereNotNull('email_verified_at')->get() as $u) {
            $other = json_decode($u->face_descriptor, true);
            if (is_array($other) && count($other) === 128) {
                $sum = 0;
                for ($i = 0; $i < 128; $i++) {
                    $sum += ((float) $desc[$i] - (float) $other[$i]) ** 2;
                }
                if (sqrt($sum) < 0.45) {
                    return back()->withInput()->with('error', 'Wajah ini sudah terdaftar pada akun lain.');
                }
            }
        }

        $path = $request->file('foto_ktp')->store('ktp', 'public');
        $otp = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'nik' => $data['nik'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'alamat' => $data['alamat'],
            'no_hp' => $data['no_hp'],
            'foto_ktp' => $path,
            'face_descriptor' => json_encode($desc),
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            $this->sendOtp($user->email, $user->name, $otp);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            $user->delete();
            return back()->withInput()->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }

        session(['pending_email' => $user->email]);

        return redirect()->route('verify')->with('success', 'Kode verifikasi telah dikirim ke ' . $user->email);
    }

    public function showVerify()
    {
        if (!session('pending_email')) {
            return redirect()->route('register');
        }
        return view('verify', ['email' => session('pending_email')]);
    }

    public function verify(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6'], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 digit.',
        ]);

        $user = User::where('email', session('pending_email'))->first();
        if (!$user) {
            return redirect()->route('register')->with('error', 'Email tidak terdaftar. Silakan daftar ulang.');
        }
        if (!$user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return back()->with('error', 'Kode OTP sudah kadaluarsa. Klik "Kirim ulang kode".');
        }
        if (!Hash::check($request->otp, $user->otp_code)) {
            return back()->with('error', 'Kode OTP salah.');
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        session()->forget('pending_email');

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    public function resend()
    {
        $user = User::where('email', session('pending_email'))->first();
        if (!$user) {
            return redirect()->route('register')->with('error', 'Email tidak terdaftar. Silakan daftar ulang.');
        }

        $otp = (string) random_int(100000, 999999);
        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        try {
            $this->sendOtp($user->email, $user->name, $otp);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }

        return back()->with('success', 'Kode baru telah dikirim ke ' . $user->email);
    }

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $cred['email'])->first();
        if (!$user) {
            return back()->withInput()->with('error', 'Email tidak terdaftar.');
        }
        if (!$user->email_verified_at) {
            session(['pending_email' => $user->email]);
            return redirect()->route('verify')->with('error', 'Email belum diverifikasi. Masukkan kode OTP, atau kirim ulang kode.');
        }
        if (Auth::attempt($cred)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withInput()->with('error', 'Password salah.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function sendOtp(string $email, string $name, string $otp): void
    {
        $body = "Halo {$name},\n\n"
            . "Kode verifikasi pendaftaran member perpustakaan Anda adalah:\n\n"
            . "    {$otp}\n\n"
            . "Kode berlaku selama 10 menit. Jangan bagikan kode ini kepada siapa pun.\n";

        try {
            Mail::raw($body, function ($m) use ($email) {
                $m->to($email)->subject('Kode Verifikasi Pendaftaran Member');
            });
        } catch (\Throwable $e) {
            // email gagal: kode tetap ditampilkan di layar
        }
        session()->flash('otp_demo', $otp);
    }
}
