<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Yalıhan Giriş Bağlantısı</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="color: #1e3a8a;">Yalıhan Mülk Sahibi Paneli</h2>
    </div>

    <p>Merhaba {{ $user->name }},</p>
    
    <p>Mülk Sahibi Paneline giriş yapmak için aşağıdaki butona tıklayın. Bu bağlantı 15 dakika boyunca geçerlidir.</p>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('owner.auth.verify', ['token' => $token]) }}" 
           style="background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
            Panele Giriş Yap
        </a>
    </div>

    <p style="font-size: 14px; color: #666;">
        Eğer butona tıklayamıyorsanız, aşağıdaki bağlantıyı tarayıcınıza kopyalayabilirsiniz:<br>
        <span style="word-break: break-all; color: #2563eb;">{{ route('owner.auth.verify', ['token' => $token]) }}</span>
    </p>

    <hr style="border: 1px solid #eee; margin: 30px 0;">
    
    <p style="font-size: 12px; color: #999; text-align: center;">
        Bu işlemi siz talep etmediyseniz, e-postayı dikkate almayınız.<br>
        &copy; {{ date('Y') }} Yalıhan Emlak
    </p>

</body>
</html>
