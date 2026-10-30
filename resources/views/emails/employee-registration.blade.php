<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Akun PresensiPro</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 40px 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #10b981, #0d9488); padding: 32px; text-align: center; color: #ffffff;">
            <div style="display: inline-block; width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 14px; line-height: 48px; font-size: 24px; font-weight: bold; margin-bottom: 12px;">P</div>
            <h1 style="margin: 0; font-size: 24px; font-weight: 900; letter-spacing: -0.5px;">Presensi<span style="color: #a7f3d0;">Pro</span></h1>
            <p style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.9; text-transform: uppercase; letter-spacing: 1px;">PT Follow Me</p>
        </div>

        <!-- Body Content -->
        <div style="padding: 40px 32px;">
            <p style="margin-top: 0; font-size: 16px; font-weight: bold; color: #1e293b;">Halo, {{ $employee->name }} 👋</p>
            <p style="font-size: 14px; color: #475569; line-height: 1.6;">
                Akun portal absensi resmi Anda telah berhasil dibuat oleh tim HRD. Anda sekarang dapat mulai mencatat kehadiran, melihat rekap, dan mengelola aktivitas kerja harian.
            </p>

            <!-- Credentials Box -->
            <div style="background: #f1f5f9; border-radius: 16px; padding: 24px; margin: 28px 0; border: 1px solid #e2e8f0;">
                <p style="margin: 0 0 16px 0; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #64748b;">Informasi Akses Akun Anda</p>
                
                <div style="margin-bottom: 14px;">
                    <span style="display: block; font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase;">Email Login</span>
                    <span style="font-size: 15px; font-weight: bold; color: #0f172a;">{{ $employee->email }}</span>
                </div>

                <div>
                    <span style="display: block; font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase;">Password Sementara</span>
                    <span style="font-size: 18px; font-weight: 900; color: #059669; font-family: monospace; letter-spacing: 1px; background: #ffffff; padding: 6px 12px; border-radius: 8px; display: inline-block; border: 1px dashed #cbd5e1; margin-top: 4px;">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <!-- Action Button -->
            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ route('login') }}" style="background: linear-gradient(135deg, #10b981, #0d9488); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-size: 15px; font-weight: bold; display: inline-block; box-shadow: 0 10px 20px rgba(16,185,129,0.2);">Masuk ke Portal Absensi</a>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 32px 0;">

            <p style="font-size: 12px; color: #94a3b8; line-height: 1.5; margin: 0;">
                <strong>Catatan Keamanan:</strong> Demi keamanan akun Anda, harap segera login menggunakan password sementara di atas lalu ubah password melalui menu Profil Anda. Jangan pernah membagikan informasi akses ini kepada siapa pun.
            </p>
        </div>

        <!-- Footer -->
        <div style="background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">© {{ date('Y') }} PT Follow Me · Sistem Kehadiran Digital</p>
        </div>
    </div>
</body>
</html>
