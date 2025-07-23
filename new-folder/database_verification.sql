-- Database Integration Verification Script
-- Run this to verify all relationships are properly configured

-- 1. Check if all required tables exist
SELECT 
    'educational_games' as table_name,
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS' 
        ELSE 'MISSING' 
    END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'educational_games'

UNION ALL

SELECT 
    'game_results' as table_name,
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS' 
        ELSE 'MISSING' 
    END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'game_results'

UNION ALL

SELECT 
    'student_points' as table_name,
    CASE 
        WHEN COUNT(*) > 0 THEN 'EXISTS' 
        ELSE 'MISSING' 
    END as status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'student_points';

-- 2. Check foreign key constraints
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('educational_games', 'game_results', 'student_points')
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 3. Verify integration with existing tables
SELECT 
    'classes' as table_name,
    COUNT(*) as record_count
FROM classes

UNION ALL

SELECT 
    'sections' as table_name,
    COUNT(*) as record_count
FROM sections

UNION ALL

SELECT 
    'subjects' as table_name,
    COUNT(*) as record_count
FROM subjects

UNION ALL

SELECT 
    'students' as table_name,
    COUNT(*) as record_count
FROM students

UNION ALL

SELECT 
    'staff' as table_name,
    COUNT(*) as record_count
FROM staff

UNION ALL

SELECT 
    'student_session' as table_name,
    COUNT(*) as record_count
FROM student_session;

-- 4. Check game permission groups
SELECT 
    pg.name as permission_group,
    pg.short_code,
    COUNT(pc.id) as categories_count
FROM permission_group pg
LEFT JOIN permission_category pc ON pc.perm_group_id = pg.id
WHERE pg.short_code IN ('game_builder', 'student_games')
GROUP BY pg.id, pg.name, pg.short_code;

-- 5. Check Super Admin permissions
SELECT 
    pg.name as permission_group,
    pc.name as permission_category,
    rp.can_view,
    rp.can_add,
    rp.can_edit,
    rp.can_delete
FROM roles_permissions rp
JOIN permission_category pc ON pc.id = rp.perm_cat_id
JOIN permission_group pg ON pg.id = pc.perm_group_id
WHERE rp.role_id = 7 -- Super Admin
    AND pg.short_code IN ('game_builder', 'student_games');

-- 6. Sample data to test relationships (Optional - for testing only)
/*
-- Insert a test game (replace IDs with actual values from your system)
INSERT INTO educational_games (
    title, 
    description, 
    game_type, 
    game_content, 
    class_id, 
    section_id, 
    subject_id, 
    created_by,
    is_active
) VALUES (
    'Sample Quiz Game',
    'A test quiz for database verification',
    'quiz',
    '{"questions":[{"question":"What is 2+2?","options":["3","4","5","6"],"correct_answer":1,"explanation":"2+2 equals 4"}]}',
    1, -- Replace with actual class_id
    1, -- Replace with actual section_id  
    1, -- Replace with actual subject_id
    1, -- Replace with actual staff_id
    1
);
*/