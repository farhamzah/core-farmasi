<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Permohonan Akun Core Farmasi Disetujui</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
    <h1 style="font-size: 22px;">Permohonan Akun Disetujui</h1>

    <p>Halo {{ $user->name }},</p>

    <p>Permohonan akun Core Farmasi UBP Anda sudah disetujui oleh Admin Core.</p>
    <p>Untuk keamanan, password tidak dikirim melalui email. Silakan buat password Core Anda sendiri melalui tombol verifikasi di bawah ini.</p>

    <table style="border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 6px 12px 6px 0; color: #475569;">Email</td>
            <td style="padding: 6px 0; font-weight: bold;">{{ $user->email }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 12px 6px 0; color: #475569;">Username</td>
            <td style="padding: 6px 0; font-weight: bold;">{{ $user->username ?: $user->email }}</td>
        </tr>
        @if ($appAccess)
            <tr>
                <td style="padding: 6px 12px 6px 0; color: #475569;">Akses Aplikasi</td>
                <td style="padding: 6px 0; font-weight: bold;">{{ $appAccess->app_code }} / {{ $appAccess->role_slug }}</td>
            </tr>
        @endif
    </table>

    @if ($passwordSetupUrl)
        <p>
            <a href="{{ $passwordSetupUrl }}" style="display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: bold;">
                Buat Password Core
            </a>
        </p>

        <p>Link pembuatan password berlaku selama {{ $passwordSetupExpiresInMinutes }} menit. Jika link kedaluwarsa, gunakan menu Lupa Password di Profile Portal untuk meminta link baru.</p>
    @else
        <p>
            <a href="{{ route('profile.password.request') }}" style="display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: bold;">
                Lupa Password / Buat Password Baru
            </a>
        </p>
    @endif

    <p>
        <a href="{{ route('profile.login') }}" style="display: inline-block; padding: 10px 14px; background: #eff6ff; color: #1d4ed8; text-decoration: none; border-radius: 10px; font-weight: bold;">
            Masuk ke Profile Portal
        </a>
    </p>

    <div style="margin: 20px 0; padding: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;">
        <p style="margin: 0 0 8px; font-weight: bold;">Panduan setelah akun disetujui</p>
        <ol style="margin: 0; padding-left: 20px;">
            <li>Klik tombol <strong>Buat Password Core</strong>. Link berlaku selama {{ $passwordSetupExpiresInMinutes }} menit.</li>
            <li>Buat password baru yang aman dan simpan sendiri. Jangan membagikan password kepada siapa pun.</li>
            <li>Masuk ke <strong>Profile Portal</strong> memakai email/username di atas dan password Core yang baru dibuat.</li>
            <li>Lengkapi profil terlebih dahulu, termasuk foto, nomor telepon, alamat, dan data pendukung yang tersedia.</li>
            <li>Setelah profil siap, buka aplikasi Farmasi yang sudah diberi akses seperti KP, TA, TU, Lab, atau aplikasi lain yang terhubung.</li>
            <li>Jika email tidak terlihat, cek folder Inbox, Spam, Promotions, atau Updates, lalu tandai sebagai bukan spam jika diperlukan.</li>
        </ol>
    </div>

    @unless ($appAccess)
        <p>Akses aplikasi seperti KP, TA, TU, atau Lab dapat diberikan terpisah oleh Admin Core sesuai kebutuhan.</p>
    @endunless

    <p style="font-size: 13px; color: #475569;">Email ini tidak memuat password mentah. Jika link kedaluwarsa, gunakan menu Lupa Password di Profile Portal untuk meminta link baru.</p>
</body>
</html>
