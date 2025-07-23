<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Gamebuilder extends Admin_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('educationalgame_model', 'gameresult_model', 'studentpoint_model', 'class_model', 'section_model', 'subject_model'));
    }

    /**
     * Main index page - Game management for staff
     */
    public function index()
    {
        // Super Admin (role_id = 7) has automatic access, others need permission
        if ($this->session->userdata('admin')['role_id'] != 7 && !$this->rbac->hasPrivilege('games_management', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'games');
        $this->session->set_userdata('sub_menu', 'gamebuilder/index');

        $data['title'] = 'Educational Games';
        $data['title_list'] = 'Game Management';

        // Get filters from request
        $filters = array();
        if ($this->input->get('class_id')) {
            $filters['class_id'] = $this->input->get('class_id');
        }

        if ($this->input->get('section_id')) {
            $filters['section_id'] = $this->input->get('section_id');
        }

        if ($this->input->get('game_type')) {
            $filters['game_type'] = $this->input->get('game_type');
        }

        // Super admin can see all games, teachers only their own
        if ($this->session->userdata('admin')['role_id'] != 7) { // Not super admin
            $filters['created_by'] = $this->session->userdata('admin')['id'];
        }

        $data['gameList'] = $this->educationalgame_model->get(null, $filters);
        $data['classes'] = $this->class_model->get();
        $data['subjects'] = $this->subject_model->get();
        $data['game_stats'] = $this->educationalgame_model->getGameStats(
            $this->session->userdata('admin')['role_id'] != 7 ? $this->session->userdata('admin')['id'] : null
        );

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/gameIndex', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Student games page - Games available for students
     */
    public function student_games()
    {
        // Check if student is logged in (from userlogin system)
        if (!$this->session->userdata('student')) {
            redirect('userlogin'); // Redirect to student login
        }

        // Students need play_games permission
        if (!$this->rbac->hasPrivilege('play_games', 'can_view')) {
            access_denied();
        }

        $this->session->set_userdata('top_menu', 'games');
        $this->session->set_userdata('sub_menu', 'gamebuilder/student-games');

        $data['title'] = 'Educational Games'; 
        $data['title_list'] = 'Available Games';

        // Get student's available games
        $student_id = $this->customlib->getStudentSessionUserID();
        $data['gameList'] = $this->educationalgame_model->getStudentGames($student_id);
        $data['student_points'] = $this->studentpoint_model->getByStudentId($student_id);
        $data['achievements'] = $this->studentpoint_model->getStudentAchievements($student_id);
        $data['leaderboard'] = $this->gameresult_model->getOverallLeaderboard(array(), 10);

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/studentGames', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Create or edit game
     */
    public function create($id = null)
    {
        // Super Admin has automatic access, others need specific permissions
        $is_super_admin = ($this->session->userdata('admin')['role_id'] == 7);
        
        if (!$is_super_admin && !$this->rbac->hasPrivilege('games_management', 'can_add') && !$id) {
            access_denied();
        }

        if (!$is_super_admin && !$this->rbac->hasPrivilege('games_management', 'can_edit') && $id) {
            access_denied();
        }

        $data['title'] = $id ? 'Edit Game' : 'Create Game';
        $data['classes'] = $this->class_model->get();
        $data['subjects'] = $this->subject_model->get();

        if ($id) {
            $data['game'] = $this->educationalgame_model->get($id);
            if (!$data['game']) {
                $this->session->set_flashdata('msg', '<div class="alert alert-danger">Game not found.</div>');
                redirect('gamebuilder');
            }

            // Check if user can edit this game (only creator or super admin)
            if ($this->session->userdata('admin')['role_id'] != 7 && 
                $data['game']['created_by'] != $this->session->userdata('admin')['id']) {
                access_denied();
            }
        }

        if ($this->input->post('submit')) {
            $this->_create_game();
        }

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/gameCreate', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Handle game creation/update
     */
    private function _create_game()
    {
        $this->form_validation->set_rules('title', 'Game Title', 'trim|required|xss_clean');
        $this->form_validation->set_rules('description', 'Description', 'trim|xss_clean');
        $this->form_validation->set_rules('game_type', 'Game Type', 'trim|required|in_list[quiz,matching]');
        $this->form_validation->set_rules('class_id', 'Class', 'trim|numeric');
        $this->form_validation->set_rules('section_id', 'Section', 'trim|numeric');
        $this->form_validation->set_rules('subject_id', 'Subject', 'trim|numeric');
        $this->form_validation->set_rules('max_attempts', 'Max Attempts', 'trim|numeric|greater_than[0]');
        $this->form_validation->set_rules('time_limit', 'Time Limit', 'trim|numeric');
        $this->form_validation->set_rules('difficulty_level', 'Difficulty', 'trim|in_list[easy,medium,hard]');

        if ($this->form_validation->run() == FALSE) {
            return;
        }

        $game_content = $this->input->post('game_content');
        $game_type = $this->input->post('game_type');

        // Validate game content structure
        if (!$this->educationalgame_model->validateGameContent($game_type, $game_content)) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">Invalid game content structure.</div>');
            return;
        }

        $data = array(
            'title' => $this->input->post('title'),
            'description' => $this->input->post('description'),
            'game_type' => $game_type,
            'game_content' => $game_content,
            'class_id' => $this->input->post('class_id') ?: null,
            'section_id' => $this->input->post('section_id') ?: null,
            'subject_id' => $this->input->post('subject_id') ?: null,
            'max_attempts' => $this->input->post('max_attempts') ?: 3,
            'time_limit' => $this->input->post('time_limit') ?: null,
            'points_per_question' => $this->input->post('points_per_question') ?: 10,
            'difficulty_level' => $this->input->post('difficulty_level') ?: 'medium',
            'is_active' => 1,
            'created_by' => $this->session->userdata('admin')['id']
        );

        if ($this->input->post('id')) {
            $data['id'] = $this->input->post('id');
        }

        $result = $this->educationalgame_model->add($data);

        if ($result) {
            $msg = $this->input->post('id') ? 'Game updated successfully.' : 'Game created successfully.';
            $this->session->set_flashdata('msg', '<div class="alert alert-success">' . $msg . '</div>');
        } else {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">Operation failed.</div>');
        }

        redirect('gamebuilder');
    }

    /**
     * Play game
     */
    public function play_game($game_id)
    {
        // Check if student is logged in (from userlogin system)
        if (!$this->session->userdata('student')) {
            redirect('userlogin'); // Redirect to student login
        }

        // Students need play_games permission
        if (!$this->rbac->hasPrivilege('play_games', 'can_view')) {
            access_denied();
        }

        $game = $this->educationalgame_model->get($game_id);
        if (!$game || !$game['is_active']) {
            show_404();
        }

        $student_id = $this->customlib->getStudentSessionUserID();
        $student_session_id = $this->customlib->getStudentSessionID();

        // Check attempt limit
        $attempts = $this->gameresult_model->getStudentAttempts($game_id, $student_id);
        if ($attempts >= $game['max_attempts']) {
            $this->session->set_flashdata('msg', '<div class="alert alert-warning">You have reached the maximum number of attempts for this game.</div>');
            redirect('gamebuilder/student-games');
        }

        $data['title'] = 'Play Game: ' . $game['title'];
        $data['game'] = $game;
        $data['student_id'] = $student_id;
        $data['student_session_id'] = $student_session_id;
        $data['attempt_number'] = $attempts + 1;

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/gamePlay', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Submit game results (AJAX)
     */
    public function submit_game()
    {
        // Check if student is logged in (from userlogin system)
        if (!$this->session->userdata('student')) {
            $this->output->set_status_header(403);
            echo json_encode(array('status' => 'error', 'message' => 'Not logged in'));
            return;
        }

        // Students need play_games permission
        if (!$this->rbac->hasPrivilege('play_games', 'can_view')) {
            $this->output->set_status_header(403);
            echo json_encode(array('status' => 'error', 'message' => 'Access denied'));
            return;
        }

        $game_id = $this->input->post('game_id');
        $student_id = $this->input->post('student_id');
        $student_session_id = $this->input->post('student_session_id');
        $answers = $this->input->post('answers');
        $time_taken = $this->input->post('time_taken');

        $game = $this->educationalgame_model->get($game_id);
        if (!$game) {
            $this->output->set_status_header(404);
            echo json_encode(array('status' => 'error', 'message' => 'Game not found'));
            return;
        }

        // Calculate score
        $result = $this->_calculate_score($game, $answers);

        // Get attempt number
        $attempts = $this->gameresult_model->getStudentAttempts($game_id, $student_id);

        $game_result_data = array(
            'game_id' => $game_id,
            'student_id' => $student_id,
            'student_session_id' => $student_session_id,
            'score' => $result['score'],
            'total_questions' => $result['total_questions'],
            'correct_answers' => $result['correct_answers'],
            'time_taken' => $time_taken,
            'points_earned' => $result['points_earned'],
            'attempt_number' => $attempts + 1,
            'game_data' => json_encode(array(
                'answers' => $answers,
                'correct_answers' => $result['correct_details']
            ))
        );

        $result_id = $this->gameresult_model->add($game_result_data);

        if ($result_id) {
            echo json_encode(array(
                'status' => 'success',
                'score' => $result['score'],
                'correct_answers' => $result['correct_answers'],
                'total_questions' => $result['total_questions'],
                'points_earned' => $result['points_earned'],
                'message' => 'Game completed successfully!'
            ));
        } else {
            $this->output->set_status_header(500);
            echo json_encode(array('status' => 'error', 'message' => 'Failed to save results'));
        }
    }

    /**
     * Calculate game score
     */
    private function _calculate_score($game, $answers)
    {
        $game_content = json_decode($game['game_content'], true);
        $total_questions = 0;
        $correct_answers = 0;
        $correct_details = array();

        if ($game['game_type'] == 'quiz') {
            $total_questions = count($game_content['questions']);
            
            foreach ($game_content['questions'] as $index => $question) {
                $user_answer = isset($answers[$index]) ? (int)$answers[$index] : -1;
                $correct_answer = (int)$question['correct_answer'];
                
                if ($user_answer === $correct_answer) {
                    $correct_answers++;
                }
                
                $correct_details[$index] = array(
                    'user_answer' => $user_answer,
                    'correct_answer' => $correct_answer,
                    'is_correct' => $user_answer === $correct_answer
                );
            }
            
        } elseif ($game['game_type'] == 'matching') {
            $total_questions = count($game_content['pairs']);
            
            foreach ($game_content['pairs'] as $index => $pair) {
                $user_answer = isset($answers[$index]) ? $answers[$index] : '';
                $correct_answer = $pair['right'];
                
                if (trim(strtolower($user_answer)) === trim(strtolower($correct_answer))) {
                    $correct_answers++;
                }
                
                $correct_details[$index] = array(
                    'user_answer' => $user_answer,
                    'correct_answer' => $correct_answer,
                    'is_correct' => trim(strtolower($user_answer)) === trim(strtolower($correct_answer))
                );
            }
        }

        $score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100, 2) : 0;
        $points_earned = $correct_answers * $game['points_per_question'];

        // Bonus points for time and difficulty
        if ($game['difficulty_level'] == 'hard') {
            $points_earned = $points_earned * 1.5; 
        } elseif ($game['difficulty_level'] == 'medium') {
            $points_earned = $points_earned * 1.2;
        }

        return array(
            'score' => $score,
            'total_questions' => $total_questions,
            'correct_answers' => $correct_answers,
            'points_earned' => round($points_earned),
            'correct_details' => $correct_details
        );
    }

    /**
     * Game results and analytics
     */
    public function results($game_id = null)
    {
        // Super Admin has automatic access, others need permission
        if ($this->session->userdata('admin')['role_id'] != 7 && !$this->rbac->hasPrivilege('game_results', 'can_view')) {
            access_denied();
        }

        $data['title'] = 'Game Results';

        $filters = array();
        if ($game_id) {
            $filters['game_id'] = $game_id;
            $data['game'] = $this->educationalgame_model->get($game_id);
        }

        // Teachers can only see results from their games
        if ($this->session->userdata('admin')['role_id'] != 7) {
            $teacher_games = $this->educationalgame_model->get(null, array('created_by' => $this->session->userdata('admin')['id']));
            $game_ids = array_column($teacher_games, 'id');
            
            if ($game_id && !in_array($game_id, $game_ids)) {
                access_denied();
            }
        }

        if ($this->input->get('class_id')) {
            $filters['class_id'] = $this->input->get('class_id');
        }

        if ($this->input->get('section_id')) {
            $filters['section_id'] = $this->input->get('section_id');
        }

        $data['results'] = $this->gameresult_model->get(null, $filters);
        $data['classes'] = $this->class_model->get();

        if ($game_id) {
            $data['analytics'] = $this->gameresult_model->getGameAnalytics($game_id);
            $data['leaderboard'] = $this->gameresult_model->getGameLeaderboard($game_id, 10);
        }

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/gameResults', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Leaderboard
     */
    public function leaderboard()
    {
        // Super Admin has automatic access, others need permission
        if ($this->session->userdata('admin')['role_id'] != 7 && !$this->rbac->hasPrivilege('game_leaderboard', 'can_view')) {
            access_denied();
        }

        $data['title'] = 'Leaderboard';

        $filters = array();
        if ($this->input->get('class_id')) {
            $filters['class_id'] = $this->input->get('class_id');
        }

        if ($this->input->get('section_id')) {
            $filters['section_id'] = $this->input->get('section_id');
        }

        $data['leaderboard'] = $this->studentpoint_model->getLeaderboard($filters, 20);
        $data['level_stats'] = $this->studentpoint_model->getLevelStats($filters);
        $data['activity_summary'] = $this->studentpoint_model->getActivitySummary($filters);
        $data['classes'] = $this->class_model->get();

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/leaderboard', $data);
        $this->load->view('layout/footer', $data);
    }

    /**
     * Delete game
     */
    public function delete($id)
    {
        // Super Admin has automatic access, others need permission
        if ($this->session->userdata('admin')['role_id'] != 7 && !$this->rbac->hasPrivilege('games_management', 'can_delete')) {
            access_denied();
        }

        $game = $this->educationalgame_model->get($id);
        if (!$game) {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">Game not found.</div>');
            redirect('gamebuilder');
        }

        // Check if user can delete this game (only creator or super admin)
        if ($this->session->userdata('admin')['role_id'] != 7 && 
            $game['created_by'] != $this->session->userdata('admin')['id']) {
            access_denied();
        }

        if ($this->educationalgame_model->remove($id)) {
            $this->session->set_flashdata('msg', '<div class="alert alert-success">Game deleted successfully.</div>');
        } else {
            $this->session->set_flashdata('msg', '<div class="alert alert-danger">Failed to delete game.</div>');
        }

        redirect('gamebuilder');
    }

    /**
     * Get game template via AJAX
     */
    public function get_template()
    {
        $game_type = $this->input->post('game_type');
        $template = $this->educationalgame_model->getGameTemplate($game_type);
        echo json_encode($template);
    }

    /**
     * Get sections for selected class (AJAX)
     */
    public function get_sections()
    {
        $class_id = $this->input->post('class_id');
        $sections = $this->section_model->getClassSections($class_id);
        echo json_encode($sections);
    }

    /**
     * Dashboard analytics
     */
    public function dashboard()
    {
        // Super Admin has automatic access, others need permission
        if ($this->session->userdata('admin')['role_id'] != 7 && !$this->rbac->hasPrivilege('games_management', 'can_view')) {
            access_denied();
        }

        $data['title'] = 'Game Analytics Dashboard';

        // Get stats based on user role
        $created_by = $this->session->userdata('admin')['role_id'] != 7 ? $this->session->userdata('admin')['id'] : null;

        $data['game_stats'] = $this->educationalgame_model->getGameStats($created_by);
        $data['activity_summary'] = $this->studentpoint_model->getActivitySummary();
        $data['recent_results'] = $this->gameresult_model->getRecentActivity(array_merge(
            array('date_from' => date('Y-m-d', strtotime('-7 days'))),
            $created_by ? array('created_by' => $created_by) : array()
        ));

        $this->load->view('layout/header', $data);
        $this->load->view('gamebuilder/dashboard', $data);
        $this->load->view('layout/footer', $data);
    }
}