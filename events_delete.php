<?php
// Make sure this is the very first line, no whitespace before!
session_start();
require_once 'config.php';

$id = $_GET['id'];

// First query
$stmt1 = $conn->prepare("UPDATE events SET deleted_at = NOW() WHERE id = ?");
$stmt1->bind_param("i", $id);
$stmt1->execute();

// Second query
$stmt2 = $conn->prepare("UPDATE mhr_event_payments SET deleted_at = NOW() WHERE event_id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();

$_SESSION['message'] = "Booking deleted successfully.";

// Make sure no output has been sent before this
header('Location: events_list.php');
exit();