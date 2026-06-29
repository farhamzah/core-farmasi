<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Reset Password Core Farmasi UBP</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
    <h1 style="font-size: 22px;">Reset Password Core Farmasi UBP</h1>

    <p>Halo {{ $user->name }},</p>

    <p>Kami menerima permintaan reset password untuk akun Core Farmasi UBP Anda.</p>

    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: bold;">
            Reset Password
        </a>
    </p>

    <p>Link ini berlaku selama {{ $expiresInMinutes }} menit. Jika Anda tidak meminta reset password, abaikan email ini.</p>

    <p style="font-size: 13px; color: #475569;">Password tidak pernah dikirim melalui email dan hanya disimpan dalam bentuk hash.</p>
</body>
</html>
