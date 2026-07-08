<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AutoChef - Verifikasi Email</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
    }
    .container {
      text-align: center;
      padding: 2rem;
      max-width: 400px;
    }
    .icon { font-size: 4rem; margin-bottom: 1rem; }
    h2 { font-size: 1.5rem; margin-bottom: 0.5rem; }
    p { font-size: 1rem; opacity: 0.9; margin-bottom: 1.5rem; }
    .btn {
      display: inline-block;
      padding: 12px 32px;
      background: rgba(255,255,255,0.2);
      border: 2px solid #fff;
      border-radius: 30px;
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
    }
    .btn:hover {
      background: #fff;
      color: #764ba2;
    }
  </style>
</head>
<body>
  <div class="container">
    @if(isset($status) && $status === 'success')
      <div class="icon">✅</div>
      <h2>Verifikasi Berhasil!</h2>
      <p>{{ $message ?? 'Email Anda telah berhasil diverifikasi.' }}</p>
    @elseif(isset($status) && $status === 'already_verified')
      <div class="icon">ℹ️</div>
      <h2>Sudah Terverifikasi</h2>
      <p>{{ $message ?? 'Email Anda sudah terverifikasi sebelumnya.' }}</p>
    @elseif(isset($status) && $status === 'error')
      <div class="icon">❌</div>
      <h2>Verifikasi Gagal</h2>
      <p>{{ $message ?? 'Terjadi kesalahan saat verifikasi.' }}</p>
    @else
      <div class="icon">📧</div>
      <h2>Membuka AutoChef...</h2>
      <p>Mengarahkan Anda ke aplikasi.</p>
    @endif

    <a href="autochef://email/verified" class="btn">Buka Aplikasi AutoChef</a>
  </div>

  <script>
    window.onload = function() {
      // Coba buka deep link ke aplikasi
      window.location = "autochef://email/verified";

      // Fallback jika aplikasi tidak terinstall
      setTimeout(function() {
        // Bisa diarahkan ke Play Store jika perlu
        // window.location = "https://play.google.com/store/apps/details?id=com.autochef.app";
      }, 3000);
    };
  </script>
</body>
</html>
