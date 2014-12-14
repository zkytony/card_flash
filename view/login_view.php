<?php
// block of HTML for form
function form_login()
{
?>
  <div class="form-login">
    <h3>Login</h3>
    <form name="login " action="<?php echo $_SERVER['PHP_SELF'];?>"  method="post">
      <p><label for="username">Username: </label><input class='input-field' name="username" id="username" title="Username" type="text" maxLength="10"></p>
      <p><label for="password">Password: </label><input class="input-field"name="password" id="password" title="Password" type="password" maxLength="15"></p>
      <p><input name="submit" id="submit" type="submit" value="Log in"/>
        <a href="sign_up.php">Sign up</a></p>
    </form>
  </div>
<?php
}
?>
