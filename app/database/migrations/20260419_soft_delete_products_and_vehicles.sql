-- Soft deletion support for products and vehicles.
-- Only these two entities use soft delete; other delete flows remain unchanged.
ALTER TABLE products
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL
AFTER updated_at;
ALTER TABLE products
ADD INDEX idx_products_deleted_at (deleted_at);
ALTER TABLE vehicles
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL
AFTER updated_at;
ALTER TABLE vehicles
ADD INDEX idx_vehicles_deleted_at (deleted_at);