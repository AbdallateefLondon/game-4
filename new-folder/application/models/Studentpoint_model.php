<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Studentpoint_model extends CI_Model
{
    
    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    /**
     * Get student points by student ID
     */
    public function getByStudentId($student_id)
    {
        $this->db->where('student_id', $student_id);
        $this->db->where('student_session_id IN (SELECT id FROM student_session WHERE student_id = ' . $student_id . ' AND session_id = ' . $this->current_session . ')');
        $result = $this->db->get('student_points')->row_array();
        
        if (!$result) {
            // Create default record if it doesn't exist
            $student_session = $this->getStudentSessionId($student_id);
            if ($student_session) {
                $default_data = array(
                    'student_id' => $student_id,
                    'student_session_id' => $student_session,
                    'total_points' => 0,
                    'current_level' => 1,
                    'points_to_next_level' => 10,
                    'games_played' => 0,
                    'games_completed' => 0,
                    'average_score' => 0.00,
                    'best_score' => 0,
                    'total_time_played' => 0,
                    'achievements' => json_encode(array()),
                    'created_at' => date('Y-m-d H:i:s')
                );
                
                $this->db->insert('student_points', $default_data);
                return $default_data;
            }
        }
        
        return $result;
    }

    /**
     * Get student session ID
     */
    private function getStudentSessionId($student_id)
    {
        $this->db->select('id');
        $this->db->where('student_id', $student_id);
        $this->db->where('session_id', $this->current_session);
        $result = $this->db->get('student_session')->row_array();
        
        return $result ? $result['id'] : null;
    }

    /**
     * Get leaderboard with filters
     */
    public function getLeaderboard($filters = array(), $limit = 20)
    {
        $this->db->select('
            student_points.*,
            students.firstname,
            students.lastname,
            students.admission_no,
            students.roll_no,
            students.image,
            classes.class,
            sections.section,
            student_session.session_id
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
        $this->db->where('student_points.games_completed >', 0); // Only students who have played games
        $this->db->order_by('student_points.total_points', 'DESC');
        $this->db->order_by('student_points.current_level', 'DESC');
        $this->db->order_by('student_points.average_score', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }

    /**
     * Get level statistics
     */
    public function getLevelStats($filters = array())
    {
        $this->db->select('
            current_level,
            COUNT(*) as student_count
        ');
        $this->db->from('student_points');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');

        // Apply filters
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->where('student_points.games_completed >', 0);
        $this->db->group_by('current_level');
        $this->db->order_by('current_level', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Get activity summary
     */
    public function getActivitySummary($filters = array())
    {
        $summary = array();

        // Total students with points
        $this->db->from('student_points');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');
        
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->where('student_points.games_completed >', 0);
        $summary['active_students'] = $this->db->count_all_results();

        // Total points distributed
        $this->db->select('SUM(total_points) as total_points, AVG(current_level) as avg_level');
        $this->db->from('student_points');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');
        
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->where('student_points.games_completed >', 0);
        $stats = $this->db->get()->row_array();
        
        $summary['total_points_distributed'] = $stats['total_points'] ?: 0;
        $summary['average_level'] = round($stats['avg_level'] ?: 1, 1);

        // Top performers
        $this->db->select('MAX(total_points) as highest_points, MAX(current_level) as highest_level');
        $this->db->from('student_points');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');
        
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $top_stats = $this->db->get()->row_array();
        
        $summary['highest_points'] = $top_stats['highest_points'] ?: 0;
        $summary['highest_level'] = $top_stats['highest_level'] ?: 1;

        return $summary;
    }

    /**
     * Get student achievements
     */
    public function getStudentAchievements($student_id)
    {
        $student_points = $this->getByStudentId($student_id);
        
        if (!$student_points) {
            return array();
        }

        $achievements = array();
        
        // Level-based achievements
        if ($student_points['current_level'] >= 5) {
            $achievements[] = array(
                'title' => 'Level Master',
                'description' => 'Reached Level ' . $student_points['current_level'],
                'icon' => 'fa-trophy',
                'color' => 'gold'
            );
        }

        // Points-based achievements  
        if ($student_points['total_points'] >= 100) {
            $achievements[] = array(
                'title' => 'Point Champion',
                'description' => 'Earned ' . $student_points['total_points'] . ' points',
                'icon' => 'fa-star',
                'color' => 'blue'
            );
        }

        // Games completed achievements
        if ($student_points['games_completed'] >= 10) {
            $achievements[] = array(
                'title' => 'Game Finisher',
                'description' => 'Completed ' . $student_points['games_completed'] . ' games',
                'icon' => 'fa-gamepad',
                'color' => 'green'
            );
        }

        // High average score
        if ($student_points['average_score'] >= 90) {
            $achievements[] = array(
                'title' => 'Excellence Award',
                'description' => 'Maintained ' . $student_points['average_score'] . '% average',
                'icon' => 'fa-medal',
                'color' => 'purple'
            );
        }

        // Perfect score achievement
        if ($student_points['best_score'] == 100) {
            $achievements[] = array(
                'title' => 'Perfect Score',
                'description' => 'Achieved 100% in a game',
                'icon' => 'fa-bullseye',
                'color' => 'red'
            );
        }

        return $achievements;
    }

    /**
     * Update student achievements
     */
    public function updateAchievements($student_id)
    {
        $achievements = $this->getStudentAchievements($student_id);
        
        $this->db->where('student_id', $student_id);
        $this->db->where('student_session_id IN (SELECT id FROM student_session WHERE student_id = ' . $student_id . ' AND session_id = ' . $this->current_session . ')');
        $this->db->update('student_points', array(
            'achievements' => json_encode($achievements),
            'updated_at' => date('Y-m-d H:i:s')
        ));
    }

    /**
     * Get student ranking
     */
    public function getStudentRanking($student_id, $filters = array())
    {
        // Get student's points
        $student_points = $this->getByStudentId($student_id);
        
        if (!$student_points) {
            return null;
        }

        // Count students with higher points
        $this->db->from('student_points');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');
        
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->where('student_points.total_points >', $student_points['total_points']);
        $higher_rank_count = $this->db->count_all_results();

        // Get total students in ranking
        $this->db->from('student_points');
        $this->db->join('student_session', 'student_session.id = student_points.student_session_id');
        
        if (isset($filters['class_id']) && $filters['class_id']) {
            $this->db->where('student_session.class_id', $filters['class_id']);
        }

        if (isset($filters['section_id']) && $filters['section_id']) {
            $this->db->where('student_session.section_id', $filters['section_id']);
        }

        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->where('student_points.games_completed >', 0);
        $total_students = $this->db->count_all_results();

        return array(
            'rank' => $higher_rank_count + 1,
            'total_students' => $total_students,
            'percentile' => $total_students > 0 ? round((($total_students - $higher_rank_count) / $total_students) * 100, 1) : 0
        );
    }
}