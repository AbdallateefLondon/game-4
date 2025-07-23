<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Gamesetup extends Admin_Controller
{
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Main setup page
     */
    public function index()
    {
        // Only Super Admin can access setup
        if ($this->session->userdata('admin')['role_id'] != 7) {
            access_denied();
        }

        $data['title'] = 'Educational Game System Setup';
        
        // Check if system is already installed
        $data['is_installed'] = $this->_checkInstallation();
        $data['installation_status'] = $this->_getInstallationStatus();

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/setup', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Install the game system
     */
    public function install()
    {
        // Only Super Admin can install
        if ($this->session->userdata('admin')['role_id'] != 7) {
            access_denied();
        }

        $results = $this->_installSystem();

        $data['title'] = 'Installation Results';
        $data['results'] = $results;
        
        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/install_results', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Uninstall the game system
     */
    public function uninstall()
    {
        // Only Super Admin can uninstall
        if ($this->session->userdata('admin')['role_id'] != 7) {
            access_denied();
        }

        if ($this->input->post('confirm_uninstall')) {
            $results = $this->_uninstallSystem();

            $data['title'] = 'Uninstallation Results';
            $data['results'] = $results;
            
            $this->load->view('layout/header', $data);
            $this->load->view('gamebuilder/uninstall_results', $data);
            $this->load->view('layout/footer', $data);
        } else {
            $data['title'] = 'Confirm Uninstallation';
            
            $this->load->view('layout/header', $data);
            $this->load->view('gamebuilder/confirm_uninstall', $data);
            $this->load->view('layout/footer', $data);
        }
    }

    /**
     * Check if system is installed
     */
    private function _checkInstallation()
    {
        $tables = array('educational_games', 'game_results', 'student_points');
        
        foreach ($tables as $table) {
            if (!$this->db->table_exists($table)) {
                return false;
            }
        }

        // Check if permissions exist
        $this->db->where('short_code', 'game_builder');
        $game_builder_exists = $this->db->get('permission_group')->num_rows() > 0;

        $this->db->where('short_code', 'student_games');
        $student_games_exists = $this->db->get('permission_group')->num_rows() > 0;

        return $game_builder_exists && $student_games_exists;
    }

    /**
     * Get installation status details
     */
    private function _getInstallationStatus()
    {
        $status = array();

        // Check tables
        $tables = array('educational_games', 'game_results', 'student_points');
        foreach ($tables as $table) {
            $status['tables'][$table] = $this->db->table_exists($table);
        }

        // Check permissions
        $this->db->where('short_code', 'game_builder');
        $status['permissions']['game_builder'] = $this->db->get('permission_group')->num_rows() > 0;

        $this->db->where('short_code', 'student_games');  
        $status['permissions']['student_games'] = $this->db->get('permission_group')->num_rows() > 0;

        // Check if Super Admin has permissions
        $status['super_admin_permissions'] = $this->_checkSuperAdminPermissions();

        return $status;
    }

    /**
     * Install the complete system
     */
    private function _installSystem()
    {
        $this->db->trans_start();
        
        $results = array(
            'success' => 0,
            'errors' => 0,
            'messages' => array()
        );

        try {
            // 1. Create educational_games table
            if (!$this->db->table_exists('educational_games')) {
                $sql = "CREATE TABLE `educational_games` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `title` varchar(255) NOT NULL,
                  `description` text,
                  `game_type` enum('quiz','matching') NOT NULL,
                  `game_content` longtext NOT NULL COMMENT 'JSON structure containing game data',
                  `class_id` int(11) DEFAULT NULL,
                  `section_id` int(11) DEFAULT NULL,
                  `subject_id` int(11) DEFAULT NULL,
                  `created_by` int(11) NOT NULL COMMENT 'Staff ID who created the game',
                  `max_attempts` int(11) DEFAULT 3,
                  `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes, NULL for no limit',
                  `points_per_question` int(11) DEFAULT 10,
                  `is_active` tinyint(1) DEFAULT 1,
                  `difficulty_level` enum('easy','medium','hard') DEFAULT 'medium',
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_class_section` (`class_id`,`section_id`),
                  KEY `idx_created_by` (`created_by`),
                  KEY `idx_game_type` (`game_type`),
                  KEY `idx_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
                
                if ($this->db->query($sql)) {
                    $results['success']++;
                    $results['messages'][] = "✓ Created educational_games table";
                } else {
                    $results['errors']++;
                    $results['messages'][] = "✗ Failed to create educational_games table";
                }
            } else {
                $results['messages'][] = "- educational_games table already exists";
            }

            // 2. Create game_results table
            if (!$this->db->table_exists('game_results')) {
                $sql = "CREATE TABLE `game_results` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `game_id` int(11) NOT NULL,
                  `student_id` int(11) NOT NULL,
                  `student_session_id` int(11) NOT NULL,
                  `score` int(11) NOT NULL DEFAULT 0,
                  `total_questions` int(11) NOT NULL,
                  `correct_answers` int(11) NOT NULL DEFAULT 0,
                  `time_taken` int(11) DEFAULT NULL COMMENT 'Time taken in seconds',
                  `points_earned` int(11) NOT NULL DEFAULT 0,
                  `attempt_number` int(11) NOT NULL DEFAULT 1,
                  `game_data` text COMMENT 'JSON data of student answers and performance',
                  `completed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_game_student` (`game_id`,`student_id`),
                  KEY `idx_student_session` (`student_session_id`),
                  KEY `idx_completed_at` (`completed_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
                
                if ($this->db->query($sql)) {
                    $results['success']++;
                    $results['messages'][] = "✓ Created game_results table";
                } else {
                    $results['errors']++;
                    $results['messages'][] = "✗ Failed to create game_results table";
                }
            } else {
                $results['messages'][] = "- game_results table already exists";
            }

            // 3. Create student_points table
            if (!$this->db->table_exists('student_points')) {
                $sql = "CREATE TABLE `student_points` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `student_id` int(11) NOT NULL,
                  `student_session_id` int(11) NOT NULL,
                  `total_points` int(11) NOT NULL DEFAULT 0,
                  `current_level` int(11) NOT NULL DEFAULT 1,
                  `points_to_next_level` int(11) NOT NULL DEFAULT 10,
                  `games_played` int(11) NOT NULL DEFAULT 0,
                  `games_completed` int(11) NOT NULL DEFAULT 0,
                  `average_score` decimal(5,2) DEFAULT 0.00,
                  `best_score` int(11) DEFAULT 0,
                  `total_time_played` int(11) DEFAULT 0 COMMENT 'Total time in seconds',
                  `achievements` text COMMENT 'JSON array of earned achievements',
                  `last_played` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_student_session` (`student_id`,`student_session_id`),
                  KEY `idx_total_points` (`total_points`),
                  KEY `idx_current_level` (`current_level`),
                  KEY `idx_last_played` (`last_played`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
                
                if ($this->db->query($sql)) {
                    $results['success']++;
                    $results['messages'][] = "✓ Created student_points table";
                } else {
                    $results['errors']++;
                    $results['messages'][] = "✗ Failed to create student_points table";
                }
            } else {
                $results['messages'][] = "- student_points table already exists";
            }

            // 4. Add Game Builder permission group
            $this->db->where('short_code', 'game_builder');
            $existing = $this->db->get('permission_group');
            
            if ($existing->num_rows() == 0) {
                $permission_group_data = array(
                    'name' => 'Game Builder',
                    'short_code' => 'game_builder',
                    'system' => 0,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                );
                
                if ($this->db->insert('permission_group', $permission_group_data)) {
                    $game_group_id = $this->db->insert_id();
                    $results['success']++;
                    $results['messages'][] = "✓ Added Game Builder permission group (ID: $game_group_id)";
                    
                    // Add permission categories for game builder
                    $categories = array(
                        array(
                            'perm_group_id' => $game_group_id,
                            'name' => 'Games Management',
                            'short_code' => 'games_management',
                            'enable_view' => 1,
                            'enable_add' => 1,
                            'enable_edit' => 1,
                            'enable_delete' => 1,
                            'created_at' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'perm_group_id' => $game_group_id,
                            'name' => 'Game Results',
                            'short_code' => 'game_results',
                            'enable_view' => 1,
                            'enable_add' => 0,
                            'enable_edit' => 0,
                            'enable_delete' => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'perm_group_id' => $game_group_id,
                            'name' => 'Student Gaming',
                            'short_code' => 'student_gaming',
                            'enable_view' => 1,
                            'enable_add' => 0,
                            'enable_edit' => 0,
                            'enable_delete' => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        )
                    );
                    
                    if ($this->db->insert_batch('permission_category', $categories)) {
                        $results['success']++;
                        $results['messages'][] = "✓ Added Game Builder permission categories";
                        
                        // Grant all permissions to Super Admin (role_id = 7) automatically
                        $this->_grantSuperAdminPermissions($game_group_id);
                        $results['messages'][] = "✓ Granted Game Builder permissions to Super Admin";
                    }
                } else {
                    $results['errors']++;
                    $results['messages'][] = "✗ Failed to add Game Builder permission group";
                }
            } else {
                $results['messages'][] = "- Game Builder permission group already exists";
                
                // Still grant Super Admin permissions if they don't exist
                $existing_group = $existing->row();
                $this->_grantSuperAdminPermissions($existing_group->id);
                $results['messages'][] = "✓ Verified Super Admin Game Builder permissions";
            }

            // 5. Add Student Games permission group
            $this->db->where('short_code', 'student_games');
            $existing_student = $this->db->get('permission_group');
            
            if ($existing_student->num_rows() == 0) {
                $student_permission_group_data = array(
                    'name' => 'Student Games',
                    'short_code' => 'student_games',
                    'system' => 0,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                );
                
                if ($this->db->insert('permission_group', $student_permission_group_data)) {
                    $student_group_id = $this->db->insert_id();
                    $results['success']++;
                    $results['messages'][] = "✓ Added Student Games permission group (ID: $student_group_id)";
                    
                    // Add permission categories for student games
                    $student_categories = array(
                        array(
                            'perm_group_id' => $student_group_id,
                            'name' => 'Play Games',
                            'short_code' => 'play_games',
                            'enable_view' => 1,
                            'enable_add' => 0,
                            'enable_edit' => 0,
                            'enable_delete' => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'perm_group_id' => $student_group_id,
                            'name' => 'View Results',
                            'short_code' => 'view_game_results',
                            'enable_view' => 1,
                            'enable_add' => 0,
                            'enable_edit' => 0,
                            'enable_delete' => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        ),
                        array(
                            'perm_group_id' => $student_group_id,
                            'name' => 'Leaderboard',
                            'short_code' => 'game_leaderboard',
                            'enable_view' => 1,
                            'enable_add' => 0,
                            'enable_edit' => 0,
                            'enable_delete' => 0,
                            'created_at' => date('Y-m-d H:i:s')
                        )
                    );
                    
                    if ($this->db->insert_batch('permission_category', $student_categories)) {
                        $results['success']++;
                        $results['messages'][] = "✓ Added Student Games permission categories";
                    }
                } else {
                    $results['errors']++;
                    $results['messages'][] = "✗ Failed to add Student Games permission group";
                }
            } else {
                $results['messages'][] = "- Student Games permission group already exists";
            }

            // 6. Add performance indexes
            $this->_addPerformanceIndexes($results);

        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "✗ Error during installation: " . $e->getMessage();
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $results['overall_success'] = false;
            $results['messages'][] = "✗ Installation failed due to database transaction errors";
        } else {
            $results['overall_success'] = true;
            $results['messages'][] = "🎉 Educational Game System installed successfully!";
        }

        return $results;
    }

    /**
     * Grant permissions to Super Admin
     */
    private function _grantSuperAdminPermissions($permission_group_id)
    {
        // Get all permission categories for this group
        $this->db->where('perm_group_id', $permission_group_id);
        $categories = $this->db->get('permission_category')->result_array();

        foreach ($categories as $category) {
            // Check if permission already exists for Super Admin (role_id = 7)
            $this->db->where('role_id', 7);
            $this->db->where('perm_cat_id', $category['id']);
            $existing = $this->db->get('roles_permissions');

            if ($existing->num_rows() == 0) {
                $permission_data = array(
                    'role_id' => 7,
                    'perm_cat_id' => $category['id'],
                    'can_view' => $category['enable_view'],
                    'can_add' => $category['enable_add'],
                    'can_edit' => $category['enable_edit'],
                    'can_delete' => $category['enable_delete'],
                    'created_at' => date('Y-m-d H:i:s')
                );

                $this->db->insert('roles_permissions', $permission_data);
            }
        }
    }

    /**
     * Add performance indexes
     */
    private function _addPerformanceIndexes(&$results)
    {
        $indexes = array(
            "CREATE INDEX `idx_educational_games_class_section_active` ON `educational_games` (`class_id`, `section_id`, `is_active`)",
            "CREATE INDEX `idx_game_results_student_game` ON `game_results` (`student_id`, `game_id`, `completed_at`)",
            "CREATE INDEX `idx_student_points_leaderboard` ON `student_points` (`total_points` DESC, `current_level` DESC)"
        );

        foreach ($indexes as $index_sql) {
            try {
                if ($this->db->query($index_sql)) {
                    $results['success']++;
                    $results['messages'][] = "✓ Added performance index";
                }
            } catch (Exception $e) {
                // Index might already exist, that's okay
                $results['messages'][] = "- Performance index already exists or failed to create";
            }
        }
    }

    /**
     * Check if Super Admin has the necessary permissions
     */
    private function _checkSuperAdminPermissions()
    {
        // Get Game Builder permissions for Super Admin
        $this->db->select('roles_permissions.*');
        $this->db->from('roles_permissions');
        $this->db->join('permission_category', 'permission_category.id = roles_permissions.perm_cat_id');
        $this->db->join('permission_group', 'permission_group.id = permission_category.perm_group_id');
        $this->db->where('roles_permissions.role_id', 7);
        $this->db->where('permission_group.short_code', 'game_builder');

        return $this->db->get()->num_rows() > 0;
    }

    /**
     * Uninstall the system
     */
    private function _uninstallSystem()
    {
        $this->db->trans_start();
        
        $results = array(
            'success' => 0,
            'errors' => 0,
            'messages' => array()
        );

        try {
            // 1. Remove permissions
            $this->db->where('short_code IN ("game_builder", "student_games")');
            $permission_groups = $this->db->get('permission_group')->result_array();

            foreach ($permission_groups as $group) {
                // Remove role permissions
                $this->db->where('perm_cat_id IN (SELECT id FROM permission_category WHERE perm_group_id = ' . $group['id'] . ')');
                $this->db->delete('roles_permissions');

                // Remove permission categories
                $this->db->where('perm_group_id', $group['id']);
                $this->db->delete('permission_category');

                // Remove permission group
                $this->db->where('id', $group['id']);
                $this->db->delete('permission_group');

                $results['success']++;
                $results['messages'][] = "✓ Removed " . $group['name'] . " permissions";
            }

            // 2. Drop tables
            $tables = array('student_points', 'game_results', 'educational_games');
            foreach ($tables as $table) {
                if ($this->db->table_exists($table)) {
                    $this->db->query("DROP TABLE `$table`");
                    $results['success']++;
                    $results['messages'][] = "✓ Dropped $table table";
                } else {
                    $results['messages'][] = "- $table table doesn't exist";
                }
            }

        } catch (Exception $e) {
            $results['errors']++;
            $results['messages'][] = "✗ Error during uninstallation: " . $e->getMessage();
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $results['overall_success'] = false;
            $results['messages'][] = "✗ Uninstallation failed due to database transaction errors";
        } else {
            $results['overall_success'] = true;
            $results['messages'][] = "✓ Educational Game System uninstalled successfully";
        }

        return $results;
    }
}