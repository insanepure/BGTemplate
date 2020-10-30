<?php
session_start();
include_once 'classes/database.php';
include_once 'classes/user.php';
$user = User::GetLoggedIn($database, session_id());

if($user != null && $user->Get('challenger') != 0)
{
	$link = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
	if($link != 'challenge.php')
	{
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/challenge.php";
		header("Refresh: 0; Url=$actual_link");
	}
}
?>