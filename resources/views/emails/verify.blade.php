<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7ff; margin: 0; padding: 40px; }
        .container { background-color: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(124, 111, 255, 0.1); max-width: 500px; margin: auto; }
        h2 { color: #1a1a3a; margin-top: 0; }
        .otp-box { background-color: #f0edff; padding: 20px; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #7c6fff; text-align: center; border-radius: 12px; margin: 20px 0; border: 2px dashed rgba(124, 111, 255, 0.4); }
        p { color: #4a4a68; line-height: 1.6; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #a0a0c0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi Email Kamu ☁️</h2>
        <p>Halo, <strong>{{ $name }}</strong>!</p>
        <p>Terima kasih telah mendaftar di CloudFile Manager. Untuk melindungi akunmu, silakan masukkan kode verifikasi 6-digit berikut ini:</p>
        
        <div class="otp-box">{{ $otp }}</div>
        
        <p>Kode ini hanya berlaku selama 15 menit. Jika kamu tidak merasa mendaftar di layanan ini, kamu dapat mengabaikan email ini.</p>
        
        <div class="footer">
            © {{ date('Y') }} CloudFile Manager - Distributed File Management System
        </div>
    </div>
</body>
</html>
