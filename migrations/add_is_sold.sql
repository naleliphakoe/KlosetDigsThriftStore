-- Migration: add_is_sold.sql
-- Adds the `is_sold` column to the `products` table if it doesn't already exist.
-- Backup your database before running.

-- This migration adds the `is_sold` column to the `products` table.
-- The runner `run_migration.php` performs a safe, idempotent check
-- and applies the ALTER TABLE using the connected MySQL server.

-- If you prefer to run SQL manually, use one of the following
-- statements depending on your server:

-- MySQL 8+:
-- ALTER TABLE products ADD COLUMN IF NOT EXISTS is_sold TINYINT(1) NOT NULL DEFAULT 0;

-- MySQL (all versions) / CLI (will error if column exists):
-- ALTER TABLE products ADD COLUMN is_sold TINYINT(1) NOT NULL DEFAULT 0;

-- SQL Server (T-SQL):
-- ALTER TABLE products ADD is_sold TINYINT NOT NULL DEFAULT 0;

-- Run `php run_migration.php` to apply this migration safely.
