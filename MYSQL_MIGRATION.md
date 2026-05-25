Migration to MySQL-backed PHP API
=================================

This project already includes PHP endpoints under `bfc/` that speak MySQL. Changes applied:

- Staff passwords are now stored hashed using `password_hash()` when created.
- A migration script `migrate-hash-passwords.php` is included to hash any existing plaintext passwords.

Quick steps
-----------

1. Ensure your MySQL server is reachable and `bfc/db-config.php` has correct credentials.
2. Run the migration (CLI recommended):

```bash
php bfc/migrate-hash-passwords.php
```

3. Create or reset an admin account (SQL example). To create a new admin with a known password:

```php
<?php
// Run from PHP-CLI to generate a bcrypt hash for insertion
echo password_hash('newpassword', PASSWORD_DEFAULT) . PHP_EOL;
?>
```

Then insert into MySQL (replace `<hash>`):

```sql
INSERT INTO staff (name, role, username, password, active, created_at, updated_at)
VALUES ('Admin Name', 'admin', 'jireh', '<hash>', 1, NOW(), NOW());
```

4. Configure the React app to call the PHP API by adding these env vars (Netlify UI or `.env` for local):

```
REACT_APP_USE_PHP_API=true
REACT_APP_PHP_API_BASE=https://your-php-host/path-to-bfc
```

5. Rebuild and deploy the React app so it uses the PHP endpoints.

API endpoints
-------------
- `bfc/log-in.php` — POST `login_id`, `password` (returns JSON when requested via AJAX)
- `bfc/store-api.php?action=add_order` — POST JSON body with `items`, `total`, `paid`, `change_amount`
- `bfc/admin-api.php?action=...` — various admin actions (requires session). For Netlify/API usage you may prefer to adapt these to token-based auth.

Security notes
--------------
- Use HTTPS for the PHP host.
- Consider switching `admin-api.php` to JWT or token auth for API-driven admin actions (currently uses PHP sessions).
- Rotate admin passwords after migration if you suspect exposure.
