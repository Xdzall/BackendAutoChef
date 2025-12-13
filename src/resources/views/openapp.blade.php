<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open AutoChef</title>
  <script>
  window.onload = function() {
    // HARUS SAMA dengan AndroidManifest & main.dart
    window.location = "autochef://email/verified"; 

    // Fallback jika aplikasi tidak terinstall (opsional)
    setTimeout(() => {
       window.location = "https://play.google.com/store/apps/details?id=com.autochef.app";
    }, 3000);
  };
</script>
</head>
<body>
  <h3>Opening AutoChef...</h3>
</body>
</html>
