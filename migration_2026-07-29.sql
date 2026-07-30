-- EPES Migration — July 29, 2026
-- Run on target database to add College/Office, designation-to-designation mapping,
-- multi-designation support, and College/Office column on employee_list/department_list.

-- 1. College/Office list table
CREATE TABLE IF NOT EXISTS college_office_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add college_office_id to employee_list
ALTER TABLE employee_list ADD COLUMN college_office_id INT DEFAULT NULL AFTER department_id;

-- 3. Add college_office_id to department_list
ALTER TABLE department_list ADD COLUMN college_office_id INT DEFAULT NULL AFTER department;

-- 4. Designation-to-designation mapping for Assign Evaluator
CREATE TABLE IF NOT EXISTS evaluator_designation_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_designation_id INT NOT NULL,
    evaluator_designation_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emp_eval_desig (employee_designation_id, evaluator_designation_id)
);

-- 5. Multi-designation junction table (employee can hold >1 designation)
CREATE TABLE IF NOT EXISTS employee_designations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    designation_id INT NOT NULL,
    UNIQUE KEY uq_emp_desig (employee_id, designation_id)
);

-- 6. Seed default colleges
INSERT IGNORE INTO college_office_list (name, code) VALUES
    ('College of Computing and Information Technology', 'CCIT'),
    ('Technical Advisory and Extension Services', 'TAEx');

-- 7. Seed default designation mappings
INSERT IGNORE INTO evaluator_designation_map (employee_designation_id, evaluator_designation_id) VALUES
    (3, 2),   -- Faculty -> Department Head
    (2, 1),   -- Department Head -> Dean
    (1, 18);  -- Dean -> VPAA

-- 8. Ensure evaluator_list has designation_id column (may be missing on older live DBs)
-- Uncomment the ALTER if the column does not exist:
-- ALTER TABLE evaluator_list ADD COLUMN designation_id INT DEFAULT 0;

-- 9. Set rbburac as VPREI (designation 19) and assign Dennis Corlet to him
--    Adjust the evaluator_list id if different on live DB:
--    SELECT id FROM evaluator_list WHERE email = 'rbburac@gmail.com';
UPDATE evaluator_list SET designation_id = 19 WHERE email = 'rbburac@gmail.com';
UPDATE employee_list SET evaluator_id = (SELECT id FROM evaluator_list WHERE email = 'rbburac@gmail.com' LIMIT 1) WHERE firstname = 'Dennis' AND lastname = 'Corlet';