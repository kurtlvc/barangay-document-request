<?php
require './includes/functions.php';
bdr_start_session();

if (isset($_SESSION['user_id'])) {
    echo "Already logged in as ";
    echo $_SESSION['name'];
    echo "<br>";
    echo $_SESSION['role'];
    echo "<br>";
    echo "<a href='logout.php'>log me out</a>";
    sleep(5);
    } else {
        echo "Please login!";
        echo "<br>";
}
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
<form id="loginForm">
    <input id="inpEmail" type="email" name="email" placeholder="E-mail" requred>
    <input id="inpPassword" type="password" name="password" placeholder="Password" requred>
    <button id="inpSubmit" type="submit">Login</button>
</form>
<p id="loginMessage"></p>
<script src="./assets/js/jquery-4.0.0.min.js"></script>
<script src="./assets/js/login.js"></script>
