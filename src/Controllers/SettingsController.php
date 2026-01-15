<?php

class SettingsController extends Controller {
    
    public function index() {
        $db = Database::getInstance();
        $result = $db->query("SELECT * FROM settings");
        
        $settings = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        
        $this->render('settings', [
            'settings' => $settings,
            'success' => $this->getGet('success'),
            'error' => $this->getGet('error')
        ]);
    }
    
    public function update() {
        if ($this->isPost()) {
            $rpcUrl = $this->getPost('ipfs_rpc_url');
            $gatewayUrl = $this->getPost('ipfs_gateway_url');
            $autoPin = $this->getPost('auto_pin', '0');
            $recursivePin = $this->getPost('recursive_pin', '0');
            
            try {
                $db = Database::getInstance();
                
                $settings = [
                    'ipfs_rpc_url' => $rpcUrl,
                    'ipfs_gateway_url' => $gatewayUrl,
                    'auto_pin' => $autoPin,
                    'recursive_pin' => $recursivePin
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (:key, :value, CURRENT_TIMESTAMP)");
                    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
                    $stmt->bindValue(':value', $value, SQLITE3_TEXT);
                    $stmt->execute();
                }
                
                $this->redirect('/settings?success=1');
            } catch (Exception $e) {
                $this->redirect('/settings?error=' . urlencode($e->getMessage()));
            }
        } else {
            $this->redirect('/settings');
        }
    }
}
