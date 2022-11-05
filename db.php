<?php

//Dane do połączenia z bazą danych
const SERVERNAME = "host";
const USERNAME = "username";
const PASSWORD = "password";
const DB_NAME = "db_name";
try {
  $pdo = new PDO('mysql:host=' . SERVERNAME . ';dbname=' . DB_NAME, USERNAME, PASSWORD);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}