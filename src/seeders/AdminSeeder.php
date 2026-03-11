<?php

require_once __DIR__ . '/../models/User.php';

$email = 'admin@orientation.com';
$nomUtilisateur = 'admin';

if (User::findByEmail($email) || User::findByNomUtilisateur($nomUtilisateur)) {
    echo "L'utilisateur admin existe déjà.";
    exit;
}

$id = User::create([
    'role' => 'admin',
    'nom' => 'Admin',
    'prenom' => 'Systeme',
    'nom_utilisateur' => 'admin',
    'email' => 'admin@orientation.com',
    'telephone' => '0340000000',
    'adresse' => 'Antananarivo',
    'code_postal' => '101',
    'mot_de_passe' => '123'
]);

echo "Admin créé avec succès. ID: " . $id;
