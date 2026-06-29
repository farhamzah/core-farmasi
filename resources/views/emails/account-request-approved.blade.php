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

    <p>Setelah password dibuat, masuk ke Profile Portal untuk melengkapi profil. Password Core ini juga dipakai oleh aplikasi Farmasi yang sudah terhubung dan sudah diberi akses.</p>

    @unless ($appAccess)
        <p>Akses aplikasi seperti KP, TA, TU, atau Lab dapat diberikan terpisah oleh Admin Core sesuai kebutuhan.</p>
    @endunless

    <p style="font-size: 13px; color: #475569;">Jika email berikutnya tidak terlihat di Inbox, cek folder Spam, Promotions, atau Updates. Email ini tidak memuat password. Jangan membagikan password kepada siapa pun.</p>
</body>
</html>
