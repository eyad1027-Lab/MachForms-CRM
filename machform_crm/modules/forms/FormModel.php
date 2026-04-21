<?php
/**
 * Machform CRM - Forms Model
 * Handle all form-related database operations
 */

class FormModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all forms with statistics
     */
    public function getAllForms($orderBy = 'form_id DESC') {
        $sql = "SELECT f.*, 
                (SELECT COUNT(*) FROM ap_form_" . "10510_log) as total_logs
                FROM ap_forms f 
                ORDER BY {$orderBy}";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get form by ID
     */
    public function getFormById($formId) {
        $sql = "SELECT * FROM ap_forms WHERE form_id = :form_id LIMIT 1";
        return $this->db->fetchOne($sql, ['form_id' => $formId]);
    }
    
    /**
     * Get active forms only
     */
    public function getActiveForms() {
        $sql = "SELECT * FROM ap_forms WHERE form_active = 1 ORDER BY form_id DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get inactive forms
     */
    public function getInactiveForms() {
        $sql = "SELECT * FROM ap_forms WHERE form_active = 0 ORDER BY form_id DESC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Create new form
     */
    public function createForm($data) {
        $defaultData = [
            'form_name' => $data['form_name'] ?? 'Untitled Form',
            'form_description' => $data['form_description'] ?? '',
            'form_email' => $data['form_email'] ?? '',
            'form_active' => $data['form_active'] ?? 1,
            'form_created_date' => date(DATE_FORMAT),
            'form_created_by' => $data['form_created_by'] ?? 1,
            'form_theme_id' => $data['form_theme_id'] ?? 0,
            'form_language' => $data['form_language'] ?? 'en',
            'form_label_alignment' => $data['form_label_alignment'] ?? 'top_label',
            'form_submit_primary_text' => $data['form_submit_primary_text'] ?? 'Submit',
            'form_success_message' => $data['form_success_message'] ?? 'Thank you for your submission.',
            'form_disabled_message' => $data['form_disabled_message'] ?? 'This form is currently disabled.',
            'form_captcha' => $data['form_captcha'] ?? 0,
            'form_tags' => $data['form_tags'] ?? ''
        ];
        
        return $this->db->insert('ap_forms', $defaultData);
    }
    
    /**
     * Update form
     */
    public function updateForm($formId, $data) {
        return $this->db->update(
            'ap_forms',
            $data,
            'form_id = :form_id',
            ['form_id' => $formId]
        );
    }
    
    /**
     * Delete form
     */
    public function deleteForm($formId) {
        // First, we would need to drop the form entries table
        // This should be done carefully in production
        return $this->db->delete(
            'ap_forms',
            'form_id = :form_id',
            ['form_id' => $formId]
        );
    }
    
    /**
     * Activate/Deactivate form
     */
    public function toggleFormStatus($formId) {
        $form = $this->getFormById($formId);
        $newStatus = $form['form_active'] == 1 ? 0 : 1;
        
        return $this->updateForm($formId, ['form_active' => $newStatus]);
    }
    
    /**
     * Get form entry count
     */
    public function getEntryCount($formId) {
        $tableName = "ap_form_{$formId}";
        
        // Check if table exists
        $checkSql = "SHOW TABLES LIKE :table_name";
        $result = $this->db->fetchOne($checkSql, ['table_name' => $tableName]);
        
        if (!$result) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as total FROM {$tableName}";
        $result = $this->db->fetchOne($sql);
        
        return (int)$result['total'];
    }
    
    /**
     * Get forms with entry counts
     */
    public function getFormsWitryCounts() {
        $forms = $this->getAllForms();
        
        foreach ($forms as &$form) {
            $form['entry_count'] = $this->getEntryCount($form['form_id']);
        }
        
        return $forms;
    }
    
    /**
     * Search forms
     */
    public function searchForms($keyword) {
        $sql = "SELECT * FROM ap_forms 
                WHERE form_name LIKE :keyword 
                OR form_description LIKE :keyword 
                OR form_tags LIKE :keyword
                ORDER BY form_id DESC";
        
        $searchTerm = "%{$keyword}%";
        return $this->db->fetchAll($sql, ['keyword' => $searchTerm]);
    }
    
    /**
     * Get forms by tag
     */
    public function getFormsByTag($tag) {
        $sql = "SELECT * FROM ap_forms 
                WHERE form_tags LIKE :tag 
                ORDER BY form_id DESC";
        
        return $this->db->fetchAll($sql, ['tag' => "%{$tag}%"]);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        $stats = [];
        
        // Total forms
        $stats['total_forms'] = $this->db->count('ap_forms');
        
        // Active forms
        $stats['active_forms'] = $this->db->count('ap_forms', 'form_active = 1');
        
        // Inactive forms
        $stats['inactive_forms'] = $this->db->count('ap_forms', 'form_active = 0');
        
        // Forms with approval enabled
        $stats['approval_enabled'] = $this->db->count('ap_forms', 'form_approval_enable = 1');
        
        // Forms with payment enabled
        $stats['payment_enabled'] = $this->db->count('ap_forms', 'payment_enable_merchant = 1');
        
        // Forms with encryption enabled
        $stats['encryption_enabled'] = $this->db->count('ap_forms', 'form_encryption_enable = 1');
        
        // Total entries (sum across all form tables)
        $stats['total_entries'] = $this->getTotalEntriesCount();
        
        return $stats;
    }
    
    /**
     * Get total entries count across all forms
     */
    private function getTotalEntriesCount() {
        $forms = $this->getAllForms();
        $total = 0;
        
        foreach ($forms as $form) {
            $total += $this->getEntryCount($form['form_id']);
        }
        
        return $total;
    }
    
    /**
     * Get recent forms
     */
    public function getRecentForms($limit = 10) {
        $sql = "SELECT * FROM ap_forms ORDER BY form_id DESC LIMIT :limit";
        return $this->db->fetchAll($sql, ['limit' => (int)$limit]);
    }
    
    /**
     * Get form approval settings
     */
    public function getApprovalSettings($formId) {
        $sql = "SELECT * FROM ap_approval_settings WHERE form_id = :form_id LIMIT 1";
        return $this->db->fetchOne($sql, ['form_id' => $formId]);
    }
    
    /**
     * Get form approvers
     */
    public function getApprovers($formId) {
        $sql = "SELECT a.*, u.username, u.email 
                FROM ap_approvers a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE a.form_id = :form_id 
                ORDER BY a.user_position ASC";
        
        return $this->db->fetchAll($sql, ['form_id' => $formId]);
    }
    
    /**
     * Get form email logic rules
     */
    public function getEmailLogic($formId) {
        $sql = "SELECT * FROM ap_email_logic WHERE form_id = :form_id ORDER BY rule_id ASC";
        return $this->db->fetchAll($sql, ['form_id' => $formId]);
    }
    
    /**
     * Get form element options
     */
    public function getElementOptions($formId, $elementId = null) {
        if ($elementId) {
            $sql = "SELECT * FROM ap_element_options 
                    WHERE form_id = :form_id AND element_id = :element_id 
                    ORDER BY position ASC";
            return $this->db->fetchAll($sql, [
                'form_id' => $formId,
                'element_id' => $elementId
            ]);
        } else {
            $sql = "SELECT * FROM ap_element_options 
                    WHERE form_id = :form_id 
                    ORDER BY element_id, position ASC";
            return $this->db->fetchAll($sql, ['form_id' => $formId]);
        }
    }
    
    /**
     * Export forms data
     */
    public function exportForms($format = 'json') {
        $forms = $this->getAllForms();
        
        if ($format === 'json') {
            return json_encode($forms, JSON_PRETTY_PRINT);
        }
        
        return $forms;
    }
}
