<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="main-header">
    <a href="<?php echo (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) ? 'dashboard.php' : 'index.php'; ?>" style="text-decoration: none; color: inherit;"><h1 class="logo">Video Capture</h1></a>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $current_page !== 'index.php' && $current_page !== 'register.php'): ?>
        <form action="search.php" method="get" class="search-form">
            <button type="submit" class="search-btn"><span class="material-symbols-outlined">search</span></button>
            <input type="text" name="query" placeholder="Search..." class="search-input">
            <button type="button" class="clear-btn"><span class="material-symbols-outlined">close</span></button>
        </form>
        <span class="user-email-display"><span class="material-symbols-outlined">account_circle</span> <?php echo htmlspecialchars($_SESSION["email"]); ?></span>
        <a href="logout.php" class="btn-logout"><span class="material-symbols-outlined">logout</span> Logout</a>
    <?php else: ?>
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'index.php' && $current_page !== 'register.php'):
        ?>
        <div class="login-register-buttons">
            <a href="index.php" class="btn-login">Login</a>
            <a href="register.php" class="btn-register">Register</a>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</header>