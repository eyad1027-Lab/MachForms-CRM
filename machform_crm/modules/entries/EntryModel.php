<?php
/**
 * Machform CRM - Entries Model
 * Handle form entries and submissions
 */

class EntryModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get table name for a form
     */
    private function getTableName($formId) {
        return "ap_form_{$formId}";
    }
    
    /**
     * Check if form entries table exists
     */
    private function tableExists($formId) {
        $tableName = $this->getTableName($formId);
        $sql = "SHOW TABLES LIKE :table_name";
        $result = $this->db->fetchOne($sql, ['table_name' => $tableName]);
        return (bool)$result;
    }
    
    /**
     * Get all entries for a form with pagination
     */
    public function getEntries($formId, $page = 1, $pageSize = DEFAULT_PAGE_SIZE, $filters = []) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return ['entries' => [], 'total' => 0, 'pages' => 0];
        }
        
        // Build WHERE clause from filters
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "date_created >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "date_created <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['ip_address'])) {
            $whereConditions[] = "ip_address LIKE :ip_address";
            $params['ip_address'] = "%{$filters['ip_address']}%";
        }
        
        // Search in element fields
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $elementSearch = [];
            for ($i = 1; $i <= 50; $i++) {
                $elementSearch[] = "element_{$i} LIKE :search";
            }
            $whereConditions[] = "(" . implode(' OR ', $elementSearch) . ")";
            $params['search'] = $searchTerm;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$tableName} {$whereClause}";
        $totalResult = $this->db->fetchOne($countSql, $params);
        $total = (int)$totalResult['total'];
        
        // Calculate pagination
        $pages = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;
        
        // Get entries
        $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = (int)$pageSize;
        $params['offset'] = (int)$offset;
        
        $entries = $this->db->fetchAll($sql, $params);
        
        return [
            'entries' => $entries,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'page_size' => $pageSize
        ];
    }
    
    /**
     * Get single entry by ID
     */
    public function getEntry($formId, $entryId) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return null;
        }
        
        $sql = "SELECT * FROM {$tableName} WHERE id = :id LIMIT 1";
        return $this->db->fetchOne($sql, ['id' => $entryId]);
    }
    
    /**
     * Get entry by edit key
     */
    public function getEntryByEditKey($formId, $editKey) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return null;
        }
        
        $sql = "SELECT * FROM {$tableName} WHERE edit_key = :edit_key LIMIT 1";
        return $this->db->fetchOne($sql, ['edit_key' => $editKey]);
    }
    
    /**
     * Update entry status
     */
    public function updateEntryStatus($formId, $entryId, $status) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return false;
        }
        
        return $this->db->update(
            $tableName,
            ['status' => (int)$status],
            'id = :id',
            ['id' => $entryId]
        );
    }
    
    /**
     * Delete entry
     */
    public function deleteEntry($formId, $entryId) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return false;
        }
        
        return $this->db->delete(
            $tableName,
            'id = :id',
            ['id' => $entryId]
        );
    }
    
    /**
     * Bulk delete entries
     */
    public function bulkDeleteEntries($formId, $entryIds) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
        $sql = "DELETE FROM {$tableName} WHERE id IN ({$placeholders})";
        
        return $this->db->query($sql, $entryIds)->rowCount();
    }
    
    /**
     * Get entry logs
     */
    public function getEntryLogs($formId, $entryId) {
        $logTableName = "ap_form_{$formId}_log";
        
        // Check if log table exists
        $checkSql = "SHOW TABLES LIKE :table_name";
        $result = $this->db->fetchOne($checkSql, ['table_name' => $logTableName]);
        
        if (!$result) {
            return [];
        }
        
        $sql = "SELECT * FROM {$logTableName} WHERE record_id = :record_id ORDER BY log_time DESC";
        return $this->db->fetchAll($sql, ['record_id' => $entryId]);
    }
    
    /**
     * Add entry log
     */
    public function addEntryLog($formId, $entryId, $message, $user = 'System', $origin = 'CRM') {
        $logTableName = "ap_form_{$formId}_log";
        
        // Check if log table exists
        $checkSql = "SHOW TABLES LIKE :table_name";
        $result = $this->db->fetchOne($checkSql, ['table_name' => $logTableName]);
        
        if (!$result) {
            return false;
        }
        
        $data = [
            'record_id' => $entryId,
            'log_time' => date(DATETIME_FORMAT),
            'log_user' => $user,
            'log_origin' => $origin,
            'log_message' => $message
        ];
        
        return $this->db->insert($logTableName, $data);
    }
    
    /**
     * Get entries statistics for a form
     */
    public function getEntriesStats($formId) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return null;
        }
        
        $stats = [];
        
        // Total entries
        $stats['total'] = $this->db->count($tableName);
        
        // Entries by status
        $stats['status_1'] = $this->db->count($tableName, 'status = 1'); // Active
        $stats['status_2'] = $this->db->count($tableName, 'status = 2'); // Pending
        $stats['status_3'] = $this->db->count($tableName, 'status = 3'); // Approved
        $stats['status_4'] = $this->db->count($tableName, 'status = 4'); // Rejected
        
        // Entries today
        $today = date(DATE_FORMAT);
        $stats['today'] = $this->db->count($tableName, "DATE(date_created) = '{$today}'");
        
        // Entries this week
        $stats['this_week'] = $this->db->count($tableName, "YEARWEEK(date_created) = YEARWEEK(NOW())");
        
        // Entries this month
        $stats['this_month'] = $this->db->count($tableName, "MONTH(date_created) = MONTH(NOW()) AND YEAR(date_created) = YEAR(NOW())");
        
        // Recent entries (last 7 days)
        $sql = "SELECT DATE(date_created) as date, COUNT(*) as count 
                FROM {$tableName} 
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(date_created)
                ORDER BY date ASC";
        $stats['recent_trend'] = $this->db->fetchAll($sql);
        
        return $stats;
    }
    
    /**
     * Export entries to CSV
     */
    public function exportToCSV($formId, $filename = null) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return false;
        }
        
        if ($filename === null) {
            $filename = "form_{$formId}_entries_" . date('Y-m-d_H-i-s') . ".csv";
        }
        
        $sql = "SELECT * FROM {$tableName} ORDER BY id DESC";
        $entries = $this->db->fetchAll($sql);
        
        if (empty($entries)) {
            return false;
        }
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Output header row
        fputcsv($output, array_keys($entries[0]));
        
        // Output data rows
        foreach ($entries as $entry) {
            fputcsv($output, $entry);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export entries to JSON
     */
    public function exportToJSON($formId) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return json_encode(['error' => 'Table not found']);
        }
        
        $sql = "SELECT * FROM {$tableName} ORDER BY id DESC";
        $entries = $this->db->fetchAll($sql);
        
        return json_encode($entries, JSON_PRETTY_PRINT);
    }
    
    /**
     * Search entries across all forms
     */
    public function searchAllForms($keyword, $limit = 50) {
        $formsModel = new FormModel();
        $forms = $formsModel->getAllForms();
        
        $results = [];
        
        foreach ($forms as $form) {
            $formId = $form['form_id'];
            $tableName = $this->getTableName($formId);
            
            if (!$this->tableExists($formId)) {
                continue;
            }
            
            $searchTerm = "%{$keyword}%";
            $elementSearch = [];
            
            for ($i = 1; $i <= 20; $i++) {
                $elementSearch[] = "element_{$i} LIKE :search";
            }
            
            if (empty($elementSearch)) {
                continue;
            }
            
            $whereClause = "(" . implode(' OR ', $elementSearch) . ")";
            $sql = "SELECT id, date_created, status FROM {$tableName} WHERE {$whereClause} LIMIT 5";
            
            $entries = $this->db->fetchAll($sql, ['search' => $searchTerm]);
            
            if (!empty($entries)) {
                $results[] = [
                    'form_id' => $formId,
                    'form_name' => $form['form_name'],
                    'entries' => $entries
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get incomplete entries (draft/resume)
     */
    public function getIncompleteEntries($formId) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return [];
        }
        
        $sql = "SELECT * FROM {$tableName} 
                WHERE resume_key IS NOT NULL AND status = 0 
                ORDER BY date_created DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Update entry data
     */
    public function updateEntry($formId, $entryId, $data) {
        $tableName = $this->getTableName($formId);
        
        if (!$this->tableExists($formId)) {
            return false;
        }
        
        $data['date_updated'] = date(DATETIME_FORMAT);
        
        return $this->db->update(
            $tableName,
            $data,
            'id = :id',
            ['id' => $entryId]
        );
    }
}
