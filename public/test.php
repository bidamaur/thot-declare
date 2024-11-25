<?php

$conn = oci_connect('dbprod', 'dbprod', '192.168.2.242/propme');

if (!$conn) {
    $e = oci_error();
    echo "Erreur de connexion : " . $e['message'];
} else {
    echo "Connecté avec succès.";
    oci_close($conn);
}