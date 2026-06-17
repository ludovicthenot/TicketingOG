# TicketingOG

Base PHP pur avec PDO, sessions natives et protections backend.

## Structure

```text
config.php
db.php
auth.php
functions.php
login.php
logout.php
dashboard.php
projects.php
profile.php
assets/
tickets/
```

`config.php` reste hors Git. Le dossier `tickets/` est reserve aux pages `list.php`, `create.php` et `view.php`.

## Installation locale

1. Copier `config.example.php` vers `config.php`.
2. Renseigner les identifiants MySQL dans `config.php`.
3. Creer le premier admin :

```powershell
php scripts/create_admin.php --username=admin --email=admin@example.com --password=Password123!
```

4. Lancer le serveur local :

```powershell
php -S localhost:8888
```

L'application sera disponible sur `http://localhost:8888`.
