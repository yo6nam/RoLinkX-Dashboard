<?php
/*
 *   RoLinkX Dashboard v3.1
 *   Copyright (C) 2023 by Razvan Marin YO6NAM / www.xpander.ro
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, write to the Free Software
 *   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *   Portions of this script based on http://www.phptoys.com/product/micro-protector.html
 */

if (isset($_POST['submit_pwd'])) {
    $pwd = isset($_POST['passwd']) ? trim($_POST['passwd']) : '';
    if ($pwd != $password) {
        showForm();
        exit();
    }
    setcookie(md5($password), "1", time() + 3600 * 24 * 7);
} else {
    showForm();
    exit();
}
function showForm()
{
    ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="css/styles.css" rel="stylesheet" />
  </head>
<body>
    <div class="container mt-3">
      <div class="row">
        <div class="d-flex justify-content-center">
          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="pwd">
            <div class="form-group mb-2">
              <input type="password" class="form-control" id="passwd" name="passwd" placeholder="Password">
            </div>
            <div class="d-flex justify-content-center">
              <button type="submit" class="btn btn-primary" name="submit_pwd">Let me in</button>
            </div>
          </form>
        </div>
      </div>
    </div>
</body>
<?php
}
?>
