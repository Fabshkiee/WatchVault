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
            <h1>Walang limitasyong mga pelikula, palabas sa TV at marami pa</h1>
            <h3>Ang iyong personal na tagasubaybay ng media</h3>
            <p>Handa nang manood? I-enter ang nais mong username o Mag-sign in kung mayroon ka nang account.</p>

            <div class="email-box">
                <input type="email" placeholder="Username">
                <a href="register.php">Magsimula</a>
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
