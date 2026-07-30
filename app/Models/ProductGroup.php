<?php
declare(strict_types=1);

namespace App\Models;

/**
 * ProductGroup Model
 * Mengelola data Kelompok Harga Borongan.
 */
class ProductGroup
{
    public function getAll(): array
    {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM kelompok_harga_produk ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM kelompok_harga_produk WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(string $name, int $pricePerPack): bool
    {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO kelompok_harga_produk (name, harga_per_bungkus) VALUES (?, ?)");
        return $stmt->execute([$name, $pricePerPack]);
    }

    public function update(int $id, string $name, int $pricePerPack): bool
    {
        $db = getDB();
        $stmt = $db->prepare("UPDATE kelompok_harga_produk SET name = ?, harga_per_bungkus = ? WHERE id = ?");
        return $stmt->execute([$name, $pricePerPack, $id]);
    }

    public function delete(int $id): bool
    {
        $db = getDB();
        // Akan gagal (constraint error) jika masih dipakai di products
        $stmt = $db->prepare("DELETE FROM kelompok_harga_produk WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
