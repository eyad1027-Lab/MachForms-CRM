<?php
/**
 * Machform CRM - Reports Model
 * Generate reports and analytics
 */

class ReportModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStats() {
        $stats = [];
        
        // Forms statistics
        $stats['total_forms'] = $this->db->count('ap_forms');
        $stats['active_forms'] = $this->db->count('ap_forms', 'form_active = 1');
        $stats['inactive_forms'] = $this->db->count('ap_forms', 'form_active = 0');
        $stats['approval_enabled_forms'] = $this->db->count('ap_forms', 'form_approval_enable = 1');
        $stats['payment_enabled_forms'] = $this->db->count('ap_forms', 'payment_enable_merchant = 1');
        $stats['encryption_enabled_forms'] = $this->db->count('ap_forms', 'form_encryption_enable = 1');
        $stats['scheduled_forms'] = $this->db->count('ap_forms', 'form_schedule_enable = 1');
        
        // Entries statistics (across all form tables)
        $forms = $this->getAllFormTables();
        $totalEntries = 0;
        $todayEntries = 0;
        $weekEntries = 0;
        $monthEntries = 0;
        
        foreach ($forms as $formId) {
            $tableName = "ap_form_{$formId}";
            if ($this->tableExists($tableName)) {
                $totalEntries += $this->db->count($tableName);
                $todayEntries += $this->db->count($tableName, "DATE(date_created) = CURDATE()");
                $weekEntries += $this->db->count($tableName, "YEARWEEK(date_created) = YEARWEEK(NOW())");
                $monthEntries += $this->db->count($tableName, "MONTH(date_created) = MONTH(NOW()) AND YEAR(date_created) = YEAR(NOW())");
            }
        }
        
        $stats['total_entries'] = $totalEntries;
        $stats['entries_today'] = $todayEntries;
        $stats['entries_this_week'] = $weekEntries;
        $stats['entries_this_month'] = $monthEntries;
        
        // Entry status breakdown
        $statusBreakdown = $this->getEntryStatusBreakdown($forms);
        $stats['status_breakdown'] = $statusBreakdown;
        
        // Recent activity
        $stats['recent_forms'] = $this->getRecentFormsActivity(10);
        $stats['top_forms'] = $this->getTopFormsByEntries(10);
        
        return $stats;
    }
    
    /**
     * Get all form IDs from ap_forms table
     */
    private function getAllFormTables() {
        $sql = "SELECT form_id FROM ap_forms ORDER BY form_id";
        $forms = $this->db->fetchAll($sql);
        return array_column($forms, 'form_id');
    }
    
    /**
     * Check if table exists
     */
    private function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE :table_name";
        $result = $this->db->fetchOne($sql, ['table_name' => $tableName]);
        return (bool)$result;
    }
    
    /**
     * Get entry status breakdown across all forms
     */
    private function getEntryStatusBreakdown($formIds) {
        $breakdown = [
            'active' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];
        
        foreach ($formIds as $formId) {
            $tableName = "ap_form_{$formId}";
            if ($this->tableExists($tableName)) {
                $breakdown['active'] += $this->db->count($tableName, 'status = 1');
                $breakdown['pending'] += $this->db->count($tableName, 'status = 2');
                $breakdown['approved'] += $this->db->count($tableName, 'status = 3');
                $breakdown['rejected'] += $this->db->count($tableName, 'status = 4');
            }
        }
        
        return $breakdown;
    }
    
    /**
     * Get recent forms activity
     */
    private function getRecentFormsActivity($limit = 10) {
        $sql = "SELECT f.form_id, f.form_name, f.form_created_date,
                COUNT(e.id) as entry_count
                FROM ap_forms f
                LEFT JOIN ap_form_10510 e ON f.form_id = 10510
                GROUP BY f.form_id
                ORDER BY f.form_id DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, ['limit' => (int)$limit]);
    }
    
    /**
     * Get top forms by entry count
     */
    private function getTopFormsByEntries($limit = 10) {
        // This is a simplified version - in production you'd dynamically check all form tables
        $sql = "SELECT form_id, form_name FROM ap_forms 
                ORDER BY form_id DESC 
                LIMIT :limit";
        
        $forms = $this->db->fetchAll($sql, ['limit' => (int)$limit]);
        
        foreach ($forms as &$form) {
            $tableName = "ap_form_{$form['form_id']}";
            if ($this->tableExists($tableName)) {
                $form['entry_count'] = $this->db->count($tableName);
            } else {
                $form['entry_count'] = 0;
            }
        }
        
        usort($forms, function($a, $b) {
            return $b['entry_count'] - $a['entry_count'];
        });
        
        return array_slice($forms, 0, $limit);
    }
    
    /**
     * Get entries trend data for chart
     */
    public function getEntriesTrend($days = 30) {
        $forms = $this->getAllFormTables();
        $trendData = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $trendData[$date] = 0;
        }
        
        foreach ($forms as $formId) {
            $tableName = "ap_form_{$formId}";
            if (!$this->tableExists($tableName)) {
                continue;
            }
            
            $sql = "SELECT DATE(date_created) as date, COUNT(*) as count
                    FROM {$tableName}
                    WHERE date_created >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                    GROUP BY DATE(date_created)
                    ORDER BY date ASC";
            
            $results = $this->db->fetchAll($sql);
            
            foreach ($results as $row) {
                if (isset($trendData[$row['date']])) {
                    $trendData[$row['date']] += (int)$row['count'];
                }
            }
        }
        
        // Convert to array format for charts
        $chartData = [];
        foreach ($trendData as $date => $count) {
            $chartData[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        
        return $chartData;
    }
    
    /**
     * Get form-specific report
     */
    public function getFormReport($formId) {
        $report = [];
        
        // Form details
        $formModel = new FormModel();
        $report['form'] = $formModel->getFormById($formId);
        
        // Entry statistics
        $entryModel = new EntryModel();
        $report['entries_stats'] = $entryModel->getEntriesStats($formId);
        
        // Approval settings
        $report['approval_settings'] = $formModel->getApprovalSettings($formId);
        
        // Approvers
        $report['approvers'] = $formModel->getApprovers($formId);
        
        // Email logic rules
        $report['email_logic'] = $formModel->getEmailLogic($formId);
        
        // Element options count
        $elementOptions = $formModel->getElementOptions($formId);
        $report['elements_count'] = count(array_unique(array_column($elementOptions, 'element_id')));
        
        return $report;
    }
    
    /**
     * Get entries by date range
     */
    public function getEntriesByDateRange($startDate, $endDate, $formId = null) {
        if ($formId) {
            $tableName = "ap_form_{$formId}";
            if (!$this->tableExists($tableName)) {
                return [];
            }
            
            $sql = "SELECT * FROM {$tableName}
                    WHERE date_created BETWEEN :start_date AND :end_date
                    ORDER BY date_created DESC";
            
            return $this->db->fetchAll($sql, [
                'start_date' => $startDate . ' 00:00:00',
                'end_date' => $endDate . ' 23:59:59'
            ]);
        }
        
        // For all forms - return summary
        $forms = $this->getAllFormTables();
        $summary = [];
        
        foreach ($forms as $fid) {
            $tableName = "ap_form_{$fid}";
            if (!$this->tableExists($tableName)) {
                continue;
            }
            
            $count = $this->db->count(
                $tableName,
                "date_created BETWEEN :start_date AND :end_date",
                [
                    'start_date' => $startDate . ' 00:00:00',
                    'end_date' => $endDate . ' 23:59:59'
                ]
            );
            
            if ($count > 0) {
                $formModel = new FormModel();
                $form = $formModel->getFormById($fid);
                $summary[] = [
                    'form_id' => $fid,
                    'form_name' => $form['form_name'] ?? 'Unknown',
                    'entry_count' => $count
                ];
            }
        }
        
        return $summary;
    }
    
    /**
     * Get payment statistics (for forms with payment enabled)
     */
    public function getPaymentStats() {
        $stats = [];
        
        // Forms with payment enabled
        $sql = "SELECT form_id, form_name, payment_merchant_type, payment_currency
                FROM ap_forms
                WHERE payment_enable_merchant = 1";
        
        $stats['payment_forms'] = $this->db->fetchAll($sql);
        $stats['payment_forms_count'] = count($stats['payment_forms']);
        
        // Currency breakdown
        $sql = "SELECT payment_currency, COUNT(*) as count
                FROM ap_forms
                WHERE payment_enable_merchant = 1
                GROUP BY payment_currency";
        
        $stats['currency_breakdown'] = $this->db->fetchAll($sql);
        
        return $stats;
    }
    
    /**
     * Get approval workflow statistics
     */
    public function getApprovalStats() {
        $stats = [];
        
        // Forms with approval enabled
        $stats['approval_forms_count'] = $this->db->count('ap_forms', 'form_approval_enable = 1');
        
        // Total approvers
        $sql = "SELECT COUNT(DISTINCT user_id) as total FROM ap_approvers";
        $result = $this->db->fetchOne($sql);
        $stats['total_approvers'] = (int)$result['total'];
        
        // Workflow types
        $sql = "SELECT workflow_type, COUNT(*) as count
                FROM ap_approval_settings
                GROUP BY workflow_type";
        $stats['workflow_types'] = $this->db->fetchAll($sql);
        
        return $stats;
    }
    
    /**
     * Export report data
     */
    public function exportReport($type, $format = 'json', $params = []) {
        $data = [];
        
        switch ($type) {
            case 'dashboard':
                $data = $this->getDashboardStats();
                break;
            case 'form':
                if (!empty($params['form_id'])) {
                    $data = $this->getFormReport($params['form_id']);
                }
                break;
            case 'entries':
                $data = $this->getEntriesByDateRange(
                    $params['start_date'] ?? date('Y-m-01'),
                    $params['end_date'] ?? date('Y-m-d'),
                    $params['form_id'] ?? null
                );
                break;
            case 'payments':
                $data = $this->getPaymentStats();
                break;
            case 'approvals':
                $data = $this->getApprovalStats();
                break;
        }
        
        if ($format === 'json') {
            return json_encode($data, JSON_PRETTY_PRINT);
        } elseif ($format === 'csv') {
            return $this->arrayToCSV($data);
        }
        
        return $data;
    }
    
    /**
     * Convert array to CSV format
     */
    private function arrayToCSV($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'w+');
        
        if (isset($data[0]) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, array_keys($data));
            fputcsv($output, $data);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Get system health status
     */
    public function getSystemHealth() {
        $health = [
            'database' => 'OK',
            'tables' => [],
            'warnings' => []
        ];
        
        // Check database connection
        try {
            $this->db->query("SELECT 1");
            $health['database'] = 'Connected';
        } catch (Exception $e) {
            $health['database'] = 'Error: ' . $e->getMessage();
        }
        
        // Check essential tables
        $essentialTables = [
            'ap_forms',
            'ap_approval_settings',
            'ap_approvers',
            'ap_email_logic'
        ];
        
        foreach ($essentialTables as $table) {
            $exists = $this->tableExists($table);
            $health['tables'][$table] = $exists ? 'Exists' : 'Missing';
            
            if (!$exists) {
                $health['warnings'][] = "Table {$table} is missing";
            }
        }
        
        // Count form entry tables
        $forms = $this->getAllFormTables();
        $existingFormTables = 0;
        
        foreach ($forms as $formId) {
            if ($this->tableExists("ap_form_{$formId}")) {
                $existingFormTables++;
            }
        }
        
        $health['form_tables'] = "{$existingFormTables}/" . count($forms);
        
        return $health;
    }
}
