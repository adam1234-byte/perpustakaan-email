<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('nik', 16)->nullable()->unique();
            $t->date('tanggal_lahir')->nullable();
            $t->text('alamat')->nullable();
            $t->string('no_hp', 20)->nullable();
            $t->string('foto_ktp')->nullable();
            $t->longText('face_descriptor')->nullable();
            $t->string('otp_code')->nullable();
            $t->timestamp('otp_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['nik', 'tanggal_lahir', 'alamat', 'no_hp', 'foto_ktp', 'face_descriptor', 'otp_code', 'otp_expires_at']);
        });
    }
};
