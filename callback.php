<?php

session_start();

require 'config.php';
require 'google_config.php';

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit();
}

try {

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        die("Google Error: " . $token['error']);
    }

    $client->setAccessToken($token);

    $oauth = new Google_Service_Oauth2($client);
    $user = $oauth->userinfo->get();

    $google_id = $user->id;
    $name      = $user->name;
    $email     = $user->email;
    $picture   = $user->picture;

    // check user
    $stmt = $connection->prepare("SELECT id, role, name FROM uesr WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $stmt = $connection->prepare(
            "INSERT INTO uesr (google_id,name,email,profile_picture,is_verified)
             VALUES (?,?,?,?,1)"
        );
        $stmt->bind_param("ssss", $google_id, $name, $email, $picture);
        $stmt->execute();
        $user_id   = $connection->insert_id;
        $user_role = 'user';
    } else {
        $existing  = $result->fetch_assoc();
        $user_id   = $existing['id'];
        $user_role = $existing['role'] ?? 'user';
    }

    // login user
    $_SESSION['user']      = $email;
    $_SESSION['user_id']   = $user_id;
    $_SESSION['role']      = $user_role;
    $_SESSION['user_name'] = $name;

    // IMPORTANT: always redirect
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    die("Login failed: " . $e->getMessage());
}