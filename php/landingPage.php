<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>WatchVault - Your Personal Media Tracker</title>
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/pages/landingPage.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>

  <div class = "title">
    <img src="../assets/watchvault-logo-purple.png" alt="logo">
    <h1>WatchVault</h1>
  </div>

  <p class="tagline">Your Personal Media Tracker</p>

  <div class = "Slogan">
    <h1>Track. Discover.</h1>
  </div>  
    <div class = "Slogan2">
      <h1>Your Personal Vault</h1>
      <p>Never lose track of your favorite movies, TV shows, and anime again. Rate, <br>
        review, and curate your perfect watchlist in one beautiful place.</p>
    </div>

  <div class = "Buttons">
    <div class = "b1">
      <button onclick="window.location.href='login.php?action=register'">Get Started</button>
    </div>
    <div class = "b2">
      <button onclick="window.location.href='login.php'">Sign in</button>
    </div>  
  </div>

  <div class="cards">
    <!-- Card 1 -->
    <div class="card">
      <div class="icon"><img src="../assets/star.png"></div>
      <h3>Rate and Review</h3>
      <p>Share your thoughts and rate everything you watch with a sleek, intuitive interface</p>
    </div>

    <!-- Card 2 -->
    <div class="card">
      <div class="icon"><img src="../assets/graphup.png"></div>
      <h3>Track Progress</h3>
      <p>Organize your watchlist by status and never forget what you're currently watching</p>
    </div>

    <!-- Card 3 -->
    <div class="card">
      <div class="icon"><img src="../assets/vault.png"></div>
      <h3>Personal Vault</h3>
      <p>Build your perfect collection with movies, TV shows, and anime all in one place</p>
    </div>
  </div>

</body>
</html>