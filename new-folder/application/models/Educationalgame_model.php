<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Educationalgame_model extends CI_Model
{
    
    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    /**
     * Get educational games with optional filters
     */
    public function get($id = null, $filters = array())
    {
        if ($id) {
            $this->db->where('educational_games.id', $id);
        }

        // Apply filters
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('educational_games.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('educational_games.section_id', $filters['section_id']);
        }

        if (isset($filters['game_type']) && $filters['game_type']) {
            $this->db->where('educational_games.game_type', $filters['game_type']);
        }

        if (isset($filters['created_by']) && $filters['created_by']) {
            $this->db->where('educational_games.created_by', $filters['created_by']);
        }

        if (isset($filters['is_active'])) {
            $this->db->where('educational_games.is_active', $filters['is_active']);
        } else {
            $this->db->where('educational_games.is_active', 1); // Default to active games only
        }

        $this->db->select('educational_games.*, classes.class, sections.section, subjects.name as subject_name, subjects.code as subject_code, staff.name as creator_name, staff.surname as creator_surname, staff.employee_id');
        $this->db->from('educational_games');
        $this->db->join('classes', 'classes.id = educational_games.class_id', 'left');
        $this->db->join('sections', 'sections.id = educational_games.section_id', 'left');
        $this->db->join('subjects', 'subjects.id = educational_games.subject_id', 'left');
        $this->db->join('staff', 'staff.id = educational_games.created_by', 'left');
        $this->db->order_by('educational_games.created_at', 'DESC');

        $query = $this->db->get();

        if ($id) {
            return $query->row_array();
        } else {
            return $query->result_array();
        }
    }

    /**
     * Add or update educational game
     */
    public function add($data)
    {
        $this->db->trans_start();

        if (isset($data['id']) && $data['id']) {
            // Update existing game
            $id = $data['id'];
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $id);
            $this->db->update('educational_games', $data);
            $result = $id;
        } else {
            // Insert new game
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('educational_games', $data);
            $result = $this->db->insert_id();
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $result;
    }

    /**
     * Remove educational game
     */
    public function remove($id)
    {
        $this->db->trans_start();
        
        // Delete related game results first
        $this->db->where('game_id', $id);
        $this->db->delete('game_results');
        
        // Delete the game
        $this->db->where('id', $id);
        $this->db->delete('educational_games');
        
        $this->db->trans_complete();
        
        return $this->db->trans_status() !== FALSE;
    }

    /**
     * Get games available for a specific student
     */
    public function getStudentGames($student_id)
    {
        // Get student's class and section info
        $student_info = $this->getStudentInfo($student_id);
        
        if (!$student_info) {
            return array();
        }

        $this->db->select('
            educational_games.*, 
            classes.class, 
            sections.section, 
            subjects.name as subject_name,
            subjects.code as subject_code,
            staff.name as creator_name,
            staff.surname as creator_surname,
            COUNT(gr.id) as attempts_made
        ');
        $this->db->from('educational_games');
        $this->db->join('classes', 'classes.id = educational_games.class_id', 'left');
        $this->db->join('sections', 'sections.id = educational_games.section_id', 'left');
        $this->db->join('subjects', 'subjects.id = educational_games.subject_id', 'left');
        $this->db->join('staff', 'staff.id = educational_games.created_by', 'left');
        $this->db->join('game_results gr', 'gr.game_id = educational_games.id AND gr.student_id = ' . $student_id, 'left');
        
        // Games for student's class/section or global games (no class assigned)
        $this->db->group_start();
            $this->db->group_start();
                $this->db->where('educational_games.class_id IS NULL');
            $this->db->group_end();
            $this->db->or_group_start();
                $this->db->where('educational_games.class_id', $student_info['class_id']);
                $this->db->group_start();
                    $this->db->where('educational_games.section_id IS NULL');
                    $this->db->or_where('educational_games.section_id', $student_info['section_id']);
                $this->db->group_end();
            $this->db->group_end();
        $this->db->group_end();
        
        $this->db->where('educational_games.is_active', 1);
        $this->db->group_by('educational_games.id');
        $this->db->order_by('educational_games.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Get student info (class, section, session)
     */
    private function getStudentInfo($student_id)
    {
        $this->db->select('student_session.class_id, student_session.section_id, student_session.session_id');
        $this->db->from('student_session');
        $this->db->where('student_session.student_id', $student_id);
        $this->db->where('student_session.session_id', $this->current_session);
        
        return $this->db->get()->row_array();
    }

    /**
     * Validate game content structure based on game type
     */
    public function validateGameContent($game_type, $game_content)
    {
        $content = json_decode($game_content, true);
        
        if (!$content) {
            return false;
        }

        switch ($game_type) {
            case 'quiz':
                return $this->validateQuizContent($content);
            case 'matching':
                return $this->validateMatchingContent($content);
            default:
                return false;
        }
    }

    /**
     * Validate quiz game content
     */
    private function validateQuizContent($content)
    {
        if (!isset($content['questions']) || !is_array($content['questions'])) {
            return false;
        }

        foreach ($content['questions'] as $question) {
            if (!isset($question['question']) || !isset($question['options']) || !isset($question['correct_answer'])) {
                return false;
            }

            if (!is_array($question['options']) || count($question['options']) < 2) {
                return false;
            }

            $correct_answer = (int)$question['correct_answer'];
            if ($correct_answer < 0 || $correct_answer >= count($question['options'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate matching game content
     */
    private function validateMatchingContent($content)
    {
        if (!isset($content['pairs']) || !is_array($content['pairs'])) {
            return false;
        }

        foreach ($content['pairs'] as $pair) {
            if (!isset($pair['left']) || !isset($pair['right'])) {
                return false;
            }

            if (empty(trim($pair['left'])) || empty(trim($pair['right']))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get game template structure for different game types
     */
    public function getGameTemplate($game_type)
    {
        switch ($game_type) {
            case 'quiz':
                return array(
                    'questions' => array(
                        array(
                            'question' => '',
                            'options' => array('', ''),
                            'correct_answer' => 0,
                            'explanation' => ''
                        )
                    )
                );
            case 'matching':
                return array(
                    'pairs' => array(
                        array('left' => '', 'right' => '')
                    )
                );
            default:
                return array();
        }
    }

    /**
     * Get game statistics
     */
    public function getGameStats($created_by = null)
    {
        $stats = array();

        // Total games
        if ($created_by) {
            $this->db->where('created_by', $created_by);
        }
        $stats['total_games'] = $this->db->count_all_results('educational_games');

        // Active games
        $this->db->where('is_active', 1);
        if ($created_by) {
            $this->db->where('created_by', $created_by);
        }
        $stats['active_games'] = $this->db->from('educational_games')->count_all_results();

        // Games by type
        foreach (array('quiz', 'matching') as $type) {
            $this->db->where('game_type', $type);
            $this->db->where('is_active', 1);
            if ($created_by) {
                $this->db->where('created_by', $created_by);
            }
            $stats[$type . '_games'] = $this->db->from('educational_games')->count_all_results();
        }

        // Total plays (from game_results)
        $this->db->select('COUNT(*) as total_plays');
        $this->db->from('game_results');
        if ($created_by) {
            $this->db->join('educational_games', 'educational_games.id = game_results.game_id');
            $this->db->where('educational_games.created_by', $created_by);
        }
        $result = $this->db->get()->row_array();
        $stats['total_plays'] = $result['total_plays'];

        return $stats;
    }

    /**
     * Get most played games
     */
    public function getMostPlayedGames($limit = 5, $created_by = null)
    {
        $this->db->select('
            educational_games.title,
            educational_games.game_type,
            COUNT(game_results.id) as play_count,
            AVG(game_results.score) as avg_score
        ');
        $this->db->from('educational_games');
        $this->db->join('game_results', 'game_results.game_id = educational_games.id');
        
        if ($created_by) {
            $this->db->where('educational_games.created_by', $created_by);
        }
        
        $this->db->where('educational_games.is_active', 1);
        $this->db->group_by('educational_games.id');
        $this->db->order_by('play_count', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }
}