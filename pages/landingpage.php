<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netflix Clone</title>
    <link rel="stylesheet" href="../css/pages/landingpage.css">
</head>
<body>

    <div class="hero">

        <!-- Top Navigation -->
        <div class="top-bar">
            <img src="../assets/Logoname.png" class="logo" alt="Watchvault">

            <div class="buttons">
                <select class="language" id="language-selector" onchange="redirectToPage(this.value)">
                    <option value="" disabled selected>Select Language</option> 
                    <option value="landingpage.php">English</option>
                    <option value="landingpagefil.php">Filipino</option>
                </select>
                <a class="signin" href="login.php">Sign In</a>
            </div>
        </div>

        <!-- Center Content -->
        <div class="center-box">
            <h1>Unlimited movies, TV shows and more</h1>
            <h3>Your personal media tracker</h3>
            <p>Ready to watch? Enter your preffered username or Sign in if you already have an account.</p>

            <div class="email-box">
                <input type="email" placeholder="Username">
                <a href="register.php">Get Started</a>
            </div>
        </div>

    </div>

    <script>
        function redirectToPage(url) {
            // Check if a valid URL was selected (not the placeholder)
            if (url) { 
                window.location.href = url;
            }
        }
    </script>
</body>
</html>
