<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open AutoChef</title>
  <script>
    window.onload = function() {
      const appLink = "autochef://email/verify";
      const playStoreLink = "https://play.google.com/store/apps/details?id=com.autochef.app";

      // Coba buka app
      window.location = appLink;

      // Jika gagal dalam 2 detik, buka Play Store
      setTimeout(() => {
        window.location = playStoreLink;
      }, 2000);
    };
  </script>
</head>
<body>
  <h3>Opening AutoChef...</h3>
</body>
</html>
