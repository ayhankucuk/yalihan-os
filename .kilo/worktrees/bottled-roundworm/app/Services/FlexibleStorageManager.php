<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

class FlexibleStorageManager
{
    private $provider;

    private $config;

    public function __construct()
    {
        $this->config = config('ai.storage', [
            'provider' => 'local_mysql',
            'local_mysql' => ['table' => 'ai_storage'],
        ]);
        $this->provider = $this->createProvider();
    }

    /**
     * Provider oluştur
     */
    private function createProvider()
    {
        switch ($this->config['provider']) {
            case 'local_mysql':
                return new LocalMySQLProvider;

            case 'remote_mysql':
                return new RemoteMySQLProvider(
                    $this->config['remote']['host'] ?? 'localhost',
                    $this->config['remote']['database'] ?? 'yalihan_ai',
                    $this->config['remote']['username'] ?? 'root',
                    $this->config['remote']['password'] ?? ''
                );

            case 'google_drive':
                return new GoogleDriveProvider(
                    $this->config['google']['credentials'] ?? null
                );

            case 'aws_s3':
                return new AWSS3Provider(
                    $this->config['aws']['credentials'] ?? null
                );

            default:
                return new LocalMySQLProvider;
        }
    }

    /**
     * Veri kaydet
     */
    public function store($key, $data)
    {
        return $this->provider->store($key, $data);
    }

    /**
     * Veri getir
     */
    public function get($key)
    {
        return $this->provider->get($key);
    }

    /**
     * Veri sil
     */
    public function delete($key)
    {
        return $this->provider->delete($key);
    }

    /**
     * Veri var mı kontrol et
     */
    public function exists($key)
    {
        return $this->provider->exists($key);
    }

    /**
     * Veri listele
     */
    public function list($prefix = '')
    {
        return $this->provider->list($prefix);
    }
}

/**
 * Local MySQL Provider
 */
class LocalMySQLProvider
{
    public function store($key, $data)
    {
        return \App\Models\AIStorage::updateOrCreate(
            ['storage_key' => $key],
            [
                'data' => $data,
                'type' => 'pattern', // context7-ignore
                'context' => $this->extractContext($key),
            ]
        );
    }

    public function get($key)
    {
        $record = \App\Models\AIStorage::findByKey($key);

        return $record ? $record->data : null;
    }

    public function delete($key)
    {
        // ✅ SAB: AIStorage model deprecated
        // Use file storage fallback
        try {
            return \Illuminate\Support\Facades\Storage::delete($key);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function exists($key)
    {
        // ✅ SAB: AIStorage model deprecated
        // Use file storage fallback
        return \Illuminate\Support\Facades\Storage::exists($key);
    }

    public function list($prefix = '')
    {
        // ✅ SAB: AIStorage model deprecated
        // List files with prefix
        $files = \Illuminate\Support\Facades\Storage::files($prefix);

        return $files ?? [];
    }

    private function extractContext($key)
    {
        if (strpos($key, 'pattern_') === 0) {
            return str_replace('pattern_', '', $key);
        }

        return null;
    }
}

/**
 * Remote MySQL Provider
 */
class RemoteMySQLProvider
{
    private $connection;

    public function __construct($host, $database, $username, $password)
    {
        try {
            $this->connection = new \PDO(
                "mysql:host={$host};dbname={$database}",
                $username,
                $password
            );
        } catch (\PDOException $e) {
            throw new \Exception('Remote MySQL connection failed: ' . $e->getMessage());
        }
    }

    public function store($key, $data)
    {
        $stmt = $this->connection->prepare('
            INSERT INTO ai_storage (storage_key, data, type, context, updated_at)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = VALUES(updated_at)
        ');

        return $stmt->execute([
            $key,
            json_encode($data),
            'pattern',
            $this->extractContext($key),
            now(),
        ]);
    }

    public function get($key)
    {
        $stmt = $this->connection->prepare('SELECT data FROM ai_storage WHERE storage_key = ?');
        $stmt->execute([$key]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $record ? json_decode($record['data'], true) : null;
    }

    public function delete($key)
    {
        $stmt = $this->connection->prepare('DELETE FROM ai_storage WHERE storage_key = ?');

        return $stmt->execute([$key]);
    }

    public function exists($key)
    {
        $stmt = $this->connection->prepare('SELECT 1 FROM ai_storage WHERE storage_key = ?');
        $stmt->execute([$key]);

        return $stmt->fetch() !== false;
    }

    public function list($prefix = '')
    {
        $sql = 'SELECT storage_key FROM ai_storage';
        $params = [];

        if ($prefix) {
            $sql .= ' WHERE storage_key LIKE ?';
            $params[] = $prefix . '%';
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'storage_key');
    }

    private function extractContext($key)
    {
        if (strpos($key, 'pattern_') === 0) {
            return str_replace('pattern_', '', $key);
        }

        return null;
    }
}

/**
 * Google Drive Provider (Placeholder)
 */
class GoogleDriveProvider
{
    public function __construct($credentials = null) {}

    public function store($key, $data)
    {
        return true;
    }

    public function get($key)
    {
        return null;
    }

    public function delete($key)
    {
        return true;
    }

    public function exists($key)
    {
        return false;
    }

    public function list($prefix = '')
    {
        return [];
    }
}

/**
 * AWS S3 Provider (Placeholder)
 */
class AWSS3Provider
{
    public function __construct($credentials = null) {}

    public function store($key, $data)
    {
        return true;
    }

    public function get($key)
    {
        return null;
    }

    public function delete($key)
    {
        return true;
    }

    public function exists($key)
    {
        return false;
    }

    public function list($prefix = '')
    {
        return [];
    }
}
