<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Product Model
 * Mengelola data Produk dan relasinya dengan kelompok harga.
 */
class Product
{
    /**
     * Ambil semua produk beserta nama kelompok harga dan harganya.
     */
    public function getAllWithGroup(): array
    {
        $db = getDB();
        $stmt = $db->query("
            SELECT p.*, pg.name as group_name, pg.harga_per_bungkus 
            FROM produk p
            LEFT JOIN kelompok_harga_produk pg ON p.id_kelompok_harga = pg.id
            ORDER BY p.name ASC
        ");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM produk WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(string $name, int $priceGroupId): bool
    {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO produk (name, id_kelompok_harga) VALUES (?, ?)");
        return $stmt->execute([$name, $priceGroupId]);
    }

    public function update(int $id, string $name, int $priceGroupId): bool
    {
        $db = getDB();
        $stmt = $db->prepare("UPDATE produk SET name = ?, id_kelompok_harga = ? WHERE id = ?");
        return $stmt->execute([$name, $priceGroupId, $id]);
    }

    public function delete(int $id): bool
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM produk WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Memeriksa apakah nama produk sudah ada di database (case-insensitive).
     */
    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM produk WHERE LOWER(name) = LOWER(?) AND id != ?");
        $stmt->execute([$name, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
