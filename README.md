# Utilisation du backend PHP (Orientation Scolaire)

## 1. Lancer XAMPP, Apache, MySQL et ouvrir phpMyAdmin

1. Ouvrir le **XAMPP Control Panel**.
2. Cliquer sur **Start** devant **Apache** et **MySQL** pour démarrer les services.[^1]
3. Une fois MySQL démarré, cliquer sur le bouton **Admin** en face de **MySQL** pour ouvrir **phpMyAdmin** dans le navigateur (ou aller sur `http://localhost/phpmyadmin/`).[^2][^3]

## 2. Exécuter le SQL de `database.sql`

1. Aller dans l’onglet **SQL** en haut.[^4]
2. Ouvrir le fichier `database.sql` (dans le répertoire du projet backend 'C:\xampp\htdocs\orientation-scolaire-professionnelle\backend' ) avec un éditeur de texte (VS Code, Notepad++, etc.).[^5]
3. Copier tout le contenu du fichier `database.sql`.
4. Le coller dans la zone de texte de l’onglet **SQL** de phpMyAdmin.
5. Cliquer sur **Exécuter** (**Go**) pour lancer les requêtes et créer les bases des donnees et tables.[^6][^4]

## 3. Cloner le backend dans `htdocs` (si ce n’est pas déjà fait)

1. Aller dans le dossier `C:\xampp\htdocs`.
2. Ouvrir un terminal (CMD ou PowerShell) dans ce dossier.
3. Cloner le repository :

```bash
git clone https://github.com/RockDago/orientation-scolaire-backend.git
```

4. Entrer dans le projet :

```bash
cd C:\xampp\htdocs\orientation-scolaire-backend
```

## 4. Lancer le seeder AdminSeeder

1. Aller dans le dossier des seeders :

```bash
cd C:\xampp\htdocs\orientation-scolaire-professionnelle\backend\src\seeders
```

2. Exécuter le seeder :

```bash
php AdminSeeder.php
```

3. Ce script génère un administrateur avec :
   - Email : `admin@orientation.com`
   - Nom d’utilisateur : `admin`
   - Mot de passe : `123`

Tu pourras modifier cet utilisateur plus tard dans la base via phpMyAdmin.

## 5. Tester le login côté frontend

1. Démarrer le frontend ( `npm run dev`).
2. Aller dans le navigateur sur :

```text
http://localhost:5173/login
```

3. Tester la connexion avec :
   - Email : `admin@orientation.com`
   - Mot de passe : `123`
