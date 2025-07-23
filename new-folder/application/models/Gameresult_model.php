<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Gameresult_model extends CI_Model
{
    
    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    /**
     * Get game results with optional filters
     */
    public function get($id = null, $filters = array())
    {
        if ($id) {
            $this->db->where('game_results.id', $id);
        }

        // Apply filters
        if (isset($filters['game_id']) && $filters['game_id']) {
            $this->db->where('game_results.game_id', $filters['game_id']);
        }

        if (isset($filters['student_id']) && $filters['student_id']) {
            $this->db->where('game_results.student_id', $filters['student_id']);
        }

        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        if (isset($filters['date_from'])) {
            $this->db->where('game_results.completed_at >=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $this->db->where('game_results.completed_at <=', $filters['date_to']);
        }

        if (isset($filters['created_by'])) {
            $this->db->where('educational_games.created_by', $filters['created_by']);
        }

        $this->db->select('
            game_results.*,
            educational_games.title as game_title,
            educational_games.game_type,
            educational_games.difficulty_level,
            students.firstname,
            students.lastname,
            students.admission_no,
            students.roll_no,
            classes.class,
            sections.section,
            subjects.name as subject_name,
            subjects.code as subject_code
        ');
        
        $this->db->from('game_results');
        $this->db->join('educational_games', 'educational_games.id = game_results.game_id');
        $this->db->join('students', 'students.id = game_results.student_id');
        $this->db->join('student_session', 'student_session.student_id = students.id AND student_session.session_id = ' . $this->current_session);
        $this->db->join('classes', 'classes.id = student_session.class_id', 'left');
        $this->db->join('sections', 'sections.id = student_session.section_id', 'left');
        $this->db->order_by('game_results.completed_at', 'DESC');

        $query = $this->db->get();

        if ($id) {
            return $query->row_array();
        } else {
            return $query->result_array();
        }
    }

    /**
     * Add game result
     */
    public function add($data)
    {
        $this->db->trans_start();

        // Insert game result
        $this->db->insert('game_results', $data);
        $result_id = $this->db->insert_id();

        // Update student points
        if ($result_id && isset($data['points_earned']) && $data['points_earned'] > 0) {
            $this->updateStudentPoints($data['student_id'], $data['student_session_id'], $data['points_earned'], $data['score']);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $result_id;
    }

    /**
     * Update student points after game completion
     */
    private function updateStudentPoints($student_id, $student_session_id, $points_earned, $score)
    {
        // Check if student points record exists
        $this->db->where('student_id', $student_id);
        $this->db->where('student_session_id', $student_session_id);
        $existing = $this->db->get('student_points')->row_array();

        $new_total_points = $points_earned;
        $games_played = 1;
        $games_completed = 1;
        $total_score = $score;
        $best_score = $score;

        if ($existing) {
            // Update existing record
            $new_total_points = $existing['total_points'] + $points_earned;
            $games_played = $existing['games_played'] + 1;
            $games_completed = $existing['games_completed'] + 1;
            $total_score = (($existing['average_score'] * $existing['games_completed']) + $score);
            $best_score = max($existing['best_score'], $score);

            $update_data = array(
                'total_points' => $new_total_points,
                'games_played' => $games_played,
                'games_completed' => $games_completed,
                'average_score' => round($total_score / $games_completed, 2),
                'best_score' => $best_score,
                'last_played' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            );

            // Calculate new level
            $level_info = $this->calculateLevel($new_total_points);
            $update_data['current_level'] = $level_info['level'];
            $update_data['points_to_next_level'] = $level_info['points_to_next'];

            $this->db->where('id', $existing['id']);
            $this->db->update('student_points', $update_data);
        } else {
            // Create new record
            $level_info = $this->calculateLevel($new_total_points);
            
            $insert_data = array(
                'student_id' => $student_id,
                'student_session_id' => $student_session_id,
                'total_points' => $new_total_points,
                'current_level' => $level_info['level'],
                'points_to_next_level' => $level_info['points_to_next'],
                'games_played' => $games_played,
                'games_completed' => $games_completed,
                'average_score' => $score,
                'best_score' => $best_score,
                'last_played' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            );

            $this->db->insert('student_points', $insert_data);
        }
    }

    /**
     * Calculate student level based on points (Non-linear: Level N = N² × 2)
     */
    private function calculateLevel($total_points)
    {
        $level = 1;
        $points_needed = 0;
        
        // Level progression: Level 1 = 0-10, Level 2 = 11-18, Level 3 = 19-34, etc.
        while (true) {
            $points_for_this_level = $level * $level * 2;
            
            if ($total_points < ($points_needed + $points_for_this_level)) {
                break;
            }
            
            $points_needed += $points_for_this_level;
            $level++;
        }

        $points_for_current_level = $level * $level * 2;
        $points_to_next = ($points_needed + $points_for_current_level) - $total_points;

        return array(
            'level' => $level,
            'points_to_next' => $points_to_next
        );
    }

    /**
     * Get student's attempt count for a specific game
     */
    public function getStudentAttempts($game_id, $student_id)
    {
        $this->db->where('game_id', $game_id);
        $this->db->where('student_id', $student_id);
        return $this->db->count_all_results('game_results');
    }

    /**
     * Get game analytics for a specific game
     */
    public function getGameAnalytics($game_id)
    {
        $analytics = array();

        // Basic stats
        $this->db->select('
            COUNT(*) as total_attempts,
            COUNT(DISTINCT student_id) as unique_players,
            AVG(score) as average_score,
            MAX(score) as highest_score,
            MIN(score) as lowest_score,
            AVG(time_taken) as average_time
        ');
        $this->db->where('game_id', $game_id);
        $analytics['basic_stats'] = $this->db->get('game_results')->row_array();

        // Score distribution
        $this->db->select('
            CASE 
                WHEN score >= 90 THEN "A (90-100%)"
                WHEN score >= 80 THEN "B (80-89%)"
                WHEN score >= 70 THEN "C (70-79%)"
                WHEN score >= 60 THEN "D (60-69%)"
                ELSE "F (Below 60%)"
            END as grade,
            COUNT(*) as count
        ');
        $this->db->where('game_id', $game_id);
        $this->db->group_by('grade');
        $this->db->order_by('score', 'DESC');
        $analytics['grade_distribution'] = $this->db->get('game_results')->result_array();

        // Recent activity (last 10 plays)
        $this->db->select('
            game_results.score,
            game_results.completed_at,
            students.firstname,
            students.lastname,
            classes.class,
            sections.section
        ');
        $this->db->from('game_results');
        $this->db->join('students', 'students.id = game_results.student_id');
        $this->db->join('student_session', 'student_session.student_id = students.id AND student_session.session_id = ' . $this->current_session);
        $this->db->join('classes', 'classes.id = student_session.class_id', 'left');
        $this->db->join('sections', 'sections.id = student_session.section_id', 'left');
        $this->db->where('game_results.game_id', $game_id);
        $this->db->order_by('game_results.completed_at', 'DESC');
        $this->db->limit(10);
        $analytics['recent_activity'] = $this->db->get()->result_array();

        return $analytics;
    }

    /**
     * Get game leaderboard
     */
    public function getGameLeaderboard($game_id, $limit = 10)
    {
        $this->db->select('
            MAX(game_results.score) as best_score,
            students.firstname,
            students.lastname,
            students.admission_no,
            classes.class,
            sections.section,
            MAX(game_results.completed_at) as last_played
        ');
        $this->db->from('game_results');
        $this->db->join('students', 'students.id = game_results.student_id');
        $this->db->join('student_session', 'student_session.student_id = students.id AND student_session.session_id = ' . $this->current_session);
        $this->db->join('classes', 'classes.id = student_session.class_id', 'left');
        $this->db->join('sections', 'sections.id = student_session.section_id', 'left');
        $this->db->where('game_results.game_id', $game_id);
        $this->db->group_by('game_results.student_id');
        $this->db->order_by('best_score', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Get overall leaderboard (across all games)
     */
    public function getOverallLeaderboard($filters = array(), $limit = 10)
    {
        $this->db->select('
            student_points.total_points,
            student_points.current_level,
            student_points.games_completed,
            student_points.average_score,
            students.firstname,
            students.lastname,
            students.admission_no,
            classes.class,
            sections.section
        ');
        $this->db->from('student_points');
        $this->db->join('students', 'students.id = student_points.student_id');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');
        $this->db->join('classes', 'classes.id = student_session.class_id', 'left');
        $this->db->join('sections', 'sections.id = student_session.section_id', 'left');

        // Apply filters
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->order_by('student_points.total_points', 'DESC');
        $this->db->order_by('student_points.current_level', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Get recent game activity
     */
    public function getRecentActivity($filters = array(), $limit = 20)
    {
        $this->db->select('
            game_results.score,
            game_results.points_earned,
            game_results.completed_at,
            educational_games.title as game_title,
            educational_games.game_type,
            students.firstname,
            students.lastname,
            classes.class,
            sections.section
        ');
        $this->db->from('game_results');
        $this->db->join('educational_games', 'educational_games.id = game_results.game_id');
        $this->db->join('students', 'students.id = game_results.student_id');
        $this->db->join('student_session', 'student_session.student_id = students.id AND student_session.session_id = ' . $this->current_session);
        $this->db->join('classes', 'classes.id = student_session.class_id', 'left');
        $this->db->join('sections', 'sections.id = student_session.section_id', 'left');

        // Apply filters
        if (isset($filters['created_by'])) {
            $this->db->where('educational_games.created_by', $filters['created_by']);
        }

        if (isset($filters['date_from'])) {
            $this->db->where('game_results.completed_at >=', $filters['date_from']);
        }

        $this->db->order_by('game_results.completed_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }
}