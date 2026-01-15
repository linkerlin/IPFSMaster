<?php

class IPFSClient {
    private $rpcUrl;
    private $gatewayUrl;
    
    public function __construct($rpcUrl = null, $gatewayUrl = null) {
        if ($rpcUrl === null) {
            $rpcUrl = $this->getSettingValue('ipfs_rpc_url', '/ip4/127.0.0.1/tcp/5001');
        }
        if ($gatewayUrl === null) {
            $gatewayUrl = $this->getSettingValue('ipfs_gateway_url', 'http://127.0.0.1:8080');
        }
        
        $this->rpcUrl = $this->convertMultiAddrToHttp($rpcUrl);
        $this->gatewayUrl = $this->detectWorkingGateway($gatewayUrl);
    }
    
    private function getSettingValue($key, $default) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = :key");
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['value'] : $default;
    }
    
    private function convertMultiAddrToHttp($addr) {
        // Convert multiaddr format to HTTP URL
        if (strpos($addr, '/ip4/') === 0) {
            // Format: /ip4/127.0.0.1/tcp/5001
            preg_match('/\/ip4\/([^\/]+)\/tcp\/(\d+)/', $addr, $matches);
            if ($matches) {
                return "http://{$matches[1]}:{$matches[2]}";
            }
        }
        return $addr;
    }
    
    private function detectWorkingGateway($primaryGateway) {
        $gateways = [$primaryGateway];
        
        // Add fallback gateways if using default
        if ($primaryGateway === 'http://127.0.0.1:8080') {
            $gateways[] = 'http://127.0.0.1:8081';
            $gateways[] = 'http://127.0.0.1:8082';
        }
        
        foreach ($gateways as $gateway) {
            if ($this->testGateway($gateway)) {
                return $gateway;
            }
        }
        
        return $primaryGateway; // Return primary even if none work
    }
    
    private function testGateway($gateway) {
        $ch = curl_init($gateway . '/api/v0/version');
        curl_setopt($ch, CURLOPT_POST, true); // IPFS API 要求使用 POST
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function apiCall($endpoint, $params = [], $files = [], $timeout = 15) {
        $url = $this->rpcUrl . '/api/v0/' . $endpoint;
        
        $ch = curl_init();
        
        // IPFS API requires POST for all endpoints
        curl_setopt($ch, CURLOPT_POST, true);
        
        if (!empty($params) || !empty($files)) {
            $postData = [];
            foreach ($params as $key => $value) {
                $postData[$key] = $value;
            }
            
            foreach ($files as $key => $file) {
                if (is_array($file)) {
                    $postData[$key] = new CURLFile($file['path'], $file['type'] ?? 'application/octet-stream', $file['name'] ?? basename($file['path']));
                } else {
                    $postData[$key] = $file;
                }
            }
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } else {
            // Even for requests without params, we need to set POSTFIELDS to ensure POST method
            curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("IPFS API Error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("IPFS API returned HTTP $httpCode: $response");
        }
        
        return json_decode($response, true);
    }

    private function apiCallRaw($endpoint, $params = [], $files = [], $timeout = 60) {
        $url = $this->rpcUrl . '/api/v0/' . $endpoint;

        $ch = curl_init();

        // IPFS API requires POST for all endpoints
        curl_setopt($ch, CURLOPT_POST, true);

        if (!empty($params) || !empty($files)) {
            $postData = [];
            foreach ($params as $key => $value) {
                $postData[$key] = $value;
            }

            foreach ($files as $key => $file) {
                $postData[$key] = $file;
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } else {
            // Even for requests without params, we need to set POSTFIELDS to ensure POST method
            curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("IPFS API Error: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("IPFS API returned HTTP $httpCode: $response");
        }

        return $response;
    }

    private function parseJsonLines($response) {
        $lines = preg_split('/\r?\n/', trim($response));
        $items = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $items[] = $decoded;
            }
        }
        return $items;
    }
    
    public function version() {
        return $this->apiCall('version');
    }
    
    public function id() {
        return $this->apiCall('id');
    }
    
    public function add($filePath, $filename = null, $pin = true) {
        if ($filename === null) {
            $filename = basename($filePath);
        }
        
        $file = new CURLFile($filePath, 'application/octet-stream', $filename);
        $response = $this->apiCallRaw('add', ['pin' => $pin ? 'true' : 'false'], [
            'file' => $file
        ]);

        $items = $this->parseJsonLines($response);
        return !empty($items) ? end($items) : null;
    }
    
    public function addDirectory($dirPath, $pin = true) {
        $files = $this->scanDirectory($dirPath);
        if (empty($files)) {
            throw new Exception('Folder is empty');
        }
        $postFiles = [];

        foreach ($files as $index => $file) {
            $relativePath = str_replace($dirPath . DIRECTORY_SEPARATOR, '', $file);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            $postFiles['file' . $index] = new CURLFile($file, 'application/octet-stream', $relativePath);
        }

        $response = $this->apiCallRaw('add', [
            'recursive' => 'true',
            'wrap-with-directory' => 'true',
            'pin' => $pin ? 'true' : 'false'
        ], $postFiles);

        return $this->parseJsonLines($response);
    }
    
    private function scanDirectory($dir) {
        $files = [];
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            }
        }
        
        return $files;
    }
    
    public function pinAdd($cid, $recursive = true) {
        $params = ['arg' => $cid];
        if ($recursive) {
            $params['recursive'] = 'true';
        }
        return $this->apiCall('pin/add', $params);
    }
    
    public function pinRm($cid, $recursive = true) {
        $params = ['arg' => $cid];
        if ($recursive) {
            $params['recursive'] = 'true';
        }
        return $this->apiCall('pin/rm', $params);
    }
    
    public function pinLs($type = 'all') {
        $params = [];
        if ($type !== 'all') {
            $params['type'] = $type;
        }
        return $this->apiCall('pin/ls', $params);
    }
    
    public function ls($cid) {
        return $this->apiCall('ls', ['arg' => $cid]);
    }
    
    public function objectStat($cid) {
        return $this->apiCall('object/stat', ['arg' => $cid]);
    }
    
    public function dagGet($cid) {
        return $this->apiCall('dag/get', ['arg' => $cid]);
    }
    
    public function getGatewayUrl($cid, $filename = null) {
        $url = $this->gatewayUrl . '/ipfs/' . $cid;
        if ($filename !== null && $filename !== '') {
            $url .= '?filename=' . rawurlencode($filename);
        }
        return $url;
    }

    public function statsRepo() {
        return $this->apiCall('repo/stat');
    }

    public function statsBw() {
        return $this->apiCall('stats/bw');
    }
    
    public function recursivePin($cid) {
        // Pin the CID and all its children
        try {
            // First, pin the main CID
            $this->pinAdd($cid, true);
            
            // Try to list contents and pin recursively
            try {
                $ls = $this->ls($cid);
                if (isset($ls['Objects'][0]['Links'])) {
                    foreach ($ls['Objects'][0]['Links'] as $link) {
                        $linkCid = $link['Hash'];
                        try {
                            $this->recursivePin($linkCid);
                        } catch (Exception $e) {
                            // Continue even if some children fail
                        }
                    }
                }
            } catch (Exception $e) {
                // If ls fails, the object might not have links, that's ok
            }
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function getRpcUrl() {
        return $this->rpcUrl;
    }
    
    public function getGatewayBaseUrl() {
        return $this->gatewayUrl;
    }
}
