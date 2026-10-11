Migration: add_is_sold

Files:
- add_is_sold.sql — SQL to add `is_sold` column to `products` table.
- run_migration.php — simple PHP runner to execute the SQL.

How to run (recommended backup first):

1) Using PHP CLI (from project folder):

```powershell
php migrations/run_migration.php
```

2) Or open in browser (be careful on public servers):

http://localhost/ThriftStore/migrations/run_migration.php

3) Or run the SQL in phpMyAdmin by importing `add_is_sold.sql`.

Notes:
- The SQL uses `ADD COLUMN IF NOT EXISTS` (MySQL 8+). If your MySQL is older, run:

```sql
ALTER TABLE products ADD COLUMN is_sold TINYINT(1) NOT NULL DEFAULT 0;
```
